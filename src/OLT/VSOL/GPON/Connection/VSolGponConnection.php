<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Connection;

use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;
use phpseclib3\Net\SSH2;

final class VSolGponConnection implements VSolGponConnectionInterface
{
    private ?SSHConnection $connection = null;
    private bool $initialized = false;
    private string $prompt = '';
    private int $timeout = 10;

    public function __construct(
        private readonly OLT $oltModel,
        ?OltCliProfileRegistry $profileRegistry = null,
        private readonly bool $enforceFirmwareVersion = true,
    ) {
        $profile = ($profileRegistry ?? new OltCliProfileRegistry())->resolve($oltModel);

        if ($profile->id !== OltCliProfile::VSOL_GPON_CLI_V2) {
            throw new \InvalidArgumentException(
                "Unsupported VSOL GPON CLI profile '{$profile->id}'."
            );
        }
    }

    public function exec(string $cmd): string|bool
    {
        return $this->runReadOnly(
            $cmd,
            fn (): string|bool => $this->executeCommand($cmd)
        );
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        if ($pon < 1 || $pon > 8) {
            throw new \InvalidArgumentException('VSOL GPON PON must be between 1 and 8.');
        }

        return $this->runReadOnly($cmd, function () use ($pon, $cmd): string|bool {
            $this->enterPon($pon);

            try {
                return $this->executeCommand($cmd);
            } finally {
                $this->leavePon();
            }
        });
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
        $this->connection?->setTimeout($timeout);
    }

    public function disconnect(): void
    {
        try {
            $this->connection?->disconnect();
        } catch (\Throwable) {
            // A broken socket must not prevent a later connection.
        } finally {
            $this->connection = null;
            $this->initialized = false;
            $this->prompt = '';
        }
    }

    private function runReadOnly(string $cmd, callable $operation): string|bool
    {
        $this->ensureInitialized();

        try {
            return $operation();
        } catch (\RuntimeException $exception) {
            $this->reconnect();

            if (!$this->isReadOnly($cmd)) {
                throw new \RuntimeException(
                    'VSOL GPON session lost synchronization. The command was not repeated.',
                    0,
                    $exception
                );
            }

            try {
                return $operation();
            } catch (\RuntimeException $retryException) {
                $this->disconnect();
                throw new \RuntimeException(
                    'VSOL GPON session lost synchronization after retrying a read-only command.',
                    0,
                    $retryException
                );
            }
        }
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->authenticateInternalCli();
            $this->enterPrivilegedMode();
            $this->executeModeCommand('terminal length 0', '~gpon-olt#\s*$~');
            $this->executeModeCommand(
                'configure terminal',
                '~gpon-olt\(config\)#\s*$~'
            );
            if ($this->enforceFirmwareVersion) {
                $this->verifyFirmwareVersion();
            }
            $this->initialized = true;
        } catch (\Throwable $exception) {
            $this->disconnect();

            if ($exception instanceof \RuntimeException) {
                throw $exception;
            }

            throw new \RuntimeException('Unable to initialize VSOL GPON CLI session.', 0, $exception);
        }
    }

    private function authenticateInternalCli(): void
    {
        $ssh = $this->getConn()->getConn();
        $read = $this->read(
            $ssh,
            '~(?:Login:|Password:|gpon-olt>\s*$)~i'
        );
        $clean = $this->cleanTerminalOutput($read);

        if (preg_match('/Login:\s*$/i', $clean)) {
            $ssh->write($this->oltModel->userName . "\n");
            $read = $this->read($ssh, '~Password:\s*$~i');
            $clean = $this->cleanTerminalOutput($read);
        }

        if (preg_match('/Password:\s*$/i', $clean)) {
            $ssh->write($this->oltModel->password . "\n");
            $read = $this->read($ssh, '~gpon-olt>\s*$~');
        }

        $this->updatePrompt($read, '~(gpon-olt>)\s*$~');
    }

    private function enterPrivilegedMode(): void
    {
        $ssh = $this->getConn()->getConn();
        $ssh->write("enable\n");
        $read = $this->read($ssh, '~(?:Password:|gpon-olt#\s*$)~i');

        if (preg_match('/Password:\s*$/i', $this->cleanTerminalOutput($read))) {
            $ssh->write($this->oltModel->password . "\n");
            $read = $this->read($ssh, '~gpon-olt#\s*$~');
        }

        $this->updatePrompt($read, '~(gpon-olt#)\s*$~');
    }

    private function enterPon(int $pon): void
    {
        $this->executeModeCommand(
            "interface gpon 0/{$pon}",
            "~gpon-olt\\(config-pon-0/{$pon}\\)#\\s*$~"
        );
    }

    private function verifyFirmwareVersion(): void
    {
        try {
            $this->executeCommand('show version');
        } catch (\Throwable) {
            // Firmware is informative only; CLI profile compatibility is the real gate.
        }
    }

    private function leavePon(): void
    {
        if (!str_contains($this->prompt, '(config-pon-')) {
            return;
        }

        $this->executeModeCommand('exit', '~gpon-olt\(config\)#\s*$~');
    }

    private function executeModeCommand(string $cmd, string $promptPattern): void
    {
        $ssh = $this->getConn()->getConn();
        $ssh->write($cmd . "\n");
        $read = $this->readResponse($ssh, $promptPattern);
        $this->updatePrompt($read, $promptPattern);
    }

    private function executeCommand(string $cmd): string|bool
    {
        if ($this->prompt === '') {
            throw new \RuntimeException('VSOL GPON SSH prompt is not synchronized.');
        }

        $ssh = $this->getConn()->getConn();
        $ssh->write($cmd . "\n");
        $read = $this->readResponse(
            $ssh,
            '~(?:--More--\s*\x00?|' . preg_quote($this->prompt, '~') . '\s*$)~'
        );

        if (!$this->endsWithPrompt($read)) {
            throw new \RuntimeException('VSOL GPON response did not end with the expected prompt.');
        }

        return $this->clearResult($read, $cmd);
    }

    private function readResponse(SSH2 $ssh, string $pattern): string
    {
        $read = $this->read($ssh, $pattern);
        $lastRead = $read;

        while (str_contains($lastRead, '--More--')) {
            $ssh->write(' ');
            $lastRead = $this->read($ssh, $pattern);
            $read .= $lastRead;
        }

        return $read;
    }

    private function read(SSH2 $ssh, string $pattern): string
    {
        $read = $ssh->read($pattern, SSH2::READ_REGEX);

        if ($ssh->isTimeout()) {
            throw new \RuntimeException('Timeout while waiting for VSOL GPON response.');
        }

        if (!is_string($read) || $read === '') {
            throw new \RuntimeException('Empty VSOL GPON response.');
        }

        return $read;
    }

    private function updatePrompt(string $read, string $pattern): void
    {
        $clean = $this->cleanTerminalOutput($read);

        if (!preg_match($pattern, $clean, $matches)) {
            throw new \RuntimeException('Unable to detect VSOL GPON SSH prompt.');
        }

        $this->prompt = trim($matches[1] ?? $matches[0]);
    }

    private function endsWithPrompt(string $read): bool
    {
        return preg_match(
            '~' . preg_quote($this->prompt, '~') . '\s*$~',
            $this->cleanTerminalOutput($read)
        ) === 1;
    }

    private function reconnect(): void
    {
        $this->disconnect();
        $this->ensureInitialized();
    }

    private function isReadOnly(string $cmd): bool
    {
        $command = strtolower(ltrim($cmd));
        return str_starts_with($command, 'show ') || str_starts_with($command, 'onu search ');
    }

    private function getConn(): SSHConnection
    {
        if ($this->connection === null) {
            $this->connection = new SSHConnection(
                $this->oltModel->ip,
                $this->oltModel->userName,
                $this->oltModel->password,
                $this->oltModel->port
            );
            $this->connection->setTimeout($this->timeout);
        }

        return $this->connection;
    }

    private function clearResult(string $read, string $command): string|bool
    {
        $data = $this->cleanTerminalOutput($read);
        $data = preg_replace('/--More--\s*\x00?/', '', $data) ?? $data;
        $data = preg_replace(
            '/^\s*' . preg_quote($command, '/') . '\s*$/m',
            '',
            $data
        ) ?? $data;
        $data = preg_replace(
            '/^\s*' . preg_quote($this->prompt, '/') . '\s*$/m',
            '',
            $data
        ) ?? $data;
        $data = trim($data);

        return $data === '' ? false : $data;
    }

    private function cleanTerminalOutput(string $data): string
    {
        $data = preg_replace_callback(
            '/\e\[(\d+)C/',
            static fn (array $matches): string => str_repeat(' ', (int) $matches[1]),
            $data
        ) ?? $data;
        $data = preg_replace('/\e\[[0-9;?]*[a-zA-Z]/', '', $data) ?? $data;
        $data = str_replace(["\x00", "\x08"], '', $data);

        return str_replace("\r", '', $data);
    }
}

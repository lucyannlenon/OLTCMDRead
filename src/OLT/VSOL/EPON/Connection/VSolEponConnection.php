<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Connection;

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

final class VSolEponConnection implements VSolEponConnectionInterface
{
    /** @var resource|null */
    private $socket = null;
    private bool $initialized = false;
    private string $prompt = '';
    private int $timeout = 10;

    public function __construct(
        private readonly OLT $oltModel,
        ?OltCliProfileRegistry $profileRegistry = null,
        private readonly bool $enforceFirmwareVersion = true,
    ) {
        $profile = ($profileRegistry ?? new OltCliProfileRegistry())->resolve($oltModel);

        if ($profile->id !== OltCliProfile::VSOL_EPON_CLI_V1) {
            throw new \InvalidArgumentException(
                "Unsupported VSOL EPON CLI profile '{$profile->id}'."
            );
        }

        if (strtolower((string) $oltModel->serviceCommunication) !== 'telnet') {
            throw new \InvalidArgumentException('VSOL EPON CLI V1 requires Telnet transport.');
        }
    }

    public function exec(string $cmd): string|bool
    {
        return $this->runReadOnly($cmd, fn (): string|bool => $this->executeCommand($cmd));
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        self::validatePon($pon);

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
        if ($timeout < 1) {
            throw new \InvalidArgumentException('VSOL EPON timeout must be positive.');
        }

        $this->timeout = $timeout;

        if (is_resource($this->socket)) {
            stream_set_timeout($this->socket, $timeout);
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }

        $this->socket = null;
        $this->initialized = false;
        $this->prompt = '';
    }

    public static function cleanTerminalOutput(string $data): string
    {
        $data = preg_replace_callback(
            '/\e\[(\d+)C/',
            static fn (array $matches): string => str_repeat(' ', (int) $matches[1]),
            $data
        ) ?? $data;
        $data = preg_replace('/\e\[[0-9;?]*[a-zA-Z]/', '', $data) ?? $data;
        $data = preg_replace('/\xFF[\xFB-\xFE]./s', '', $data) ?? $data;
        $data = preg_replace('/\xFF\xFA.*?\xFF\xF0/s', '', $data) ?? $data;
        $data = str_replace(["\x00", "\x08"], '', $data);

        return str_replace("\r", '', $data);
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
                    'VSOL EPON session lost synchronization. The command was not repeated.',
                    0,
                    $exception
                );
            }

            try {
                return $operation();
            } catch (\RuntimeException $retryException) {
                $this->disconnect();
                throw new \RuntimeException(
                    'VSOL EPON session lost synchronization after retrying a read-only command.',
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
            $this->openSocket();
            $this->readUntil('~Login:\s*$~i');
            $this->writeLine((string) $this->oltModel->userName);
            $this->readUntil('~Password:\s*$~i');
            $this->writeLine((string) $this->oltModel->password);
            $read = $this->readUntil('~epon-olt>\s*$~');
            $this->updatePrompt($read, '~(epon-olt>)\s*$~');

            $this->writeLine('enable');
            $read = $this->readUntil('~(?:Password:|epon-olt#)\s*$~i');
            if (preg_match('/Password:\s*$/i', self::cleanTerminalOutput($read))) {
                $this->writeLine((string) $this->oltModel->password);
                $read = $this->readUntil('~epon-olt#\s*$~');
            }
            $this->updatePrompt($read, '~(epon-olt#)\s*$~');

            $this->executeModeCommand('terminal length 0', '~epon-olt#\s*$~');
            $this->executeModeCommand(
                'configure terminal',
                '~epon-olt\(config\)#\s*$~'
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

            throw new \RuntimeException('Unable to initialize VSOL EPON Telnet session.', 0, $exception);
        }
    }

    private function openSocket(): void
    {
        $address = sprintf('tcp://%s:%d', $this->oltModel->ip, (int) $this->oltModel->port);
        $socket = @stream_socket_client(
            $address,
            $errorCode,
            $errorMessage,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            throw new \RuntimeException(
                "Unable to connect to VSOL EPON Telnet endpoint: {$errorMessage} ({$errorCode})."
            );
        }

        stream_set_blocking($socket, false);
        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
    }

    private function verifyFirmwareVersion(): void
    {
        $response = $this->executeCommand('show version');
        $configured = preg_quote(
            strtoupper(trim((string) $this->oltModel->firmwareVersion)),
            '/'
        );

        if (
            !is_string($response)
            || preg_match(
                '/^\s*Software Version:\s*' . $configured . '\s*$/mi',
                strtoupper($response)
            ) !== 1
        ) {
            throw new \RuntimeException(
                'Connected VSOL EPON firmware does not match the configured firmware version.'
            );
        }
    }

    private function enterPon(int $pon): void
    {
        $this->executeModeCommand(
            "interface epon 0/{$pon}",
            "~epon-olt\\(config-pon-0/{$pon}\\)#\\s*$~"
        );
    }

    private function leavePon(): void
    {
        if (!str_contains($this->prompt, '(config-pon-')) {
            return;
        }

        $this->executeModeCommand('exit', '~epon-olt\(config\)#\s*$~');
    }

    private function executeModeCommand(string $cmd, string $promptPattern): void
    {
        $this->writeLine($cmd);
        $read = $this->readUntil($promptPattern);
        $this->updatePrompt($read, $promptPattern);
    }

    private function executeCommand(string $cmd): string|bool
    {
        if ($this->prompt === '') {
            throw new \RuntimeException('VSOL EPON Telnet prompt is not synchronized.');
        }

        $this->writeLine($cmd);
        $read = $this->readUntil(
            '~(?:--More--\s*|' . preg_quote($this->prompt, '~') . '\s*$)~'
        );

        while (str_contains($read, '--More--')) {
            $this->writeRaw(' ');
            $read .= $this->readUntil(
                '~(?:--More--\s*|' . preg_quote($this->prompt, '~') . '\s*$)~'
            );
        }

        if (!$this->endsWithPrompt($read)) {
            throw new \RuntimeException('VSOL EPON response did not end with the expected prompt.');
        }

        return $this->clearResult($read, $cmd);
    }

    private function readUntil(string $pattern): string
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('VSOL EPON Telnet socket is not connected.');
        }

        $buffer = '';
        $deadline = microtime(true) + $this->timeout;

        while (microtime(true) < $deadline) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            $remaining = max(0.0, $deadline - microtime(true));
            $seconds = (int) $remaining;
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);
            $ready = @stream_select($read, $write, $except, $seconds, $microseconds);

            if ($ready === false) {
                throw new \RuntimeException('Unable to read from VSOL EPON Telnet socket.');
            }

            if ($ready === 0) {
                continue;
            }

            $chunk = fread($this->socket, 8192);
            if ($chunk === false) {
                throw new \RuntimeException('Unable to read VSOL EPON Telnet response.');
            }

            if ($chunk === '' && feof($this->socket)) {
                throw new \RuntimeException('VSOL EPON Telnet connection was closed.');
            }

            $buffer .= $this->handleTelnetNegotiation($chunk);
            if (preg_match($pattern, self::cleanTerminalOutput($buffer)) === 1) {
                return $buffer;
            }
        }

        throw new \RuntimeException('Timeout while waiting for VSOL EPON response.');
    }

    private function handleTelnetNegotiation(string $chunk): string
    {
        $length = strlen($chunk);
        $clean = '';

        for ($index = 0; $index < $length; $index++) {
            if (ord($chunk[$index]) !== 255 || $index + 1 >= $length) {
                $clean .= $chunk[$index];
                continue;
            }

            $command = ord($chunk[++$index]);
            if (in_array($command, [251, 252, 253, 254], true) && $index + 1 < $length) {
                $option = $chunk[++$index];
                $reply = in_array($command, [251, 252], true) ? chr(254) : chr(252);
                $this->writeRaw(chr(255) . $reply . $option);
                continue;
            }

            if ($command === 250) {
                while ($index + 1 < $length) {
                    $index++;
                    if (ord($chunk[$index]) === 255 && $index + 1 < $length && ord($chunk[$index + 1]) === 240) {
                        $index++;
                        break;
                    }
                }
            }
        }

        return $clean;
    }

    private function writeLine(string $value): void
    {
        $this->writeRaw($value . "\r\n");
    }

    private function writeRaw(string $value): void
    {
        if (!is_resource($this->socket) || fwrite($this->socket, $value) === false) {
            throw new \RuntimeException('Unable to write to VSOL EPON Telnet socket.');
        }
    }

    private function updatePrompt(string $read, string $pattern): void
    {
        $clean = self::cleanTerminalOutput($read);
        if (!preg_match($pattern, $clean, $matches)) {
            throw new \RuntimeException('Unable to detect VSOL EPON Telnet prompt.');
        }

        $this->prompt = trim($matches[1] ?? $matches[0]);
    }

    private function endsWithPrompt(string $read): bool
    {
        return preg_match(
            '~' . preg_quote($this->prompt, '~') . '\s*$~',
            self::cleanTerminalOutput($read)
        ) === 1;
    }

    private function clearResult(string $read, string $command): string|bool
    {
        $data = self::cleanTerminalOutput($read);
        $data = preg_replace('/--More--\s*/', '', $data) ?? $data;
        $data = preg_replace('/^\s*' . preg_quote($command, '/') . '\s*$/m', '', $data) ?? $data;
        $data = preg_replace('/^\s*' . preg_quote($this->prompt, '/') . '\s*$/m', '', $data) ?? $data;
        $data = trim($data);

        return $data === '' ? false : $data;
    }

    private function reconnect(): void
    {
        $this->disconnect();
        $this->ensureInitialized();
    }

    private function isReadOnly(string $cmd): bool
    {
        return str_starts_with(strtolower(ltrim($cmd)), 'show ');
    }

    private static function validatePon(int $pon): void
    {
        if ($pon < 1 || $pon > 4) {
            throw new \InvalidArgumentException('VSOL EPON PON must be between 1 and 4.');
        }
    }
}

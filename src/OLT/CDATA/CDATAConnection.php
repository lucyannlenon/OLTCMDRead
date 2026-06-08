<?php

namespace LLENON\OltInformation\OLT\CDATA;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use phpseclib3\Net\SSH2;

class CDATAConnection implements ConnectionInterface
{
    private ?SSHConnection $connection = null;
    private bool $initialized = false;
    private ?string $prompt = null;
    private int $timeout = 10;

    public function __construct(
        private readonly OLT $oltModel
    ) {
    }

    public function exec(string $cmd): string|bool
    {
        $this->ensureInitialized();

        try {
            return $this->executeCommand($cmd);
        } catch (\RuntimeException $exception) {
            $this->reconnect();

            if (!$this->isReadOnly($cmd)) {
                throw new \RuntimeException(
                    "CDATA SSH session lost synchronization. The command was not repeated automatically.",
                    0,
                    $exception
                );
            }

            try {
                return $this->executeCommand($cmd);
            } catch (\RuntimeException $retryException) {
                $this->disconnect();
                throw new \RuntimeException(
                    "CDATA SSH session lost synchronization after retrying the read-only command.",
                    0,
                    $retryException
                );
            }
        }
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
            $this->prompt = null;
            $this->initialized = false;
        }
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->synchronizePrompt();

            if (str_ends_with($this->prompt ?? '', '>')) {
                $this->executeModeCommand('enable', '~(?:^|\r?\n)([^\r\n]*#)\s*$~');
            }

            if (!str_contains($this->prompt ?? '', '(config)#')) {
                $this->executeModeCommand(
                    'config',
                    '~(?:^|\r?\n)([^\r\n]*\(config\)#)\s*$~'
                );
            }

            if (!str_contains($this->prompt ?? '', '(config)#')) {
                throw new \RuntimeException(
                    "Unable to enter CDATA configuration mode. Current prompt: "
                    . ($this->prompt ?? '[none]')
                );
            }

            $this->initialized = true;
        } catch (\RuntimeException $exception) {
            $this->disconnect();
            throw $exception;
        }
    }

    private function synchronizePrompt(): void
    {
        $ssh = $this->getConn()->getConn();
        $read = $this->read(
            $ssh,
            "~(?:User password:|(?:^|\r?\n)[^\r\n]*[#>]\s*$)~i"
        );

        if (preg_match('/User password:\s*$/i', $this->removeTerminalSequences($read))) {
            $ssh->write($this->oltModel->password . "\n");
            $read = $this->read($ssh, "~(?:^|\r?\n)([^\r\n]*[#>])\s*$~");
        }

        $this->updatePrompt($read);
    }

    private function executeModeCommand(string $cmd, string $expectedPromptPattern): void
    {
        $ssh = $this->getConn()->getConn();
        $ssh->write("$cmd\n");
        $read = $this->read($ssh, $expectedPromptPattern);
        $this->updatePrompt($read);
    }

    private function executeCommand(string $cmd): string|bool
    {
        if ($this->prompt === null) {
            throw new \RuntimeException("CDATA SSH prompt is not synchronized.");
        }

        $ssh = $this->getConn()->getConn();
        $responsePattern = "~(?:--More|" . preg_quote($this->prompt, "~") . "\s*$)~";

        $ssh->write("$cmd\n");
        $read = $this->readResponse($ssh, $responsePattern);

        if (!$this->endsWithPrompt($read)) {
            throw new \RuntimeException("CDATA SSH response did not end with the expected prompt.");
        }

        return $this->clearResult($read, $cmd);
    }

    private function readResponse(SSH2 $ssh, string $responsePattern): string
    {
        $read = $this->read($ssh, $responsePattern);
        $lastRead = $read;

        while (str_contains($lastRead, '--More')) {
            $ssh->write(' ');
            $lastRead = $this->read($ssh, $responsePattern);
            $read .= "\r\n" . $lastRead;
        }

        return $read;
    }

    private function read(SSH2 $ssh, string $responsePattern): string
    {
        $read = $ssh->read($responsePattern, SSH2::READ_REGEX);

        if ($ssh->isTimeout()) {
            throw new \RuntimeException("Timeout while waiting for CDATA SSH response.");
        }

        if (!is_string($read) || $read === '') {
            throw new \RuntimeException("Empty CDATA SSH response.");
        }

        return $read;
    }

    private function updatePrompt(string $read): void
    {
        $clean = $this->removeTerminalSequences($read);

        if (!preg_match("~(?:^|\r?\n)([^\r\n]*[#>])\s*$~", $clean, $matches)) {
            throw new \RuntimeException("Unable to detect CDATA SSH prompt.");
        }

        $prompt = trim($matches[1]);
        if ($prompt === '') {
            throw new \RuntimeException("Detected an empty CDATA SSH prompt.");
        }

        $this->prompt = $prompt;
    }

    private function endsWithPrompt(string $read): bool
    {
        return $this->prompt !== null
            && preg_match(
                "~" . preg_quote($this->prompt, "~") . "\s*$~",
                $this->removeTerminalSequences($read)
            ) === 1;
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
        $data = $this->removeTerminalSequences($read);
        $data = preg_replace('/--More\s*\([^)]*\)--/', '', $data) ?? $data;
        $data = str_replace($this->prompt ?? '', '', $data);
        $data = preg_replace('/^\s*' . preg_quote($command, '/') . '\s*$/m', '', $data) ?? $data;
        $data = trim($data);

        return $data === '' ? false : $data;
    }

    private function removeTerminalSequences(string $data): string
    {
        $data = preg_replace('/\e\[[0-9;?]*[a-zA-Z]/', '', $data) ?? $data;
        return str_replace("\x08", '', $data);
    }
}

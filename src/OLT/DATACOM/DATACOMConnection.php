<?php

namespace LLENON\OltInformation\OLT\DATACOM;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Versioning\OltFirmwareGuard;
use phpseclib3\Net\SSH2;

class DATACOMConnection implements ConnectionInterface
{
    private ?SSHConnection $connection = null;
    private bool $initialized = false;
    private ?string $prompt = null;
    private int $timeout = 10;

    public function __construct(
        private readonly OLT $oltModel,
        private readonly bool $enforceFirmwareVersion = true
    )
    {

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
                    "DATACOM SSH session lost synchronization. The command was not repeated automatically.",
                    0,
                    $exception
                );
            }

            try {
                return $this->executeCommand($cmd);
            } catch (\RuntimeException $retryException) {
                $this->disconnect();
                throw new \RuntimeException(
                    "DATACOM SSH session lost synchronization after retrying the read-only command.",
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
            $guard = null;
            if ($this->enforceFirmwareVersion) {
                $guard = new OltFirmwareGuard();
                $guard->validateConfiguration($this->oltModel);
            }

            $this->synchronizePrompt();

            if ($guard !== null) {
                try {
                    $guard->assertDetectedVersion($this->oltModel, $this->executeCommand('show firmware'));
                } catch (\Throwable) {
                    // Firmware is informative only; CLI profile compatibility is the real gate.
                }
            }

            $this->initialized = true;
        } catch (\Throwable $exception) {
            $this->disconnect();
            throw $exception;
        }
    }

    private function executeCommand(string $cmd): string|bool
    {
        if ($this->prompt === null) {
            throw new \RuntimeException("DATACOM SSH prompt is not synchronized.");
        }

        $ssh = $this->getConn()->getConn();
        $responsePattern = "~(?:--More--|\(END\)|" . preg_quote($this->prompt, "~") . "\s*$)~";

        $ssh->write("$cmd\n");
        $read = $this->readResponse($ssh, $responsePattern);

        if (!$this->endsWithPrompt($read)) {
            throw new \RuntimeException("DATACOM SSH response did not end with the expected prompt.");
        }

        $read = $this->removeMore($read);

        return $this->clearResult($read, $this->prompt, $cmd);
    }

    private function readResponse(SSH2 $ssh, string $responsePattern): string
    {
        $read = $this->read($ssh, $responsePattern);
        $lastRead = $read;

        while (str_contains($lastRead, "--More--") || str_contains($lastRead, "(END)")) {
            $ssh->write(str_contains($lastRead, "(END)") ? "q" : " ");
            $lastRead = $this->read($ssh, $responsePattern);
            $read .= "\r\n" . $lastRead;
        }

        return $read;
    }

    private function read(SSH2 $ssh, string $responsePattern): string
    {
        $read = $ssh->read($responsePattern, SSH2::READ_REGEX);

        if ($ssh->isTimeout()) {
            throw new \RuntimeException("Timeout while waiting for DATACOM SSH response.");
        }

        if (!is_string($read) || $read === '') {
            throw new \RuntimeException("Empty DATACOM SSH response.");
        }

        return $read;
    }

    private function synchronizePrompt(): void
    {
        $ssh = $this->getConn()->getConn();
        $read = $this->read($ssh, "~(?:^|\r?\n)([^\r\n]*[#>])\s*$~");

        if (!preg_match("~(?:^|\r?\n)([^\r\n]*[#>])\s*$~", $read, $matches)) {
            throw new \RuntimeException("Unable to detect DATACOM SSH prompt.");
        }

        $prompt = trim($matches[1]);
        if ($prompt === '') {
            throw new \RuntimeException("Detected an empty DATACOM SSH prompt.");
        }

        $this->prompt = $this->removeAnsiSequences($prompt);
    }

    private function endsWithPrompt(string $read): bool
    {
        return $this->prompt !== null
            && preg_match("~" . preg_quote($this->prompt, "~") . "\s*$~", $this->removeAnsiSequences($read)) === 1;
    }

    private function reconnect(): void
    {
        $this->disconnect();
        $this->ensureInitialized();
    }

    public function disconnect(): void
    {
        try {
            $this->connection?->disconnect();
        } catch (\Throwable) {
            // A broken socket must not prevent the next command from reconnecting.
        } finally {
            $this->connection = null;
            $this->prompt = null;
            $this->initialized = false;
        }
    }

    private function isReadOnly(string $cmd): bool
    {
        return str_starts_with(strtolower(ltrim($cmd)), "show ");
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

    private function clearResult(bool|string|null $read, string $prompt, string $command): string|bool
    {
        if (empty($read)) {
            return false;
        }

        $data = $this->removeAnsiSequences($read);
        $data = str_replace("\x08", '', $data);
        $data = str_replace("--More--", '', $data);
        $data = str_replace($prompt, '', $data);
        $data = str_replace($command, '', $data);
        return trim($data);
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;

        if ($this->connection !== null) {
            $this->connection->setTimeout($timeout);
        }
    }

    private function removeMore(bool|string|null $data): string
    {

        $cleanedData = $this->removeAnsiSequences($data);
        $cleanedData = preg_replace('/--More--/', '', $cleanedData);
        $cleanedData = preg_replace('/\(END\)/', '', $cleanedData);

        if ($cleanedData) {
            return $cleanedData;
        }
        return "";

    }

    private function removeAnsiSequences(bool|string|null $data): string
    {
        if ($data === null || $data === false) {
            return "";
        }

        return preg_replace('/\e\[[0-9;?]*[a-zA-Z]/', '', $data) ?? "";
    }

}

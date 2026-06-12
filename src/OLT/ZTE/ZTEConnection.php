<?php

namespace LLENON\OltInformation\OLT\ZTE;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use phpseclib3\Net\SSH2;

class ZTEConnection implements ConnectionInterface
{
    private ?SSHConnection $connection = null;
    private bool $initialized = false;
    private ?string $prompt = null;
    /** @var array<string, array{t:int, v:string|bool}> */
    private array $cache = [];
    private int $timeout = 10;

    /**
     * Per-command-prefix TTL in seconds.
     * Distance is physical fiber — changes only on cable work, safe to cache for an hour.
     * VLAN/service provisioning changes only when an admin reconfigures — 5 minutes is safe.
     * Signal is intentionally not cached because callers require a live reading.
     * More specific prefixes must come before broader ones (e.g. 'show gpon onu distance'
     * before 'show gpon onu detail-info').
     */
    private const CACHE_TTL_MAP = [
        'show gpon onu distance'              => 3600,
        'show gpon remote-onu service'        => 300,
        'show gpon remote-onu interface pon'  => null,
        'show gpon remote-onu '               => 60,
        'show gpon onu detail-info'           => 60,
        'show pon onu information'            => 30,
    ];

    public function __construct(
        private readonly OLT $oltModel
    )
    {

    }


    public function exec(string $cmd): string|bool
    {
        $this->ensureInitialized();
        return $this->runCommand($cmd);
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->synchronizePrompt();

            // Reduce interactive paging / "--More--" round-trips.
            // ZTE CLIs commonly support this; if the command is unknown it is harmless for most flows.
            $this->executeCommand("terminal length 0");
            $this->initialized = true;
        } catch (\RuntimeException $exception) {
            $this->disconnect();
            throw $exception;
        }
    }

    private function runCommand(string $cmd): string|bool
    {
        $ttl = $this->getCacheTtl($cmd);
        if ($ttl !== null) {
            $cached = $this->cache[$cmd] ?? null;
            if ($cached && (time() - $cached['t']) <= $ttl) {
                return $cached['v'];
            }
        }

        try {
            $result = $this->executeCommand($cmd);
        } catch (\RuntimeException $exception) {
            $this->reconnect();

            if (!$this->isReadOnly($cmd)) {
                throw new \RuntimeException(
                    "ZTE SSH session lost synchronization. The command was not repeated automatically.",
                    0,
                    $exception
                );
            }

            try {
                $result = $this->executeCommand($cmd);
            } catch (\RuntimeException $retryException) {
                $this->disconnect();
                throw new \RuntimeException(
                    "ZTE SSH session lost synchronization after retrying the read-only command.",
                    0,
                    $retryException
                );
            }
        }

        if ($ttl !== null) {
            $this->cache[$cmd] = ['t' => time(), 'v' => $result];
        }
        return $result;
    }

    private function executeCommand(string $cmd): string|bool
    {
        if ($this->prompt === null) {
            throw new \RuntimeException("ZTE SSH prompt is not synchronized.");
        }

        $ssh = $this->getConn()->getConn();
        $responsePattern = "~(?:--More--|" . preg_quote($this->prompt, "~") . "\s*$)~";

        $ssh->write("$cmd\n");
        $read = $this->readResponse($ssh, $responsePattern);

        if (!$this->endsWithPrompt($read)) {
            throw new \RuntimeException("ZTE SSH response did not end with the expected prompt.");
        }

        return $this->clearResult($read, $this->prompt, $cmd);
    }

    private function readResponse(SSH2 $ssh, string $responsePattern): string
    {
        $read = $this->read($ssh, $responsePattern);
        $lastRead = $read;

        while (str_contains($lastRead, "--More--")) {
            $ssh->write(" ");
            $lastRead = $this->read($ssh, $responsePattern);
            $read .= "\r\n" . $lastRead;
        }

        return $read;
    }

    private function read(SSH2 $ssh, string $responsePattern): string
    {
        $read = $ssh->read($responsePattern, SSH2::READ_REGEX);

        if ($ssh->isTimeout()) {
            throw new \RuntimeException("Timeout while waiting for ZTE SSH response.");
        }

        if (!is_string($read) || $read === '') {
            throw new \RuntimeException("Empty ZTE SSH response.");
        }

        return $read;
    }

    private function synchronizePrompt(): void
    {
        $ssh = $this->getConn()->getConn();
        $read = $this->read($ssh, "~(?:^|\r?\n)([^\r\n]*[#>])\s*$~");

        if (!preg_match("~(?:^|\r?\n)([^\r\n]*[#>])\s*$~", $read, $matches)) {
            throw new \RuntimeException("Unable to detect ZTE SSH prompt.");
        }

        $prompt = trim($matches[1]);
        if ($prompt === '') {
            throw new \RuntimeException("Detected an empty ZTE SSH prompt.");
        }

        $this->prompt = $prompt;
    }

    private function endsWithPrompt(string $read): bool
    {
        return $this->prompt !== null
            && preg_match("~" . preg_quote($this->prompt, "~") . "\s*$~", $read) === 1;
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

    private function getCacheTtl(string $cmd): ?int
    {
        $cmd = ltrim($cmd);
        foreach (self::CACHE_TTL_MAP as $prefix => $ttl) {
            if (str_starts_with($cmd, $prefix)) {
                return $ttl;
            }
        }
        return null;
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
        $data = str_replace("\x08", '', $read);
        $data = str_replace("--More--", '', $data);
        $data = str_replace($prompt, '', $data);
        $data = str_replace($command, '', $data);
        return trim($data);
    }

    public function setTimeout(int $timeout):void
    {
        $this->timeout = $timeout;

        if ($this->connection !== null) {
            $this->connection->setTimeout($timeout);
        }
    }
}

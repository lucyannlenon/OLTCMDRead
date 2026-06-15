<?php

namespace LLENON\OltInformation\Connections;

class TL1Connection implements ConnectionInterface
{
    private $fp; // Type hint removed for flexibility, as it can be null or resource
    private string $ipOlt;
    private string $ipTl1;
    private string $user;
    private string $pass;
    private bool $debug;
    /** @var array<string, array{t:int, v:string}> */
    private array $cache = [];
    private int $cacheTtlSeconds = 3;

    public function getIpOlt(): string
    {
        return $this->ipOlt;
    }

    public function __construct(string $ipOlt, string $ipTl1, string $user, string $pass, bool $debug = false)
    {

        $this->validateInput($ipOlt, $ipTl1, $user, $pass);
        $this->ipOlt = $ipOlt;
        $this->ipTl1 = $ipTl1;
        $this->user = $user;
        $this->pass = $pass;
        $this->debug = $debug;

        $this->connect();
        $response = $this->exec("LOGIN:::CTAG::UN={$user},PWD={$pass};");
        if (!$this->isSuccessfulLoginResponse($response)) {
            $this->close();
            throw new \RuntimeException('TL1 authentication failed.');
        }
    }

    private function connect(): void
    {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
        $this->fp = @fsockopen($this->ipTl1, 3337, $errno, $errstr, 30);
        if (!$this->fp) {
            throw new \RuntimeException("Failed to connect to TL1 gateway: $errstr ($errno)");
        }
        stream_set_timeout($this->fp, 10); // Set 10-second timeout
    }

    public function exec(string $cmd): string
    {
        if ($this->isCacheable($cmd)) {
            $cached = $this->cache[$cmd] ?? null;
            if ($cached && (time() - $cached['t']) <= $this->cacheTtlSeconds) {
                return $cached['v'];
            }
        }

        $ret = [];

        if (!is_resource($this->fp)) {
            if ($this->debug) {
                error_log("Connection closed, attempting to reconnect for command: $cmd");
            }
            $this->connect();
        }

        if ($this->debug) {
            error_log("Executing command: $cmd");
        }

        if (fwrite($this->fp, "$cmd\n") === false) {
            if ($this->debug) {
                error_log("fwrite failed for command: $cmd, attempting to reconnect");
            }
            $this->connect();
            if (fwrite($this->fp, "$cmd\n") === false) {
                throw new \RuntimeException("Failed to write command: $cmd (Broken pipe or invalid resource)");
            }
        }

        while (true) {
            $c = fread($this->fp, 1);
            if ($c === false || $c === ';' || feof($this->fp)) {
                break;
            }
            $lin = trim($c . fgets($this->fp));
            $ret[] = $lin;
        }

        $result = implode("\n", $ret);
        if ($this->debug) {
            error_log("Command response: $result");
        }
        if ($this->isCacheable($cmd)) {
            $this->cache[$cmd] = ['t' => time(), 'v' => $result];
        }
        return $result;
    }

    private function isCacheable(string $cmd): bool
    {
        $cmd = ltrim($cmd);
        return str_starts_with($cmd, 'LST-ONUSTATE::')
            || str_starts_with($cmd, 'LST-UNREGONU::');
    }

    private function isSuccessfulLoginResponse(string $response): bool
    {
        if (trim($response) === '') {
            return false;
        }

        $normalized = strtoupper($response);

        if (str_contains($normalized, 'COMPLD')
            && preg_match('/\bEN\s*=\s*0\b/', $normalized) === 1) {
            return true;
        }

        return !str_contains($normalized, 'DENY')
            && !str_contains($normalized, 'FAILED')
            && preg_match('/\bEN\s*=\s*[1-9]\d*\b/', $normalized) !== 1
            && (
                str_contains($normalized, 'COMPLD')
                || str_contains($normalized, 'SUCCESS')
            );
    }

    public function close(): void
    {
        if (is_resource($this->fp)) {
            try {
                $this->exec("LOGOUT:::CTAG::;");
            } catch (\Exception $e) {
                if ($this->debug) {
                    error_log("Logout failed: " . $e->getMessage());
                }
            }
            fclose($this->fp);
            $this->fp = null;
        }
    }

    private function validateInput(string $ipOlt, string $ipTl1, string $user, string $pass): void
    {
        if (empty($ipOlt) || empty($ipTl1) || empty($user) || empty($pass)) {
            throw new \InvalidArgumentException("All constructor parameters must be non-empty.");
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function setTimeout(int $timeout): void
    {
        if (is_resource($this->fp)) {
            stream_set_timeout($this->fp, $timeout);
        }
    }
}

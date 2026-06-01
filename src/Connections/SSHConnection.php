<?php

namespace LLENON\OltInformation\Connections;

use LLENON\OltInformation\Exceptions\InvalidUserException;
use phpseclib3\Net\SSH2;

class SSHConnection implements ConnectionInterface
{
    private ?SSH2 $conn = null;
    private int $timeout = 10;

    public function __construct(
        private string                        $address,
        private string                        $username,
        #[\SensitiveParameter] private string $password,
        private string                        $port = "22"
    )
    {
        // Lazy-connect on first use to avoid paying connection cost when an instance
        // is created but never used (common in factories/adapters).
    }

    public function getConn(): SSH2
    {
        $this->ensureConnected();
        return $this->conn;
    }

    private function ensureConnected(): void
    {
        if ($this->conn instanceof SSH2 && $this->conn->isConnected()) {
            return;
        }
        $this->startConnection();
    }


    public function exec(string $cmd): string|bool
    {

        return $this->getConn()->exec($cmd);
    }


    private function startConnection(): void
    {
        $this->conn = new SSH2($this->address, (int) $this->port, $this->timeout);
        $success = $this->conn->login($this->username, $this->password);

        if (!$success) {
            $this->disconnect();
            throw new InvalidUserException("Invalid credentials user: $this->username, ip: $this->address, port: $this->port");
        }
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;

        if ($this->conn instanceof SSH2) {
            $this->conn->setTimeout($timeout);
        }
    }

    public function disconnect(): void
    {
        if ($this->conn instanceof SSH2) {
            try {
                $this->conn->disconnect();
            } catch (\Throwable) {
                // The local reference still needs to be discarded when the socket is broken.
            } finally {
                $this->conn = null;
            }
        }
    }
}

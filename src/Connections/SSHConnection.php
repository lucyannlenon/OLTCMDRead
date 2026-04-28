<?php

namespace LLENON\OltInformation\Connections;

use LLENON\OltInformation\Exceptions\InvalidUserException;
use phpseclib3\Net\SSH2;

class SSHConnection implements ConnectionInterface
{
    private ?SSH2 $conn = null;

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
        $this->conn = new SSH2($this->address, $this->port);
        $success = $this->conn->login($this->username, $this->password);

        if (!$success) {
            throw new InvalidUserException("Invalid credentials user: $this->username, ip: $this->address, port: $this->port");
        }
    }

    public function setTimeout(int $timeout): void
    {
        $this->getConn()->setTimeout($timeout);
    }
}

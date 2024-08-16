<?php

namespace LLENON\OltInformation\Connections;

use LLENON\OltInformation\Exceptions\InvalidUserException;
use phpseclib3\Net\SSH2;

class SSHConnection implements ConnectionInterface
{
    private static SSH2 $conn;
    private static string $lasAddress;

    public function __construct(
        private string                        $address,
        private string                        $username,
        #[\SensitiveParameter] private string $password,
        private string                        $port = "22"
    )
    {

        if (empty(self::$conn) || !self::$conn->isConnected() || self::$lasAddress != $this->address) {
            $this->startConnection();
        }


    }

    public function getConn(): SSH2
    {
        return self::$conn;
    }


    public function exec(string $cmd): string|bool
    {

        return $this->getConn()->exec($cmd);
    }


    private function startConnection(): void
    {
        self::$lasAddress = $this->address;
        self::$conn = new SSH2($this->address, $this->port);
        $success = self::$conn->login($this->username, $this->password);

        if (!$success) {
            throw new InvalidUserException("Invalid credentials user: $this->username, ip: $this->address, port: $this->port");
        }
    }
}
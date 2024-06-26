<?php

namespace LLENON\OltInformation\Connections;

use LLENON\OltInformation\Exceptions\InvalidUserException;
use phpseclib3\Net\SSH2;

readonly class SSHConnection implements ConnectionInterface
{
    private SSH2 $conn;

    public function __construct(
        private string $address,
        private string $username,
        private string $password,
        private string $port = "22"
    )
    {


        $this->conn = new SSH2($this->address, $this->port);
        $success = $this->conn->login($this->username, $this->password);
        if (!$success) {
            throw new InvalidUserException("Invalid credentials user: $this->username, ip: $this->address, port: $this->port");
        }
    }

    public function getConn(): SSH2
    {
        return $this->conn;
    }


    public function exec(string $cmd): string|bool
    {

        return $this->getConn()->exec($cmd);
    }
}
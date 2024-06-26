<?php

namespace LLENON\OltInformation\OLT\ZTE;

use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;

class ZTEConnection implements \LLENON\OltInformation\Connections\ConnectionInterface
{
    private \LLENON\OltInformation\Connections\ConnectionInterface $connection;

    public function __construct(
        private readonly OLT $oltModel
    )
    {

    }


    public function exec(string $cmd): mixed
    {
        $hostname = "{$this->oltModel->nome}#";
        $this->getConn()->getConn()->read($hostname);
        $this->getConn()->getConn()->write("$cmd\n");
        $read = $this->getConn()->getConn()->read($hostname);

        return $this->clearResult($read, $hostname, $cmd);
    }

    private function getConn(): SSHConnection
    {
        if (empty($this->connection)) {
            $this->connection = new SSHConnection(
                $this->oltModel->ip,
                $this->oltModel->userName,
                $this->oltModel->password,
                $this->oltModel->port
            );
        }
        return $this->connection;


    }

    private function clearResult(bool|string|null $read, string $hostname, string $command): string|bool
    {
        if (empty($read)) {
            return false;
        }
        $data = str_replace($hostname, '', $read);
        $data = str_replace($command, '', $data);
        return trim($data);
    }
}
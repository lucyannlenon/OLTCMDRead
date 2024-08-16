<?php

namespace LLENON\OltInformation\OLT\DATACOM;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use phpseclib3\Net\SSH2;

class DATACOMConnection implements ConnectionInterface
{
    private ConnectionInterface $connection;

    public function __construct(
        private readonly OLT $oltModel
    )
    {

    }


    public function exec(string $cmd): string|bool
    {
        $hostname = "{$this->oltModel->nome}#";
        $ssh = $this->getConn()->getConn();
        $ssh->read($hostname);
        $ssh->write("$cmd\n");

        $hostname2 = "#--More--|" . preg_quote($this->oltModel->nome) . "\##";
        $read = $ssh->read($hostname2, SSH2::READ_REGEX);

        if (str_contains($read, "--More--")) {
            do {
                $ssh->write(" ");
                $read2 = str_replace("\x08", "", $ssh->read($hostname2, SSH2::READ_REGEX));
                $read .= $read2;
            } while (str_contains($read2, "--More--"));
        }
        $read = $this->removeMore($read);
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

    public function setTimeout(int $timeout):void
    {
        $this->getConn()->getConn()->setTimeout($timeout);
    }

    private function removeMore(bool|string|null $data): string
    {

        $cleanedData = preg_replace('/\e\[[0-9;]*[a-zA-Z]/', '', $data);
        $cleanedData = preg_replace('/--More--/', '', $cleanedData);
        $cleanedData = preg_replace('/\(END\)/', '', $cleanedData);

        if ($cleanedData) {
            return $cleanedData;
        }
        return "";

    }
}
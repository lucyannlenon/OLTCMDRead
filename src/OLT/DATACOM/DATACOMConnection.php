<?php

namespace LLENON\OltInformation\OLT\DATACOM;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use phpseclib3\Net\SSH2;

class DATACOMConnection implements ConnectionInterface
{
    private ConnectionInterface $connection;
    private int $connectTimeout = 10;
    private int $readTimeout = 60;

    public function __construct(
        private readonly OLT $oltModel
    )
    {

    }


    public function exec(string $cmd): string|bool
    {
        $hostname = "{$this->oltModel->nome}#";
        $ssh = $this->getConn()->getConn();

        // Envia o comando inicial e faz a leitura inicial
        $ssh->read($hostname);
        $ssh->write("$cmd\n");

        $hostnamePattern = "#";
        $read = '';
        $startTime = microtime(true);

        // Continua lendo até que receba o prompt final "#" ou "--More--"
        do {
            $read2 = $ssh->read($hostname, SSH2::READ_NEXT);

            // Verifica o timeout
            if ((microtime(true) - $startTime) > $this->readTimeout) {
                throw new \Exception("Timeout reached while waiting for SSH response.");
            }

            // Se "--More--" estiver presente, envia espaço para continuar
            if (str_contains($read2, "--More--")) {
                $ssh->write(" ");
            }
            $read .= $read2;
        } while (!$this->isFinalResponse($read2, $hostnamePattern));

        // Limpa e retorna o resultado
        $read = $this->removeMore($read);

        $clearResult = $this->clearResult($read, $hostname, $cmd);
        return $clearResult;
    }

    private function isFinalResponse(string $read, string $pattern): bool
    {
        $read = trim($read);
        // Verifica se a resposta finaliza com o prompt ou se ainda contém "--More--"
        return str_ends_with($read, $pattern) && !str_contains($read, "--More--");
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
            $this->connection->setTimeout($this->connectTimeout);
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

    public function setTimeout(int $timeout): void
    {
        $this->readTimeout = $timeout;
        $this->connectTimeout = $timeout;

        if (!empty($this->connection)) {
            $this->connection->setTimeout($timeout);
        }
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

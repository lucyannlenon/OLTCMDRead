<?php

namespace LLENON\OltInformation\OLT\ZTE;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Connections\SSHConnection;
use LLENON\OltInformation\DTO\OLT;
use phpseclib3\Net\SSH2;

class ZTEConnection implements ConnectionInterface
{
    private ConnectionInterface $connection;
    private bool $initialized = false;
    /** @var array<string, array{t:int, v:string|bool}> */
    private array $cache = [];
    private int $cacheTtlSeconds = 3;
    private int $timeout = 10;

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

        // Reduce interactive paging / "--More--" round-trips.
        // ZTE CLIs commonly support this; if the command is unknown it is harmless for most flows.
        $this->runCommand("terminal length 0");
        $this->initialized = true;
    }

    private function runCommand(string $cmd): string|bool
    {
        if ($this->isCacheable($cmd)) {
            $cached = $this->cache[$cmd] ?? null;
            if ($cached && (time() - $cached['t']) <= $this->cacheTtlSeconds) {
                return $cached['v'];
            }
        }

        $hostname = "{$this->oltModel->nome}#";
        $ssh = $this->getConn()->getConn();
        $ssh->read($hostname);
        $ssh->write("$cmd\n");
        $hostname2 = "#--More--|".preg_quote($this->oltModel->nome)."\##";
        $read = $ssh->read($hostname2, SSH2::READ_REGEX);
        if (str_contains($read, "--More--")) {
            do {
                $ssh->write(" ");
                $read2 = str_replace("\x08","",$ssh->read($hostname2, SSH2::READ_REGEX));

                $read .= "\r\n".$read2;
            } while (str_contains($read2, "--More--"));
        }

        $result = $this->clearResult($read, $hostname, $cmd);
        if ($this->isCacheable($cmd)) {
            $this->cache[$cmd] = ['t' => time(), 'v' => $result];
        }
        return $result;
    }

    private function isCacheable(string $cmd): bool
    {
        $cmd = ltrim($cmd);
        return str_starts_with($cmd, 'show pon onu information')
            || str_starts_with($cmd, 'show gpon onu detail-info')
            || str_starts_with($cmd, 'show pon power onu-rx')
            || str_starts_with($cmd, 'show gpon remote-onu ');
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
            $this->connection->setTimeout($this->timeout);
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
        $this->timeout = $timeout;

        if (!empty($this->connection)) {
            $this->connection->setTimeout($timeout);
        }
    }
}

<?php

namespace LLENON\OltInformation\Connections;

class TL1Connection implements ConnectionInterface
{
    private mixed $fp;
    private string $ipOlt;

    public function getIpOlt(): string
    {
        return $this->ipOlt;
    }


    public function __construct(string $ipOlt, string $ipTl1, string $User, string $Pass, bool $debug = false)
    {
        $this->validateInput($ipTl1, $ipOlt, $User, $Pass);

        $this->fp = @fsockopen($ipTl1, 3337);
        if (!$this->fp) {
            throw new \Exception("Failed to connect to $ipTl1");
        }

        $this->ipOlt = $ipOlt;
        $ret =  $this->exec("LOGIN:::CTAG::UN={$User},PWD={$Pass};");
    }

    public function exec(string $cmd): string
    {
        $ret = [];

        fwrite($this->fp, "$cmd\n");
        while (true) {
            $c = fread($this->fp, 1);
            if ($c === false || $c === ';' || feof($this->fp)) break;
            $lin = trim($c . fgets($this->fp));
            $ret[] =$lin;
        }
        return implode("\n",$ret);
    }

    public function close(): void
    {
        $this->exec("LOGOUT:::CTAG::;");
        fclose($this->fp);
    }

    private function validateInput(string $ipAdmin, string $ipTL1, string $User, string $Pass): void
    {
        if (empty($ipAdmin) || empty($ipTL1) || empty($User) || empty($Pass)) {
            throw new \InvalidArgumentException("All constructor parameters must be non-empty.");
        }
    }


    public function __destruct()
    {
        $this->close();
    }

    public function setTimeout(int $timeout): void
    {
       // todo not necessary in this case
    }
}
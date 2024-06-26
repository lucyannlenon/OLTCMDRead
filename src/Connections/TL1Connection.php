<?php

namespace LLENON\OltInformation\Connections;

class TL1Connection implements ConnectionInterface
{
    private mixed $fp;
    private bool $DEBUG = false;
    private string $ipTL1;

    public function getIpTL1(): string
    {
        return $this->ipTL1;
    }


    public function __construct(string $ipTL1,string $ipAdmin, string $User, string $Pass, bool $debug = false)
    {
        $this->validateInput($ipAdmin, $ipTL1, $User, $Pass);

        $this->fp = @fsockopen($ipAdmin, 3337);
        if (!$this->fp) {
            throw new \Exception("Failed to connect to $ipAdmin");
        }

        $this->ipTL1 = $ipTL1;
        $this->exec("LOGIN:::CTAG::UN={$User},PWD={$Pass};");
        $this->DEBUG = $debug;
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
}
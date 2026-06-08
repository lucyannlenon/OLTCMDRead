<?php

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\CDATA\Support\PonAddress;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class ListOnuMacAddressCommand extends AbstractCommand
{
    private PonAddress $pon;
    private int $onuId;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection, new LearnedMacAddressStringParser());
    }

    public function execute(string $pon, int $onuId): array
    {
        if ($onuId < 1 || $onuId > 64) {
            throw new \InvalidArgumentException('CDATA ONU ID must be between 1 and 64.');
        }

        $this->pon = PonAddress::fromString($pon);
        $this->onuId = $onuId;

        return $this->exec();
    }

    protected function getCommand(): string
    {
        return "show mac-address ont {$this->pon->full()} {$this->onuId}";
    }
}

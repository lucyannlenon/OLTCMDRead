<?php

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\CDATA\Support\PonAddress;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class ListOnuCommand extends AbstractCommand
{
    private PonAddress $pon;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection, new ListOnuStringParser());
    }

    public function execute(string $pon): array
    {
        $this->pon = PonAddress::fromString($pon);
        return $this->exec();
    }

    protected function getCommand(): string
    {
        return "show ont info {$this->pon->frameSlot()} {$this->pon->port} all";
    }
}

<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\GetServicePortStringParser;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class GetServicePortCommand extends AbstractCommand
{

    private ?string $pon;
    private string $onuId;

    public function __construct(ConnectionInterface $connection)
    {
        $parser = new GetServicePortStringParser();
        parent::__construct($connection, $parser);
    }


    public function execute(string $pon, string $onuId): ?int
    {
        $this->pon = $pon;
        $this->onuId = $onuId;


        $items = $this->exec();

        return !empty($items) ? $items[0] : null;
    }

    protected function getCommand(): string
    {
        return "show running-config service-port | select gpon {$this->pon} | context-match \"onu {$this->onuId} \"";
    }
}
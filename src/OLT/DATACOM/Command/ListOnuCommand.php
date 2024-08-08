<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\ListOnuParse;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class ListOnuCommand extends AbstractCommand
{

    private ?string $pon;

    public function __construct(ConnectionInterface $connection)
    {
        $parser = new ListOnuParse();
        parent::__construct($connection, $parser);
    }


    public function execute(?string $pon): mixed
    {
        $this->pon = $pon;
        return $this->exec();

    }

    protected function getCommand(): string
    {
        return "show interface gpon " . ($this->pon ?? "") . " onu";
    }
}
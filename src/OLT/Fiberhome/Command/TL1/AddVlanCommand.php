<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;


use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class AddVlanCommand extends AbstractTL1Command
{
    private \LLENON\OltInformation\OLT\Dto\Onu $onu;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new EmptyReturnStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(\LLENON\OltInformation\OLT\Dto\Onu $onu): array
    {
        $this->onu = $onu;
        $this->exec();
        return  [] ;
    }

    protected function getCommand(): string
    {
        return "ADD-PONVLAN::OLTID={$this->getIpOlt()},".
            "PONID={$this->onu->getPon()},".
            "AUTHTYPE=MAC,".
            "ONUID={$this->onu->getGponId()}:CTAG::".
            "CVLAN={$this->onu->getVlan()},".
            "VLANMODE=tag;";
    }
}
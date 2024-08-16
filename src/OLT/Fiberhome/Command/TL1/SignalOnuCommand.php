<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\SignalReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class SignalOnuCommand extends AbstractTL1Command
{
    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection, ?StringParserInterface $parser = null)
    {
        if (!$parser)
            $parser = new SignalReturnStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(string $pon, string $gponId): float
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
        $data = $this->exec();
        if (!empty($data)) {
            return $data[0];
        }
        return 0;
    }



    protected function getCommand(): string
    {
//        LST-ONULANINFO::ONUIP=onu_name|OLTID=olt_name[,PONID=ponport_location,ONUIDTYPE=id-type,ONUID=onu_index],ONUPORT=lanport_index:CTAG::;
        return "LST-OMDDM::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId}:CTAG::;";
    }
}
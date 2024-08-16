<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\DistanceReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class DistanceOnuCommand extends AbstractTL1Command
{
    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new DistanceReturnStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(string $pon, string $gponId): int
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
        $data = $this->exec();
        if (empty($data)) {
            return 0;
        }
        return $data[0];
    }

    protected function getCommand(): string
    {
        #"LST-ONUSTATE::OLTID=olt-name,PONID=ponport_location[,ONUIDTYPE=id-type,ONUID=onu-index]:CTAG::;"
        return "LST-ONUDISTANCE::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId}:CTAG::;";
    }
}
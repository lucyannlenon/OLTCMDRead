<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\DistanceReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class RemoveOnuCommand extends AbstractTL1Command
{
    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new EmptyReturnStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(string $pon, string $gponId): void
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
        $this->exec();
    }

    protected function getCommand(): string
    {
        return "DEL-ONU::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId}:CTAG::;";
    }
}
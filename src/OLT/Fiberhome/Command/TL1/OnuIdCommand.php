<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\IdOnuStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\ListUnAuthorizedStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class OnuIdCommand extends AbstractTL1Command
{
    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new IdOnuStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(string $pon, string $gponId): string
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
        $data = $this->exec();
        if (empty($data)) {
            throw new \Exception("Invalid id for {$this->pon}: {$this->gponId} olt: {$this->getIpOlt()}");
        }
        return (string) $data[0];
    }


    protected function getCommand(): string
    {
        return "LST-ONUSTATE::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId}:CTAG::;";
    }
}
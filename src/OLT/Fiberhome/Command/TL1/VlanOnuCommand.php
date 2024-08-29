<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\DTO\StatusLinkEnum;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EtherReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\SignalReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\VlanReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class VlanOnuCommand extends AbstractTL1Command
{

    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new VlanReturnStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @param string $gponId
     * @return OnuEthernet[]
     */
    public function execute(string $pon, string $gponId): int
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
        try {
            return $this->exec()[0] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getCommand(): string
    {
        return "LST-PORTVLAN::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId},ONUPORT=NA-NA-NA-1:CTAG::;";
    }
}
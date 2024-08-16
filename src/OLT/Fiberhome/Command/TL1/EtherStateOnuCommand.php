<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\DTO\StatusLinkEnum;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EtherReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\SignalReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class EtherStateOnuCommand extends AbstractTL1Command
{

    private string $pon;
    private string $gponId;

    public function __construct(FiberhomeConnection $connection, ?StringParserInterface $parser = null)
    {
        if (!$parser)
            $parser = new EtherReturnStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @param string $gponId
     * @return OnuEthernet[]
     */
    public function execute(string $pon, string $gponId): array
    {
        $this->pon = $pon;
        $this->gponId = $gponId;
       return $this->exec();

    }

    protected function getCommand(): string
    {
       return "LST-ONULANINFO::OLTID={$this->getIpOlt()},PONID={$this->pon},ONUIDTYPE=MAC,ONUID={$this->gponId}:CTAG::;";
    }
}
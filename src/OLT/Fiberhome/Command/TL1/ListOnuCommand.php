<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\DTO\ONU;
use LLENON\OltInformation\DTO\StatusLinkEnum;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\EtherReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\SignalReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\TestReturnStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class ListOnuCommand extends AbstractTL1Command
{

    private string $pon;

    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new TestReturnStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @return array[]
     */
    public function execute(string $pon): array
    {
        $this->pon = $pon;
        /** @var \LLENON\OltInformation\OLT\Dto\Onu[] $data */
        $data = $this->exec();
        foreach ($data as $onu) {
            $onu->setPon($this->pon);
        }
        return $data;
    }

    protected function getCommand(): string
    {
        return "LST-ONUSTATE::OLTID={$this->getIpOlt()},PONID={$this->pon}:CTAG::;";
    }
}
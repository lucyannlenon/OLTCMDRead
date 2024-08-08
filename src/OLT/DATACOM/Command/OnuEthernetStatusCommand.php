<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\EtherReturnStringParser;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class OnuEthernetStatusCommand extends AbstractCommand
{

    private ?string $pon;
    private string $onuId;

    public function __construct(ConnectionInterface $connection)
    {
        $parser = new EtherReturnStringParser();
        parent::__construct($connection, $parser);
    }

    /**
     * @param string $pon
     * @param string $onuId
     * @return OnuEthernet[]
     */
    public function execute(string $pon, string $onuId): array
    {
        $this->pon = $pon;
        $this->onuId = $onuId;


        return $this->exec();

    }

    protected function getCommand(): string
    {

        return "show interface gpon {$this->pon} onu {$this->onuId} ethernet ";
    }
}
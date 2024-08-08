<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\EthernetReturnStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class OnuEthernetStatusCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new EthernetReturnStringParser());
    }

    /**
     * @param string $pon
     * @param string $onuId
     * @return OnuEthernet[]
     */
    public function execute(string $pon, string $onuId): array
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        return$this->exec();


    }

    protected function getCommand(): string
    {
        return <<<EOT
show gpon remote-onu interface eth gpon_onu-{$this->pon}:{$this->onuId}    
EOT;

    }
}
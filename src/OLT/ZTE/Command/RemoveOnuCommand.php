<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class RemoveOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new EmptyReturnStringParser());
    }

    public function execute(string $pon, string $onuId): void
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        $this->exec();

    }

    protected function getCommand(): string
    {
        return <<<EOT
config t
interface gpon_olt-{$this->pon} 
no onu {$this->onuId} 
exit
exit
EOT;

    }
}
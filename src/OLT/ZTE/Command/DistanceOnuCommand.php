<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\DistanceStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class DistanceOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new DistanceStringParser());
    }

    public function execute(string $pon, string $onuId): string
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        $data = $this->exec();
        if (!empty($data)) {
            return $data[0] . "m";
        }
        return "No Response";
    }

    protected function getCommand(): string
    {
        return <<<EOT
show gpon onu distance gpon_onu-{$this->pon}:{$this->onuId}
EOT;

    }
}
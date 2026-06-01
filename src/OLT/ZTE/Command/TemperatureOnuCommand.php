<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\TemperatureStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class TemperatureOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new TemperatureStringParser());
    }

    public function execute(string $pon, string $onuId): string
    {
        $this->pon = $pon;
        $this->onuId = $onuId;
        $data = $this->exec();

        if (!empty($data)) {
            return $data[0];
        }

        return 'No Response';
    }

    protected function getCommand(): string
    {
        return "show gpon remote-onu interface pon gpon_onu-{$this->pon}:{$this->onuId}";
    }
}

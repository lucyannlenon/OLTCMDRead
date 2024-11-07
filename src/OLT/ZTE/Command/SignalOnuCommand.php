<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\SignalStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class SignalOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new SignalStringParser());
    }

    public function execute(string $pon, string $onuId): string
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        $data = $this->exec();

        if (empty($data)) {
            throw new \InvalidArgumentException("Onu {$onuId} not found!");
        }

        return $data[0];
    }

    protected function getCommand(): string
    {
        return <<<EOT
show pon power onu-rx gpon_onu-{$this->pon}:{$this->onuId}
EOT;

    }
}
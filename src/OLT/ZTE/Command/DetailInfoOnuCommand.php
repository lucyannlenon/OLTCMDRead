<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\ZTE\DataProcessors\DetailInfoStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class DetailInfoOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new DetailInfoStringParser());
    }

    public function execute(string $pon, string $onuId): array
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        $data = $this->exec();

        if (empty($data)) {
            throw new \InvalidArgumentException("Onu {$onuId} not found!");
        }

        return $data;
    }

    protected function getCommand(): string
    {
        return <<<EOT
show gpon onu detail-info gpon_onu-{$this->pon}:{$this->onuId}
EOT;

    }
}
<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use JetBrains\PhpStorm\NoReturn;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\EmptyReturnStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class WanStatusOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new EmptyReturnStringParser());
    }

    #[NoReturn] public function execute(string $pon, string $onuId): void
    {
        $this->onuId = $onuId;
        $this->pon = $pon;
        $data = $this->exec();
        dd($data);
    }

    protected function getCommand(): string
    {
        return <<<EOT
show gpon remote-onu wan-ip gpon_onu-{$this->pon}:{$this->onuId}
EOT;

    }
}
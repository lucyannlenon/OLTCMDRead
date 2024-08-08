<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\ListUnAuthorizedStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class ListUnAuthorization extends AbstractCommand
{
    private string $command = "show pon onu unc";

    public function __construct(
         ZTEConnection $connection
    )
    {
        parent::__construct($connection, new ListUnAuthorizedStringParser());
    }

    /**
     * @return Onu[]
     */
    public function execute(): array
    {
        return $this->exec();

    }

    protected function getCommand(): string
    {
        return $this->command;
    }
}
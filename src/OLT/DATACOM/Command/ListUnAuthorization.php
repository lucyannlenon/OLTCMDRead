<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\ListUnAuthorizationParse;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class ListUnAuthorization extends AbstractCommand
{
    private string $command = "show interface gpon discovered-onus";

    public function __construct(
         DATACOMConnection $connection
    )
    {
        parent::__construct($connection, new ListUnAuthorizationParse());
    }

    /**
     * @return Onu[]
     */
    public function execute(): array
    {
         $this->exec();
        return   $this->exec();

    }

    protected function getCommand(): string
    {
        return $this->command;
    }
}
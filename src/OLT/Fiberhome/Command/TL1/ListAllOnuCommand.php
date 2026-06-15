<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

final class ListAllOnuCommand extends AbstractTL1Command
{
    public function __construct(FiberhomeConnection $connection)
    {
        parent::__construct($connection, new ListOnuStringParser());
    }

    public function execute(): array
    {
        return $this->exec();
    }

    protected function getCommand(): string
    {
        return "LST-ONUSTATE::OLTID={$this->getIpOlt()}:CTAG::;";
    }
}

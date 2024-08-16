<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\ListUnAuthorizedStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

class ListUnAuthorizationOnu extends AbstractTL1Command
{
    public function __construct(FiberhomeConnection $connection)
    {
        $parser = new ListUnAuthorizedStringParser();
        parent::__construct($connection, $parser);
    }

    public function execute(): array
    {
        return  $this->exec() ;
    }


    protected function getCommand(): string
    {
        return "LST-UNREGONU::OLTID={$this->getIpOlt()}:CTAG::;";
    }
}
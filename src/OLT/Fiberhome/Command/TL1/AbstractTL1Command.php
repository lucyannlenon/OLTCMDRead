<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

abstract class AbstractTL1Command extends AbstractCommand
{
    protected string $ipOlt ;
    public function __construct(FiberhomeConnection $connection, StringParserInterface $parser)
    {
        $this->ipOlt = $connection->getIpOlt() ;
        parent::__construct($connection, $parser);
    }

    public function getIpOlt(): string
    {
        return $this->ipOlt;
    }

    public function setIpOlt(string $ipOlt): AbstractTL1Command
    {
        $this->ipOlt = $ipOlt;
        return $this;
    }



}
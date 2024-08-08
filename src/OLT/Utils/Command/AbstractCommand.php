<?php

namespace LLENON\OltInformation\OLT\Utils\Command;


use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

abstract class AbstractCommand
{

    public function __construct(
        protected ConnectionInterface         $connection,
        protected StringParserInterface $parser
    )
    {
        $this->connection->setTimeout(1);
    }

    protected function exec(): array
    {
        $string = $this->connection->exec($this->getCommand());
        return $this->parser->parse($string);
    }

    protected abstract function getCommand(): string;
}
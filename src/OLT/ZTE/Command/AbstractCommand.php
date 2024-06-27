<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\ZTE\DataProcessors\StringParserInterface;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

abstract class AbstractCommand
{
    public function __construct(
        protected ZTEConnection         $connection,
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
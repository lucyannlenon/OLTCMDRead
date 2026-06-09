<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\ListOnuStringParser;

final class ListOnuCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly ListOnuStringParser $parser = new ListOnuStringParser()
    ) {
    }

    public function execute(): array
    {
        $response = $this->connection->exec('show onu info');
        return $response === false ? [] : $this->parser->parse($response);
    }
}

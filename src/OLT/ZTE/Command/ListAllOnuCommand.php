<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\ZTE\DataProcessors\GponCardStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

final class ListAllOnuCommand
{
    private ListOnuCommand $listOnus;

    public function __construct(
        private readonly ZTEConnection $connection,
        private readonly GponCardStringParser $cardParser = new GponCardStringParser()
    ) {
        $this->listOnus = new ListOnuCommand($connection);
    }

    public function execute(): array
    {
        $response = $this->connection->exec('show card');
        if (!is_string($response)) {
            return [];
        }

        $onus = [];
        foreach ($this->cardParser->parse($response) as $card) {
            for ($port = 1; $port <= $card['ports']; $port++) {
                array_push(
                    $onus,
                    ...$this->listOnus->execute("{$card['shelf']}/{$card['slot']}/{$port}")
                );
            }
        }

        return $onus;
    }
}

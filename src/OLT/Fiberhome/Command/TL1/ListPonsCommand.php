<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\Utils\PonList\RegisteredPonList;

final class ListPonsCommand
{
    public function __construct(
        private readonly FiberhomeConnection $connection
    ) {
    }

    /** @return list<string> */
    public function execute(): array
    {
        $response = $this->connection->exec(
            "LST-ONUSTATE::OLTID={$this->connection->getIpOlt()}:CTAG::;"
        );

        if (!is_string($response) || trim($response) === '') {
            return [];
        }

        preg_match_all('/NA-NA-\d+-\d+/i', $response, $matches);
        if (($matches[0] ?? []) !== []) {
            return RegisteredPonList::normalize($matches[0]);
        }

        return RegisteredPonList::fromOnus((new ListAllOnuCommand($this->connection))->execute());
    }
}

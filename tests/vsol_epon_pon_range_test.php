<?php

declare(strict_types=1);

use LLENON\OltInformation\OLT\VSOL\EPON\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

$connection = new class implements VSolEponConnectionInterface {
    public function exec(string $cmd): string|bool
    {
        return false;
    }

    public function execInPon(int $pon, string $cmd): string|bool
    {
        return false;
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
    }
};

$result = (new ListOnuCommand($connection))->execute(8);

if ($result !== []) {
    throw new RuntimeException('Expected an empty result for the empty eight-port fixture.');
}

echo "VSOL EPON eight-port range test passed.\n";

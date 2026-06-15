<?php

declare(strict_types=1);

use LLENON\OltInformation\OLT\ZTE\DataProcessors\GponCardStringParser;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$cards = (new GponCardStringParser())->parse(<<<'TEXT'
Shelf Slot CfgType CardName Port HardVer Status
1 1 GMPA GMPA 6 V1.0.0 INSERVICE
1 3 GVGH GVGH 16 V1.0.0 INSERVICE
1 4 PUMD PUMD 0 N/A INSERVICE
TEXT);

expect(count($cards) === 1, 'Only GPON service cards must be returned.');
expect($cards[0]['slot'] === 3 && $cards[0]['ports'] === 16, 'GPON card topology was not parsed.');

echo "ZTE inventory tests passed.\n";

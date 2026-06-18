<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\RemoveOnuCommand;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * Fake connection that records every command sent and answers the
 * service-port lookup with a valid id so the write block is reached.
 */
$connection = new class implements ConnectionInterface {
    /** @var string[] */
    public array $commands = [];

    public function exec(string $cmd): mixed
    {
        $this->commands[] = $cmd;

        if (str_contains($cmd, 'show running-config service-port')) {
            return 'service-port 4012 gpon 1/1/4 onu 73 gem 1 match vlan vlan-id 2000';
        }

        return '';
    }

    public function setTimeout(int $timeout): void
    {
    }
};

(new RemoveOnuCommand($connection))->execute('1/1/4', '73');

// First command is the service-port lookup, second is the write block.
$writeCommand = $connection->commands[1] ?? '';
$lines = array_values(array_filter(
    array_map('trim', explode("\n", $writeCommand)),
    static fn (string $line): bool => $line !== ''
));
$lastLine = end($lines);

// DmOS keeps the session inside config mode after `commit`; without a final
// `exit` the prompt stays `host(config)#` and DATACOMConnection waits for the
// operational prompt forever, raising a false "lost synchronization" even
// though the ONU was removed. The command must return to operational mode.
expect(
    $lastLine === 'exit',
    "DATACOM remove command must return the session to the operational prompt "
        . "(last line should be 'exit', got '{$lastLine}')."
);

expect(
    in_array('commit', $lines, true),
    'DATACOM remove command must still commit the configuration.'
);

echo "DATACOM remove ONU command test passed.\n";

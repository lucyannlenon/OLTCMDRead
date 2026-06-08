<?php

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;

class LocateMacAddressCommand extends AbstractCommand
{
    private string $macAddress;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection, new LearnedMacAddressStringParser());
    }

    public function execute(string $macAddress): array
    {
        $this->macAddress = self::normalizeMacAddress($macAddress);
        return $this->exec();
    }

    protected function getCommand(): string
    {
        return "show location {$this->macAddress}";
    }

    private static function normalizeMacAddress(string $macAddress): string
    {
        $hex = strtoupper(preg_replace('/[^0-9A-F]/i', '', $macAddress) ?? '');

        if (strlen($hex) !== 12) {
            throw new \InvalidArgumentException("Invalid MAC address '{$macAddress}'.");
        }

        return implode(':', str_split($hex, 2));
    }
}

<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors\LearnedMacAddressStringParser;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

final class ListOnuMacAddressCommand extends AbstractTL1Command
{
    private string $pon = '';
    private string $onuMac = '';
    private string $portId = 'NA-NA-NA-1';
    private ?int $vlan = null;

    public function __construct(FiberhomeConnection $connection)
    {
        parent::__construct($connection, new LearnedMacAddressStringParser());
    }

    /**
     * @return list<LearnedMacAddress>
     */
    public function execute(string $pon, string $onuMac): array
    {
        $this->pon = trim($pon);
        $this->onuMac = trim($onuMac);

        if ($this->pon === '') {
            throw new \InvalidArgumentException('Fiberhome ONU MAC lookup requires a PON value.');
        }

        if ($this->onuMac === '') {
            throw new \InvalidArgumentException('Fiberhome ONU MAC lookup requires an ONU MAC value.');
        }

        $this->vlan = (new VlanOnuCommand($this->connection))->execute($this->pon, $this->onuMac);
        if ($this->vlan === null || $this->vlan <= 0) {
            return [];
        }

        $normalizedOnuMac = null;
        try {
            $normalizedOnuMac = MacLocation::normalizeMacAddress($this->onuMac);
        } catch (\InvalidArgumentException) {
            // Keep the command tolerant; the DTO filter below only runs when the ONU MAC is valid.
        }

        $results = [];
        foreach ($this->exec() as $entry) {
            if (!is_array($entry) || !isset($entry['macAddress'])) {
                continue;
            }

            if ($normalizedOnuMac !== null && $entry['macAddress'] === $normalizedOnuMac) {
                continue;
            }

            try {
                $results[] = new LearnedMacAddress(
                    $entry['macAddress'],
                    (string) ($entry['vlan'] ?? ''),
                    $this->pon,
                    $this->onuMac,
                    (string) ($entry['type'] ?? 'dynamic'),
                    isset($entry['gemIndex']) ? (string) $entry['gemIndex'] : null,
                    isset($entry['gemId']) ? (string) $entry['gemId'] : null,
                    isset($entry['uniPort']) ? (string) $entry['uniPort'] : null,
                );
            } catch (\InvalidArgumentException) {
                // Invalid MAC lines are skipped so one bad row does not abort the result.
            }
        }

        return $results;
    }

    protected function getCommand(): string
    {
        return sprintf(
            'LST-PORTMACADDRESS::OLTID=%s,PONID=%s,ONUIDTYPE=MAC,ONUID=%s,PORTID=%s,VLAN=%d:CTAG::;',
            $this->getIpOlt(),
            $this->pon,
            $this->onuMac,
            $this->portId,
            $this->vlan ?? 0
        );
    }
}

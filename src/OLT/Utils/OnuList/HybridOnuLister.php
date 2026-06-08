<?php

namespace LLENON\OltInformation\OLT\Utils\OnuList;

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\CDATA\Command\ListOnuCommand as CdataListOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\DATACOM\Command\ListOnuCommand as DatacomListOnuCommand;
use LLENON\OltInformation\OLT\ZTE\Command\ListOnuCommand as ZteListOnuCommand;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;
use LLENON\OltInformation\SNMP\SnmpConfig;
use LLENON\OltInformation\SNMP\SnmpPool;
use LLENON\OltInformation\SNMP\OidRegistry;

/**
 * Lists ONUs via SNMP when possible; falls back to SSH/TL1 commands.
 *
 * SNMP is vendor/OID-specific. Provide an OID registry and we will run the walk
 * while keeping SSH as fallback.
 */
final class HybridOnuLister
{
    public function __construct(
        private readonly SnmpPool $pool = new SnmpPool(),
        private readonly ?OidRegistry $oids = null
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function listOnu(OLT $olt, string $pon, ?SnmpConfig $snmp = null): array
    {
        if ($snmp) {
            $oid = $this->oids?->getListOnuOid($olt);
            if ($oid) {
                // For now we return raw snmpwalk output lines; call sites can parse as needed.
                // This still removes SSH load and can be executed concurrently by the caller.
                $out = $this->pool->run([['host' => $olt->ip, 'oid' => $oid, 'op' => 'walk']], $snmp, 1)[0] ?? '';
                $lines = preg_split("/\\R/", trim($out)) ?: [];
                return array_values(array_filter(array_map('trim', $lines), static fn($l) => $l !== ''));
            }
        }

        // Fallbacks (existing behavior).
        $modelKey = is_string($olt->model) ? $olt->model : (string) $olt->model;
        if (str_contains(strtoupper($modelKey), 'ZTE')) {
            return (new ZteListOnuCommand(new ZTEConnection($olt)))->execute($pon);
        }

        if (str_contains(strtoupper($modelKey), 'DATACOM')) {
            return (new DatacomListOnuCommand(new DATACOMConnection($olt)))->execute($pon);
        }

        if (str_contains(strtoupper($modelKey), 'CDATA')) {
            return (new CdataListOnuCommand(new CDATAConnection($olt)))->execute($pon);
        }

        // Unknown model: safest is return empty (or throw) to avoid wrong command.
        return [];
    }
}

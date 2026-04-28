<?php

namespace LLENON\OltInformation\SNMP;

use LLENON\OltInformation\DTO\OLT;

/**
 * Resolves vendor/model-specific OIDs.
 *
 * Keep mapping out of code by storing it in a PHP array config file.
 */
final class OidRegistry
{
    /**
     * @param array<string, array<string, string>> $map
     *   shape:
     *   [
     *     'ZTE' => ['list_onu' => '1.3.6.1...'],
     *     'DATACOM' => ['list_onu' => '1.3.6.1...'],
     *   ]
     */
    public function __construct(private readonly array $map)
    {
    }

    public static function fromFile(string $path): self
    {
        /** @var mixed $data */
        $data = require $path;
        if (!is_array($data)) {
            throw new SnmpException("Invalid OID map file: {$path}");
        }
        return new self($data);
    }

    public function getListOnuOid(OLT $olt): ?string
    {
        $vendor = $this->detectVendorKey($olt);
        if ($vendor === null) {
            return null;
        }
        return $this->map[$vendor]['list_onu'] ?? null;
    }

    private function detectVendorKey(OLT $olt): ?string
    {
        $modelKey = strtoupper((string) $olt->model);
        foreach (array_keys($this->map) as $vendorKey) {
            if (str_contains($modelKey, strtoupper($vendorKey))) {
                return $vendorKey;
            }
        }
        return null;
    }
}


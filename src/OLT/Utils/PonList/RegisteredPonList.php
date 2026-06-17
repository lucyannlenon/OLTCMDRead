<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Utils\PonList;

final class RegisteredPonList
{
    /**
     * @param array<object> $onus
     * @return list<string>
     */
    public static function fromOnus(array $onus): array
    {
        $pons = [];

        foreach ($onus as $onu) {
            if (!is_object($onu) || !method_exists($onu, 'getPon')) {
                continue;
            }

            $pon = trim((string) $onu->getPon());
            if ($pon === '') {
                continue;
            }

            $pons[] = $pon;
        }

        return self::normalize($pons);
    }

    /**
     * @param array<string> $pons
     * @return list<string>
     */
    public static function normalize(array $pons): array
    {
        $unique = [];

        foreach ($pons as $pon) {
            $normalized = trim($pon);
            if ($normalized === '') {
                continue;
            }

            $unique[strtoupper($normalized)] = $normalized;
        }

        $values = array_values($unique);
        usort($values, strnatcasecmp(...));

        return $values;
    }
}

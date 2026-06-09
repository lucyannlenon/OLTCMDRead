<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\EthernetStatus;

final class EthernetStatusStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $linkState = self::tableValue($input, 'Link State');
        $autoNeg = self::tableValue($input, 'AutoNeg');
        $loopDetect = self::tableValue($input, 'Loopdetect');

        if ($linkState === null) {
            return [];
        }

        return [
            new EthernetStatus(
                'N/A',
                strtolower($linkState),
                $autoNeg === null ? 'N/A' : strtolower($autoNeg),
                $loopDetect === null ? 'N/A' : strtolower($loopDetect)
            ),
        ];
    }

    private static function tableValue(string $input, string $heading): ?string
    {
        $lines = preg_split('/\R/', $input) ?: [];

        foreach ($lines as $index => $line) {
            if (
                preg_match(
                    '/^\s*PortId\s+' . preg_quote($heading, '/') . '\s*$/i',
                    $line
                ) !== 1
            ) {
                continue;
            }

            $valueLine = $lines[$index + 1] ?? '';
            if (preg_match('/^\s*\d+\s+(\S+)\s*$/', $valueLine, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

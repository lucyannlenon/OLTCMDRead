<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\EthernetStatus;

final class EthernetStatusStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $values = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match('/^\s*([^:]+):\s*(.*?)\s*$/', $line, $matches)) {
                $values[strtolower(trim($matches[1]))] = trim($matches[2]);
            }
        }

        foreach (['speed status', 'operate status', 'speed config', 'ethernet loop'] as $key) {
            if (!isset($values[$key])) {
                return [];
            }
        }

        return [
            new EthernetStatus(
                $values['speed status'],
                $values['operate status'],
                $values['speed config'],
                $values['ethernet loop']
            ),
        ];
    }
}

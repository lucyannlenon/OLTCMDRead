<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

final readonly class OltVersionEvidenceProbe
{
    /**
     * @param null|\Closure(OltInventoryEntry):ConnectionInterface $connectionFactory
     */
    public function __construct(
        private ?\Closure $connectionFactory = null,
        private OltProbeSanitizer $sanitizer = new OltProbeSanitizer(),
    ) {
    }

    /** @return array<string, int|string|list<string>|null> */
    public function probe(OltInventoryEntry $entry, string $command = 'show version'): array
    {
        if (preg_match('/^show(?:\s|$)/i', trim($command)) !== 1) {
            throw new \InvalidArgumentException('Version evidence probes allow only show commands.');
        }

        $connection = null;

        try {
            $connection = $this->connectionFactory !== null
                ? ($this->connectionFactory)($entry)
                : $this->createConnection($entry);
            $output = $connection->exec($command);

            return array_merge($entry->safeMetadata(), [
                'evidence' => $this->extractEvidence(is_string($output) ? $output : '', $entry),
                'errorCode' => null,
            ]);
        } catch (\Throwable $exception) {
            return array_merge($entry->safeMetadata(), [
                'evidence' => [],
                'errorCode' => 'VERSION_PROBE_FAILED',
            ]);
        } finally {
            if (is_object($connection) && method_exists($connection, 'disconnect')) {
                $connection->disconnect();
            }
        }
    }

    private function createConnection(OltInventoryEntry $entry): ConnectionInterface
    {
        return match (strtoupper((string) $entry->olt->model)) {
            OltModel::CDATA => new CDATAConnection($entry->olt, enforceFirmwareVersion: false),
            OltModel::DATACOM => new DATACOMConnection($entry->olt, enforceFirmwareVersion: false),
            OltModel::ZTE => new ZTEConnection($entry->olt, enforceFirmwareVersion: false),
            default => throw new \InvalidArgumentException('Version evidence probe does not support this model.'),
        };
    }

    /** @return list<string> */
    private function extractEvidence(string $output, OltInventoryEntry $entry): array
    {
        $lines = preg_split('/\R/', $output) ?: [];
        $evidence = [];
        $fallback = [];
        $secrets = [
            (string) $entry->olt->userName,
            (string) $entry->olt->password,
            (string) $entry->olt->ip,
            (string) $entry->name,
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $sanitized = $this->sanitizer->sanitize($line, $secrets);
            if (count($fallback) < 10) {
                $fallback[] = $sanitized;
            }

            if (preg_match(
                '/version|software|firmware|boot|system|model|inventory|equipment|device|card|mac|fdb|forward|address|table|service|bridge|\bl2\b|gpon/i',
                $line
            ) === 1) {
                $evidence[] = $sanitized;
            }

            if (count($evidence) >= 20) {
                break;
            }
        }

        return count($evidence) <= 1 && count($fallback) > count($evidence)
            ? $fallback
            : $evidence;
    }
}

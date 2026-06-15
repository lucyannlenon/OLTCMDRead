<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

use LLENON\OltInformation\Enum\OltModel;

final readonly class OltInventoryProbe
{
    /**
     * @param null|\Closure(OltInventoryEntry):OltDiagnosticResult $diagnostic
     */
    public function __construct(
        private ?\Closure $diagnostic = null,
        private ?FiberhomeTl1Config $fiberhomeConfig = null,
    ) {
    }

    /** @return list<OltInventoryProbeResult> */
    public function run(array $entries, ?int $id = null, ?string $model = null): array
    {
        $results = [];
        $normalizedModel = $model === null ? null : strtoupper(trim($model));

        foreach ($entries as $entry) {
            if (!$entry instanceof OltInventoryEntry) {
                throw new \InvalidArgumentException('OLT probe requires inventory entries.');
            }

            if ($id !== null && $entry->id !== $id) {
                continue;
            }

            if ($normalizedModel !== null && strtoupper((string) $entry->olt->model) !== $normalizedModel) {
                continue;
            }

            $diagnostic = $this->diagnostic !== null
                ? ($this->diagnostic)($entry)
                : $this->diagnose($entry);
            $results[] = new OltInventoryProbeResult($entry, $diagnostic);
        }

        return $results;
    }

    private function diagnose(OltInventoryEntry $entry): OltDiagnosticResult
    {
        $model = strtoupper(trim((string) $entry->olt->model));
        $fiberhomeConfig = null;

        if (in_array($model, [OltModel::FIBERHOME, OltModel::FIBERHOMEOLDVERSION], true)) {
            $fiberhomeConfig = $this->fiberhomeConfig ?? FiberhomeTl1Config::fromEnvironment();
        }

        return (new OltCredentialDiagnostic($fiberhomeConfig))->diagnose($entry->olt);
    }
}

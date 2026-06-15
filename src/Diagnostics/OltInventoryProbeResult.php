<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

final readonly class OltInventoryProbeResult
{
    public function __construct(
        public OltInventoryEntry $entry,
        public OltDiagnosticResult $diagnostic,
    ) {
    }

    /** @return array<string, bool|int|string|null> */
    public function toSafeArray(): array
    {
        return array_merge(
            $this->entry->safeMetadata(),
            $this->diagnostic->toArray()
        );
    }
}

<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

use LLENON\OltInformation\DTO\OLT;

final readonly class OltInventoryEntry
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sourceFile,
        public OLT $olt,
        public ?string $tl1Server = null,
    ) {
        if ($id < 1 || trim($name) === '' || trim($sourceFile) === '') {
            throw new \InvalidArgumentException('OLT inventory metadata is incomplete.');
        }
    }

    /** @return array<string, int|string|null> */
    public function safeMetadata(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sourceFile' => $this->sourceFile,
            'model' => strtoupper(trim((string) $this->olt->model)),
            'transport' => strtolower(trim((string) $this->olt->serviceCommunication)),
            'port' => (int) $this->olt->port,
            'cliProfile' => $this->olt->cliProfile,
            'firmwareVersion' => $this->olt->firmwareVersion,
            'credentialScope' => $this->tl1Server === null ? 'device' : 'shared_gateway',
        ];
    }
}

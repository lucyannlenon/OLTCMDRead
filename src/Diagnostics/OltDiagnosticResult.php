<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

final readonly class OltDiagnosticResult
{
    public function __construct(
        public bool $reachable,
        public bool $credentialsValid,
        public string $model,
        public string $transport,
        public string $credentialScope,
        public ?string $firmwareDetected,
        public ?bool $firmwareMatch,
        public int $durationMs,
        public ?string $errorCode,
        public string $message,
    ) {
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'reachable' => $this->reachable,
            'credentialsValid' => $this->credentialsValid,
            'model' => $this->model,
            'transport' => $this->transport,
            'credentialScope' => $this->credentialScope,
            'firmwareDetected' => $this->firmwareDetected,
            'firmwareMatch' => $this->firmwareMatch,
            'durationMs' => $this->durationMs,
            'errorCode' => $this->errorCode,
            'message' => $this->message,
        ];
    }
}

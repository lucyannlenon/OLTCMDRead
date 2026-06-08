<?php

namespace LLENON\OltInformation\OLT\CDATA\Support;

final readonly class PonAddress
{
    private function __construct(
        public int $frame,
        public int $slot,
        public int $port
    ) {
    }

    public static function fromString(string $pon): self
    {
        $pon = trim($pon);

        if (preg_match('/^([1-8])$/', $pon, $matches)) {
            return new self(0, 0, (int) $matches[1]);
        }

        if (preg_match('/^(\d+)\/(\d+)\/([1-8])$/', $pon, $matches)) {
            return new self((int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        throw new \InvalidArgumentException(
            "Invalid CDATA PON address '{$pon}'. Use a port from 1 to 8 or F/S/P."
        );
    }

    public function frameSlot(): string
    {
        return "{$this->frame}/{$this->slot}";
    }

    public function full(): string
    {
        return "{$this->frame}/{$this->slot}/{$this->port}";
    }
}

<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Capabilities;

final readonly class OltFeatureResult
{
    public function __construct(
        public string $feature,
        public string $state,
        public mixed $value = null,
        public ?string $reasonCode = null,
    ) {
        OltFeature::assertValid($feature);
        OltFeatureState::assertValid($state);

        if ($state === OltFeatureState::UNSUPPORTED && $value !== null) {
            throw new \InvalidArgumentException('Unsupported OLT features cannot contain a value.');
        }

        if ($reasonCode !== null && preg_match('/^[A-Z][A-Z0-9_]*$/', $reasonCode) !== 1) {
            throw new \InvalidArgumentException('OLT feature reason codes must use uppercase snake case.');
        }
    }

    public static function supported(string $feature, mixed $value): self
    {
        return new self($feature, OltFeatureState::SUPPORTED, $value);
    }

    public static function unavailable(string $feature, ?string $reasonCode = null): self
    {
        return new self($feature, OltFeatureState::UNAVAILABLE, null, $reasonCode);
    }

    public static function unsupported(string $feature, string $reasonCode): self
    {
        return new self($feature, OltFeatureState::UNSUPPORTED, null, $reasonCode);
    }

    /** @return array{feature:string,state:string,value:mixed,reasonCode:?string} */
    public function toArray(): array
    {
        return [
            'feature' => $this->feature,
            'state' => $this->state,
            'value' => $this->normalizeValue($this->value),
            'reasonCode' => $this->reasonCode,
        ];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->normalizeValue(...), $value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }
}

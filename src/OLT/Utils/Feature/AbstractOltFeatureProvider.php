<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Utils\Feature;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\Capabilities\OltFeatureResult;
use LLENON\OltInformation\OltInterfaces\OltFeatureProviderInterface;

abstract readonly class AbstractOltFeatureProvider implements OltFeatureProviderInterface
{
    /** @param list<string> $features */
    public function __construct(
        private array $features
    ) {
        foreach ($features as $feature) {
            OltFeature::assertValid($feature);
        }
    }

    public function supportedFeatures(): array
    {
        return array_values(array_unique($this->features));
    }

    public function supports(string $feature): bool
    {
        OltFeature::assertValid($feature);
        return in_array($feature, $this->features, true);
    }

    public function unsupported(string $feature): OltFeatureResult
    {
        OltFeature::assertValid($feature);
        return OltFeatureResult::unsupported($feature, 'FEATURE_NOT_EXPOSED');
    }
}

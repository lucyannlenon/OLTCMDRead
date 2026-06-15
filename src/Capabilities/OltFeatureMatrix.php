<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Capabilities;

use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

final readonly class OltFeatureMatrix
{
    /**
     * @return array{
     *   vendors:list<string>,
     *   rows:list<array{feature:string,states:array<string,string>}>
     * }
     */
    public function build(): array
    {
        $vendors = [
            OltModel::CDATA,
            OltModel::DATACOM,
            OltModel::ZTE,
            OltModel::FIBERHOME,
            OltModel::VSOL,
            OltModel::VSOLGPON,
        ];
        $profilesByModel = [];

        foreach ((new OltCliProfileRegistry())->all() as $profile) {
            $profilesByModel[$profile->model][] = $profile;
        }

        $rows = [];
        foreach (OltFeature::all() as $feature) {
            $states = [];

            foreach ($vendors as $vendor) {
                $profiles = $profilesByModel[$vendor] ?? [];
                if ($profiles === []) {
                    $states[$vendor] = 'not-tested';
                    continue;
                }

                $supported = false;
                foreach ($profiles as $profile) {
                    if (in_array($feature, $profile->features, true)) {
                        $supported = true;
                        break;
                    }
                }

                $states[$vendor] = $supported
                    ? OltFeatureState::SUPPORTED
                    : OltFeatureState::UNSUPPORTED;
            }

            $rows[] = ['feature' => $feature, 'states' => $states];
        }

        return ['vendors' => $vendors, 'rows' => $rows];
    }

    public function toMarkdown(): string
    {
        $matrix = $this->build();
        $header = '| Feature | ' . implode(' | ', $matrix['vendors']) . ' |';
        $separator = '|---|' . str_repeat('---|', count($matrix['vendors']));
        $lines = [$header, $separator];

        foreach ($matrix['rows'] as $row) {
            $lines[] = '| ' . $row['feature'] . ' | '
                . implode(' | ', array_map(
                    static fn (string $vendor): string => $row['states'][$vendor],
                    $matrix['vendors']
                ))
                . ' |';
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}

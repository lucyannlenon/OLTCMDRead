<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Capabilities;

use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Versioning\OltCliProfileRegistry;

final readonly class OltCapabilityRegistry
{
    /**
     * @return array{revision: string, models: list<array<string, mixed>>}
     */
    public function catalog(): array
    {
        $profiles = [];
        foreach ((new OltCliProfileRegistry())->all() as $profile) {
            $profiles[$profile->model][] = [
                'id' => $profile->id,
                'firmwares' => array_values($profile->firmwareVersions),
                'defaultTransport' => $profile->transport,
                'defaultPort' => $profile->defaultPort,
                'credentialScope' => $profile->credentialScope,
                'features' => array_values($profile->features),
                'requiresFirmware' => $profile->requiresFirmware,
            ];
        }

        $models = [];
        foreach ([
            OltModel::CDATA,
            OltModel::VSOL,
            OltModel::VSOLGPON,
            OltModel::ZTE,
            OltModel::DATACOM,
            OltModel::FIBERHOME,
            OltModel::FIBERHOMEOLDVERSION,
        ] as $model) {
            $modelProfiles = $profiles[$model] ?? [];
            $models[] = [
                'model' => $model,
                'transports' => [$this->transport($model)],
                'defaultPort' => $this->port($model),
                'credentialScope' => str_starts_with($model, 'FIBERHOME') ? 'shared_gateway' : 'device',
                'cliProfiles' => $modelProfiles,
                'firmwareMode' => $modelProfiles === []
                    ? 'free'
                    : (array_merge(
                        ...array_map(static fn (array $profile): array => $profile['firmwares'], $modelProfiles)
                    ) === [] ? 'unavailable' : 'catalog'),
                'firmwares' => array_values(array_unique(array_merge(
                    ...array_map(static fn (array $profile): array => $profile['firmwares'], $modelProfiles)
                ))),
                'features' => array_values(array_unique(array_merge(
                    ...array_map(static fn (array $profile): array => $profile['features'], $modelProfiles)
                ))),
                'diagnosticSupported' => true,
            ];
        }

        $normalized = json_encode($models, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return [
            'revision' => hash('sha256', $normalized),
            'models' => $models,
        ];
    }

    private function transport(string $model): string
    {
        return match ($model) {
            OltModel::VSOL => 'telnet',
            OltModel::FIBERHOME, OltModel::FIBERHOMEOLDVERSION => 'tl1',
            default => 'ssh',
        };
    }

    private function port(string $model): int
    {
        return match ($model) {
            OltModel::VSOL => 23,
            OltModel::FIBERHOME, OltModel::FIBERHOMEOLDVERSION => 3337,
            default => 22,
        };
    }
}

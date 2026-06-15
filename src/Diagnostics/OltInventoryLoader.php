<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltModel;

final readonly class OltInventoryLoader
{
    /** @return list<OltInventoryEntry> */
    public function loadDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("OLT inventory directory does not exist.");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($files, SORT_STRING);

        $entries = array_map($this->loadFile(...), $files);
        $ids = array_map(static fn (OltInventoryEntry $entry): int => $entry->id, $entries);

        if (count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('OLT inventory contains duplicate IDs.');
        }

        return $entries;
    }

    public function loadFile(string $file): OltInventoryEntry
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException('OLT inventory file does not exist.');
        }

        try {
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(
                'Invalid JSON in OLT inventory file ' . basename($file) . '.',
                0,
                $exception
            );
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('OLT inventory file must contain a JSON object.');
        }

        $required = [
            'id',
            'oltName',
            'userName',
            'password',
            'address',
            'model',
            'port',
            'typoConnection',
        ];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                throw new \InvalidArgumentException(
                    sprintf('OLT inventory file %s is missing field %s.', basename($file), $field)
                );
            }
        }

        $model = strtoupper(trim((string) $data['model']));
        $transport = strtolower(trim((string) $data['typoConnection']));
        $port = filter_var($data['port'], FILTER_VALIDATE_INT);

        if (!in_array($model, $this->supportedModels(), true)) {
            throw new \InvalidArgumentException(
                sprintf('OLT inventory file %s has unsupported model.', basename($file))
            );
        }

        if (!in_array($transport, ['ssh', 'telnet', 'tl1'], true)) {
            throw new \InvalidArgumentException(
                sprintf('OLT inventory file %s has unsupported transport.', basename($file))
            );
        }

        if ($port === false || $port < 1 || $port > 65535) {
            throw new \InvalidArgumentException(
                sprintf('OLT inventory file %s has invalid port.', basename($file))
            );
        }

        $tl1Server = isset($data['tl1Server']) ? trim((string) $data['tl1Server']) : null;
        if ($model === OltModel::FIBERHOME && ($transport !== 'tl1' || $tl1Server === null || $tl1Server === '')) {
            throw new \InvalidArgumentException(
                sprintf('Fiberhome inventory file %s requires a TL1 server.', basename($file))
            );
        }

        return new OltInventoryEntry(
            (int) $data['id'],
            trim((string) $data['oltName']),
            basename($file),
            new OLT(
                (string) $data['userName'],
                (string) $data['password'],
                $model,
                (string) $data['address'],
                $port,
                $transport,
                trim((string) $data['oltName']),
                $this->nullableString($data['cliProfile'] ?? null),
                $this->nullableString($data['firmwareVersion'] ?? null)
            ),
            $tl1Server === '' ? null : $tl1Server
        );
    }

    /** @return list<string> */
    private function supportedModels(): array
    {
        return [
            OltModel::CDATA,
            OltModel::DATACOM,
            OltModel::ZTE,
            OltModel::FIBERHOME,
            OltModel::FIBERHOMEOLDVERSION,
            OltModel::VSOL,
            OltModel::VSOLGPON,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}

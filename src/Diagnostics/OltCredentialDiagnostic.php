<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\InvalidUserException;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\DATACOM\DATACOMConnection;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnection;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnection;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

final readonly class OltCredentialDiagnostic
{
    /**
     * @param null|callable(OLT):array{connection: ConnectionInterface, command: string, transport: string, credentialScope?: string} $connectionFactory
     */
    public function __construct(
        private ?FiberhomeTl1Config $fiberhomeConfig = null,
        private ?\Closure $connectionFactory = null,
        private OltFirmwareParser $firmwareParser = new OltFirmwareParser(),
    ) {
    }

    public function diagnose(OLT $olt): OltDiagnosticResult
    {
        $startedAt = hrtime(true);
        $model = strtoupper(trim((string) $olt->model));
        $connection = null;
        $transport = strtolower(trim((string) $olt->serviceCommunication));
        $credentialScope = 'device';

        try {
            $probe = $this->connectionFactory !== null
                ? ($this->connectionFactory)($olt)
                : $this->createProbe($olt, $model);
            $connection = $probe['connection'];
            $transport = $probe['transport'];
            $credentialScope = $probe['credentialScope'] ?? 'device';
            $output = $connection->exec($probe['command']);
            $detected = $this->firmwareParser->extract($output);

            return new OltDiagnosticResult(
                true,
                true,
                $model,
                $transport,
                $credentialScope,
                $detected,
                $this->compareFirmware($olt->firmwareVersion, $detected),
                $this->durationMs($startedAt),
                null,
                'OLT credentials are valid.',
            );
        } catch (\Throwable $exception) {
            [$reachable, $code, $message] = $this->classify($exception);

            return new OltDiagnosticResult(
                $reachable,
                false,
                $model,
                $transport,
                $credentialScope,
                null,
                null,
                $this->durationMs($startedAt),
                $code,
                $message,
            );
        } finally {
            if (is_object($connection)) {
                if (method_exists($connection, 'disconnect')) {
                    $connection->disconnect();
                } elseif (method_exists($connection, 'close')) {
                    $connection->close();
                }
            }
        }
    }

    /**
     * @return array{connection: ConnectionInterface, command: string, transport: string, credentialScope?: string}
     */
    private function createProbe(OLT $olt, string $model): array
    {
        return match ($model) {
            OltModel::CDATA => [
                'connection' => new CDATAConnection($olt, enforceFirmwareVersion: false),
                'command' => 'show version',
                'transport' => 'ssh',
            ],
            OltModel::ZTE => [
                'connection' => new ZTEConnection($olt, enforceFirmwareVersion: false),
                'command' => 'show software',
                'transport' => 'ssh',
            ],
            OltModel::DATACOM => [
                'connection' => new DATACOMConnection($olt, enforceFirmwareVersion: false),
                'command' => 'show firmware',
                'transport' => 'ssh',
            ],
            OltModel::VSOL => [
                'connection' => new VSolEponConnection($olt, enforceFirmwareVersion: false),
                'command' => 'show version',
                'transport' => 'telnet',
            ],
            OltModel::VSOLGPON => [
                'connection' => new VSolGponConnection($olt, enforceFirmwareVersion: false),
                'command' => 'show version',
                'transport' => 'ssh',
            ],
            OltModel::FIBERHOME, OltModel::FIBERHOMEOLDVERSION => $this->fiberhomeProbe($olt),
            default => throw new \InvalidArgumentException("Unsupported OLT model '{$model}'."),
        };
    }

    /**
     * @return array{connection: ConnectionInterface, command: string, transport: string, credentialScope: string}
     */
    private function fiberhomeProbe(OLT $olt): array
    {
        if ($this->fiberhomeConfig === null) {
            throw new \LogicException('Fiberhome TL1 gateway is not configured.');
        }

        return [
            'connection' => new FiberhomeConnection(
                (string) $olt->ip,
                $this->fiberhomeConfig->gatewayAddress,
                $this->fiberhomeConfig->username,
                $this->fiberhomeConfig->password,
            ),
            'command' => 'LST-ONUSTATE::OLTID=' . $olt->ip . ':CTAG::;',
            'transport' => 'tl1',
            'credentialScope' => 'shared_gateway',
        ];
    }

    private function compareFirmware(?string $expected, ?string $detected): ?bool
    {
        if ($expected === null || trim($expected) === '' || $detected === null) {
            return null;
        }

        return strtoupper(trim($expected)) === strtoupper(trim($detected));
    }

    /**
     * @return array{bool, string, string}
     */
    private function classify(\Throwable $exception): array
    {
        $message = strtolower($exception->getMessage());

        if ($exception instanceof InvalidUserException
            || str_contains($message, 'login failed')
            || str_contains($message, 'authentication')
            || str_contains($message, 'invalid credentials')
            || str_contains($message, 'denied')) {
            return [true, 'AUTHENTICATION_FAILED', 'OLT authentication failed.'];
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return [false, 'CONNECTION_TIMEOUT', 'OLT connection timed out.'];
        }

        if (str_contains($message, 'connect') || str_contains($message, 'socket')) {
            return [false, 'UNREACHABLE', 'OLT endpoint is unreachable.'];
        }

        if (str_contains($message, 'prompt')) {
            return [true, 'PROMPT_NOT_DETECTED', 'OLT CLI prompt could not be detected.'];
        }

        if ($exception instanceof \InvalidArgumentException) {
            return [false, 'UNSUPPORTED_MODEL', 'OLT model or profile is unsupported.'];
        }

        return [true, 'COMMAND_FAILED', 'OLT read-only diagnostic command failed.'];
    }

    private function durationMs(int $startedAt): int
    {
        return max(0, (int) ((hrtime(true) - $startedAt) / 1_000_000));
    }
}

<?php

declare(strict_types=1);

use LLENON\OltInformation\Capabilities\OltCapabilityRegistry;
use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\Diagnostics\OltCredentialDiagnostic;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\InvalidUserException;

require __DIR__ . '/../vendor/autoload.php';

final class FakeDiagnosticConnection implements ConnectionInterface
{
    public bool $disconnected = false;

    public function __construct(
        private readonly string|\Throwable $result,
    ) {
    }

    public function exec(string $cmd): mixed
    {
        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }

    public function setTimeout(int $timeout): void
    {
    }

    public function disconnect(): void
    {
        $this->disconnected = true;
    }
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$connection = new FakeDiagnosticConnection("Software Version: V2.1.9R\n");
$diagnostic = new OltCredentialDiagnostic(
    connectionFactory: static fn (OLT $olt): array => [
        'connection' => $connection,
        'command' => 'show version',
        'transport' => 'ssh',
    ],
);
$result = $diagnostic->diagnose(new OLT(
    'admin',
    'secret',
    OltModel::VSOLGPON,
    '192.0.2.1',
    '22',
    'ssh',
    'test',
    OltCliProfile::VSOL_GPON_CLI_V2,
    'V2.1.8R',
));
expect($result->reachable, 'Successful diagnostic must be reachable.');
expect($result->credentialsValid, 'Firmware mismatch must not invalidate credentials.');
expect($result->firmwareDetected === 'V2.1.9R', 'Detected firmware must be returned.');
expect($result->firmwareMatch === false, 'Firmware mismatch must be explicit.');
expect($connection->disconnected, 'Diagnostic connection must be disconnected.');
expect(!array_key_exists('rawOutput', $result->toArray()), 'Raw output must not be exposed.');

$failedConnection = new FakeDiagnosticConnection(new InvalidUserException(
    'Invalid credentials user: secret-user, password: secret-pass'
));
$failedDiagnostic = new OltCredentialDiagnostic(
    connectionFactory: static fn (OLT $olt): array => [
        'connection' => $failedConnection,
        'command' => 'show version',
        'transport' => 'ssh',
    ],
);
$failed = $failedDiagnostic->diagnose(new OLT(
    'secret-user',
    'secret-pass',
    OltModel::ZTE,
    '192.0.2.2',
    '22',
    'ssh',
));
expect($failed->reachable, 'Authentication failure means the endpoint was reached.');
expect(!$failed->credentialsValid, 'Authentication failure must invalidate credentials.');
expect($failed->errorCode === 'AUTHENTICATION_FAILED', 'Authentication error code must be stable.');
expect(!str_contains(json_encode($failed->toArray(), JSON_THROW_ON_ERROR), 'secret'), 'Secrets must not leak.');

$catalog = (new OltCapabilityRegistry())->catalog();
$catalogAgain = (new OltCapabilityRegistry())->catalog();
expect($catalog['revision'] === $catalogAgain['revision'], 'Catalog revision must be deterministic.');
expect(count($catalog['models']) === 7, 'All supported model families must be listed.');

$vsol = array_values(array_filter(
    $catalog['models'],
    static fn (array $item): bool => $item['model'] === OltModel::VSOL,
))[0] ?? null;
expect(is_array($vsol), 'VSOL capability must exist.');
expect($vsol['firmwareMode'] === 'catalog', 'VSOL must use homologated firmware catalog.');
expect(
    $vsol['firmwares'] === ['V1.01.51_230922190137'],
    'VSOL homologated firmware must be exposed.',
);

$fiberhome = array_values(array_filter(
    $catalog['models'],
    static fn (array $item): bool => $item['model'] === OltModel::FIBERHOME,
))[0] ?? null;
expect($fiberhome['credentialScope'] === 'shared_gateway', 'Fiberhome must declare shared credentials.');

echo "OLT diagnostics tests passed.\n";

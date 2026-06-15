<?php

declare(strict_types=1);

use LLENON\OltInformation\Connections\TL1Connection;
use LLENON\OltInformation\Diagnostics\FiberhomeTl1Config;

require __DIR__ . '/../vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('IPSERVER_TL1=192.0.2.20');
putenv('USERNAME_TL1=test-user');
putenv('PASSWORD_TL1=test-password');

$config = FiberhomeTl1Config::fromEnvironment();
expect($config->gatewayAddress === '192.0.2.20', 'TL1 gateway was not loaded.');
expect($config->username === 'test-user', 'TL1 username was not loaded.');

putenv('PASSWORD_TL1');
try {
    FiberhomeTl1Config::fromEnvironment();
    throw new RuntimeException('Incomplete TL1 environment must be rejected.');
} catch (InvalidArgumentException $exception) {
    expect(!str_contains($exception->getMessage(), 'test-password'), 'TL1 config error leaked a password.');
}

putenv('IPSERVER_TL1');
putenv('USERNAME_TL1');

$connection = (new ReflectionClass(TL1Connection::class))->newInstanceWithoutConstructor();
$loginValidator = new ReflectionMethod(TL1Connection::class, 'isSuccessfulLoginResponse');

expect(
    $loginValidator->invoke($connection, "M  CTAG COMPLD\nEN=0   ENDESC=No error"),
    'A successful TL1 response containing "No error" must be accepted.'
);
expect(
    !$loginValidator->invoke($connection, "M  CTAG DENY\nEN=5   ENDESC=Authentication error"),
    'A denied TL1 response must be rejected.'
);

echo "Fiberhome TL1 config tests passed.\n";

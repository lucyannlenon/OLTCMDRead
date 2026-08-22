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

$reflection = new ReflectionClass(TL1Connection::class);
$socketProperty = $reflection->getProperty('fp');
$cacheProperty = $reflection->getProperty('cache');
$cacheTtlProperty = $reflection->getProperty('cacheTtlSeconds');
$debugProperty = $reflection->getProperty('debug');

$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
expect(is_array($sockets), 'Could not create TL1 socket pair for transport test.');
$completeConnection = $reflection->newInstanceWithoutConstructor();
$socketProperty->setValue($completeConnection, $sockets[0]);
$cacheProperty->setValue($completeConnection, []);
$cacheTtlProperty->setValue($completeConnection, 3);
$debugProperty->setValue($completeConnection, false);
fwrite($sockets[1], "M  CTAG COMPLD\nEN=0\n;");
expect(
    str_contains($completeConnection->exec('TEST'), 'COMPLD'),
    'A terminated TL1 response must be returned.'
);
fclose($sockets[1]);
$socketProperty->setValue($completeConnection, null);

$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
expect(is_array($sockets), 'Could not create incomplete TL1 socket pair.');
$incompleteConnection = $reflection->newInstanceWithoutConstructor();
$socketProperty->setValue($incompleteConnection, $sockets[0]);
$cacheProperty->setValue($incompleteConnection, []);
$cacheTtlProperty->setValue($incompleteConnection, 3);
$debugProperty->setValue($incompleteConnection, false);
fwrite($sockets[1], "M  CTAG COMPLD\nEN=0");
stream_socket_shutdown($sockets[1], STREAM_SHUT_WR);
$incompleteFailed = false;
try {
    $incompleteConnection->exec('TEST');
} catch (RuntimeException $exception) {
    $incompleteFailed = true;
    expect(str_contains($exception->getMessage(), 'incomplete'), 'Unexpected TL1 transport error.');
}
expect($incompleteFailed, 'An incomplete TL1 response must fail.');
$socketProperty->setValue($incompleteConnection, null);

echo "Fiberhome TL1 config tests passed.\n";

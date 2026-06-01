<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;

const PROMPT_REGEX = '~[#>]\s*$~';
const RESPONSE_REGEX = '~(?:--More--|[#>]\s*$)~';

/**
 * This script compares the current ZTEConnection protocol with a candidate
 * protocol. It performs read-only CLI commands and does not change the library.
 */

$config = [
    'host' => requiredEnv('ZTE_HOST'),
    'port' => (int) optionalEnv('ZTE_PORT', '22'),
    'username' => requiredEnv('ZTE_USERNAME'),
    'password' => requiredEnv('ZTE_PASSWORD'),
    'olt_name' => requiredEnv('ZTE_OLT_NAME'),
    'pon' => requiredEnv('ZTE_PON'),
    'onu_id' => requiredEnv('ZTE_ONU_ID'),
    'timeout' => (int) optionalEnv('ZTE_TIMEOUT', '10'),
];

$mode = $argv[1] ?? 'both';
if (!in_array($mode, ['current', 'candidate', 'both'], true)) {
    fwrite(STDERR, "Usage: php examples/zte_onu_info_diagnostic.php [current|candidate|both]\n");
    exit(2);
}

$commands = [
    'disable_paging' => 'terminal length 0',
    'signal' => "show pon power onu-rx gpon_onu-{$config['pon']}:{$config['onu_id']}",
    'distance' => "show gpon onu distance gpon_onu-{$config['pon']}:{$config['onu_id']}",
    'ethernet' => "show gpon remote-onu interface eth gpon_onu-{$config['pon']}:{$config['onu_id']}    ",
    'vlan' => "show gpon remote-onu service gpon_onu-{$config['pon']}:{$config['onu_id']}",
    'detail_info' => "show gpon onu detail-info gpon_onu-{$config['pon']}:{$config['onu_id']}",
];

printf(
    "ZTE ONU info diagnostic: host=%s port=%d olt_name=%s pon=%s onu_id=%s timeout=%ds\n",
    $config['host'],
    $config['port'],
    json_encode($config['olt_name'], JSON_UNESCAPED_SLASHES),
    $config['pon'],
    $config['onu_id'],
    $config['timeout']
);
echo "Only timing, byte counts, and response tails are printed. Credentials are never printed.\n";

try {
    if ($mode === 'current' || $mode === 'both') {
        runCurrentProtocol($config, $commands);
    }

    if ($mode === 'candidate' || $mode === 'both') {
        runCandidateProtocol($config, $commands);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("\nERROR: %s: %s\n", $exception::class, $exception->getMessage()));
    exit(1);
}

/**
 * Reproduces ZTEConnection::runCommand(): read the configured prompt before
 * every command, write the command, then read until the configured prompt or
 * the pagination marker.
 *
 * @param array<string, int|string> $config
 * @param array<string, string> $commands
 */
function runCurrentProtocol(array $config, array $commands): void
{
    beginMode('current');
    $ssh = connect($config);
    $prompt = "{$config['olt_name']}#";
    $responseRegex = '#--More--|' . preg_quote((string) $config['olt_name']) . '\##';

    printf("configured_prompt=%s response_regex=%s\n", json_encode($prompt), json_encode($responseRegex));

    foreach ($commands as $label => $command) {
        echo "\n[$label]\n";
        timedRead($ssh, 'pre_read_configured_prompt', $prompt);
        timedWrite($ssh, 'write_command', "$command\n");
        readResponse($ssh, $responseRegex);
    }

    finishMode($ssh);
}

/**
 * Tests the intended protocol: synchronize once after login with a generic CLI
 * prompt, then write each command directly and consume its complete response.
 *
 * @param array<string, int|string> $config
 * @param array<string, string> $commands
 */
function runCandidateProtocol(array $config, array $commands): void
{
    beginMode('candidate');
    $ssh = connect($config);

    timedRead($ssh, 'initial_prompt_sync', PROMPT_REGEX, SSH2::READ_REGEX);

    foreach ($commands as $label => $command) {
        echo "\n[$label]\n";
        timedWrite($ssh, 'write_command', "$command\n");
        readResponse($ssh, RESPONSE_REGEX);
    }

    finishMode($ssh);
}

/**
 * @param array<string, int|string> $config
 */
function connect(array $config): SSH2
{
    $startedAt = microtime(true);
    $ssh = new SSH2((string) $config['host'], (int) $config['port'], (int) $config['timeout']);
    $success = $ssh->login((string) $config['username'], (string) $config['password']);
    report('connect_and_login', $startedAt, $success ? 'success' : 'failed');

    if (!$success) {
        throw new RuntimeException('SSH login failed.');
    }

    $ssh->setTimeout((int) $config['timeout']);
    return $ssh;
}

function readResponse(SSH2 $ssh, string $regex): void
{
    $response = timedRead($ssh, 'read_response', $regex, SSH2::READ_REGEX);
    $page = 1;

    while (str_contains($response, '--More--')) {
        timedWrite($ssh, "write_page_{$page}", ' ');
        $response = timedRead($ssh, "read_page_{$page}", $regex, SSH2::READ_REGEX);
        $page++;
    }
}

function timedRead(SSH2 $ssh, string $label, string $expect, int $mode = SSH2::READ_SIMPLE): string
{
    $startedAt = microtime(true);
    $response = $ssh->read($expect, $mode);
    $response = is_string($response) ? $response : '';

    report(
        $label,
        $startedAt,
        sprintf('bytes=%d tail=%s', strlen($response), json_encode(lastNonEmptyLine($response)))
    );

    return $response;
}

function timedWrite(SSH2 $ssh, string $label, string $content): void
{
    $startedAt = microtime(true);
    $ssh->write($content);
    report($label, $startedAt, sprintf('bytes=%d', strlen($content)));
}

function beginMode(string $mode): void
{
    $GLOBALS['mode_started_at'] = microtime(true);
    echo "\n============================================================\n";
    echo "MODE: $mode\n";
    echo "============================================================\n";
}

function finishMode(SSH2 $ssh): void
{
    $ssh->disconnect();
    printf("\nTOTAL: %.3fs\n", microtime(true) - $GLOBALS['mode_started_at']);
}

function report(string $label, float $startedAt, string $details): void
{
    printf("  %-28s %8.3fs  %s\n", $label, microtime(true) - $startedAt, $details);
}

function lastNonEmptyLine(string $value): string
{
    $value = str_replace("\x08", '', $value);
    $value = preg_replace('/\e\[[\d;]*[A-Za-z]/', '', $value) ?? $value;
    $lines = preg_split('/\r?\n/', $value) ?: [];

    for ($index = count($lines) - 1; $index >= 0; $index--) {
        $line = trim($lines[$index]);
        if ($line !== '') {
            return substr($line, 0, 160);
        }
    }

    return '';
}

function requiredEnv(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        fwrite(STDERR, "Missing required environment variable: $name\n");
        exit(2);
    }

    return $value;
}

function optionalEnv(string $name, string $default): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

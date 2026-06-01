<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

function createZteOltModel(): OLT
{
    $path = __DIR__ . '/config/zte.json';
    $contents = @file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read ZTE config file: {$path}");
    }

    $config = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    foreach (['userName', 'password', 'model', 'address', 'port', 'oltName'] as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            throw new RuntimeException("Missing ZTE config key: {$key}");
        }
    }

    return new OLT(
        $config['userName'],
        $config['password'],
        $config['model'],
        $config['address'],
        $config['port'],
        'ssh',
        $config['oltName']
    );
}

function createZteConnection(): ZTEConnection
{
    return new ZTEConnection(createZteOltModel());
}

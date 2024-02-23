<?php

use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;

include __DIR__ . "/../vendor/autoload.php";


$config = json_decode(file_get_contents(__DIR__ . "/config/fiberhome.json"), TRUE);


$olt = new OLT($config['userName'], $config['password'], $config['model'], $config['address'], $config['port'], $config['typoConnection'], $config['oltName']);


$client = new Client($config['login'], $config['macAddress'], $config['gponName']);

$fiberhome = new \LLENON\OltInformation\Adapters\OltFiberHomeCmdOLDVERSION($olt, $client);
dd($fiberhome->getDadosDoCliente());


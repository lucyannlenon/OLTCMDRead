<?php

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;

    include __DIR__ . "/../vendor/autoload.php";

    $config = include __DIR__ . "/config/datacom.php";


    $olt = new OLT($config['userName'], $config['password'], $config['model'], $config['address'], $config['port'], $config['typoConnection']);
    // comando inicial
    // show interface gpon onu | match-all MONU006C2781

    $client = new Client($config['login'], $config['macAddress'], $config['gponName']);

    $dataCom = new \LLENON\OltInformation\Adapters\DATACOMOLTCmd($olt, $client);
    dd($dataCom->getDadosDoCliente());



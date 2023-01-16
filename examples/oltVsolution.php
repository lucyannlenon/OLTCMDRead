<?php

    use LLENON\OltInformation\Adapters\VSolOLTCmd;
    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;

    include __DIR__ . "/../vendor/autoload.php";


    $config = include __DIR__ . "/config/vsol.php";


    $olt = new OLT($config['userName'], $config['password'], $config['model'], $config['address'], $config['port'], $config['typoConnection'], $config['oltNome']);
    $client = new Client($config['login'], $config['macAddress'], $config['gponName']);


    $oltVsol = new VSolOLTCmd($olt, $client);
    dd($oltVsol->getDadosDoCliente());



    // echo $conn->exec("show onu opm-diag pon 2,16");

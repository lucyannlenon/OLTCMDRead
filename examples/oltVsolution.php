<?php

    use LLENON\OltInformation\Adapters\VSolOLTCmd;
    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;

    include __DIR__ . "/../vendor/autoload.php";




$config = json_decode(file_get_contents(__DIR__ . "/config/vsol.json"), TRUE);


    $olt = new OLT($config['userName'], $config['password'], $config['model'], $config['address'], $config['port'], $config['typoConnection'], $config['oltName']);
    $client = new Client($config['login'], $config['macAddress'], $config['gponName']);

    /**
     * -> enable
     * -> configure terminal
     * -> onu search MONU00B38BA9
     *
     * <- pon 2 onu 2 sn MONU00B38BA9 Online
     * <- --------------search end----------------
     *
     * -> show onu state 2 6
     * <- 1/1/2:6     enable         disable       OffLine        1(GPON)
     *
     * -> interface gpon slot/pon
     *
     * -> show onu 89  distance
     * <- onu 89 Distance: 785m
     *
     * -> show onu 89  optical_info
     * <- ONU ID: 89
     * <- ONU PON Interface:            pon_0/1
     * <- GEM_blocklen:                 48
     * <- SF threshold:                 5
     * <- SD threshold:                 9
     * <- Alarm:                        enable
     * <- Alarm disable interval:       0
     * <- Total T-CONT number:          31
     * <- Piggyback DBA rpt mode:       not support
     * <- Whole ONU DBA rpt mode:       not support
     * <- Rx optical level:             -19.580(dBm)
     * <- Lower rx optical threshold:   ont internal policy
     * <- Upper rx optical threshold:   ont internal policy
     * <- Tx optical level:             3.190(dBm)
     * <- Lower tx optical threshold:   ont internal policy
     * <- Upper tx optical threshold:   ont internal policy
     * <- ONU response time:            0
     * <- Power feed voltage:           3.30(V)
     * <- Laser bias current:           12.200(mA)
     * <- Temperature:                  44.309(C)
     *
     * -> show onu 89 time-stamp
     * <- onu id     last regist time        last deregist time      alive time
     * <- 1/89       2000:07:09 12:32:55     2000:07:09 12:29:41      25 04:59:44
     */

    $oltVsol = new \LLENON\OltInformation\Adapters\VSolOLTCmd($olt, $client);

    dd($oltVsol->getDadosDoCliente());



    // echo $conn->exec("show onu opm-diag pon 2,16");

<?php

use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;

include __DIR__ . "/../vendor/autoload.php";


$config = json_decode(file_get_contents(__DIR__ . "/config/fiberhome.json"), TRUE);

//ZTEGd3496b67 16/2
$tl1 = new \LLENON\OltInformation\Connections\TL1Connection($config['address'], $config['tl1Server'], $config['userName'], $config['password']);

$discoveryOnu = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\State($tl1);

$id = "ZTEGd3496b6f";


$onu = new \LLENON\OltInformation\DTO\ONU($id);
$onu->setOnuType("AN5506-04-B2");
$onu->setName("lucyann teste");
$onu->setPon("NA-NA-16-8");

$data = $discoveryOnu->exec([
    'onu' => $onu,
    'vlan'=>'100',
    'username'=>'lucyann.lenon@izi.com.br',
    'password'=> '123abc'
]);
var_dump($data);
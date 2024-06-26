<?php
include __DIR__ . "/../vendor/autoload.php";

$config = json_decode(file_get_contents(__DIR__ . "/config/zte.json"), TRUE);

$oltModel = new \LLENON\OltInformation\DTO\OLT(
    $config["userName"],
    $config["password"],
    $config['model'],
    $config['address'],
    $config['port'],
    'ssh',
    $config['oltName']
);
$conn = new \LLENON\OltInformation\OLT\ZTE\ZTEConnection($oltModel);

##>ListUnAuthorization
//$listUnAuthorization = new \LLENON\OltInformation\OLT\ZTE\Command\ListUnAuthorization($conn);
//$result = $listUnAuthorization->execute();
//dd($result);
##<ListUnAuthorization

##>NextIdCommand
//$NextIdCommand = new \LLENON\OltInformation\OLT\ZTE\Command\NextIdCommand($conn);
//$result =  $NextIdCommand->execute("1/3/1");
//dd($result);
##<NextIdCommand

##>AddOnuBridgeCommand
$onu = new \LLENON\OltInformation\OLT\Dto\Onu();
$onu->setPon("1/3/1")
    ->setGponId("MONU0085A939")
    ->setVlan('100');

$AddOnuBridgeCommand = new \LLENON\OltInformation\OLT\ZTE\Command\AddOnuBridgeCommand($conn);
$result = $AddOnuBridgeCommand->execute($onu);
dd($result);
##<AddOnuBridgeCommand

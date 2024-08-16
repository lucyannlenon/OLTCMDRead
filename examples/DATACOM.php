<?php

use LLENON\OltInformation\OLT\DATACOM\Command\DetailInfoOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\DistanceOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\GetVlanCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\ListUnAuthorization;
use LLENON\OltInformation\OLT\DATACOM\Command\OnuEthernetStatusCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\RemoveOnuCommand;
use LLENON\OltInformation\OLT\DATACOM\Command\SignalOnuCommand;

include __DIR__ . "/../vendor/autoload.php";

$config = json_decode(file_get_contents(__DIR__ . "/config/datacom.json"), TRUE);

$oltModel = new \LLENON\OltInformation\DTO\OLT(
    $config["userName"],
    $config["password"],
    $config['model'],
    $config['address'],
    $config['port'],
    'ssh',
    $config['oltName']
);
$conn = new \LLENON\OltInformation\OLT\DATACOM\DATACOMConnection($oltModel);

##>ListUnAuthorization
//$listUnAuthorization = new ListUnAuthorization($conn);
//$result = $listUnAuthorization->execute();
//dd($result);
##<ListUnAuthorization

##>NextIdCommand
//$NextIdCommand = new \LLENON\OltInformation\OLT\ZTE\Command\NextIdCommand($conn);
//$result =  $NextIdCommand->execute("1/3/7");
//dd($result);
##<NextIdCommand

##>AddOnuBridgeCommand
//$onu = new \LLENON\OltInformation\OLT\Dto\Onu();
//$onu->setPon("1/3/1")
//    ->setGponId("MONU0085A939")
//    ->setVlan('100')
//    ->setUsername("lenon");
//
//$AddOnuBridgeCommand = new \LLENON\OltInformation\OLT\ZTE\Command\AddOnuBridgeCommand($conn);
//$AddOnuBridgeCommand->execute($onu);
##<AddOnuBridgeCommand

##>AddOnuWIFICommand
//$onu = new \LLENON\OltInformation\OLT\Dto\Onu();
//$onu->setPon("1/3/1")
//    ->setGponId("ZTEGD34984F3")
//    ->setVlan('100')
//    ->setUsername("lucyann.lenon@izi.com.br")
//    ->setPassword('123abc');
//
//$AddOnuWIFICommand = new \LLENON\OltInformation\OLT\ZTE\Command\AddOnuWIFICommand($conn);
//$AddOnuWIFICommand->execute($onu);
##<AddOnuWIFICommand

##>ListOnuCommand
//$ListOnuCommand = new ListOnuCommand($conn);
//$result =  $ListOnuCommand->execute("1/1/5");
//dd($result);
##<ListOnuCommand

###>RemoveOnuCommand
//$RemoveOnuCommand = new RemoveOnuCommand($conn);
//$RemoveOnuCommand->execute("1/1/5","12");
##<RemoveOnuCommand

###>SignalOnuCommand
//$SignalOnuCommand = new SignalOnuCommand($conn);
//$signal = $SignalOnuCommand->execute("1/1/5","2");
//dd($signal);
##<SignalOnuCommand

###>DistanceOnuCommand
//$DistanceOnuCommand = new DistanceOnuCommand($conn);
//$distance = $DistanceOnuCommand->execute("1/1/5","2");
//dd($distance);
##<DistanceOnuCommand

###>OnuEthernetStatusCommand
$OnuEthernetStatusCommand = new OnuEthernetStatusCommand($conn);
$data =$OnuEthernetStatusCommand->execute("1/1/1","24");
dd($data);
##<OnuEthernetStatusCommand

###>WanStatusOnuCommand
//$WanStatusOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\WanStatusOnuCommand($conn);
//$data =$WanStatusOnuCommand->execute("1/3/1","2");
//dd($data);
##<WanStatusOnuCommand

####>VlanOnuCommand
//$VlanOnuCommand = new GetVlanCommand($conn);
//$data =$VlanOnuCommand->execute("1/1/5","2");
//dd($data);
##<VlanOnuCommand

#####>DetailInfoOnuCommand
//$DetailInfoOnuCommand = new DetailInfoOnuCommand($conn);
//$data =$DetailInfoOnuCommand->execute("1/1/1","24");
//dd($data);
##<DetailInfoOnuCommand
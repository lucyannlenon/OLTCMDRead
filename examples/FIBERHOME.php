<?php

use LLENON\OltInformation\DTO\Client;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\Command\TL1\RemoveOnuCommand;
use LLENON\OltInformation\OLT\Fiberhome\FiberhomeConnection;

include __DIR__ . "/../vendor/autoload.php";


$config = json_decode(file_get_contents(__DIR__ . "/config/fiberhome.json"), TRUE);

//ZTEGd3496b67 16/2
$conn = new FiberhomeConnection($config['address'], $config['tl1Server'], $config['userName'], $config['password']);
$onu = new \LLENON\OltInformation\OLT\Dto\Onu();
$onu->setPon("NA-NA-11-2")
    ->setGponId("ZTEG9b040091")
    ->setModel('AN5506-04-B2')
    ->setVlan('100')
    ->setUsername("lenon");


##>ListUnAuthorization
//$discoveryOnu = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\ListUnAuthorizationOnu($conn);
//dd($discoveryOnu->execute());
##<ListUnAuthorization

##>NextIdCommand
//$NextIdCommand = new \LLENON\OltInformation\OLT\ZTE\Command\NextIdCommand($conn);
//$result =  $NextIdCommand->execute("1/3/7");
//dd($result);
##<NextIdCommand

##>AddOnuBridgeCommand
//$AddOnuBridgeCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\AddOnuBridgeCommand($conn);
//dd($AddOnuBridgeCommand->execute($onu));
##<AddOnuBridgeCommand


##>AddOnuBridgeCommand
//$AddOnuBridgeCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\AddVlanCommand($conn);
//dd($AddOnuBridgeCommand->execute($onu));
##<AddOnuBridgeCommand

##>AddOnuWIFICommand
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
//$RemoveOnuCommand->execute($onu->getPon(), $onu->getGponId());
##<RemoveOnuCommand

###>SignalOnuCommand
//$SignalOnuCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\SignalOnuCommand($conn);
//$signal = $SignalOnuCommand->execute($onu->getPon(), $onu->getGponId());
//dd($signal);
##<SignalOnuCommand

###>TemperatureOnuCommand
//$TemperatureOnuCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\TemperatureOnuCommand($conn);
//$signal = $TemperatureOnuCommand->execute($onu->getPon(), $onu->getGponId());
//dd($signal);
##<TemperatureOnuCommand

###>DistanceOnuCommand
//$DistanceOnuCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\DistanceOnuCommand($conn);
//$distance = $DistanceOnuCommand->execute($onu->getPon(), $onu->getGponId());
//dd($distance);
##<DistanceOnuCommand

###>OnuEthernetStatusCommand
//$OnuEthernetStatusCommand = new OnuEthernetStatusCommand($conn);
//$data = $OnuEthernetStatusCommand->execute("1/1/1", "24");
//dd($data);
##<OnuEthernetStatusCommand

###>EtherStateOnuCommand
//$EtherStateOnuCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\EtherStateOnuCommand($conn);
//$signal = $EtherStateOnuCommand->execute($onu->getPon(), $onu->getGponId());
//dd($signal);
##<EtherStateOnuCommand

####>VlanOnuCommand
$VlanOnuCommand = new \LLENON\OltInformation\OLT\Fiberhome\Command\TL1\VlanOnuCommand($conn);
$data =$VlanOnuCommand->execute($onu->getPon(), $onu->getGponId());
dd($data);
##<VlanOnuCommand

####>ListOnuCommand
//$ListOnuCommand = new ListOnuCommand($conn);
//$data =$ListOnuCommand->execute($onu->getPon());
//dd($data);
##<ListOnuCommand

#####>DetailInfoOnuCommand
//$DetailInfoOnuCommand = new DetailInfoOnuCommand($conn);
//$data =$DetailInfoOnuCommand->execute("1/1/1","24");
//dd($data);
##<DetailInfoOnuCommand
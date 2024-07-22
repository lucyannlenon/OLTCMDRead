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
//$ListOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\ListOnuCommand($conn);
//$result =  $ListOnuCommand->execute("1/3/1");
//dd($result);
##<ListOnuCommand

###>RemoveOnuCommand
//$RemoveOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\RemoveOnuCommand($conn);
//$RemoveOnuCommand->execute("1/3/1","2");
##<RemoveOnuCommand

###>SignalOnuCommand
//$SignalOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\SignalOnuCommand($conn);
//$SignalOnuCommand->execute("1/3/1","2");
##<SignalOnuCommand

###>DistanceOnuCommand
//$DistanceOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\DistanceOnuCommand($conn);
//$distance = $DistanceOnuCommand->execute("1/3/1","2");
//dd($distance);
##<DistanceOnuCommand

###>OnuEthernetStatusCommand
//$OnuEthernetStatusCommand = new \LLENON\OltInformation\OLT\ZTE\Command\OnuEthernetStatusCommand($conn);
//$data =$OnuEthernetStatusCommand->execute("1/3/1","2");
//dd($data);
##<OnuEthernetStatusCommand

###>WanStatusOnuCommand
//$WanStatusOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\WanStatusOnuCommand($conn);
//$data =$WanStatusOnuCommand->execute("1/3/1","2");
//dd($data);
##<WanStatusOnuCommand
####>VlanOnuCommand
$VlanOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\VlanOnuCommand($conn);
$data =$VlanOnuCommand->execute("1/3/1","1");
dd($data);
##<WanStatusOnuCommand
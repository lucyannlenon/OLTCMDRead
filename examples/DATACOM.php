<?php



include __DIR__ . "/../vendor/autoload.php";

$config = include __DIR__ . "/config/datacom.php";


$conn = new \LLENON\OltInformation\Connections\SSHConnection($config['address'],$config['userName'], $config['password'], $config['port'] ) ;

$list = new \LLENON\OltInformation\DATACOM\Command\ListOnu($conn) ;

$lines = $list->execute();
var_dump($lines);
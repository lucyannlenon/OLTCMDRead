<?php
// Saída: uma linha por ONU no formato "pon serial"
require_once __DIR__ . '/zte_connection.php';

$conn = createZteConnection();
$listCmd = new \LLENON\OltInformation\OLT\ZTE\Command\ListUnAuthorization($conn);
$onus = $listCmd->execute();

foreach ($onus as $onu) {
    echo $onu->getPon() . " " . $onu->getGponId() . "\n";
}

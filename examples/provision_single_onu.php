<?php
// Uso: php provision_single_onu.php <pon> <serial> <username> <vlan>
require_once __DIR__ . '/zte_connection.php';

[$script, $pon, $serial, $username, $vlan] = $argv;

$conn = createZteConnection();
$addCommand = new \LLENON\OltInformation\OLT\ZTE\Command\AddOnuBridgeCommand($conn);

$onu = new \LLENON\OltInformation\OLT\Dto\Onu();
$onu->setPon($pon)
    ->setGponId($serial)
    ->setUsername($username)
    ->setVlan($vlan);

try {
    $id = $addCommand->execute($onu);
    echo "[OK] PON:{$pon} | Serial:{$serial} | ID:{$id}\n";
    exit(0);
} catch (\Exception $e) {
    echo "[ERRO] PON:{$pon} | Serial:{$serial} | {$e->getMessage()}\n";
    exit(1);
}

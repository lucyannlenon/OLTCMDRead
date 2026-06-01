<?php
require_once __DIR__ . '/zte_connection.php';

$conn = createZteConnection();

$pons = ['1/3/1','1/3/2','1/3/3','1/3/4','1/3/6','1/3/7','1/3/8'];

$listCmd   = new \LLENON\OltInformation\OLT\ZTE\Command\ListOnuCommand($conn);
$removeCmd = new \LLENON\OltInformation\OLT\ZTE\Command\RemoveOnuCommand($conn);

$removed = 0;
$errors  = 0;

foreach ($pons as $pon) {
    $onus = $listCmd->execute($pon);

    foreach ($onus as $onu) {
        $runCfg = $conn->exec("show running-config interface gpon_onu-{$pon}:{$onu->getId()}");

        if ($runCfg && str_contains($runCfg, 'name teste')) {
            echo "Removendo PON:{$pon} ID:{$onu->getId()} Serial:{$onu->getGponId()}\n";
            try {
                $removeCmd->execute($pon, $onu->getId());
                $removed++;
            } catch (\Exception $e) {
                echo "  [ERRO] {$e->getMessage()}\n";
                $errors++;
            }
        }
    }
}

echo "\nConcluído. Removidas: {$removed} | Erros: {$errors}\n";

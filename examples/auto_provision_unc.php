<?php
require_once __DIR__ . '/zte_connection.php';

$oltModel = createZteOltModel();
$conn = new \LLENON\OltInformation\OLT\ZTE\ZTEConnection($oltModel);

$listUnAuthorization = new \LLENON\OltInformation\OLT\ZTE\Command\ListUnAuthorization($conn);
$onus = $listUnAuthorization->execute();

if (empty($onus)) {
    echo "Nenhuma ONU não autorizada encontrada.\n";
    exit;
}

echo "Encontradas " . count($onus) . " ONU(s) não autorizadas:\n";
foreach ($onus as $onu) {
    echo "  PON: {$onu->getPon()} | Serial: {$onu->getGponId()}\n";
}

echo "\nIniciando provisionamento paralelo...\n\n";

$pids = [];

foreach ($onus as $onu) {
    $onu->setUsername('teste')->setVlan('2009');

    $pid = pcntl_fork();

    if ($pid === -1) {
        echo "[ERRO] Não foi possível criar processo filho para {$onu->getGponId()}\n";
        continue;
    }

    if ($pid === 0) {
        // Processo filho: cria sua própria conexão SSH
        $childConn = new \LLENON\OltInformation\OLT\ZTE\ZTEConnection($oltModel);
        $addCommand = new \LLENON\OltInformation\OLT\ZTE\Command\AddOnuBridgeCommand($childConn);

        try {
            $id = $addCommand->execute($onu);
            echo "[OK] PON: {$onu->getPon()} | Serial: {$onu->getGponId()} | ID atribuído: {$id}\n";
            exit(0);
        } catch (\Exception $e) {
            echo "[ERRO] PON: {$onu->getPon()} | Serial: {$onu->getGponId()} | {$e->getMessage()}\n";
            exit(1);
        }
    }

    // Processo pai: registra o PID filho
    $pids[$pid] = $onu->getGponId();
}

// Aguarda todos os filhos terminarem
foreach ($pids as $pid => $serial) {
    pcntl_waitpid($pid, $status);
}

echo "\nProvisionamento concluído.\n";

<?php

declare(strict_types=1);

/**
 * Validacao ao vivo da medicao de sinal optico em OLT CDATA (EPON).
 *
 * Confirma o unico ponto que nao da para testar offline: se a CLI da OLT
 * aceita "show ont optical-info <F/S> <P> <ONU-ID>" e devolve o formato que o
 * OpticalInfoStringParser espera. Mostra a saida CRUA e a PARSEADA lado a lado.
 *
 * NAO contem credenciais: tudo vem do JSON de inventario da OLT.
 *
 * Uso:
 *   php examples/validate_cdata_signal.php <olt.json> --mac=AA:BB:CC:DD:EE:FF
 *   php examples/validate_cdata_signal.php <olt.json> --pon=0/0/2 --onu=3
 *
 * Opcoes:
 *   --mac=<MAC>        Localiza a ONU pelo MAC (usa o fluxo real findOnu()).
 *   --pon=<F/S/P>      PON explicita (ex.: 0/0/2). Requer --onu.
 *   --onu=<id>         ONU-ID explicito (1-64). Requer --pon.
 *   --no-firmware-check  Desativa a validacao de firmware/cliProfile.
 *
 * Exemplo:
 *   php examples/validate_cdata_signal.php \
 *       examples/config/olts/001-olt-sao-geraldo.json --mac=C4:70:0B:89:51:60
 */

require __DIR__ . '/../vendor/autoload.php';

use LLENON\OltInformation\Capabilities\OltFeatureState;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\CDATA\CDataFeatureAdapter;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\CDATA\Support\PonAddress;
use LLENON\OltInformation\OLT\Dto\OnuIdentity;

function fail(string $message): never
{
    fwrite(STDERR, "ERRO: {$message}\n");
    exit(1);
}

// ---- argumentos -----------------------------------------------------------
$configPath = null;
$opts = ['mac' => null, 'pon' => null, 'onu' => null, 'no-firmware-check' => false];

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        if (!array_key_exists($key, $opts)) {
            fail("Opcao desconhecida: --{$key}");
        }
        $opts[$key] = $value;
    } elseif ($configPath === null) {
        $configPath = $arg;
    }
}

if ($configPath === null) {
    fail('Informe o caminho do JSON da OLT. Veja o cabecalho do arquivo para uso.');
}
if (!is_file($configPath)) {
    fail("Arquivo de config nao encontrado: {$configPath}");
}

$config = json_decode((string) file_get_contents($configPath), true);
if (!is_array($config)) {
    fail("JSON invalido em {$configPath}");
}

foreach (['userName', 'password', 'address', 'model', 'port', 'typoConnection'] as $field) {
    if (!isset($config[$field])) {
        fail("Campo obrigatorio ausente no JSON: {$field}");
    }
}

if (strtoupper((string) $config['model']) !== 'CDATA') {
    fail("Este validador e especifico para CDATA (model='{$config['model']}').");
}

$useMac = $opts['mac'] !== null && $opts['mac'] !== true;
$useExplicit = ($opts['pon'] !== null && $opts['pon'] !== true)
    && ($opts['onu'] !== null && $opts['onu'] !== true);

if (!$useMac && !$useExplicit) {
    fail('Informe --mac=<MAC> OU --pon=<F/S/P> --onu=<id>.');
}

// ---- conexao --------------------------------------------------------------
$olt = new OLT(
    $config['userName'],
    $config['password'],
    $config['model'],
    $config['address'],
    (string) $config['port'],
    $config['typoConnection'],
    $config['oltName'] ?? '',
    $config['cliProfile'] ?? null,
    $config['firmwareVersion'] ?? null
);

$enforceFirmware = $opts['no-firmware-check'] !== true; // flag presente => desativa
$connection = new CDATAConnection($olt, $enforceFirmware);

echo "== OLT CDATA: {$config['address']}:{$config['port']} ({$olt->nome}) ==\n";
echo "Validacao de firmware: " . ($enforceFirmware ? 'ativa' : 'desativada') . "\n\n";

$exitCode = 0;

try {
    // 1) Resolve a PON/ONU-ID --------------------------------------------------
    if ($useMac) {
        $adapter = new CDataFeatureAdapter($connection);
        $result = $adapter->findOnu((string) $opts['mac']);
        if ($result->state !== OltFeatureState::SUPPORTED || $result->value === null) {
            fail("ONU nao encontrada para o MAC {$opts['mac']} (codigo: {$result->reasonCode}).");
        }
        /** @var OnuIdentity $identity */
        $identity = $result->value;
        $pon = $identity->pon;
        $onuId = (int) $identity->onuId;
        echo "ONU localizada via findOnu(): pon={$pon} onu-id={$onuId} state={$identity->state}\n\n";
    } else {
        $pon = (string) $opts['pon'];
        $onuId = (int) $opts['onu'];
        echo "Alvo explicito: pon={$pon} onu-id={$onuId}\n\n";
    }

    // 2) Monta e dispara o comando exato --------------------------------------
    // A OLT so aceita o optical-info na forma relativa dentro de "interface
    // epon <F/S>"; a forma absoluta em (config)# retorna "Unknown command".
    $address = PonAddress::fromString($pon);
    $command = "show ont optical-info {$address->port} {$onuId}";

    echo "Contexto: interface epon {$address->frameSlot()}\n";
    echo "Comando enviado:\n  {$command}\n\n";

    $raw = $connection->execInEponInterface($address->frameSlot(), $command);

    echo "---- SAIDA CRUA DA OLT ----\n";
    echo ($raw === false ? '[resposta vazia]' : $raw) . "\n";
    echo "---------------------------\n\n";

    // 3) Passa pelo parser de producao ----------------------------------------
    $parsed = $raw === false ? [] : (new OpticalInfoStringParser())->parse($raw);
    $info = $parsed[0] ?? null;

    echo "---- RESULTADO PARSEADO ----\n";
    if ($info === null) {
        echo "FALHA: o parser nao extraiu nenhuma metrica.\n";
        echo "=> O formato da saida da OLT NAO bate com o esperado. Ajustar OpticalInfoStringParser\n";
        echo "   com base na 'SAIDA CRUA' acima.\n";
        $exitCode = 2;
    } else {
        printf("  Rx optical power (sinal) : %s dBm\n", $info->rxOpticalPower ?? '[nulo]');
        printf("  Tx optical power         : %s dBm\n", $info->txOpticalPower ?? '[nulo]');
        printf("  Temperatura              : %s C\n", $info->temperature ?? '[nulo]');
        printf("  Voltagem                 : %s V\n", $info->voltage ?? '[nulo]');
        printf("  Laser bias current       : %s mA\n", $info->laserBiasCurrent ?? '[nulo]');

        if ($info->rxOpticalPower === null) {
            echo "\nAVISO: parseou o bloco mas o campo de sinal (Rx) veio nulo. Conferir label na saida crua.\n";
            $exitCode = 2;
        } else {
            echo "\nOK: comando aceito e sinal extraido com sucesso. Implementacao validada ao vivo.\n";
        }
    }
    echo "----------------------------\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "\nEXCECAO (" . $e::class . "): " . $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    $connection->disconnect();
}

exit($exitCode);

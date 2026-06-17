# Diagnostico tecnico: sinal de ONU na OLT Sao Geraldo

## Escopo

Este documento registra o diagnostico da coleta de sinal optico da ONU para a
OLT `001-olt-sao-geraldo.json`, modelo `CDATA`, perfil `CDATA_EPON_CLI_V1`.

## Evidencia levantada

Arquivo de inventario analisado:

- `examples/config/olts/001-olt-sao-geraldo.json`

Implementacoes relevantes:

- Adapter legado: `src/Adapters/CDATAOLTCmd.php`
- Conexao SSH versionada: `src/OLT/CDATA/CDATAConnection.php`
- Comando versionado de sinal: `src/OLT/CDATA/Command/OpticalInfoCommand.php`
- Parser do retorno optico: `src/OLT/CDATA/DataProcessors/OpticalInfoStringParser.php`
- Parser de PON: `src/OLT/CDATA/Support/PonAddress.php`

Teste de conectividade SSH executado neste ambiente:

```text
ssh -vvv -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  -o ConnectTimeout=8 root@<olt> exit

debug1: connect to address <olt> port 22: Connection timed out
ssh: connect to host <olt> port 22: Connection timed out
```

Conclusao (parcial): aquele primeiro teste, de um host sem rota, deu timeout.
Posteriormente houve rota ate a OLT e a CLI foi validada ao vivo — ver as
secoes abaixo, que sao a fonte de verdade deste documento.

## Comando correto para buscar o sinal (CONFIRMADO AO VIVO)

Validado diretamente na OLT (ONU `80:07:1B:B3:B4:80`, PON `0/0/8`, ONU-ID 20).
O comando optico **so funciona na forma relativa, dentro do contexto
`interface epon <frame/slot>`**:

```text
config
interface epon <frame/slot>
show ont optical-info <porta> <onu-id>
```

Exemplo real:

```text
config
interface epon 0/0
show ont optical-info 8 20
```

Retorno:

```text
Frame/Slot                 : 0/0
Port                       : 8
ONT-ID                     : 20
ONT-MAC                    : 80:07:1B:B3:B4:80
Voltage(V)                 : 3.39
Tx optical power(dBm)      : 1.89
Rx optical power(dBm)      : -19.28
Laser bias current(mA)     : 13.00
Temperature(C)             : 31.30
```

O valor usado para sinal e o campo `Rx optical power(dBm)`.

### A forma absoluta NAO funciona

Em `(config)#`, a forma absoluta e **recusada**:

```text
(config)# show ont optical-info 0/0 8 20
Unknown command: (vtysh)show ont optical-info0/0820
```

> Observacao: o `(vtysh)...` junta os tokens ao reportar o erro; isso e um
> artefato do formatador de erro da OLT, nao perda de espacos no envio.

`show ont info <frame/slot> <porta> <onu-id|all>` funciona em `(config)#`
(forma absoluta) — apenas o `optical-info` exige o contexto da interface.

## O commit 3205057 foi um fix incorreto (regressao)

O commit `3205057` "add missing F/S prefix to optical-info command" fez o fluxo
versionado montar a forma absoluta (`show ont optical-info 0/0 8 20`) em
`(config)#` — exatamente a forma que a OLT recusa. O adapter **legado**
(`CDATAOLTCmd`) sempre fez certo (entra em `interface epon` antes, forma
relativa).

## Correcao aplicada (fluxo versionado)

Migracao para o layer versionado, validada ao vivo via
`CDataFeatureAdapter::findOnu()` -> `opticalMetrics()`:

- `CDATAConnection::execInEponInterface(frameSlot, cmd)` — entra em
  `interface epon <F/S>`, roda o comando, faz `exit` de volta para `(config)#`
  e re-sincroniza o prompt (a conexao continua reutilizavel depois).
- `OLT/CDATA/Command/OpticalInfoCommand` — passou a emitir a forma relativa
  `show ont optical-info <porta> <onu-id>` via `execInEponInterface`.
- `OpticalInfoStringParser` (inalterado) ja extraia `Rx optical power(dBm)`
  corretamente.

Resultado da validacao ao vivo: `rxPowerDbm = -19.28`, `txPowerDbm = 1.89`,
`temperatureCelsius = 31.3`, `voltage = 3.39`, `laserBiasCurrent = 13.0`.

## Como validar

```bash
php examples/validate_cdata_signal.php \
    examples/config/olts/001-olt-sao-geraldo.json --mac=<MAC-DA-ONU>
```

O script usa o caminho versionado real e imprime a saida crua + parseada.

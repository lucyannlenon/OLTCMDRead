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

Conclusao: nao foi possivel validar a CLI ao vivo a partir deste host porque a
porta 22 da OLT nao respondeu dentro do timeout.

## Comando correto para buscar o sinal

Pelo profile homologado `CDATA_EPON_CLI_V1`, o comando esperado para obter a
leitura optica da ONU e (ja implementado no fluxo versionado pelo commit
`3205057` "fix(cdata): add missing F/S prefix to optical-info command"):

```text
show ont optical-info <frame/slot> <porta> <onu-id>
```

Exemplo para uma ONU na PON `0/0/2` com `onu-id = 3`:

```text
show ont optical-info 0/0 2 3
```

O parser versionado espera um retorno neste formato:

```text
Voltage(V)                 : 3.30
Tx optical power(dBm)      : 1.97
Rx optical power(dBm)      : -21.94
Laser bias current(mA)     : 15.16
Temperature(C)             : 50.73
```

O valor usado pelo sistema para sinal e o campo:

```text
Rx optical power(dBm)
```

## Diferenca para o adapter legado

O adapter legado `CDATAOLTCmd` entra primeiro em:

```text
interface epon <frame/slot>
```

e depois executa:

```text
show ont optical-info <porta> <onu-id>
```

Exemplo equivalente:

```text
interface epon 0/0
show ont optical-info 2 3
```

Ou seja:

- Fluxo legado: comando relativo ao contexto da interface.
- Fluxo versionado: comando absoluto com `frame/slot`, `porta` e `onu-id`.

Os dois fluxos podem ser validos, mas pertencem a contextos diferentes de CLI.

## Estado atual da medicao de sinal (revisado)

Revisao do codigo (legado + versionado) confirma que a medicao de sinal esta
correta e que o ajuste de comando ja foi aplicado:

- Campo de sinal correto em ambos os fluxos: `Rx optical power(dBm)`.
  - Legado: `CDATAOLTCmd::setSinalCliente()`.
  - Versionado: `OpticalInfoStringParser` -> `rxOpticalPower` consumido por
    `CDataFeatureAdapter::opticalMetrics()`.
- Fluxo versionado consistente de ponta a ponta:
  - `ListOnuStringParser` monta a PON como `F/S/P` (ex.: `0/0/2`).
  - `PonAddress` separa em `frame/slot` + `porta`.
  - `OpticalInfoCommand` emite `show ont optical-info 0/0 2 3` (formato
    absoluto), conforme commit `3205057`.

Portanto nao ha bug de codigo pendente na forma de medir o sinal. O que resta
e validacao em ambiente, nao correcao de codigo:

1. Conectividade SSH com a OLT (porta 22 em timeout neste host) — ambiental.
2. Confirmar ao vivo que `show ont optical-info` responde nesta caixa EPON.
   - O indicio disponivel e que `show ont info 0/0 1 all` foi observado
     funcionando, o que sustenta o uso do formato absoluto com `F/S`.

## Validacao recomendada diretamente na OLT

Quando houver rota SSH ate a OLT, executar nesta ordem:

```text
enable
config
show ont info by-mac <MAC-DA-ONU>
```

Anotar:

- `Frame/Slot`
- `Port`
- `ONT-ID`

Depois testar o comando absoluto:

```text
show ont optical-info <frame/slot> <porta> <onu-id>
```

Se a operacao estiver sendo feita dentro da interface, testar o fluxo legado:

```text
interface epon <frame/slot>
show ont optical-info <porta> <onu-id>
```

## Recomendacao objetiva

Para o perfil `CDATA_EPON_CLI_V1`, o fluxo versionado ja usa o comando absoluto
(implementado no commit `3205057`):

```text
show ont optical-info <frame/slot> <porta> <onu-id>
```

Nenhuma alteracao de codigo adicional e necessaria para a medicao de sinal. Os
itens em aberto sao apenas de validacao ao vivo (conectividade SSH e confirmacao
do comando na CLI da OLT).

Se o sistema que esta falhando ainda usa `src/Adapters/CDATAOLTCmd.php`, ele
precisa ser conferido contra o contexto real da sessao SSH para garantir que o
prompt realmente esteja dentro de `interface epon <frame/slot>` antes da leitura
do sinal.

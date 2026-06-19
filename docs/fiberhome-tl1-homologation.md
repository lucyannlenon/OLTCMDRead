# Homologacao Fiberhome via TL1

## Atualizacao de 15 de junho de 2026

O endpoint do gateway TL1 compartilhado foi alterado. Os quatro arquivos
locais do inventario Fiberhome foram atualizados no campo `tl1Server`:

- ID 2: MUTUM FIBERHOME 1
- ID 3: MUTUM FIBERHOME 2
- ID 4: IPANEMA FIBERHOME 1
- ID 9: FIBERHOME IPA2

Os arquivos em `examples/config/olts` sao ignorados pelo Git. O endereco real
do gateway e suas credenciais nao devem ser incluidos em commits,
documentacao ou saidas de diagnostico.

O acesso deve ser configurado no ambiente:

```bash
export IPSERVER_TL1='gateway-tl1'
export USERNAME_TL1='usuario'
export PASSWORD_TL1='senha'
```

Em ambiente local, esses valores tambem podem existir em `.env.local`. Esse
arquivo deve permanecer fora do Git e nao deve ser copiado para a
documentacao, exemplos commitados ou saidas de diagnostico.

Para testes manuais e probes ad hoc, use o exemplo `examples/FIBERHOME.php`
como ponto de partida e uma OLT Fiberhome de teste configurada localmente em
`examples/config/olts`.

## Resultado da validacao

O probe somente leitura foi executado sequencialmente nas quatro OLTs:

```bash
php examples/probe_olt_inventory.php \
  --config=examples/config/olts \
  --model=FIBERHOME
```

Resultado homologado:

| ID | Gateway acessivel | Credencial TL1 | Firmware |
|---:|---|---|---|
| 2 | sim | valida | indisponivel via TL1 |
| 3 | sim | valida | indisponivel via TL1 |
| 4 | sim | valida | indisponivel via TL1 |
| 9 | sim | valida | indisponivel via TL1 |

Cada consulta levou aproximadamente 10 segundos. O firmware permanece
explicitamente `unavailable`, pois ainda nao existe uma consulta TL1
confirmada que retorne uma versao confiavel.

## Correcao do login

O gateway retorna uma autenticacao bem-sucedida no formato:

```text
M  CTAG COMPLD
EN=0   ENDESC=No error
```

O validador antigo procurava a palavra `ERROR` em toda a resposta e
classificava incorretamente `No error` como falha de autenticacao. A validacao
agora prioriza `COMPLD` com `EN=0` e rejeita respostas `DENY`, `FAILED` ou com
codigo `EN` diferente de zero.

Essa regressao e coberta por `tests/fiberhome_tl1_config_test.php`.

## Limites atuais

- O acesso Fiberhome continua exclusivamente pelo gateway TL1 compartilhado.
- Nao existe fallback direto por SSH ou Telnet.
- Firmware, MACs aprendidas, lookup reverso de MAC e discovery da MAC do
  roteador continuam sem suporte confirmado via TL1.
- Listagem, status, sinal, temperatura, distancia, Ethernet, VLAN e ONUs nao
  autorizadas permanecem no perfil para validacao operacional por ONU.

## Atualizacao de 19 de junho de 2026

Foi adicionada a implementacao local do comando `ListOnuMacAddressCommand`
para Fiberhome, com parser tolerante e teste isolado em Docker.

A ONU `ZTEGd9872298` foi confirmada online na OLT `002 MUTUM FIBERHOME 1`
(`10.99.99.66`), no `PONID=NA-NA-15-2`, e serviu como alvo real para a
homologacao somente leitura.

Consultas confirmadas:

```text
LST-ONUSTATE::OLTID=10.99.99.66,PONID=NA-NA-15-2,ONUIDTYPE=MAC,ONUID=ZTEGd9872298:CTAG::;
LST-ONULANINFO::OLTID=10.99.99.66,PONID=NA-NA-15-2,ONUIDTYPE=MAC,ONUID=ZTEGd9872298:CTAG::;
LST-PORTVLAN::OLTID=10.99.99.66,PONID=NA-NA-15-2,ONUIDTYPE=MAC,ONUID=ZTEGd9872298,ONUPORT=NA-NA-NA-1:CTAG::;
LST-PORTMACADDRESS::OLTID=10.99.99.66,PONID=NA-NA-15-2,ONUIDTYPE=MAC,ONUID=ZTEGd9872298,PORTID=NA-NA-NA-1,VLAN=100:CTAG::;
```

Os verbos `LST-MAC`, `LST-ONUMAC` e `LST-MACADDR` foram descartados com
`DENY / invalid parameter format`.

Resposta vazia homologada para a tabela de MACs:

```text
M  CTAG COMPLD
total_blocks=1
block_number=1
block_records=0

List of mac address
--------------------------------------------------------------------------------
VLAN  MAC
--------------------------------------------------------------------------------
```

Com isso, `learned_macs` passa a ser suportado para Fiberhome. Ainda falta uma
captura real com uma ou mais MACs aprendidas para enriquecer o parser com
evidencia positiva, mas o comando e a resposta vazia ja estao confirmados.

`reverse_mac_lookup` e `router_mac_discovery` continuam sem suporte confirmado
via TL1.

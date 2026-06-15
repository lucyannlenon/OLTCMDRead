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

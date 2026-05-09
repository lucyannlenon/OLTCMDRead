# RFC 0001 — Consulta de MAC do CPE em ONUs Bridge com Autorização Automática

- **Status:** Rascunho
- **Data:** 2026-05-09
- **Contexto:** Integração OLT → BNG

---

## 1. Problema

Em ambientes com BNG (Broadband Network Gateway), o modelo de acesso é:

```
BNG ← PPPoE ← [Roteador/CPE] ← UNI ← [ONU Bridge] ← PON ← OLT
```

Algumas OLTs — principalmente **VSOL** e **DATACOM** — possuem mecanismo de autorização automática de ONUs em modo Bridge. Essas ONUs são provisionadas sem intervenção manual e, por isso, **não estão registradas no sistema de gerência**.

O BNG identifica sessões PPPoE pelo MAC do roteador (CPE) do cliente. Para correlacionar um usuário do BNG com a ONU física na OLT, precisamos saber: **qual é o MAC do CPE conectado atrás de cada ONU Bridge?**

Hoje essa informação não é coletada. O fluxo que existe só cobre ONUs mapeadas manualmente.

---

## 2. Solução Proposta

Cada ONU em modo Bridge aprende os MACs das portas UNI via FDB (Forwarding Database). A OLT expõe essa tabela via CLI. A proposta é implementar um comando por vendor que consulte essa tabela e retorne os MACs aprendidos em cada ONU.

Com o MAC do CPE em mãos, o sistema pode consultar o BNG e identificar o usuário da sessão PPPoE.

---

## 3. Escopo

| Vendor  | Camada de implementação              | Prioridade |
|---------|--------------------------------------|------------|
| VSOL    | Legada (`VSolOLTGPONCmd` / nova classe) | Alta       |
| DATACOM | Nova (`src/OLT/DATACOM/Command/`)    | Alta       |

ZTE e FiberHome ficam fora do escopo inicial por não apresentarem o cenário de autorização automática Bridge.

---

## 4. Interface Esperada

### DTO de retorno

Novo DTO `OLT\Dto\OnuCpeMac`:

```php
class OnuCpeMac
{
    public string $mac;
    public string $port;   // porta UNI (ex: "eth1", "1")
    public ?string $vlan;  // VLAN aprendida, se disponível
}
```

### Assinatura do comando (DATACOM — camada nova)

```php
// src/OLT/DATACOM/Command/GetCpeMacCommand.php
$cmd = new GetCpeMacCommand(new DATACOMConnection($olt));
$macs = $cmd->execute(pon: '1/1/1', onuId: '5');
// retorna OnuCpeMac[]
```

### Assinatura do comando (VSOL — camada legada ou nova)

```php
// a definir após confirmar o comando CLI
$macs = $vsolCmd->getCpeMacs(pon: '2', onuId: '14');
// retorna OnuCpeMac[]
```

---

## 5. Comandos CLI a Confirmar

Esta é a principal pendência antes da implementação. Os comandos abaixo são **prováveis** — precisam ser validados num OLT real com output real.

### VSOL (dentro de `configure terminal` → `interface gpon {slot}/{pon}`)

```
show mac onu {onuId}
```
ou
```
show fdb onu {onuId}
```

**Output esperado (hipotético):**
```
ONU  Port  MAC Address        VLAN
14   eth1  aa:bb:cc:dd:ee:ff  100
14   eth1  11:22:33:44:55:66  100
```

### DATACOM

```
show mac-address-table interface gpon {slot}/{pon}/{port} onu {onuId}
```

**Output esperado (hipotético):**
```
VID   MAC Address        Type     Interface
100   aa:bb:cc:dd:ee:ff  dynamic  gpon 1/1/1 onu 5
```

> **Ação necessária:** rodar os comandos num OLT de teste e colar o output real aqui para finalizar o parser.

---

## 6. Arquitetura de Implementação

### DATACOM (camada nova — padrão já estabelecido)

```
src/OLT/DATACOM/Command/
    GetCpeMacCommand.php               ← extends AbstractCommand
    DataProcessors/
        CpeMacStringParser.php         ← implements StringParserInterface
```

### VSOL (a decidir)

**Opção A — método na classe existente** (`VSolOLTGPONCmd`)
- Menor esforço, mantém o padrão atual do VSOL.
- Contra: mistura responsabilidades na classe já grande.

**Opção B — nova camada estruturada** (`src/OLT/VSOL/`)
- Cria `VSolConnection`, `GetCpeMacCommand`, `CpeMacStringParser`.
- Consistente com ZTE e DATACOM.
- Esforço maior, mas abre caminho para migrar outros comandos VSOL.

Recomendação: **Opção B**, criando só o necessário agora e migrando o restante do VSOL progressivamente.

---

## 7. Perguntas em Aberto

1. O comando correto no VSOL para consultar a FDB da ONU é `show mac onu {id}` ou outro?
2. O DATACOM retorna VLAN junto ao MAC? É útil expor isso no DTO?
3. Uma ONU Bridge pode aprender múltiplos MACs (switch no cliente)? O sistema precisa tratar essa lista ou só o primeiro MAC?
4. Qual é o próximo passo na integração com o BNG — a consulta ao BNG ficará nesta lib ou em sistema externo?

---

## 8. O que NÃO está no escopo

- Autenticação/provisionamento das ONUs Bridge (só leitura).
- Integração direta com o BNG (essa lib entrega o MAC; quem consulta o BNG é o sistema consumidor).
- Vendors além de VSOL e DATACOM.

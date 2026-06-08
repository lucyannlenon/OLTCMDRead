# EPON ONU Router MAC Discovery

## Objetivo

Descobrir quais enderecos MAC foram aprendidos atras de cada ONU para permitir
a correlacao:

```text
OLT -> porta PON/ONU -> MAC do roteador -> sessao/login PPPoE -> cliente
```

O procedimento abaixo foi validado em uma OLT EPON com CLI compativel com o
adapter `CDATAOLTCmd`. Todos os comandos usados sao somente de leitura.

## Equipamento validado

Informacoes observadas, sem dados identificadores do ambiente:

```text
Device model     : EPON OLT
Hardware version : V1.0
Firmware version : V1.6.0_240629
```

A identificacao do fabricante nao e conclusiva porque a CLI informa apenas
`EPON OLT`. A sintaxe dos comandos coincide com a usada pelo adapter CDATA
existente neste repositorio.

O servidor SSH oferece algoritmos de host key antigos. No cliente OpenSSH foi
necessario habilitar `ssh-rsa` apenas para esse host:

```bash
ssh -o HostKeyAlgorithms=+ssh-rsa usuario@192.168.x.x
```

Nao desabilite globalmente as validacoes SSH. O ideal e atualizar o firmware ou
limitar essa excecao a uma entrada especifica em `~/.ssh/config`.

Depois da autenticacao do transporte SSH, esta CLI ainda apresenta
`>>User password:` antes de liberar o prompt `OLT>`. `CDATAConnection` trata
essa segunda etapa automaticamente usando a mesma credencial configurada no
DTO da OLT.

## Procedimento manual

Entre no modo privilegiado e depois no modo de configuracao:

```text
enable
config
```

Liste as ONUs de uma porta PON:

```text
show ont info 0/0 1 all
```

Para listar todas as ONUs da OLT, a CLI deve ser consultada uma vez por porta:

```text
show ont info 0/0 1 all
show ont info 0/0 2 all
show ont info 0/0 3 all
show ont info 0/0 4 all
show ont info 0/0 5 all
show ont info 0/0 6 all
show ont info 0/0 7 all
show ont info 0/0 8 all
```

O adapter implementa essa iteracao em `CDATA\Command\ListAllOnuCommand`.

Formato relevante da resposta:

```text
F/S  P  ID  ONT MAC            Run state  Config state
0/0  1  1   AA:BB:CC:DD:EE:01 online     success
0/0  1  2   AA:BB:CC:DD:EE:02 online     success
```

Consulte as MACs aprendidas atras de uma ONU especifica:

```text
show mac-address ont 0/0/1 1
```

Formato relevante da resposta:

```text
MAC                 VLAN  Port       ONT-Id  MAC-Type
11:22:33:44:55:66   100   pon0/0/1   1       dynamic
AA:BB:CC:DD:EE:01   100   pon0/0/1   1       dynamic
```

Nesse exemplo, `AA:BB:CC:DD:EE:01` e a MAC da ONU obtida pelo primeiro
comando. Portanto, `11:22:33:44:55:66` e uma MAC de equipamento conectado
atras dela e se torna candidata a MAC WAN do roteador.

Tambem e possivel fazer a busca inversa de uma MAC conhecida:

```text
show location 11:22:33:44:55:66
```

A resposta inclui `Port` e `ONT-Id`, permitindo localizar a ONU sem varrer
todas as portas:

```text
MAC                 VLAN  Port       ONT-Id  MAC-Type
11:22:33:44:55:66   100   pon0/0/1   1       dynamic
```

## Algoritmo para o servico

1. Executar `show ont info 0/0 <pon> all` para obter ONUs, MAC da ONU e estado.
2. Considerar inicialmente apenas ONUs `online`.
3. Para cada ONU, executar
   `show mac-address ont 0/0/<pon> <onu-id>`.
4. Normalizar todas as MACs para um unico formato, por exemplo
   `AA:BB:CC:DD:EE:FF`.
5. Remover da lista a MAC da propria ONU.
6. Correlacionar as MACs restantes com a fonte de sessoes PPPoE/AAA.
7. Persistir a evidencia com OLT, PON, ONU ID, VLAN, MAC, login PPPoE e horario
   da coleta.

Quando o sistema PPPoE ja fornece a MAC e o objetivo e localizar apenas um
cliente, usar `show location <mac>` e mais eficiente do que varrer todas as
ONUs.

## Regras e limitacoes

- Uma ONU pode aprender zero, uma ou varias MACs.
- A MAC da propria ONU pode aparecer na tabela e deve ser excluida.
- Mais de uma MAC restante pode indicar roteador, bridge, switch ou dispositivos
  ligados diretamente na ONU.
- A melhor confirmacao de que uma MAC pertence ao roteador e a correspondencia
  com uma sessao ativa no concentrador PPPoE ou no AAA/RADIUS.
- A tabela e dinamica e sofre aging. Um roteador offline pode nao aparecer, e
  uma entrada antiga pode permanecer por algum tempo.
- A VLAN deve ser preservada na correlacao para evitar ambiguidades.
- MAC aleatoria/clonada e troca de roteador podem alterar o vinculo com o
  cliente.
- O comando `show mac-address dynamic` lista a porta PON, mas nao mostrou o
  `ONT-Id`; para o mapeamento completo, prefira a consulta por ONU ou
  `show location`.
- A coleta deve limitar concorrencia e comandos por segundo para nao
  sobrecarregar a CPU da OLT.
- Credenciais, IPs reais, seriais e MACs do ambiente nao devem ser gravados em
  logs ou no repositorio.

## Contrato sugerido

Um resultado normalizado pode usar a estrutura:

```json
{
  "olt": "olt-id-interno",
  "frame": 0,
  "slot": 0,
  "pon": 1,
  "onu_id": 1,
  "onu_mac": "AA:BB:CC:DD:EE:01",
  "learned_mac": "11:22:33:44:55:66",
  "vlan": 100,
  "mac_type": "dynamic",
  "pppoe_login": "cliente-exemplo",
  "collected_at": "2026-06-06T12:00:00-03:00"
}
```

O parser deve ser testado com respostas gravadas e anonimizadas antes de ser
ligado a uma rotina de coleta em massa.

## Uso do adapter

As classes modernas ficam em `LLENON\OltInformation\OLT\CDATA`:

```php
$connection = new CDATAConnection($olt);

$onusDaPon = (new ListOnuCommand($connection))->execute('0/0/1');
$todasAsOnus = (new ListAllOnuCommand($connection))->execute();
$macs = (new ListOnuMacAddressCommand($connection))->execute('0/0/1', 1);
$localizacao = (new LocateMacAddressCommand($connection))
    ->execute('11:22:33:44:55:66');
```

`OnuRouterMacDiscovery::discoverAll()` combina a listagem das oito PONs com a
tabela MAC de cada ONU online e remove a MAC da propria ONU. Um exemplo
executavel, configurado apenas por variaveis de ambiente, esta em
`examples/cdata_router_mac_discovery.php`.

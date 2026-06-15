# OLTCMDRead

## Versionamento

Cada novo commit enviado para `main` recebe automaticamente a proxima tag
patch SemVer pelo workflow `.github/workflows/auto-version.yml`.

Exemplo:

```text
v1.0.6 -> v1.0.7 -> v1.0.8
```

O pacote Composer usa essas tags Git como versoes. O `composer.json` nao
declara uma versao fixa.

## Documentacao

- [Descoberta da MAC do roteador por ONU EPON](docs/epon-onu-router-mac-discovery.md)
- [Homologacao Fiberhome via TL1](docs/fiberhome-tl1-homologation.md)
- [Matriz de recursos por fabricante](docs/olt-feature-matrix.md)
- [Perfis de CLI versionados](docs/superpowers/specs/2026-06-09-olt-versioned-cli-profiles-design.md)
- [VSOL EPON por Telnet](docs/superpowers/specs/2026-06-09-vsol-epon-telnet-support-design.md)

## Homologacao multi-vendor

Os perfis de CDATA, DATACOM, ZTE, Fiberhome, VSOL EPON e VSOL GPON publicam
o mesmo catalogo normalizado. Cada operacao retorna `supported`,
`unavailable` ou `unsupported`, sem usar valores vazios para esconder
limitacoes do equipamento.

Os arquivos JSON ignorados em `examples/config/olts` podem ser verificados
com probes somente de leitura:

```bash
php examples/probe_olt_inventory.php --config=examples/config/olts
php examples/probe_olt_version_evidence.php --config=examples/config/olts
php examples/probe_olt_mac_table.php --config=examples/config/olts
php examples/olt_feature_matrix.php
```

Fiberhome usa exclusivamente o gateway TL1 compartilhado nesta fase. As
credenciais devem existir apenas no ambiente:

```bash
export IPSERVER_TL1='gateway-tl1'
export USERNAME_TL1='usuario'
export PASSWORD_TL1='senha'
```

O perfil DATACOM agrupa as tres versoes homologadas porque os equipamentos
responderam ao mesmo conjunto de comandos e ao mesmo formato de parser. O
comando confirmado para detectar firmware e `show firmware`.

## VSOL EPON

O modelo `VSOL` usa Telnet e exige o perfil e firmware homologados:

```php
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;

$olt = new OLT(
    $username,
    $password,
    OltModel::VSOL,
    $host,
    '23',
    'telnet',
    'VSOL EPON',
    OltCliProfile::VSOL_EPON_CLI_V1,
    'V1.01.51_230922190137'
);
```

O perfil valida o firmware com `show version`. O adapter fornece status,
distancia, uptime, sinal, temperatura e estado Ethernet. As classes modernas
tambem listam ONUs, MACs aprendidas e localizam uma MAC conhecida.

Veja `examples/vsol_epon.php`. Credenciais e enderecos devem ser fornecidos
somente por variaveis de ambiente.

## VSOL GPON

O modelo `VSOLGPON` exige perfil de CLI e firmware homologado:

```php
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;

$olt = new OLT(
    $username,
    $password,
    OltModel::VSOLGPON,
    $host,
    '22',
    'ssh',
    'VSOL GPON',
    OltCliProfile::VSOL_GPON_CLI_V2,
    'V2.1.8R'
);
```

O perfil valida a versao configurada contra `show version` antes de executar
comandos operacionais. Veja `examples/vsol_gpon.php` para diagnostico de
cliente, listagem de ONUs e descoberta de MAC.

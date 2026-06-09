# OLTCMDRead

## Documentacao

- [Descoberta da MAC do roteador por ONU EPON](docs/epon-onu-router-mac-discovery.md)
- [Perfis de CLI versionados](docs/superpowers/specs/2026-06-09-olt-versioned-cli-profiles-design.md)
- [VSOL EPON por Telnet](docs/superpowers/specs/2026-06-09-vsol-epon-telnet-support-design.md)

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

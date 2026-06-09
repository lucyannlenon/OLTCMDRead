# VSOL EPON Telnet Support Design

## Goal

Add version-aware VSOL EPON support with the same operational capabilities as
the VSOL GPON implementation:

- Client status, distance, uptime, optical signal, and temperature.
- Ethernet port state.
- ONU listing and lookup.
- Learned MAC addresses behind an ONU.
- Reverse location of a learned MAC.
- Router MAC discovery.

The implementation must preserve the existing `Client` result contract while
separating Telnet transport, CLI commands, parsing, and firmware compatibility.

## Confirmed CLI Behavior

The homologated device reports:

```text
Olt Device Model: EPON-OLT
Hardware Version: V1.2.4M
Software Version: V1.01.51_230922190137
```

The session uses Telnet and presents `Login:` and `Password:` prompts. After
authentication, the user prompt is `epon-olt>`. Privileged mode requires
`enable` and the enable password, producing `epon-olt#`.

ONU and MAC commands are available only after `configure terminal`, where the
prompt becomes `epon-olt(config)#`. Per-ONU commands use
`interface epon 0/<pon>`, producing `epon-olt(config-pon-0/<pon>)#`.

`terminal length 0` disables pagination. Terminal output can contain carriage
returns and ANSI cursor movement sequences and must be normalized before
parsing.

Real credentials, addresses, OLT serial numbers, ONU MAC addresses, and full
running configurations must not be stored in the repository or fixtures.

## Selected Approach

Create an independent `OLT/VSOL/EPON` module and retain `VSolOLTCmd` as the
legacy public adapter bridge.

This is preferred over extending the legacy adapter in place because the
current class combines connection, commands, and parsing. It is also preferred
over merging GPON and EPON because their transports, prompts, authentication,
addressing, commands, and output formats differ.

Shared concepts remain generic:

- `OLT` configuration.
- CLI profile registry.
- Generic ONU and learned-MAC DTOs where their fields are compatible.
- The existing `Client` and `Ethernet` adapter result DTOs.

Protocol-specific behavior remains under `OLT/VSOL/EPON`.

## Version Profile

Add the profile:

```text
model: VSOL
profile: VSOL_EPON_CLI_V1
firmware versions: V1.01.51_230922190137
transport: telnet
```

The profile can support additional exact firmware versions after they are
homologated against the same authentication, command, and output grammar.

The migrated `VSolOLTCmd` adapter requires both `cliProfile` and
`firmwareVersion`. The profile registry must reject missing, unknown,
incompatible, or unhomologated values before opening a network connection.
The connection must also execute `show version` after initialization and
verify that the connected software version matches the configured version.

## Components

### Connection

`VSolEponConnection` owns:

- Telnet socket connection and negotiation cleanup.
- Login and password prompts.
- Enable mode and enable password.
- Pagination disablement.
- Configuration mode.
- PON interface entry and exit.
- Prompt synchronization.
- Timeout and disconnect behavior.
- Firmware verification.
- Retry of read-only commands after one reconnect.

The connection exposes `exec()` for configuration-mode read commands and
`execInPon()` for commands requiring a PON interface.

### Commands and parsers

Each command issues one read-only CLI operation and delegates output conversion
to a parser with no network dependency.

Confirmed command mapping:

```text
show version
show onu status all
show onu status <HHHH:HHHH:HHHH>
show onu basic-info all
show onu opm-diag pon <pon>,<onu>
show mac address-table
interface epon 0/<pon>
show onu <onu> mac-address-table
show onu <onu> ctc eth 1 port_info
show onu <onu> ctc eth 1 autoneg
show onu <onu> ctc eth 1 loopdetect
```

The status row supplies PON, ONU ID, registration MAC, online state, distance,
and alive time. Optical diagnostics supply temperature and RX power.

The basic-info listing supplies ONU model and identifier. Status and basic-info
results are joined by PON and ONU ID to produce complete ONU entries.

Ethernet diagnostics use the CTC command family. `port_info` supplies link
state, while `autoneg` and `loopdetect` supply configuration and loop state.
This firmware does not expose a negotiated numeric Ethernet speed in the
confirmed output, so the adapter returns `N/A` for speed instead of inventing
a value.

### MAC discovery

`show onu <onu> mac-address-table` returns exact PON and ONU ownership and is
the authoritative source for learned MACs behind one ONU.

The global `show mac address-table` output includes a subscriber number that
does not directly equal the ONU ID. Reverse lookup therefore follows this
deterministic strategy:

1. Normalize the target MAC.
2. Read the global table to identify the candidate EPON PON.
3. List ONUs on that PON.
4. Query each ONU MAC table until the target is found.

Router MAC discovery lists online ONUs, reads each ONU table, normalizes MACs,
and removes the ONU registration MAC when present. The service preserves
multiple learned MACs because the library cannot determine router ownership
without external PPPoE or AAA evidence.

## Adapter Data Flow

The caller supplies the ONU registration MAC through the existing
`Client::macAddress` field. The adapter normalizes it to the CLI format
`HHHH:HHHH:HHHH` and executes the status lookup.

If no row is found, it throws `ClienteNotFund`. If the ONU is offline, it maps
PON, ONU ID, status, distance, and uptime and skips online-only diagnostics.

For an online ONU, it additionally reads optical and Ethernet diagnostics and
maps them to the existing `Client` and `Ethernet` DTOs. The connection is
always disconnected in a `finally` block.

## Errors and Safety

Connection errors must distinguish login, enable, timeout, firmware mismatch,
and prompt synchronization without including credentials.

Malformed output returns an empty or unavailable result where the legacy
contract already permits it. Missing ONU lookup remains a
`ClienteNotFund` error.

Only read-only operational commands may be automatically retried. Mode
transitions required to execute those reads may be replayed during reconnect,
but configuration-changing commands are outside this module.

All real-equipment validation is read-only. Examples use environment variables
and documentation uses placeholder addresses and credentials.

## Compatibility

`OltModel::VSOL` remains the adapter selection key. `VSolOLTCmd` keeps its
constructor and `getDadosDoCliente()` public API, adding only the optional
injectable connection used for testing.

Existing callers must add the profile and firmware fields when constructing a
VSOL EPON `OLT`. Other legacy models remain unaffected.

PHP compatibility remains `>=8.3`.

## Verification

Automated tests cover:

1. Profile acceptance and rejection.
2. Status parsing for online and offline ONUs.
3. Optical, distance, uptime, and Ethernet parsing.
4. Basic ONU listing and status/basic-info joining.
5. Learned MAC parsing and normalization.
6. Reverse MAC location fallback by PON and ONU table.
7. Router MAC discovery and ONU-registration-MAC exclusion.
8. Legacy `Client` mapping and guaranteed disconnect.
9. Command strings issued by each operation.
10. Terminal control sequence cleanup.

After automated tests pass, a read-only smoke test against the homologated
device verifies version, status, optical diagnostics, Ethernet state, ONU
listing, per-ONU learned MACs, and reverse MAC location.

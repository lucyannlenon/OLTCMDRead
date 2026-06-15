# Multi-Vendor OLT Feature Parity Design

## Goal

Homologate the 13 configured OLTs and migrate CDATA, DATACOM, ZTE, and
Fiberhome to the same version-aware architecture already used by VSOL EPON
and VSOL GPON.

Every vendor must expose the same normalized operational contract. Vendor,
transport, firmware, command syntax, and parser differences remain isolated
inside vendor adapters.

The first phase uses read-only probes against real equipment to discover the
installed firmware, supported commands, and actual output grammars. The
implementation starts only after those results have been anonymized and
grouped into compatible CLI families.

## Current Inventory

The ignored configuration directory `examples/config/olts` contains:

| Model | Quantity | Transport |
|---|---:|---|
| CDATA | 1 | SSH |
| DATACOM | 3 | SSH |
| ZTE | 5 | SSH |
| Fiberhome | 4 | TL1 gateway |

VSOL EPON and VSOL GPON are already represented in the source architecture,
but no VSOL device is present in this 13-device inventory. They remain part of
the final feature-parity audit.

All 13 configuration files currently have empty `cliProfile` and
`firmwareVersion` fields.

Fiberhome must use only the configured TL1 gateway in this phase. There is no
direct Telnet or SSH fallback to the OLT.

## Existing Baseline

The current modern baseline consists of:

- Versioned CLI profiles with exact homologated firmware lists.
- Firmware validation before operational commands for migrated VSOL models.
- Read-only connection retry after prompt desynchronization.
- Separate connection, command, parser, DTO, and discovery responsibilities.
- ONU listing, learned-MAC lookup, reverse MAC lookup, and router MAC
  discovery for VSOL EPON, VSOL GPON, and CDATA.
- Multi-vendor credential and firmware diagnostics.
- A capability catalog, although it currently reports only versioned VSOL
  profiles and does not describe operational feature support.

CDATA has router MAC discovery but is not yet version-gated. DATACOM, ZTE, and
Fiberhome have several operational commands, but do not yet provide the full
normalized discovery contract or versioned profiles.

## Selected Strategy

Profiles are defined by compatible CLI family and firmware, not by vendor
alone and not by individual device.

The 13 devices are all probed because devices from the same vendor can run
different firmware or return different output. Devices are grouped under one
profile only when they share:

1. Authentication and privilege transitions.
2. Prompt and pagination behavior.
3. Operational command syntax.
4. Output grammar required by the parsers.
5. Feature availability and semantics.

An existing profile accepts another firmware only after the complete read-only
probe passes with the same commands and parsers. A meaningful incompatibility
creates another profile for that model.

This avoids unsafe vendor-wide assumptions without duplicating one adapter per
physical OLT.

## Normalized Feature Contract

Every model must publish the following capabilities through common interfaces
and normalized DTOs:

| Feature | Required result |
|---|---|
| Connection diagnostic | Reachability, credential validity, transport, and stable error code |
| Firmware diagnostic | Detected version and configured-version match |
| CLI profile validation | Model/profile/firmware compatibility before operational access |
| ONU listing | PON, ONU ID, registration identifier, and state |
| ONU lookup | Locate by registration identifier and normalized PON/ONU address |
| ONU status | Online/offline and available operational state |
| Optical signal | Normalized receive power |
| Temperature | Normalized Celsius value |
| Distance | Normalized meters |
| Uptime | Normalized duration or seconds |
| Ethernet state | Link state |
| Ethernet speed | Normalized speed when exposed |
| VLAN | Normalized VLAN identifier |
| Unauthorized ONUs | Normalized unregistered ONU list |
| Learned MACs | MAC, PON, ONU ID, VLAN, type, and UNI port when available |
| Reverse MAC lookup | MAC to PON/ONU ownership |
| Router MAC discovery | Online ONU scan with registration MAC removed |

The common API must distinguish three outcomes:

- `supported`: the command succeeded and returned a normalized value, which
  may legitimately be empty.
- `unavailable`: the feature exists, but the current ONU state or response
  does not provide a value.
- `unsupported`: the homologated CLI or TL1 gateway does not expose the
  feature.

Adapters must not represent `unsupported` as an empty array, empty string, or
zero.

## Architecture

### CLI profiles

`OltCliProfileRegistry` remains the source of truth for:

- Profile identifier.
- OLT model.
- Exact normalized firmware versions.
- Transport and connection strategy.
- Command family.
- Supported capability set.

The initial profile count is not predetermined. It is derived from the
homologation results. At minimum, each discovered compatible CLI family gets
one profile.

### Capability catalog

`OltCapabilityRegistry` is extended from model-level defaults to profile-level
operational capabilities. Consumers can determine whether a feature is
supported before executing it.

The catalog revision remains deterministic and changes whenever a profile,
firmware, transport, or capability changes.

### Common services and DTOs

Common interfaces define ONU inventory, ONU diagnostics, learned MAC lookup,
reverse lookup, and router MAC discovery. Shared DTOs normalize fields while
allowing optional vendor evidence such as MAC type or UNI port.

The common service coordinates the workflow. Vendor command classes only issue
one vendor-specific command and parse its output.

### Vendor modules

Implementation remains under:

```text
src/OLT/CDATA/
src/OLT/DATACOM/
src/OLT/ZTE/
src/OLT/Fiberhome/
src/OLT/VSOL/EPON/
src/OLT/VSOL/GPON/
```

Each module may contain:

- A connection implementation.
- Version-specific command-set selection.
- Focused command classes.
- Output parsers.
- Vendor address and MAC normalization helpers.
- A vendor adapter implementing the common interfaces.

Existing legacy adapters remain available and delegate to modern services when
the migrated equivalent exists.

### Fiberhome boundary

Fiberhome operations use only the shared TL1 gateway. The profile records the
gateway credential scope and the capabilities actually exposed by TL1.

If the gateway cannot return firmware or an operational feature, the result is
explicitly `unsupported`. Direct device access is outside this design.

## Read-Only Homologation

### Probe safety

The probe runner reads the ignored JSON configuration files and must never
print or persist passwords, real addresses, serial numbers, or complete MAC
addresses.

Only documented read-only command families are permitted:

- SSH/Telnet: `show` commands and non-mutating terminal pagination commands.
- TL1: `LST-*` and other confirmed query verbs.

Configuration-changing commands and provisioning operations are prohibited.
Mode transitions needed to access read-only commands are allowed only when
already required by the vendor CLI.

### Probe sequence

For each of the 13 OLTs:

1. Validate local configuration without exposing secrets.
2. Connect using the configured transport or Fiberhome TL1 gateway.
3. Detect prompt, authentication flow, and pagination behavior.
4. Read model, hardware identification, and firmware where exposed.
5. Probe each normalized feature using minimal data:
   - global inventory commands first;
   - one online ONU for online-only diagnostics;
   - one offline ONU when needed to verify state handling;
   - one learned MAC for reverse lookup when available.
6. Record command support, sanitized output shape, duration, and stable failure
   classification.
7. Disconnect before moving to the next OLT.

The runner applies conservative per-device command pacing and never probes
devices in unbounded parallel.

### Sanitized evidence

Fixtures preserve only lines needed by parsers. They replace:

- IPs and hostnames with documentation values.
- Usernames and passwords with placeholders.
- ONU serials and registration MACs with deterministic fictional values.
- Learned MACs with deterministic fictional values.
- Site names with generic labels.

Raw production output is not committed.

## Vendor Homologation Order

The implementation follows risk and reuse:

1. **CDATA**: validate the existing MAC discovery and add a versioned profile.
2. **DATACOM**: highest missing-priority target from the earlier CPE MAC RFC;
   reuse the existing modern connection and ONU commands.
3. **ZTE**: reuse its mature diagnostics, parser, prompt synchronization, and
   ONU inventory foundation.
4. **Fiberhome**: map the common contract to TL1 and explicitly classify TL1
   gaps.
5. **VSOL regression**: verify EPON and GPON still satisfy the completed common
   contract.

Within DATACOM, ZTE, and Fiberhome, all configured devices are probed before
the final profile grouping is chosen.

## Learned MAC Discovery

The normalized discovery workflow is:

1. List ONUs.
2. Filter to online ONUs by default.
3. Read the forwarding/FDB entries owned by each ONU.
4. Normalize MAC, PON, ONU ID, VLAN, type, and UNI port.
5. Remove the ONU registration MAC when it appears in the learned table.
6. Preserve multiple learned MACs because router ownership requires external
   PPPoE or AAA evidence.

Reverse lookup uses a dedicated vendor command when available. If the CLI
exposes only a global table without reliable ONU ownership, the adapter may
use a deterministic bounded fallback that narrows by PON and queries ONU
tables. If no reliable read-only method exists, the capability is
`unsupported`.

## Error Handling

Common errors use stable categories:

- `AUTHENTICATION_FAILED`
- `UNREACHABLE`
- `CONNECTION_TIMEOUT`
- `PROMPT_NOT_DETECTED`
- `COMMAND_UNSUPPORTED`
- `MALFORMED_RESPONSE`
- `PROFILE_REQUIRED`
- `PROFILE_INCOMPATIBLE`
- `FIRMWARE_UNSUPPORTED`
- `FEATURE_UNAVAILABLE`

Errors include the model, profile, transport, and command capability name, but
not credentials, addresses, full commands containing identifiers, or raw
device output.

Automatic retries remain limited to read-only commands. Mutating commands are
never retried by the shared connection layer.

## Testing

### Parser and command tests

Each profile receives anonymized fixture tests for every supported feature.
Tests assert command strings, normalization, empty results, malformed output,
and unsupported behavior.

### Contract tests

A shared adapter contract test runs against test doubles for every registered
profile. It verifies that each adapter exposes the same methods and normalized
result types.

### Profile tests

Tests cover:

- Missing profile and firmware.
- Unknown profile.
- Profile/model mismatch.
- Homologated and unhomologated firmware.
- Multiple firmware versions sharing one compatible profile.
- Capability catalog revision and profile capability reporting.

### Real-equipment smoke tests

After unit tests pass, the probe runner repeats the approved read-only feature
set against all 13 devices. It emits a sanitized summary only.

No real-equipment test is part of an unattended default test command.

## Final Feature-Parity Audit

Completion requires an automatically generated matrix with one row per common
feature and one column for:

- CDATA
- DATACOM
- ZTE
- Fiberhome
- VSOL EPON
- VSOL GPON

Each cell reports:

- `supported` with the homologated profiles;
- `unavailable` only for state-dependent runtime data;
- `unsupported` with the confirmed equipment or TL1 limitation;
- `not-tested` only while implementation is incomplete.

The work is complete when:

1. No cell remains `not-tested`.
2. Every configured OLT passes connection and profile diagnostics.
3. Every vendor adapter passes the common contract suite.
4. All features available through the vendor transport are implemented.
5. Every genuine platform limitation is explicit and documented.
6. Existing VSOL and legacy adapter behavior has no unintended regression.

The target is functional parity. A feature is not considered implemented by
returning a placeholder value when the vendor command was not confirmed.

## Delivery Phases

1. Build the safe probe runner and normalized capability model.
2. Run inventory and firmware discovery on all 13 OLTs.
3. Group devices into CLI profiles from observed compatibility.
4. Implement and test CDATA parity.
5. Implement and test DATACOM parity.
6. Implement and test ZTE parity.
7. Implement and test Fiberhome TL1 parity.
8. Adapt VSOL modules to the common contract.
9. Run all parser, profile, contract, and regression tests.
10. Run the final read-only smoke test and generate the feature matrix.

## Out of Scope

- Direct SSH or Telnet access to Fiberhome OLTs.
- Provisioning, authorization, removal, VLAN changes, or other mutating
  operations during homologation.
- BNG, PPPoE, AAA, or RADIUS integration.
- Determining which of multiple learned MACs is the customer's router without
  external session evidence.
- Treating an unconfirmed vendor command as supported.

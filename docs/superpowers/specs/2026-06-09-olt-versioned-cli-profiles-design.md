# Versioned OLT CLI Profiles Design

## Goal

Introduce reusable CLI profiles so one command implementation can support
multiple homologated firmware versions from the same OLT family. Use the first
profile to modernize VSOL GPON support for firmware `V2.1.8R`, including client
diagnostics and router MAC discovery.

The design must keep the physical model, installed firmware, and CLI
implementation as separate concepts.

## Confirmed VSOL Behavior

The laboratory OLT identifies its software as `V2.1.8R`.

Its SSH session has two authentication layers:

1. SSH transport authentication with the configured username and password.
2. Internal CLI authentication, which may request `Login:` and `Password:`.

After internal authentication, the prompt is `gpon-olt>`. The session requires
`enable`, followed by the enable password, before operational GPON commands are
available. Existing code also enters `configure terminal` before executing
ONU commands.

Credentials, addresses, serial numbers, MAC addresses, and complete running
configuration captured from real equipment must not be stored in the
repository or test fixtures.

## Concepts

### OLT model

The model identifies the vendor and access technology, for example
`VSOLGPON`. It remains the high-level adapter selection used by existing
consumers.

### Firmware version

`firmwareVersion` records the version installed on one device, for example
`V2.1.8R`. It is required for migrated, version-aware models and must be
provided explicitly by the caller.

### CLI profile

`cliProfile` selects a reusable implementation of authentication, prompts,
commands, and parsers. Its identifier describes a CLI family rather than one
firmware release, for example `VSOL_GPON_CLI_V2`.

A CLI profile declares the firmware versions it has been tested against. One
profile can support multiple versions of the same OLT family. Compatibility
must use an explicit list of normalized exact versions. An unknown version is
rejected until it is homologated.

## Considered Approaches

### Generic profile registry

Create a vendor-independent registry that resolves a CLI profile and validates
its model and firmware compatibility. The profile selects the connection,
authentication strategy, commands, and parsers.

This is the selected approach because it supports future VSOL, CDATA, DATACOM,
FiberHome, and ZTE migrations without mixing firmware releases into model
constants or duplicating command implementations.

### Versioned model constants

Add constants such as `VSOLGPON_V218R`. This is simple initially, but couples a
physical model to firmware and encourages duplicate adapters for compatible
releases.

### Version-specific OLT subclasses

Create one DTO subclass per vendor or firmware family. This provides stronger
types but conflicts with the current construction pattern and introduces
unnecessary inheritance.

## Architecture

Add a generic versioning namespace containing:

- A CLI profile identifier type or constants.
- A profile registry.
- A compatibility definition containing the supported model and exact
  firmware versions.
- Explicit exceptions for missing, unknown, and incompatible profiles or
  firmware versions.

Extend the `OLT` DTO with `cliProfile` and `firmwareVersion`. Keep these fields
optional at the DTO constructor level during gradual migration so current
models remain source-compatible. A migrated adapter must require both values
and validate them before opening a network connection.

The registry resolves a profile only after confirming:

1. The profile exists.
2. The profile supports the configured OLT model.
3. The firmware version is normalized and homologated by the profile.

The first registration is:

```text
model: VSOLGPON
profile: VSOL_GPON_CLI_V2
firmware versions: V2.1.8R
```

Additional tested VSOL firmware versions can be added to the same profile
without duplicating commands. A firmware that changes authentication, prompts,
or command output gets a new profile.

## VSOL GPON Components

Create a modern `OLT/VSOL/GPON` implementation with these boundaries:

- Connection: owns SSH transport, internal CLI login, privileged mode,
  configuration mode, prompt synchronization, pagination, timeout, reconnect,
  and read-only retry behavior.
- Commands: issue one operational command and delegate parsing.
- Parsers: convert recorded CLI output into DTOs without network access.
- Discovery service: combines ONU listing and learned MAC lookup.
- Legacy adapter bridge: maps modern command results to the existing `Client`
  DTO used by `VSolOLTGPONCmd`.

The modern command set covers:

- ONU lookup and status.
- Optical RX signal and temperature.
- Distance.
- Uptime.
- Ethernet state and speed.
- ONU listing.
- Learned MAC addresses behind one ONU.
- Reverse location of a known MAC address.

Exact command strings and output grammars must be confirmed through read-only
CLI help and operational queries before implementation. Unsupported commands
must not be inferred from another vendor or firmware.

## Data Flow

The caller creates an `OLT` with model, CLI profile, and firmware version. The
adapter validates compatibility through the registry before connecting.

For client diagnostics, the adapter locates the ONU, obtains its normalized
PON and ONU identifiers, then requests only the data appropriate to its state.
Offline ONUs return their status without issuing optical or Ethernet commands
that require an online ONU.

For router MAC discovery, the service lists ONUs, optionally filters to online
entries, obtains learned MAC addresses for each ONU, normalizes them, and
removes the ONU's own identifier when it appears in the forwarding table.
Reverse lookup uses the dedicated MAC location command when supported.

## Errors And Safety

Missing profile or firmware configuration fails before network access.
Incompatible model/profile and unhomologated firmware combinations have
distinct exceptions with values safe for logs.

Authentication failures distinguish SSH transport, internal CLI login, and
enable password failures. Prompt synchronization, timeout, unsupported command,
and malformed response errors include the profile identifier but never include
credentials or complete device output.

Automatic retries are limited to commands marked read-only. Configuration
commands are not repeated after a lost session.

All discovery and validation performed against real equipment must use
read-only commands. The implementation must not persist or log real
credentials, addresses, serials, or MAC addresses.

## Compatibility And Migration

Existing non-migrated adapters continue to work without version fields. The
first strict adapter is `VSOLGPON`: it requires `cliProfile` and
`firmwareVersion`.

`VSolOLTGPONCmd` remains available as the existing public integration point,
but delegates connection and parsing to the modern profile implementation.
This preserves `OLTAdapterControl` and the current `Client` result contract.

Other manufacturers can migrate incrementally. Each migrated model adopts the
same required validation without forcing unrelated legacy adapters to change
at once.

## Verification

Add tests for:

1. Missing profile and missing firmware rejection for VSOL GPON.
2. Unknown profile, incompatible model, and unhomologated firmware rejection.
3. One CLI profile accepting multiple explicitly homologated firmware
   versions.
4. Authentication and prompt transitions using a scripted connection double.
5. Parsers for online and offline ONU status, optical diagnostics, distance,
   uptime, Ethernet state, ONU lists, learned MACs, and reverse MAC location.
6. Pagination and terminal escape sequence cleanup.
7. Legacy `Client` DTO mapping.
8. Router MAC filtering and normalization.

Fixtures must be anonymized and contain only the minimum lines required for
each parser. After unit tests pass, run a read-only smoke test against the
laboratory VSOL GPON for every confirmed command.

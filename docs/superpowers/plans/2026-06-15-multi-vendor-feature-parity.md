# Multi-Vendor OLT Feature Parity Implementation Plan

## Objective

Homologate the 13 configured OLTs with read-only probes and provide one common
feature contract for CDATA, DATACOM, ZTE, Fiberhome, VSOL EPON, and VSOL GPON.

Vendor implementations may use different commands, transports, and parsers,
but consumers must receive normalized results and explicit capability status.

## Inventory

| Vendor | Devices | Transport | Initial state |
|---|---:|---|---|
| CDATA | 1 | SSH | MAC discovery exists; profile and full parity missing |
| DATACOM | 3 | SSH | Several ONU commands exist; MAC discovery and profile missing |
| ZTE | 5 | SSH | Mature ONU diagnostics exist; MAC discovery and profile missing |
| Fiberhome | 4 | TL1 gateway only | Several TL1 queries exist; profile and MAC discovery unknown |
| VSOL EPON | 0 in inventory | Telnet | Versioned implementation exists |
| VSOL GPON | 0 in inventory | SSH | Versioned implementation exists |

The JSON files under `examples/config/olts` are ignored by Git and contain the
runtime credentials. They must never be copied into tests, documentation, or
command output.

## Execution Rules

- Run only read-only equipment commands during homologation.
- Do not infer commands from another vendor.
- Do not add a firmware to a profile until its complete supported-feature
  probe passes.
- Probe all devices even when they share a vendor.
- Group devices only after comparing firmware, authentication, commands, and
  output grammar.
- Fiberhome uses only the configured TL1 gateway in this phase.
- Commit sanitized fixtures only; never commit raw production output.
- Keep real-equipment probes out of the default automated test command.
- Implement one vendor family at a time and run the full regression suite
  after each family.

## Execution Environment

The implementation requires PHP 8.3 or newer with the Composer dependencies
and required extensions. The current host may not expose `php` directly in
`PATH`; in that case, run the documented PHP and Composer commands through the
repository Docker/Compose environment while mounting the same working tree.

Use one execution environment consistently for parser and contract tests so
extension and line-ending differences do not create profile-specific false
failures.

---

## Phase 1: Common Capability Contract

### Task 1: Add capability identifiers and result states

**Files:**

- Create: `src/Capabilities/OltFeature.php`
- Create: `src/Capabilities/OltFeatureState.php`
- Create: `src/Capabilities/OltFeatureResult.php`
- Create: `tests/olt_feature_contract_test.php`

**Steps:**

1. Define constants for:
   - connection diagnostic;
   - firmware diagnostic;
   - ONU listing and lookup;
   - status, signal, temperature, distance, and uptime;
   - Ethernet state and speed;
   - VLAN;
   - unauthorized ONUs;
   - learned MACs;
   - reverse MAC lookup;
   - router MAC discovery.
2. Define `supported`, `unavailable`, and `unsupported` states.
3. Add an immutable result wrapper containing feature, state, normalized value,
   and a safe optional reason code.
4. Reject a value on `unsupported`; permit empty normalized values on
   `supported`.
5. Test state validation and serialization without vendor dependencies.

**Verify:**

```bash
php tests/olt_feature_contract_test.php
```

### Task 2: Add normalized DTOs

**Files:**

- Create: `src/OLT/Dto/OnuIdentity.php`
- Create: `src/OLT/Dto/OnuOperationalStatus.php`
- Create: `src/OLT/Dto/OnuOpticalMetrics.php`
- Create: `src/OLT/Dto/OnuEthernetStatus.php`
- Create: `src/OLT/Dto/LearnedMacAddress.php` or extend the existing class
- Create: `src/OLT/Dto/MacLocation.php`
- Create: `tests/olt_normalized_dto_test.php`

**Steps:**

1. Normalize identifiers without losing the original vendor address:
   model, PON, ONU ID, registration ID, and state.
2. Normalize numeric units:
   receive power in dBm, temperature in Celsius, distance in meters, uptime in
   seconds when conversion is possible.
3. Define learned MAC fields:
   MAC, PON, ONU ID, VLAN, type, and UNI port.
4. Use nullable fields only for values a supported command may omit.
5. Keep compatibility with existing VSOL and CDATA DTO consumers through
   mapping rather than immediate deletion of old DTOs.
6. Test MAC normalization, unit validation, and serialization.

**Verify:**

```bash
php tests/olt_normalized_dto_test.php
```

### Task 3: Add common adapter interfaces

**Files:**

- Create: `src/OltInterfaces/OltFeatureProviderInterface.php`
- Create: `src/OltInterfaces/OnuInventoryInterface.php`
- Create: `src/OltInterfaces/OnuDiagnosticsInterface.php`
- Create: `src/OltInterfaces/OnuMacDiscoveryInterface.php`
- Create: `src/OLT/Utils/Discovery/OnuRouterMacDiscovery.php`
- Create: `tests/olt_adapter_contract_test.php`

**Steps:**

1. Define focused interfaces instead of one large vendor interface.
2. Require capability discovery before command execution.
3. Move the vendor-independent discovery algorithm into a common service:
   list online ONUs, obtain learned MACs, remove registration MAC, preserve all
   remaining candidates.
4. Keep reverse lookup separate because some vendors require bounded fallback.
5. Build a contract-test helper that every vendor adapter can execute with
   fakes.

**Verify:**

```bash
php tests/olt_adapter_contract_test.php
```

### Task 4: Extend CLI profiles and capability catalog

**Files:**

- Modify: `src/Versioning/OltCliProfileDefinition.php`
- Modify: `src/Versioning/OltCliProfileRegistry.php`
- Modify: `src/Capabilities/OltCapabilityRegistry.php`
- Modify: `src/Enum/OltCliProfile.php`
- Modify: `tests/olt_diagnostics_test.php`
- Create: `tests/olt_profile_capability_test.php`

**Steps:**

1. Add transport, default port, credential scope, and supported feature IDs to
   each profile definition.
2. Preserve exact normalized firmware matching.
3. Move model-level capability assumptions to profile-level data.
4. Keep unversioned models temporarily visible as migration-pending without
   claiming unsupported features as supported.
5. Include profile capabilities in the deterministic catalog revision.
6. Update existing VSOL profiles with their confirmed feature sets.
7. Test profile resolution, capability lookup, and deterministic catalog
   output.

**Verify:**

```bash
php tests/olt_diagnostics_test.php
php tests/olt_profile_capability_test.php
```

---

## Phase 2: Safe Homologation Runner

### Task 5: Add secure inventory loading

**Files:**

- Create: `src/Diagnostics/OltInventoryLoader.php`
- Create: `src/Diagnostics/OltInventoryEntry.php`
- Create: `tests/olt_inventory_loader_test.php`
- Create: `tests/fixtures/inventory/valid.json`
- Create: `tests/fixtures/inventory/invalid.json`

**Steps:**

1. Read the existing JSON keys:
   `id`, `oltName`, `userName`, `password`, `address`, `model`, `port`,
   `typoConnection`, `cliProfile`, `firmwareVersion`, and optional
   `tl1Server`.
2. Convert each entry to `OLT` plus safe inventory metadata.
3. Validate required fields by transport.
4. Mark passwords with `SensitiveParameter` where passed through constructors.
5. Ensure exceptions identify only filename, inventory ID, and safe field
   names.
6. Test that serialized exceptions/results do not contain credentials or
   addresses.

**Verify:**

```bash
php tests/olt_inventory_loader_test.php
```

### Task 6: Add probe definitions and allowlist

**Files:**

- Create: `src/Diagnostics/OltProbeDefinition.php`
- Create: `src/Diagnostics/OltProbeRegistry.php`
- Create: `src/Diagnostics/OltProbeStep.php`
- Create: `src/Diagnostics/OltProbeResult.php`
- Create: `tests/olt_probe_registry_test.php`

**Steps:**

1. Represent each probe step with feature ID, read-only command, parser or
   output classifier, timeout, and prerequisite.
2. Allow only:
   - `show` and confirmed non-mutating terminal commands for SSH/Telnet;
   - confirmed TL1 query verbs such as `LST-*`.
3. Reject mutating commands before connection.
4. Support prerequisites such as selecting one online ONU from inventory.
5. Add result states for supported, unsupported, unavailable, malformed, and
   transport failure.
6. Do not register guessed DATACOM, ZTE, or Fiberhome MAC commands yet.

**Verify:**

```bash
php tests/olt_probe_registry_test.php
```

### Task 7: Add sanitization and evidence recording

**Files:**

- Create: `src/Diagnostics/OltProbeSanitizer.php`
- Create: `src/Diagnostics/OltProbeSummary.php`
- Create: `tests/olt_probe_sanitizer_test.php`

**Steps:**

1. Redact IPv4/IPv6 addresses, hostnames, usernames, serials, and MACs.
2. Replace identifiers with deterministic placeholders so related fixture
   lines remain correlatable.
3. Store only selected minimal output lines, not full sessions.
4. Ensure command output is not included in thrown exceptions.
5. Test with examples containing credentials, IPs, MACs, and serial numbers.

**Verify:**

```bash
php tests/olt_probe_sanitizer_test.php
```

### Task 8: Add the read-only probe CLI

**Files:**

- Create: `examples/probe_olt_inventory.php`
- Create: `src/Diagnostics/OltInventoryProbe.php`
- Create: `tests/olt_inventory_probe_test.php`

**Steps:**

1. Support:

```text
php examples/probe_olt_inventory.php --config=examples/config/olts
php examples/probe_olt_inventory.php --id=6
php examples/probe_olt_inventory.php --model=DATACOM
php examples/probe_olt_inventory.php --summary=/tmp/olt-probe-summary.json
```

2. Default to one device at a time.
3. Add a delay between commands and a maximum command count per device.
4. Print only inventory ID, configured name, model, feature state, duration,
   profile candidate, and sanitized reason.
5. Require an explicit `--capture-sanitized=<directory>` option before writing
   evidence.
6. Disconnect in `finally`.
7. Test entirely with fake connections.

**Verify:**

```bash
php tests/olt_inventory_probe_test.php
php examples/probe_olt_inventory.php --help
```

---

## Phase 3: Baseline Homologation of 13 OLTs

### Task 9: Run connectivity and firmware probes

**Runtime input:**

- `examples/config/olts/*.json`

**Output:**

- Temporary sanitized summary under `/tmp`.
- No committed profile changes yet.

**Steps:**

1. Run the probe for each inventory ID from 1 through 13.
2. Capture:
   model, transport, credential scope, detected firmware, prompt family,
   pagination behavior, and inventory command support.
3. For Fiberhome, connect only through each configured TL1 gateway.
4. Investigate failures individually; do not continue operational probes on a
   device with failed credentials or unstable prompt synchronization.
5. Compare devices within each vendor.
6. Produce a proposed profile grouping table:
   profile candidate, inventory IDs, firmware versions, and observed
   differences.

**Verify:**

- All 13 entries have a stable diagnostic result.
- No summary contains passwords, real addresses, or complete production MACs.

### Task 10: Probe the existing feature set

**Steps:**

1. Run already implemented read-only commands for each vendor.
2. Select at most one online ONU and one offline ONU per profile candidate.
3. Record support and sanitized output shapes for:
   listing, lookup, status, signal, temperature, distance, uptime, Ethernet,
   VLAN, and unauthorized ONUs.
4. Mark a feature `unsupported` only after command help/output confirms that
   the transport does not expose it.
5. Mark a feature `unavailable` when the command exists but selected ONU state
   prevents a value.
6. Update the proposed profile grouping if output grammars differ.

**Gate:**

Do not implement MAC discovery for a vendor until its real command and
sanitized output have been confirmed.

### Task 11: Discover MAC/FDB commands

**Steps:**

1. Start with built-in CLI help or vendor-confirmed read-only command trees.
2. For each profile candidate, identify:
   - per-ONU learned MAC command;
   - global MAC table command;
   - dedicated reverse lookup command, if available;
   - VLAN, MAC type, and UNI-port fields.
3. Test one known online ONU with a learned MAC.
4. Verify whether the ONU registration MAC appears in the FDB.
5. Verify whether reverse lookup reliably identifies PON and ONU.
6. For Fiberhome, use only TL1 query verbs. If the TL1 gateway cannot expose
   FDB ownership, record `unsupported` with evidence.
7. Store minimal anonymized fixtures for each distinct output grammar.

**Gate:**

Each registered MAC command must be supported by anonymized evidence from at
least one OLT in its profile.

---

## Phase 4: CDATA Parity

### Task 12: Homologate and version CDATA

**Files:**

- Modify: `src/Enum/OltCliProfile.php`
- Modify: `src/Versioning/OltCliProfileRegistry.php`
- Modify: `src/OLT/CDATA/CDATAConnection.php`
- Modify: `tests/cdata_adapter_test.php`
- Create: `tests/cdata_profile_test.php`

**Steps:**

1. Register the observed CDATA CLI profile and exact firmware.
2. Require profile and firmware before network access.
3. Verify connected firmware with the confirmed version command.
4. Keep one read-only retry and existing prompt synchronization.
5. Replace the fixed eight-PON assumption if the real inventory reports a
   different topology.
6. Test missing profile, wrong firmware, firmware mismatch, and valid profile.

### Task 13: Complete CDATA feature adapter

**Files:**

- Create: `src/OLT/CDATA/CDataFeatureAdapter.php`
- Add focused commands/parsers under `src/OLT/CDATA/Command/` and
  `src/OLT/CDATA/DataProcessors/` only for confirmed missing features.
- Modify: `src/OLT/CDATA/OnuRouterMacDiscovery.php`
- Extend: `tests/cdata_adapter_test.php`

**Steps:**

1. Map all existing CDATA features to normalized DTOs.
2. Add confirmed missing diagnostics and unauthorized ONU support.
3. Move MAC discovery to the common service while preserving the old public
   class as a compatibility facade.
4. Add explicit unsupported results for genuine firmware limitations.
5. Run the common adapter contract.

**Verify:**

```bash
php tests/cdata_profile_test.php
php tests/cdata_adapter_test.php
php tests/olt_adapter_contract_test.php
```

---

## Phase 5: DATACOM Parity

### Task 14: Register DATACOM profiles

**Files:**

- Modify: `src/Enum/OltCliProfile.php`
- Modify: `src/Versioning/OltCliProfileRegistry.php`
- Modify: `src/OLT/DATACOM/DATACOMConnection.php`
- Create: `tests/datacom_profile_test.php`

**Steps:**

1. Create one profile per homologated DATACOM CLI family.
2. Associate the exact firmware versions observed on inventory IDs 6, 8, and
   11.
3. Add firmware verification before operational commands.
4. Test shared-profile and split-profile cases according to probe results.

### Task 15: Implement DATACOM learned MAC and reverse lookup

**Files:**

- Create confirmed command classes under `src/OLT/DATACOM/Command/`.
- Create corresponding parsers under
  `src/OLT/DATACOM/Command/DataProcessors/`.
- Create: `src/OLT/DATACOM/DatacomFeatureAdapter.php`
- Create: `tests/datacom_mac_discovery_test.php`
- Create sanitized fixtures under `tests/fixtures/datacom/`.

**Steps:**

1. Implement only the command syntax confirmed in Task 11.
2. Parse multiple learned MACs and preserve VLAN/type/UNI port.
3. Implement dedicated reverse lookup when available.
4. Otherwise implement a bounded PON/ONU fallback only if ownership can be
   proven reliably.
5. Remove ONU registration identifiers from router MAC results.
6. Test alternate output grammars per profile.

### Task 16: Complete DATACOM normalized feature mapping

**Steps:**

1. Map existing list, detail, signal, distance, VLAN, Ethernet, and
   unauthorized ONU commands.
2. Add confirmed temperature and uptime mappings where exposed.
3. Return explicit unsupported results for missing features.
4. Add the DATACOM adapter to the shared contract test.

**Verify:**

```bash
php tests/datacom_profile_test.php
php tests/datacom_mac_discovery_test.php
php tests/olt_adapter_contract_test.php
```

---

## Phase 6: ZTE Parity

### Task 17: Register ZTE profiles

**Files:**

- Modify: `src/Enum/OltCliProfile.php`
- Modify: `src/Versioning/OltCliProfileRegistry.php`
- Modify: `src/OLT/ZTE/ZTEConnection.php`
- Create: `tests/zte_profile_test.php`

**Steps:**

1. Group inventory IDs 5, 7, 10, 12, and 13 by observed CLI compatibility.
2. Register exact firmware lists.
3. Verify firmware before operational commands.
4. Preserve current prompt synchronization, pagination, and per-command cache
   rules.
5. Ensure dynamic MAC/FDB reads are not cached.

### Task 18: Implement ZTE learned MAC and reverse lookup

**Files:**

- Add confirmed command classes under `src/OLT/ZTE/Command/`.
- Add parsers under `src/OLT/ZTE/DataProcessors/`.
- Create: `src/OLT/ZTE/ZteFeatureAdapter.php`
- Create: `tests/zte_mac_discovery_test.php`
- Create sanitized fixtures under `tests/fixtures/zte/`.

**Steps:**

1. Implement the confirmed per-ONU FDB command.
2. Parse PON, ONU ID, VLAN, MAC type, and UNI port when present.
3. Implement confirmed reverse lookup or a bounded reliable fallback.
4. Filter ONU registration identifiers.
5. Test all distinct ZTE profile output grammars.

### Task 19: Complete ZTE normalized feature mapping

**Steps:**

1. Map existing list, lookup, signal, temperature, distance, Ethernet, VLAN,
   status, WAN status, and unauthorized ONU commands.
2. Add uptime only if confirmed separately from offline timestamps.
3. Do not expose provisioning commands through the read-only feature adapter.
4. Add ZTE to the shared contract test.

**Verify:**

```bash
php tests/zte_profile_test.php
php tests/zte_mac_discovery_test.php
php tests/olt_adapter_contract_test.php
```

---

## Phase 7: Fiberhome TL1 Parity

### Task 20: Register Fiberhome TL1 profiles

**Files:**

- Modify: `src/Enum/OltCliProfile.php`
- Modify: `src/Versioning/OltCliProfileRegistry.php`
- Modify: `src/Diagnostics/OltCredentialDiagnostic.php`
- Modify: `src/Diagnostics/OltFirmwareParser.php`
- Create: `tests/fiberhome_profile_test.php`

**Steps:**

1. Group inventory IDs 2, 3, 4, and 9 by TL1 response compatibility.
2. Record credential scope as `shared_gateway`.
3. Register exact firmware only if TL1 exposes a reliable version.
4. If TL1 cannot expose firmware, model this explicitly in the profile instead
   of inventing a value or opening direct device access.
5. Keep gateway authentication and OLT reachability as separate diagnostic
   concepts where the protocol permits.

### Task 21: Map existing Fiberhome TL1 features

**Files:**

- Create: `src/OLT/Fiberhome/FiberhomeFeatureAdapter.php`
- Add or modify parsers under `src/OLT/Fiberhome/Command/DataProcessors/`.
- Create: `tests/fiberhome_feature_adapter_test.php`
- Create sanitized fixtures under `tests/fixtures/fiberhome/`.

**Steps:**

1. Normalize existing ONU listing, lookup, signal, temperature, distance,
   Ethernet, VLAN, and unauthorized ONU queries.
2. Confirm whether TL1 exposes uptime and Ethernet speed.
3. Distinguish empty successful responses from unsupported TL1 operations.
4. Add Fiberhome to the shared contract test.

### Task 22: Implement or classify Fiberhome MAC discovery

**Steps:**

1. Implement learned MAC and reverse lookup only if confirmed TL1 query verbs
   expose reliable ONU ownership.
2. Add parsers and fixtures if supported.
3. Otherwise register learned MAC, reverse lookup, and router discovery as
   `unsupported` for that profile with a TL1 limitation reason.
4. Do not add SSH or Telnet fallback.

**Verify:**

```bash
php tests/fiberhome_profile_test.php
php tests/fiberhome_feature_adapter_test.php
php tests/olt_adapter_contract_test.php
```

---

## Phase 8: VSOL Alignment and Legacy Bridges

### Task 23: Adapt VSOL EPON and GPON to the common contract

**Files:**

- Create: `src/OLT/VSOL/EPON/VSolEponFeatureAdapter.php`
- Create: `src/OLT/VSOL/GPON/VSolGponFeatureAdapter.php`
- Modify existing VSOL discovery facades as needed.
- Extend:
  `tests/vsol_epon_adapter_test.php`,
  `tests/vsol_gpon_adapter_test.php`,
  `tests/olt_adapter_contract_test.php`.

**Steps:**

1. Map existing VSOL DTOs to the normalized DTOs.
2. Declare confirmed unsupported features explicitly.
3. Preserve existing adapter APIs and firmware verification.
4. Run the same contract tests used by the four newly migrated vendors.

### Task 24: Preserve legacy public adapters

**Files:**

- Modify only as required:
  `src/Adapters/CDATAOLTCmd.php`,
  `src/Adapters/DATACOMOLTCmd.php`,
  `src/Adapters/OltFiberHomeCmd.php`,
  `src/Adapters/OltFiberHomeCmdOLDVERSION.php`,
  `src/Adapters/VSolOLTCmd.php`,
  `src/Adapters/VSolOLTGPONCmd.php`,
  `src/OLTAdapterControl.php`.
- Create: `tests/legacy_adapter_regression_test.php`

**Steps:**

1. Keep constructors and existing public methods source-compatible.
2. Delegate read-only diagnostics to modern adapters where equivalent.
3. Keep mutating/provisioning behavior outside the common feature interface.
4. Add regression fixtures for existing return contracts.

**Verify:**

```bash
php tests/legacy_adapter_regression_test.php
php tests/vsol_epon_adapter_test.php
php tests/vsol_gpon_adapter_test.php
```

---

## Phase 9: Feature Matrix and Final Audit

### Task 25: Generate the capability matrix

**Files:**

- Create: `src/Capabilities/OltFeatureMatrix.php`
- Create: `examples/olt_feature_matrix.php`
- Create: `tests/olt_feature_matrix_test.php`
- Create: `docs/olt-feature-matrix.md`

**Steps:**

1. Generate rows from `OltFeature` constants.
2. Generate columns for CDATA, DATACOM, ZTE, Fiberhome, VSOL EPON, and VSOL
   GPON.
3. Derive static support from registered profiles.
4. Overlay sanitized real-probe results by profile.
5. Fail the audit when any cell is `not-tested`.
6. Document `unsupported` reasons, especially TL1 limitations.

**Verify:**

```bash
php tests/olt_feature_matrix_test.php
php examples/olt_feature_matrix.php
```

### Task 26: Run the complete offline test suite

**Commands:**

```bash
find src tests examples -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/olt_feature_contract_test.php
php tests/olt_normalized_dto_test.php
php tests/olt_profile_capability_test.php
php tests/olt_inventory_loader_test.php
php tests/olt_probe_registry_test.php
php tests/olt_probe_sanitizer_test.php
php tests/olt_inventory_probe_test.php
php tests/cdata_profile_test.php
php tests/cdata_adapter_test.php
php tests/datacom_profile_test.php
php tests/datacom_mac_discovery_test.php
php tests/zte_profile_test.php
php tests/zte_mac_discovery_test.php
php tests/fiberhome_profile_test.php
php tests/fiberhome_feature_adapter_test.php
php tests/vsol_epon_adapter_test.php
php tests/vsol_gpon_adapter_test.php
php tests/legacy_adapter_regression_test.php
php tests/olt_adapter_contract_test.php
php tests/olt_feature_matrix_test.php
composer validate --no-check-publish
git diff --check
```

### Task 27: Run final read-only smoke tests on all 13 OLTs

**Steps:**

1. Run the inventory probe sequentially across all entries.
2. Confirm configured firmware matches detected firmware where detectable.
3. Execute every supported feature once per profile and inventory diagnostics
   once per device.
4. Confirm all devices assigned to the same profile still parse correctly.
5. Generate the sanitized final matrix.
6. Confirm no `not-tested` cells remain.
7. Search tracked changes for leaked credentials, production IPs, serials, and
   MACs.

**Completion criteria:**

- All 13 OLTs have stable read-only diagnostic results.
- Every OLT is assigned to a confirmed profile or has a documented blocking
  transport limitation.
- Every vendor passes the common adapter contract.
- Every available vendor feature is implemented.
- Every unavailable platform feature is explicitly `unsupported`.
- Existing VSOL and legacy adapter tests pass.
- No production secret or raw equipment output is tracked.

## Suggested Commit Sequence

1. `add normalized olt feature contract`
2. `add safe olt inventory probe`
3. `homologate olt cli profile families`
4. `add versioned cdata feature adapter`
5. `add datacom mac discovery and feature parity`
6. `add zte mac discovery and feature parity`
7. `add fiberhome tl1 feature parity`
8. `align vsol adapters with common feature contract`
9. `add multi-vendor olt feature matrix`
10. `document olt homologation results`

# VSOL EPON Telnet Support Implementation Plan

## Objective

Implement the approved VSOL EPON Telnet design for profile
`VSOL_EPON_CLI_V1`, firmware `V1.01.51_230922190137`, while preserving the
existing `VSolOLTCmd` public adapter contract.

## Tasks

1. Extend the version registry with the VSOL EPON profile and exact homologated
   firmware.
2. Add a Telnet connection abstraction that handles login, enable,
   configuration mode, PON mode, terminal cleanup, firmware verification,
   disconnect, and one read-only retry.
3. Add EPON parsers and DTOs for ONU status, optical diagnostics, Ethernet
   state, global MAC location, and per-ONU learned MACs.
4. Add commands for ONU lookup/listing, optical diagnostics, per-ONU MACs,
   reverse MAC location, and Ethernet state.
5. Add router MAC discovery that excludes the ONU registration MAC.
6. Replace the internals of `VSolOLTCmd` with the new implementation while
   retaining its constructor and `getDadosDoCliente()` behavior.
7. Add fixture-based tests using a fake EPON connection.
8. Add an environment-variable-based example and README documentation.
9. Run syntax checks, EPON tests, existing GPON and CDATA tests, Composer
   validation, credential scans, and a read-only smoke test on the homologated
   device.

## Constraints

- Do not include real network addresses, credentials, serials, or MACs in
  source, fixtures, documentation, or test output.
- Do not modify or revert unrelated work already present in the working tree.
- Retry only read-only operations.
- Return `N/A` for Ethernet speed because the confirmed CLI output does not
  expose negotiated speed.

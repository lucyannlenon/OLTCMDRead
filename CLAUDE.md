# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`llenon/olt-information` is a PHP 8.1+ library for querying ONU (Optical Network Unit) data from OLT (Optical Line Terminal) equipment. It supports VSOL, VSOLGPON, CDATA, DATACOM, FIBERHOME, and FIBERHOMEOLDVERSION devices over SSH, Telnet, and TL1 protocols.

## Commands

```bash
# Install dependencies
composer install

# Rebuild autoloader after adding/moving classes
composer dump-autoload

# Syntax-check a single file
php -l src/Adapters/VSolOLTCmd.php

# Run an example script (requires real OLT access)
php examples/oltVsolution.php
```

No test suite currently exists. The `tests/` directory and PHPUnit dependency would need to be added before writing tests.

## Architecture

There are two parallel layers. New vendor integrations should follow the newer Command/Connection layer.

### Legacy Adapters Layer (`src/Adapters/`)

The original approach. `OLTAdapterControl` is the single entry point: it reads `OltModel::ADAPTERS` (a string→class map) to instantiate the right adapter based on `OLT->model`, then delegates `getDadosDoCliente()` to it.

Each adapter (`VSolOLTCmd`, `CDATAOLTCmd`, `DATACOMOLTCmd`, `OltFiberHomeCmd`, `OltFiberHomeCmdOLDVERSION`) follows the same pattern:
1. In `__construct`: call `OltModel::getSerive()` to get a `meklis/console-client` SSH or Telnet instance, call `OltModel::getHelper()` to attach a vendor prompt helper, then connect and authenticate.
2. In `getDadosDoCliente()`: send CLI commands, parse output with regex, populate the `Client` DTO, disconnect, and return.

### Newer Command/Connection Layer (`src/OLT/{ZTE,DATACOM,Fiberhome}/`)

A more structured approach:

- **Vendor connection wrappers** (`ZTEConnection`, `DATACOMConnection`, `FiberhomeConnection`) implement `ConnectionInterface` and wrap `SSHConnection` (phpseclib3) or `TL1Connection` (raw TCP socket). They handle `--More--` pagination and per-vendor result cleanup internally.
- **Command classes** extend `AbstractCommand` (`src/OLT/Utils/Command/AbstractCommand.php`), which pairs a `ConnectionInterface` with a `StringParserInterface`. Each command defines `getCommand(): string` and exposes an `execute()` method.
- **DataProcessors** are standalone `StringParserInterface` implementations that parse raw CLI output strings into arrays of DTOs.

### Connection Types

| Class | Transport | Used by |
|---|---|---|
| `Console\SSH` | `ext-ssh2` shell (via `meklis/console-client`) | Legacy adapters |
| `Meklis\Network\Console\Telnet` | Telnet (via `meklis/console-client`) | Legacy adapters |
| `SSHConnection` | phpseclib3 (`SSH2`) | `ZTEConnection`, `DATACOMConnection` |
| `TL1Connection` | Raw TCP socket to port 3337, TL1 protocol | `FiberhomeConnection` (FiberHome only) |

`TL1Connection` has a short-lived TTL cache for repeated `LST-ONUSTATE` / `LST-UNREGONU` commands. `ZTEConnection` caches `show pon onu information` and related read commands similarly.

### Key DTOs

- **`DTO\OLT`**: Connection parameters — `ip`, `port`, `model`, `userName`, `password`, `serviceCommunication` (`"ssh"` or `"telnet"`), `nome` (device hostname used as CLI prompt).
- **`DTO\Client`**: The populated result — `login`, `macAddress`, `gponName` (inputs); `slot`, `pon`, `onuPosition`, `signal`, `status`, `distance`, `uptime`, `onuTemperatura` (outputs).
- **`OLT\Dto\Onu`**: Used by the newer layer for list-ONU operations — `pon`, `id`, `gponId`, `state`, `offlineTimes`, etc.

### SNMP Layer (`src/SNMP/`)

An optional parallel path for listing ONUs. `HybridOnuLister` tries SNMP first (via `SnmpPool`, which spawns `snmpwalk`/`snmpget` processes in parallel), then falls back to SSH commands for ZTE and DATACOM. OID mappings are in `src/config/snmp_oids.php`; the file currently contains only commented-out placeholders — populate it per-deployment.

## Adding a New Vendor

1. Create `src/OLT/{Vendor}/VendorConnection.php` implementing `ConnectionInterface` (wrap `SSHConnection` or `TL1Connection`).
2. Create command classes under `src/OLT/{Vendor}/Command/` extending `AbstractCommand`.
3. Create parsers under `src/OLT/{Vendor}/Command/DataProcessors/` implementing `StringParserInterface`.
4. If the legacy `getDadosDoCliente()` interface is needed, add an adapter in `src/Adapters/` and register it in `OltModel::ADAPTERS`.

## Conventions

- PHP 8.1+; use strict typing and named arguments where already adopted in nearby code.
- Namespace root: `LLENON\OltInformation\`, one class per file, PSR-4.
- Commit messages are short lowercase imperatives: `"add …"`, `"fix …"`, `"modify …"`.
- Use placeholders (`xxx`, `192.168.x.x`) in examples — never real credentials or IPs.
- `OLT->nome` must match the actual CLI hostname prompt (e.g. `"router01"` produces prompt `router01#`) — `ZTEConnection` and `DATACOMConnection` rely on this to detect end-of-output.

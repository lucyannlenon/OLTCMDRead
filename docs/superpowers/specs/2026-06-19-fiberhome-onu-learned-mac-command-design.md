# Fiberhome ONU Learned-MAC TL1 Command

## Context

`llenon/olt-information` exposes learned client-MAC discovery for CDATA, DATACOM,
ZTE, VSOL EPON and VSOL GPON through per-vendor `ListOnuMacAddressCommand`
classes that return `list<LLENON\OltInformation\OLT\Dto\LearnedMacAddress>`.

Fiberhome is the only configured vendor without this capability:

- `FiberhomeFeatureAdapter::learnedMacs()` returns `unsupported(OltFeature::LEARNED_MACS)`.
- `docs/olt-feature-matrix.md` lists `learned_macs`, `reverse_mac_lookup` and
  `router_mac_discovery` as **unsupported** for FIBERHOME.
- `docs/fiberhome-tl1-homologation.md` states the learned-MAC table has **no
  confirmed TL1 command** yet.

A consumer (`olt-service`) needs to query the client MACs learned behind a single
Fiberhome ONU so it can populate account-link data, exactly as it already does
for the other vendors. This spec defines the **command contract** the library must
add. It deliberately leaves the exact TL1 verb and raw response format as a
**homologation task** to be filled in against a real OLT — the rest of the
contract (class names, signatures, return type, normalization) is fixed so the
consumer can be written against it now.

## Goal

Add a `ListOnuMacAddressCommand` for Fiberhome that, given one ONU's technical
location, returns the MAC addresses learned behind that ONU as a
`list<LearnedMacAddress>`, following the existing per-vendor command pattern.

## Non-Goals

- No reverse MAC lookup (`locateMac`) for Fiberhome.
- No OLT-wide / batch router-MAC discovery for Fiberhome.
- No change to the shared `LearnedMacAddress` DTO or to other vendors.
- No change to `FiberhomeConnection` (TL1 access stays via the shared gateway).

## How Fiberhome addresses an ONU in TL1

Existing Fiberhome TL1 commands address an ONU by **PON + ONU MAC**, e.g.
`SignalOnuCommand`:

```
LST-OMDDM::OLTID={oltIp},PONID={pon},ONUIDTYPE=MAC,ONUID={onuMac}:CTAG::;
```

- `OLTID` is the OLT IP (`FiberhomeConnection::getIpOlt()`).
- `PONID` is the PON slot string (e.g. `NA-NA-11-2` or `1/1/5`, as returned by
  `ListOnuCommand`).
- `ONUID` is the ONU's registration MAC (the value stored as `gponId`/`getGponId()`).

The new MAC command MUST identify the ONU the same way.

## Required Public Contract (fixed — implement exactly)

### Command class

```
namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

final class ListOnuMacAddressCommand extends AbstractTL1Command
{
    public function __construct(FiberhomeConnection $connection);

    /**
     * @param string $pon     PON slot string as returned by ListOnuCommand (PONID).
     * @param string $onuMac  ONU registration MAC (gponId / ONUID).
     * @return list<\LLENON\OltInformation\OLT\Dto\LearnedMacAddress>
     */
    public function execute(string $pon, string $onuMac): array;

    protected function getCommand(): string;
}
```

Constraints:

- Extends `AbstractTL1Command`, constructed with a dedicated parser (below),
  matching every other Fiberhome TL1 command.
- `execute()` stores `$pon`/`$onuMac`, calls `$this->exec()`, and returns the
  parsed list. It MUST NOT throw on an empty/`COMPLD` response with zero rows —
  it returns `[]`.
- `execute()` MAY throw `\InvalidArgumentException` if `$pon` or `$onuMac` is
  blank after `trim()`.
- Each returned `LearnedMacAddress` is built with:
  - `macAddress`: the learned MAC (the `LearnedMacAddress` constructor
    normalizes it to `AA:BB:...` and throws on non-12-hex input).
  - `vlan`: VLAN string if the response carries one, else `''`.
  - `pon`: the `$pon` argument.
  - `onuId`: the `$onuMac` argument (Fiberhome's ONU identifier is the MAC).
  - `type`: `'dynamic'` unless the response distinguishes static/dynamic.
  - `gemIndex` / `gemId` / `uniPort`: set if derivable from the response, else `null`.
- **The ONU's own registration MAC (`$onuMac`) MUST be excluded** from the result
  if the device echoes it as a learned entry. (The consumer also guards against
  this, but the command should not emit the ONU MAC as a client MAC.)

### Parser class

```
namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

final class LearnedMacAddressStringParser implements
    LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface
{
    /** @return list<array{...}>  intermediate rows, or DTOs — see note */
    public function parse(string $input): array;
}
```

Follow the existing Fiberhome parser style (`ListOnuStringParser`): iterate raw
lines, skip header/footer/`COMPLD`/`EN=...` framing lines, and extract one record
per learned MAC. Keep parsing tolerant: an unrecognized line is skipped, never
fatal. (Whether the parser emits intermediate arrays that `execute()` maps to
`LearnedMacAddress`, or emits DTOs directly, is the implementer's choice — match
whichever sibling vendor parser is cleanest; CDATA's
`LearnedMacAddressStringParser` is the closest reference.)

## HOMOLOGATION TASK (fill in against a real Fiberhome OLT)

These two items are unknown today and must be captured from a live device. They
are the **only** parts the library implementer needs to discover; everything
above is fixed.

### 1. The TL1 command template — `getCommand()`

Candidate forms to validate (pick the one the firmware actually answers; record
the result in `docs/fiberhome-tl1-homologation.md`):

```
LST-MAC::OLTID={oltIp},PONID={pon},ONUIDTYPE=MAC,ONUID={onuMac}:CTAG::;
LST-ONUMAC::OLTID={oltIp},PONID={pon},ONUIDTYPE=MAC,ONUID={onuMac}:CTAG::;
LST-MACADDR::OLTID={oltIp},PONID={pon},ONUIDTYPE=MAC,ONUID={onuMac}:CTAG::;
```

Document: the exact verb that returns the learned/host MAC table for one ONU,
whether it requires `ONUPORT`/`EPORTID`, and whether it is per-ONU or per-PON
(if only per-PON exists, `execute()` still filters to `$onuMac` and the consumer
keeps calling it per ONU).

### 2. A raw sample response

Paste a real `COMPLD` response containing at least two learned MACs (ideally
across two VLANs) into `docs/fiberhome-tl1-homologation.md`, plus one empty
(zero-MAC) response and one `DENY`/error response. The parser and its unit test
fixtures are written against these captures.

## Testing Strategy (library side)

Add `tests/fiberhome_onu_mac_command_test.php` (matching the repo's existing
standalone test style, e.g. `datacom_mac_discovery_test.php` /
`zte_mac_discovery_test.php`), driven by a fake `FiberhomeConnection`/response:

- parses a multi-MAC sample into the correct number of `LearnedMacAddress` rows
  with normalized MACs, correct `pon`/`onuId`
- collapses nothing at parser level but excludes the ONU's own `$onuMac`
- returns `[]` for an empty `COMPLD` response (no throw)
- skips malformed lines without throwing
- `execute()` throws `\InvalidArgumentException` on blank `$pon` or `$onuMac`
- discards rows whose MAC is not 12 hex chars (constructor guard) — verify the
  parser drops them before constructing the DTO, so one bad line does not abort
  the whole result

Update `docs/olt-feature-matrix.md` to flip `learned_macs` for FIBERHOME from
`unsupported` to `supported` only **after** homologation confirms the command,
and reflect the verified command in `FiberhomeFeatureAdapter::learnedMacs()` if
that adapter path is also wired (optional; the consumer uses the command class
directly).

## Consumer Expectation (informational — implemented in `olt-service`)

`olt-service` will add a `FiberhomeClientMacDiscoveryAdapter` that does, per ONU:

```php
$connection = new FiberhomeConnection($address, $tl1Server, $tl1User, $tl1Pass);
$command    = new ListOnuMacAddressCommand($connection);
$learned    = $command->execute($onu->getPon(), $onu->getGponId()); // list<LearnedMacAddress>
```

So the published signature `execute(string $pon, string $onuMac): list<LearnedMacAddress>`
must remain stable once shipped. Any change to it is a breaking change for
`olt-service`.

# ONT Router/WAN MAC Fallback for Router Discovery

## Context

`router_mac_discovery` today assumes the useful customer MAC is learned behind
the ONU. The shared discovery flow lists ONUs, reads `learned_macs`, and removes
the ONU registration MAC when present.

That works for bridge-style installations, but it misses a real class of
deployments: ONT devices operating in router/WAN mode. In these cases the ONU
may expose no downstream learned MACs because the WAN identity is effectively
the ONU's own MAC. When that happens, `learned_macs` is empty or contains only
the ONU MAC, and `router_mac_discovery` returns no candidate even though a
useful router/WAN MAC is known implicitly.

The library should support this case, but without weakening the meaning of
`learned_macs`.

## Goal

Add a future fallback rule to `router_mac_discovery`:

- if no learned client MAC remains after normal filtering
- and the ONU is identified as an ONT operating in router/WAN mode
- then the discovery result may return the ONU's own MAC as the router/WAN MAC
  candidate

## Non-Goals

- Do not change the meaning of `learned_macs`.
- Do not inject ONU MAC fallback into reverse MAC lookup.
- Do not create one generic heuristic shared blindly across all vendors.
- Do not treat every ONT as router/WAN by default.

## Decision

The fallback applies only to `router_mac_discovery`.

`learned_macs` remains a raw technical view of the MAC table:

- if the table is empty, it returns `[]`
- if the table contains only the ONU registration MAC, it returns exactly what
  the device reported
- no synthetic fallback entry is added there

`router_mac_discovery` becomes the only interpreted layer allowed to emit the
ONU registration MAC as a candidate, and only when a vendor-specific
router/WAN-mode heuristic confirms the case.

## Why This Boundary

`learned_macs` is consumed as diagnostic truth. If it starts returning a
synthetic fallback MAC, callers lose the ability to distinguish:

- the OLT really learned a downstream client MAC
- the ONU itself is the effective WAN endpoint

`router_mac_discovery` is already an inference layer, so the fallback belongs
there.

## Required Future Behavior

Given one ONU during router discovery:

1. Read the ONU's learned MAC list.
2. Remove the ONU registration MAC from that list when present.
3. If one or more MACs remain, return those MACs exactly as today.
4. If no MAC remains, evaluate a vendor-specific `router/WAN evidence` rule.
5. If that rule confirms router/WAN mode, return the ONU registration MAC as a
   fallback candidate.
6. If there is no such evidence, return an empty candidate list.

## Vendor Strategy

This must be implemented per vendor.

There is no safe generic cross-vendor rule because:

- ONT vs ONU naming is inconsistent
- some vendors expose model/type but not WAN mode
- some vendors expose WAN/IP/service state only in vendor-specific commands
- some vendors can be ONT hardware while still working in bridge mode

Each supported vendor needs its own evidence adapter or command path.

## Proposed Architecture

Introduce a vendor-aware evidence step used only by `router_mac_discovery`.

Suggested shape:

```php
interface OnuRouterWanEvidenceInterface
{
    public function routerWanEvidence(OnuIdentity $onu): OltFeatureResult;
}
```

Where the result answers:

- `supported` with `true`: vendor confirms router/WAN mode
- `supported` with `false`: vendor checked and did not confirm router/WAN mode
- `unavailable`: vendor supports the idea but runtime evidence could not be read
- `unsupported`: vendor has no implemented evidence path yet

Then the shared discovery flow can do:

1. normal learned-MAC discovery
2. if no MAC remains, ask the vendor evidence provider
3. if `true`, emit the ONU registration MAC as fallback

## Data Contract

When the fallback is used, `router_mac_discovery` should still return a normal
`LearnedMacAddress`-shaped item so callers do not need a second result format.

The fallback item should use:

- `macAddress`: ONU registration MAC
- `pon`: ONU PON
- `onuId`: ONU id
- `vlan`: best available VLAN if known, else `''`
- `type`: a distinct marker such as `router_wan_fallback`
- optional fields only when they are already derivable

This marker is important so downstream systems can distinguish:

- learned downstream MACs
- inferred ONU-as-WAN fallback MACs

## Evidence Requirements

A vendor may only emit the fallback when it has evidence of router/WAN mode,
not merely ONT hardware identity.

Examples of acceptable evidence:

- WAN/IP interface command showing routed WAN state
- NAT/router-mode service profile
- explicit WAN service binding for the ONU
- vendor command proving routed subscriber mode

Examples of insufficient evidence:

- model name contains `ONT`
- ONU has Ethernet ports
- ONU is online
- learned MAC table is empty

## Vendor Work Items

Each vendor implementation must answer two questions:

1. How do we identify that this device is a routed ONT/WAN case?
2. Which existing command gives the most reliable evidence?

Expected status by vendor:

- CDATA: pending RFC follow-up
- DATACOM: pending RFC follow-up
- ZTE: pending RFC follow-up
- Fiberhome TL1: pending RFC follow-up
- VSOL EPON: pending RFC follow-up
- VSOL GPON: pending RFC follow-up

Each vendor should get its own short design note or implementation section once
the exact command path is known.

## Testing Requirements

Future implementation must add tests for the shared discovery flow:

- normal bridge case: downstream MACs exist, ONU MAC is removed
- empty learned-MAC case with no evidence: returns empty list
- empty learned-MAC case with positive router/WAN evidence: returns ONU MAC as
  fallback
- mixed case where learned list contains only the ONU MAC and evidence is
  positive: still returns exactly one fallback candidate
- evidence unsupported/unavailable: no fallback

Vendor-specific tests must also prove the evidence command path.

## Risks

- False positives would misidentify ONU registration MAC as customer router MAC.
- Vendors may expose partial WAN state that looks routed but is not customer WAN.
- Some environments may use hybrid service profiles that require more than one
  command to classify safely.

Because of this, the implementation must default to no fallback unless evidence
is explicit.

## Rollout Guidance

Implement in stages:

1. Add the shared fallback hook to `router_mac_discovery`.
2. Enable it for one vendor only after command evidence is confirmed.
3. Expand vendor by vendor with explicit tests and docs.

Do not enable a global default.

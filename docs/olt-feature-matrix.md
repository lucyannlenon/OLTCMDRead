# OLT Feature Matrix

This file is generated from the registered CLI profiles with:

```bash
php examples/olt_feature_matrix.php
```

`supported` means a confirmed profile exposes the feature. `unsupported`
means the registered transport/profile does not currently expose it. Runtime
values can still be `unavailable` for an offline ONU.

Fiberhome is limited to the configured shared TL1 gateway. Gateway access and
authentication were validated for all four configured Fiberhome OLTs on
June 15, 2026. Firmware remains unsupported. Per-ONU learned-MAC lookup is now
validated through `LST-PORTMACADDRESS`, while reverse MAC lookup and router MAC
discovery remain unsupported.

See [Fiberhome TL1 homologation](fiberhome-tl1-homologation.md) for the
validation result and current limitations.

| Feature | CDATA | DATACOM | ZTE | FIBERHOME | VSOL | VSOLGPON |
|---|---|---|---|---|---|---|
| connection_diagnostic | supported | supported | supported | supported | supported | supported |
| firmware_diagnostic | supported | supported | supported | unsupported | supported | supported |
| onu_list | supported | supported | supported | supported | supported | supported |
| onu_lookup | supported | supported | supported | supported | supported | supported |
| onu_status | unsupported | supported | supported | supported | supported | supported |
| optical_signal | unsupported | supported | supported | supported | supported | supported |
| temperature | unsupported | supported | supported | supported | supported | supported |
| distance | unsupported | supported | supported | supported | supported | supported |
| uptime | unsupported | unsupported | unsupported | unsupported | supported | supported |
| ethernet_state | unsupported | supported | supported | supported | supported | supported |
| ethernet_speed | unsupported | unsupported | unsupported | unsupported | unsupported | supported |
| vlan | unsupported | supported | supported | supported | unsupported | unsupported |
| unauthorized_onus | unsupported | supported | supported | supported | unsupported | unsupported |
| learned_macs | supported | supported | supported | supported | supported | supported |
| reverse_mac_lookup | supported | supported | supported | unsupported | supported | supported |
| router_mac_discovery | supported | supported | supported | unsupported | supported | supported |

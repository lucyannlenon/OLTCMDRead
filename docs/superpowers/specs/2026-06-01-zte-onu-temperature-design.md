# ZTE ONU Temperature Design

## Goal

Return the live ONU temperature for ZTE devices through the existing `temp`
field. The value must preserve the CLI unit using the normalized format
`44.133(C)`.

## Confirmed CLI Behavior

The read-only command below was executed successfully against a laboratory ONU:

```text
show gpon remote-onu interface pon gpon_onu-<pon>:<onu>
```

The response includes a line in this format:

```text
Temperature:                 44.133(C)
```

## Considered Approaches

### Dedicated temperature command

Add a ZTE `TemperatureOnuCommand` with a parser focused only on the
`Temperature:` line. This follows the existing ZTE command structure, keeps the
signal contract unchanged, and limits the implementation scope.

This is the selected approach.

### Replace the signal command with an optical diagnostics command

Use the same diagnostics output to extract both signal and temperature. This
could reduce one CLI command, but it would change the established live signal
path and increase regression risk.

### Add temperature extraction to an unrelated detail command

Reuse the existing details flow. This would couple unrelated output formats and
make the parser harder to understand. The current details command does not
return the required temperature line.

## Design

Add `LLENON\OltInformation\OLT\ZTE\Command\TemperatureOnuCommand`. It executes
the confirmed diagnostics command for the selected PON and ONU ID and returns
the first parsed value. If the temperature is absent, it returns
`No Response`, matching the defensive behavior used by ZTE distance reads.

Add `LLENON\OltInformation\OLT\ZTE\DataProcessors\TemperatureStringParser`. It
scans the multiline diagnostics output for `Temperature:` and returns values in
the normalized `<number>(C)` format. The parser accepts optional whitespace
around the value and unit but does not interpret other diagnostics fields.

Update the HTTP service `ZteOnuInfoAdapter` to replace `temp => null` with a
live temperature read. The temperature must not be cached because it is a
dynamic optical diagnostic value.

## Verification

1. Run `php -l` for each added or modified PHP file.
2. Execute `TemperatureOnuCommand` against the laboratory ONU.
3. Confirm that the result is exactly in the form `44.133(C)`.
4. Confirm that the existing signal command still returns only the normalized
   signal value.


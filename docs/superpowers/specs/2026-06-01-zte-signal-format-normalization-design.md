# ZTE Signal Format Normalization Design

## Goal

Normalize ZTE optical signal responses so callers receive only the signal value
with a stable unit suffix.

## Scope

Change only `src/OLT/ZTE/DataProcessors/SignalStringParser.php`.

## Behavior

The parser accepts signal lines such as:

```text
gpon_onu-1/3/1:1 -24.682(dbm)
gpon_onu-1/3/1:1 -24.682 dBm
gpon_onu-1/3/1:1 -24.682
```

For each recognized numeric signal, it returns:

```text
-24.682(dbm)
```

The parser removes the ONU identifier and normalizes the unit suffix to
lowercase `(dbm)`. If a line does not contain a recognized numeric signal, it
preserves the trimmed line instead of silently discarding unexpected output.

## Verification

1. Run PHP syntax validation for the parser.
2. Execute local parser cases for `(dbm)`, `dBm`, and missing-unit responses.
3. Confirm an unexpected line remains visible to callers.

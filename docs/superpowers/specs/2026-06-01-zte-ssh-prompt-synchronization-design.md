# ZTE SSH Prompt Synchronization Design

## Goal

Remove redundant SSH waits from every ZTE command without changing the public
command API or risking duplicate configuration changes.

The current `ZTEConnection::runCommand()` reads the configured OLT name as a
prompt before each write. The previous response read normally consumed that
prompt already, so the next pre-read can wait until timeout. The configured OLT
name can also be a display label instead of the real terminal hostname.

## Scope

Change only `src/OLT/ZTE/ZTEConnection.php`.

The improvement applies to all consumers of `ZTEConnection`, including ONU
information, signal, lists, provisioning, and removal. Existing command
classes and parsers keep their current API.

## Session Initialization

On first use:

1. Open the SSH connection lazily through `SSHConnection`.
2. Read the initial terminal prompt once using a generic prompt detector.
3. Extract and store the complete real prompt returned by the OLT.
4. Execute `terminal length 0` through the normal command path.

The stored prompt is the exact terminal prompt captured after login. It is not
derived from `OLT::$nome`.

## Command Execution

For each command:

1. Check the existing per-command cache.
2. Write the command immediately, without reading a prompt first.
3. Read until either `--More--` or the stored prompt is received.
4. While pagination is active, write a space and continue reading.
5. Remove pagination artifacts, the echoed command, and the stored prompt from
   the returned result.
6. Store cacheable results using the existing TTL policy.

The final prompt detector must use the stored exact prompt. A broad `[#>]`
terminator must not be used for normal command execution because multiline
configuration blocks can emit intermediate prompts such as `(config)#`.

## Recovery Policy

If command execution expires or completes without the expected final prompt:

1. Discard the current SSH connection and its stored prompt.
2. Open a fresh SSH connection, capture its initial prompt, and execute
   `terminal length 0`.
3. For read-only commands whose trimmed text starts with `show `, repeat the
   command once on the fresh session.
4. For every other command, return an explicit exception after
   reconnecting without repeating the command.

This distinction avoids duplicate provisioning or removal when the OLT applied
a configuration change but its response was lost.

If reconnection also fails, return an explicit exception. Do not loop
indefinitely.

## Error Handling

- Treat an empty initial prompt as initialization failure.
- Treat timeout or missing final prompt as synchronization failure and discard
  that session.
- Preserve the existing `--More--` behavior.
- Do not silently return partial output after synchronization failure.
- Keep connection timeout configuration controlled by `setTimeout()`.

## Verification

1. Run PHP syntax validation for `ZTEConnection.php`.
2. Run `examples/zte_onu_info_diagnostic.php both` against a test OLT.
3. Confirm the current mode shows redundant waits and the candidate mode does
   not.
4. Measure a cold `POST /api/onu/info` request in `olt-service`.
5. Run read-only list and signal commands.
6. Run one controlled provisioning and removal cycle against a test ONU.
7. Confirm a simulated synchronization failure retries a `show ...` command at
   most once and does not repeat a mutating command.

## Expected Result

Cold ZTE operations should take approximately the actual SSH login and OLT
execution time, without accumulated per-command timeout waits.

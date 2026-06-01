# ZTE ONU Info Performance Diagnostic

## Context

The `olt-service` endpoint `POST /api/onu/info` returned the expected ZTE ONU
data, but a live request took `100.90s`. A repeated request took `100.61s`.
Caching does not solve the underlying latency because short-lived entries can
expire while the first slow request is still running.

This document describes the previous library protocol, the diagnostic script,
and the implemented change in `src/OLT/ZTE/ZTEConnection.php`.

## Previous Request Flow

The service requests five fields sequentially through the same
`ZTEConnection`:

1. Signal: `show pon power onu-rx`
2. Distance: `show gpon onu distance`
3. Ethernet: `show gpon remote-onu interface eth`
4. VLAN: `show gpon remote-onu service`
5. Extra details: `show gpon onu detail-info`

Before the first field, `ZTEConnection::ensureInitialized()` also executes:

```text
terminal length 0
```

For each command, `ZTEConnection::runCommand()` previously performed:

```text
read(configured OLT name + "#")
write(command)
read("--More--" or configured OLT name + "#")
```

## Suspected Causes

Two independent waits need to be measured:

1. The response read normally consumes the final CLI prompt. Before the next
   command, the library tries to read that prompt again. With no prompt left in
   the SSH buffer, this can wait until the `10s` timeout.
2. The library builds the expected CLI prompt from `OLT::$nome`. In the service
   payload, this can be a display name such as `OLT - DISPLAY NAME`, which is not
   guaranteed to match the actual ZTE CLI prompt. If it differs, both reads can
   wait until timeout even when the OLT answered immediately.

The cache in `ZTEConnection` can reduce repeated commands only inside one
connection instance. It does not remove either protocol wait on a cold read.
Signal is intentionally excluded from caching: `show pon power onu-rx` must run
for every ONU info request so the returned optical reading is live.

## Diagnostic Script

`examples/zte_onu_info_diagnostic.php` runs read-only commands and prints:

- SSH login duration
- duration of every `read()` and `write()`
- response byte counts
- the last non-empty line of each response to reveal the actual CLI prompt
- total time for each mode

It never prints credentials or complete command responses.

The modes are:

- `current`: reproduces the current library protocol exactly.
- `candidate`: synchronizes the prompt once after login with a generic prompt
  ending in `#` or `>`, then writes each command directly and consumes the
  response.
- `both`: runs both modes using separate SSH sessions.

## Run

From `/var/srv/workspace/php/OLTCMDRead`, define the connection parameters:

```bash
export ZTE_HOST='10.x.x.x'
export ZTE_PORT='22'
export ZTE_USERNAME='zte-user'
read -rsp 'ZTE password: ' ZTE_PASSWORD
export ZTE_PASSWORD
export ZTE_OLT_NAME='configured display name'
export ZTE_PON='1/3/4'
export ZTE_ONU_ID='3'
export ZTE_TIMEOUT='10'
```

Run both protocols:

```bash
php examples/zte_onu_info_diagnostic.php both
```

Run only one protocol when iterating:

```bash
php examples/zte_onu_info_diagnostic.php current
php examples/zte_onu_info_diagnostic.php candidate
```

If PHP is not installed on the host, use the `php` container from
`../olt-service`. Pass the exported environment variables into the container:

```bash
docker compose -f ../olt-service/docker-compose.yml exec \
  -e ZTE_HOST -e ZTE_PORT -e ZTE_USERNAME -e ZTE_PASSWORD \
  -e ZTE_OLT_NAME -e ZTE_PON -e ZTE_ONU_ID -e ZTE_TIMEOUT \
  -T php sh -lc \
  'cd /deps/olt-information && php examples/zte_onu_info_diagnostic.php both'
```

## Expected Evidence

The output should answer:

1. Does `pre_read_configured_prompt` take close to `ZTE_TIMEOUT` after the first
   command?
2. Does the response tail contain a CLI prompt different from
   `ZTE_OLT_NAME + "#"`?
3. Does `candidate` remove the repeated timeout waits while returning response
   bytes for every command?
4. Does any specific command still take materially longer after protocol waits
   are removed?

## Diagnostic Result

The read-only candidate mode confirmed the hypothesis against a live test ONU:

```text
configured OLT name: configured-display-name
actual CLI prompt:   actual-cli-hostname#
candidate total:     0.978s
```

The configured display name did not match the actual CLI hostname. The
candidate mode completed SSH login, paging setup, and the five ONU info
commands without accumulated timeout waits.

## Implemented Library Change

`ZTEConnection` now:

1. Synchronizes the CLI prompt once immediately after SSH login.
2. Stores the exact prompt returned by the OLT instead of deriving it from
   `OLT::$nome`.
3. Stops reading a prompt before every command.
4. Matches normal response completion against the stored exact prompt.
5. Keeps `--More--` handling and `terminal length 0`.
6. Reconnects after synchronization failure and retries read-only `show ...`
   commands at most once.
7. Does not repeat mutating commands automatically after synchronization
   failure.

Using `ZTEConnection` directly against the same live ONU, the five information
queries completed in `1.293s`, including SSH login and initialization.

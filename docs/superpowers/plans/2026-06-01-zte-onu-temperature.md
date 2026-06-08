# ZTE ONU Temperature Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a live ONU temperature read for ZTE devices using a dedicated command and parser that returns values in the normalized `44.133(C)` format.

**Architecture:** A new `TemperatureStringParser` scans the multiline output of `show gpon remote-onu interface pon gpon_onu-<pon>:<onu>` for the `Temperature:` line and extracts the value. A new `TemperatureOnuCommand` executes that CLI command and returns the first parsed value or `"No Response"`. `ZTEConnection::CACHE_TTL_MAP` gains an explicit no-cache entry for the temperature command prefix so the dynamic value is never served stale.

**Tech Stack:** PHP 8.1+, phpseclib3 (via `ZTEConnection`), existing `AbstractCommand` / `StringParserInterface` contracts.

---

### Task 1: TemperatureStringParser

**Files:**
- Create: `src/OLT/ZTE/DataProcessors/TemperatureStringParser.php`

- [ ] **Step 1: Write the parser class**

```php
<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class TemperatureStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        foreach (explode("\r\n", $input) as $line) {
            if (preg_match('/Temperature:\s*([\d.]+)\s*\(\s*([^\s)]+)\s*\)/', $line, $matches)) {
                return ["{$matches[1]}({$matches[2]})"];
            }
        }

        return [];
    }
}
```

- [ ] **Step 2: Syntax-check the file**

Run: `php -l src/OLT/ZTE/DataProcessors/TemperatureStringParser.php`
Expected output: `No syntax errors detected in src/OLT/ZTE/DataProcessors/TemperatureStringParser.php`

- [ ] **Step 3: Verify parser logic manually**

Create `/tmp/test_temperature_parser.php`:

```php
<?php
require __DIR__ . '/var/srv/workspace/php/OLTCMDRead/vendor/autoload.php';

use LLENON\OltInformation\OLT\ZTE\DataProcessors\TemperatureStringParser;

$parser = new TemperatureStringParser();

// Typical OLT output with surrounding lines
$typical = "ONT Vender-ID: ZTEG\r\nTemperature:                 44.133(C)\r\nVoltage:                     3.213(V)\r\n";
$result = $parser->parse($typical);
assert($result === ['44.133(C)'], "Expected ['44.133(C)'], got " . json_encode($result));

// Compact spacing
$compact = "Temperature: 22.0(C)\r\n";
$result = $parser->parse($compact);
assert($result === ['22.0(C)'], "Compact: got " . json_encode($result));

// Extra whitespace inside unit
$loose = "Temperature:  33.5 ( C )\r\n";
$result = $parser->parse($loose);
assert($result === ['33.5(C)'], "Loose: got " . json_encode($result));

// No temperature line → empty
$missing = "Some other line\r\nAnother line\r\n";
$result = $parser->parse($missing);
assert($result === [], "Missing: got " . json_encode($result));

echo "All assertions passed.\n";
```

Run: `php /tmp/test_temperature_parser.php`
Expected: `All assertions passed.`

- [ ] **Step 4: Commit**

```bash
git add src/OLT/ZTE/DataProcessors/TemperatureStringParser.php
git commit -m "add zte temperature string parser"
```

---

### Task 2: TemperatureOnuCommand

**Files:**
- Create: `src/OLT/ZTE/Command/TemperatureOnuCommand.php`

- [ ] **Step 1: Write the command class**

```php
<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\Command\AbstractCommand;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\TemperatureStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class TemperatureOnuCommand extends AbstractCommand
{
    private string $pon;
    private string $onuId;

    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new TemperatureStringParser());
    }

    public function execute(string $pon, string $onuId): string
    {
        $this->pon = $pon;
        $this->onuId = $onuId;
        $data = $this->exec();

        if (!empty($data)) {
            return $data[0];
        }

        return 'No Response';
    }

    protected function getCommand(): string
    {
        return "show gpon remote-onu interface pon gpon_onu-{$this->pon}:{$this->onuId}";
    }
}
```

- [ ] **Step 2: Syntax-check the file**

Run: `php -l src/OLT/ZTE/Command/TemperatureOnuCommand.php`
Expected output: `No syntax errors detected in src/OLT/ZTE/Command/TemperatureOnuCommand.php`

- [ ] **Step 3: Commit**

```bash
git add src/OLT/ZTE/Command/TemperatureOnuCommand.php
git commit -m "add zte temperature onu command"
```

---

### Task 3: Exclude Temperature Reads from ZTEConnection Cache

**Files:**
- Modify: `src/OLT/ZTE/ZTEConnection.php`

The command `show gpon remote-onu interface pon gpon_onu-<pon>:<onu>` currently matches
the catch-all `'show gpon remote-onu '` entry (60 s TTL). The spec requires this value
to always be fetched live. A `null` TTL entry placed before the catch-all prevents caching.
`getCacheTtl` already returns `?int`, so `null` from the map means "no cache" without
any logic change.

- [ ] **Step 1: Add the no-cache entry to CACHE_TTL_MAP**

Current constant (lines 27–34 of `src/OLT/ZTE/ZTEConnection.php`):

```php
    private const CACHE_TTL_MAP = [
        'show gpon onu distance'       => 3600,
        'show gpon remote-onu service' => 300,
        'show gpon remote-onu '        => 60,
        'show gpon onu detail-info'    => 60,
        'show pon onu information'     => 30,
    ];
```

Replace with:

```php
    private const CACHE_TTL_MAP = [
        'show gpon onu distance'              => 3600,
        'show gpon remote-onu service'        => 300,
        'show gpon remote-onu interface pon'  => null,
        'show gpon remote-onu '               => 60,
        'show gpon onu detail-info'           => 60,
        'show pon onu information'            => 30,
    ];
```

- [ ] **Step 2: Syntax-check ZTEConnection**

Run: `php -l src/OLT/ZTE/ZTEConnection.php`
Expected output: `No syntax errors detected in src/OLT/ZTE/ZTEConnection.php`

- [ ] **Step 3: Verify cache is bypassed for temperature command**

Create `/tmp/test_cache_ttl.php`:

```php
<?php
require '/var/srv/workspace/php/OLTCMDRead/vendor/autoload.php';

// Use reflection to call the private getCacheTtl method
$oltDto = new \LLENON\OltInformation\DTO\OLT('user', 'pass', 'ZTE', '192.168.x.x', 22, 'ssh', 'router01');
$conn = new \LLENON\OltInformation\OLT\ZTE\ZTEConnection($oltDto);

$ref = new ReflectionMethod($conn, 'getCacheTtl');
$ref->setAccessible(true);

$temperatureCmd = 'show gpon remote-onu interface pon gpon_onu-1/3/1:2';
$ttl = $ref->invoke($conn, $temperatureCmd);
assert($ttl === null, "Temperature command must not be cached; got " . var_export($ttl, true));

$serviceCmd = 'show gpon remote-onu service gpon_onu-1/3/1:2';
$ttl = $ref->invoke($conn, $serviceCmd);
assert($ttl === 300, "Service command must be 300s; got " . var_export($ttl, true));

$catchAllCmd = 'show gpon remote-onu interface eth gpon_onu-1/3/1:2';
$ttl = $ref->invoke($conn, $catchAllCmd);
assert($ttl === 60, "Ethernet command must be 60s; got " . var_export($ttl, true));

echo "All assertions passed.\n";
```

Run: `php /tmp/test_cache_ttl.php`
Expected: `All assertions passed.`

- [ ] **Step 4: Commit**

```bash
git add src/OLT/ZTE/ZTEConnection.php
git commit -m "perf(zte): exclude onu temperature reads from connection cache"
```

---

### Task 4: Wire TemperatureOnuCommand into examples/OltZteTeste.php

**Files:**
- Modify: `examples/OltZteTeste.php`

This is the canonical ZTE live-test script. Adding a `TemperatureOnuCommand` block here
lets the engineer verify the full flow against a real OLT without writing separate code.

- [ ] **Step 1: Add the temperature example block**

At the end of `examples/OltZteTeste.php`, before the closing `?>` (if any) or at end of file, add:

```php
###>TemperatureOnuCommand
//$TemperatureOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\TemperatureOnuCommand($conn);
//$temperature = $TemperatureOnuCommand->execute("1/3/1", "2");
//dd($temperature);
##<TemperatureOnuCommand
```

- [ ] **Step 2: Syntax-check the example file**

Run: `php -l examples/OltZteTeste.php`
Expected output: `No syntax errors detected in examples/OltZteTeste.php`

- [ ] **Step 3: Commit**

```bash
git add examples/OltZteTeste.php
git commit -m "docs: add zte temperature command example"
```

---

### Task 5: Live Verification Against Laboratory ONU

No automated test infrastructure exists. Verification requires a real OLT connection.

- [ ] **Step 1: Enable TemperatureOnuCommand in OltZteTeste.php**

In `examples/OltZteTeste.php`, uncomment the `TemperatureOnuCommand` block and comment out
the active `SignalOnuCommand` block:

```php
###>SignalOnuCommand
//$SignalOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\SignalOnuCommand($conn);
//$signal =$SignalOnuCommand->execute("1/3/1","2");
//dd($signal);
##<SignalOnuCommand

###>TemperatureOnuCommand
$TemperatureOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\TemperatureOnuCommand($conn);
$temperature = $TemperatureOnuCommand->execute("1/3/1", "2");
dd($temperature);
##<TemperatureOnuCommand
```

Replace `"1/3/1"` and `"2"` with actual lab PON and ONU ID.

- [ ] **Step 2: Populate examples/config/zte.json with lab credentials**

File must contain at minimum:
```json
{
    "userName": "actual-user",
    "password": "actual-password",
    "model": "ZTE",
    "address": "192.168.x.x",
    "port": 22,
    "oltName": "actual-cli-hostname"
}
```
`oltName` must be the actual ZTE CLI hostname (what appears before `#` in the prompt).

- [ ] **Step 3: Run against the lab ONU**

Run: `php examples/OltZteTeste.php`

Expected output format (exact numbers will differ):
```
string(11) "44.133(C)"
```

Confirm:
- The string is in the form `<number>(C)` — digits, a dot, more digits, then `(C)`.
- It is NOT `"No Response"`.
- It is NOT `null`.

- [ ] **Step 4: Re-enable SignalOnuCommand and verify signal is unchanged**

Revert `examples/OltZteTeste.php` to have `SignalOnuCommand` active again:

```php
###>SignalOnuCommand
$SignalOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\SignalOnuCommand($conn);
$signal = $SignalOnuCommand->execute("1/3/1", "2");
dd($signal);
##<SignalOnuCommand

###>TemperatureOnuCommand
//$TemperatureOnuCommand = new \LLENON\OltInformation\OLT\ZTE\Command\TemperatureOnuCommand($conn);
//$temperature = $TemperatureOnuCommand->execute("1/3/1", "2");
//dd($temperature);
##<TemperatureOnuCommand
```

Run: `php examples/OltZteTeste.php`

Expected output format:
```
string(12) "-22.36(dbm)"
```

Confirm the signal is in the form `<number>(dbm)` and does NOT include a temperature value.

- [ ] **Step 5: Commit verification state (examples/OltZteTeste.php)**

```bash
git add examples/OltZteTeste.php
git commit -m "docs: restore zte example to signal command after temperature verification"
```

---

## External: ZteOnuInfoAdapter Update

The spec refers to updating `ZteOnuInfoAdapter` in the external `olt-service` HTTP service
(`../olt-service`). That service currently returns `temp => null` for ZTE ONU info responses.
After this library is tagged and updated as a dependency in `olt-service`, replace that `null`
assignment with a call to `TemperatureOnuCommand::execute($pon, $onuId)`. The result is a
`string` in `44.133(C)` format and must not be cached at the service layer either.
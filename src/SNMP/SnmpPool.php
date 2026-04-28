<?php

namespace LLENON\OltInformation\SNMP;

/**
 * Parallel SNMP runner using `snmpwalk` / `snmpget` processes.
 * If the binary isn't available or a call fails, throw and let callers fallback to SSH.
 */
final class SnmpPool
{
    /**
     * @param array<int, array{host:string, oid:string, op?:'walk'|'get'}> $jobs
     * @return array<int, string> outputs in the same order as jobs
     */
    public function run(array $jobs, SnmpConfig $config, int $concurrency = 8): array
    {
        if ($concurrency < 1) {
            $concurrency = 1;
        }

        $pending = array_values($jobs);
        $outputs = array_fill(0, count($pending), '');
        $running = [];

        $index = 0;
        while ($index < count($pending) || $running) {
            while ($index < count($pending) && count($running) < $concurrency) {
                $job = $pending[$index];
                $running[$index] = $this->startJob($job, $config);
                $index++;
            }

            foreach ($running as $jobIndex => $proc) {
                $status = proc_get_status($proc['p']);
                if (!$status['running']) {
                    $stdout = stream_get_contents($proc['out']) ?: '';
                    $stderr = stream_get_contents($proc['err']) ?: '';
                    fclose($proc['out']);
                    fclose($proc['err']);
                    $exitCode = proc_close($proc['p']);
                    unset($running[$jobIndex]);

                    if ($exitCode !== 0) {
                        throw new SnmpException(trim($stderr) !== '' ? trim($stderr) : 'snmp command failed');
                    }

                    $outputs[$jobIndex] = $stdout;
                }
            }

            usleep(20000);
        }

        return $outputs;
    }

    /**
     * @param array{host:string, oid:string, op?:'walk'|'get'} $job
     * @return array{p:resource, out:resource, err:resource}
     */
    private function startJob(array $job, SnmpConfig $config): array
    {
        $op = $job['op'] ?? 'walk';
        $bin = $op === 'get' ? 'snmpget' : 'snmpwalk';

        // Note: keep args simple / unquoted to avoid shell parsing surprises.
        $cmd = [
            $bin,
            '-v', $config->version,
            '-c', $config->community,
            '-On',
            '-t', (string) $config->timeoutSeconds,
            '-r', (string) $config->retries,
            $job['host'] . ':' . $config->port,
            $job['oid'],
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new SnmpException('failed to start snmp process');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return ['p' => $process, 'out' => $pipes[1], 'err' => $pipes[2]];
    }
}


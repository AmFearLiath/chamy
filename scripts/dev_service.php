<?php
// scripts/dev_service.php
// Development service: starts/monitors local Chamy services and writes status/logs
// Usage:
//   php scripts/dev_service.php run [server-cmd]
//   php scripts/dev_service.php start [server-cmd]    (use with PowerShell wrapper to background)
//   php scripts/dev_service.php stop
//   php scripts/dev_service.php status

date_default_timezone_set('Europe/Berlin');

$mode = $argv[1] ?? 'run';
$serverCmd = $argv[2] ?? 'php -S localhost:8080 -t public';
$root = realpath(__DIR__ . '/..') ?: __DIR__;
$logFile = __DIR__ . '/terminal.log';
$statusFile = __DIR__ . '/terminal_status.json';
$pidFile = __DIR__ . '/dev_service.pid';
$refresh = 2;

function appendLog(string $msg)
{
    global $logFile;
    $time = date('[H:i:s]');
    file_put_contents($logFile, "$time $msg\n", FILE_APPEND | LOCK_EX);
}

function writeStatus(array $data)
{
    global $statusFile;
    file_put_contents($statusFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function checkHttp(string $url, int $timeout = 2): bool
{
    $opts = [
        'http' => [ 'method' => 'GET', 'timeout' => $timeout, 'ignore_errors' => true ],
    ];
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    return ($res !== false);
}

if ($mode === 'status') {
    $status = is_readable($statusFile) ? json_decode(file_get_contents($statusFile), true) : [];
    echo json_encode($status ?: ['status' => 'unknown'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

if ($mode === 'stop') {
    if (is_file($pidFile)) {
        $pid = (int)trim(file_get_contents($pidFile));
        if ($pid > 0) {
            if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
                exec("taskkill /PID $pid /F 2>&1", $out, $rc);
            } else {
                posix_kill($pid, SIGTERM);
                $rc = 0;
            }
            appendLog("dev_service: stop requested for pid $pid");
        }
        @unlink($pidFile);
    }
    echo "dev_service stopped\n";
    exit(0);
}

// RUN or START
if ($mode === 'run' || $mode === 'start') {
    appendLog("dev_service: starting (cmd: $serverCmd)");

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $cwd = $root;
    $proc = proc_open($serverCmd, $descriptors, $pipes, $cwd);
    if (!is_resource($proc)) {
        appendLog('dev_service: failed to start server process');
        exit(1);
    }

    // Save pid (if available)
    $status = proc_get_status($proc);
    $pid = $status['pid'] ?? 0;
    if ($pid) file_put_contents($pidFile, (string)$pid);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $start = time();

    // Main monitor loop
    while (true) {
        // read server stdout/stderr
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        if ($out !== false && strlen(trim($out)) > 0) appendLog('[server] ' . trim($out));
        if ($err !== false && strlen(trim($err)) > 0) appendLog('[server-err] ' . trim($err));

        // Check HTTP
        $httpOk = checkHttp('http://localhost:8080');

        $uptime = gmdate('H:i:s', max(0, time() - $start));
        $st = [
            'status' => $httpOk ? 'OK' : 'DOWN',
            'lastAction' => $httpOk ? 'HTTP OK' : 'HTTP DOWN',
            'activeUsers' => 0,
            'uptime' => $uptime,
            'checked_at' => date('c')
        ];
        writeStatus($st);

        // Append a small heartbeat into the terminal log occasionally
        if (rand(1, 5) === 1) appendLog('dev_service: heartbeat (' . $st['status'] . ')');

        // Check process
        $pstat = proc_get_status($proc);
        if (!$pstat['running']) {
            appendLog('dev_service: monitored process exited');
            break;
        }

        // Sleep
        sleep($refresh);
    }

    // cleanup
    foreach ($pipes as $p) {
        @fclose($p);
    }
    proc_close($proc);
    @unlink($pidFile);
    appendLog('dev_service: monitor loop finished');
    exit(0);
}

echo "Usage: php scripts/dev_service.php run|start|stop|status [server-cmd]\n";

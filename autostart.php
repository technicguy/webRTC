<?php
/**
 * autostart.php
 * Called via AJAX from index.php on page load.
 * Reads WS port from webcall_config.json — no hard-coded values.
 *
 * 1. Load port from webcall_config.json
 * 2. Check if port is already open  → return "already_running"
 * 3. Find the `node` executable
 * 4. Auto-run `npm install` if node_modules/ws is missing
 * 5. Launch `node server.js` as a detached background process
 * 6. Poll up to 4 s for the port to respond
 * 7. Return JSON to browser
 */

header('Content-Type: application/json');

define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'webcall_config.json');
define('SERVER_JS',   __DIR__ . DIRECTORY_SEPARATOR . 'server.js');
define('LOG_FILE',    __DIR__ . DIRECTORY_SEPARATOR . 'ws_server.log');
define('PID_FILE',    __DIR__ . DIRECTORY_SEPARATOR . 'ws_server.pid');

/* ── Load config ── */
$cfgRaw  = file_exists(CONFIG_FILE) ? @file_get_contents(CONFIG_FILE) : '{}';
$cfg     = @json_decode($cfgRaw, true) ?: [];
$WS_PORT = (int)($cfg['websocket']['port'] ?? 9090);

/* ── 1. Already running? ── */
if (portOpen('127.0.0.1', $WS_PORT)) {
    echo json_encode(['status'  => 'already_running',
                      'message' => "WebSocket server already running on port {$WS_PORT}"]);
    exit;
}

/* ── 2. Verify server.js exists ── */
if (!file_exists(SERVER_JS)) {
    echo json_encode(['status'  => 'error',
                      'message' => 'server.js not found at: ' . SERVER_JS]);
    exit;
}

/* ── 3. Find node ── */
$node = findNode();
if (!$node) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Node.js not found. Download from https://nodejs.org then restart WAMP/Apache.'
    ]);
    exit;
}

/* ── 4. Auto npm install if ws module missing ── */
$wsModule = __DIR__ . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'ws';
if (!is_dir($wsModule)) {
    $npm = findNpm();
    if (!$npm) {
        echo json_encode(['status'  => 'error',
                          'message' => "node_modules/ws missing and npm not found.\nRun manually:\n  cd " . __DIR__ . "\n  npm install"]);
        exit;
    }
    $npmCmd = (PHP_OS_FAMILY === 'Windows')
        ? 'cd /d ' . escapeshellarg(__DIR__) . ' && ' . escapeshellarg($npm) . ' install 2>&1'
        : 'cd '    . escapeshellarg(__DIR__) . ' && '  . escapeshellarg($npm) . ' install 2>&1';
    exec($npmCmd, $out, $code);
    if ($code !== 0 || !is_dir($wsModule)) {
        echo json_encode(['status'  => 'error',
                          'message' => "npm install failed.\n" . implode("\n", $out)]);
        exit;
    }
}

/* ── 5. Launch server.js detached ── */
$pid = launchDetached($node, SERVER_JS, LOG_FILE);

/* ── 6. Poll up to 4 s for port ── */
$started = false;
for ($i = 0; $i < 20; $i++) {
    usleep(200000);
    if (portOpen('127.0.0.1', $WS_PORT)) { $started = true; break; }
}

if ($started) {
    if ($pid) file_put_contents(PID_FILE, $pid);
    echo json_encode(['status'  => 'started',
                      'message' => "WebSocket server started on port {$WS_PORT}"
                                 . ($pid ? " (PID {$pid})" : '')]);
} else {
    $log = file_exists(LOG_FILE) ? file_get_contents(LOG_FILE) : '(no log yet)';
    echo json_encode(['status'  => 'error',
                      'message' => "server.js launched but port {$WS_PORT} not responding.\n\nLog:\n{$log}"]);
}

/* ════════════════════════════════════════
   HELPERS
════════════════════════════════════════ */

function portOpen(string $host, int $port): bool {
    $s = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($s) { fclose($s); return true; }
    return false;
}

function launchDetached(string $node, string $script, string $logFile): ?int {
    if (PHP_OS_FAMILY === 'Windows') {
        $vbs = sys_get_temp_dir() . '\\webcall_ws_launch.vbs';
        $n = str_replace('"', '""', $node);
        $s = str_replace('"', '""', $script);
        $l = str_replace('"', '""', $logFile);
        $vbsContent = <<<VBS
Dim sh
Set sh = CreateObject("WScript.Shell")
sh.Run """$n"" ""$s"" >> ""$l"" 2>&1", 0, False
Set sh = Nothing
VBS;
        file_put_contents($vbs, $vbsContent);
        exec('wscript.exe //nologo ' . escapeshellarg($vbs));
        return null;
    } else {
        $cmd = 'nohup ' . escapeshellarg($node) . ' '
             . escapeshellarg($script)
             . ' >> ' . escapeshellarg($logFile)
             . ' 2>&1 & echo $!';
        $out = shell_exec($cmd);
        return $out ? (int) trim($out) : null;
    }
}

function findNode(): ?string {
    $cmd    = PHP_OS_FAMILY === 'Windows' ? 'where node 2>nul' : 'which node 2>/dev/null';
    $result = trim((string) shell_exec($cmd));
    $first  = strtok($result, "\n\r");
    if ($first && file_exists($first)) return $first;
    if (PHP_OS_FAMILY === 'Windows') {
        $paths = [
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe',
            getenv('LOCALAPPDATA') . '\\Programs\\nodejs\\node.exe',
            getenv('APPDATA') . '\\nvm\\current\\node.exe',
        ];
        foreach ($paths as $p) { if ($p && file_exists($p)) return $p; }
    }
    return null;
}

function findNpm(): ?string {
    $cmd    = PHP_OS_FAMILY === 'Windows' ? 'where npm 2>nul' : 'which npm 2>/dev/null';
    $result = trim((string) shell_exec($cmd));
    $first  = strtok($result, "\n\r");
    if ($first && file_exists($first)) return $first;
    if (PHP_OS_FAMILY === 'Windows') {
        $paths = [
            'C:\\Program Files\\nodejs\\npm.cmd',
            'C:\\Program Files (x86)\\nodejs\\npm.cmd',
        ];
        foreach ($paths as $p) { if ($p && file_exists($p)) return $p; }
    }
    return null;
}

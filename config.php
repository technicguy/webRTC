<?php
/**
 * config.php  –  WebCall Central Configuration Loader
 *
 * Reads webcall_config.json once and exposes every setting as
 * typed PHP variables + a JS-ready associative array ($CFG).
 *
 * Include at the TOP of index.php and guest.php:
 *   require_once __DIR__ . '/config.php';
 *
 * Available after include:
 *   $CFG['server_ip']      string   e.g. "192.168.18.72"
 *   $CFG['protocol']       string   "http" | "https"
 *   $CFG['base_path']      string   e.g. "/webRTC"
 *   $CFG['base_url']       string   "http://192.168.18.72/webRTC"
 *   $CFG['ws_port']        int      e.g. 9090
 *   $CFG['ws_protocol']    string   "ws" | "wss"
 *   $CFG['ws_url']         string   "ws://192.168.18.72:9090"
 *   $CFG['ws_mode']        bool     true = WebSocket, false = HTTP Polling
 *   $CFG['rooms']          array    [ ['id'=>1,'label'=>…,'sub'=>…], … ]
 *   $CFG['poll_fast_ms']   int      e.g. 600
 *   $CFG['poll_slow_ms']   int      e.g. 2000
 *   $CFG['stun_url']       string   "stun:stun.l.google.com:19302"
 *
 * Helper: cfg_js()  – returns an inline <script> block that injects
 *   WEBCALL_CFG into the browser's global scope.
 */

define('CFG_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'webcall_config.json');

function _loadWebcallConfig(): array {
    if (!file_exists(CFG_FILE)) {
        die(json_encode(['error' => 'webcall_config.json not found at ' . CFG_FILE]));
    }

    $raw = file_get_contents(CFG_FILE);
    $json = json_decode($raw, true);

    if (!is_array($json)) {
        die(json_encode(['error' => 'webcall_config.json is not valid JSON']));
    }

    $server   = $json['server']    ?? [];
    $ws       = $json['websocket'] ?? [];
    $modeObj  = $json['mode']      ?? [];
    $polling  = $json['polling']   ?? [];
    $stun     = $json['stun']      ?? [];
    $rooms    = $json['rooms']     ?? [];

    $serverIp   = $server['ip']       ?? '127.0.0.1';
    $protocol   = $server['protocol'] ?? 'http';
    $basePath   = rtrim($server['path'] ?? '/webRTC', '/');
    $wsPort     = (int)($ws['port']     ?? 9090);
    $wsProt     = $ws['protocol']       ?? 'ws';
    $wsMode     = (bool)($modeObj['ws_mode'] ?? true);

    return [
        'server_ip'    => $serverIp,
        'protocol'     => $protocol,
        'base_path'    => $basePath,
        'base_url'     => "{$protocol}://{$serverIp}{$basePath}",
        'ws_port'      => $wsPort,
        'ws_protocol'  => $wsProt,
        'ws_url'       => "{$wsProt}://{$serverIp}:{$wsPort}",
        'ws_mode'      => $wsMode,
        'rooms'        => array_values($rooms),
        'poll_fast_ms' => (int)($polling['fast_ms'] ?? 600),
        'poll_slow_ms' => (int)($polling['slow_ms'] ?? 2000),
        'stun_url'     => $stun['urls'] ?? 'stun:stun.l.google.com:19302',
    ];
}

$CFG = _loadWebcallConfig();

/**
 * Emit a <script> block that makes all config available as
 *   window.WEBCALL_CFG  in JavaScript.
 * Call echo cfg_js(); inside <head> or before your scripts.
 */
function cfg_js(): string {
    global $CFG;
    $json = json_encode([
        'BASE_URL'     => $CFG['base_url'],
        'WS_URL'       => $CFG['ws_url'],
        'WS_PORT'      => $CFG['ws_port'],
        'WS_MODE'      => $CFG['ws_mode'],
        'ROOMS'        => $CFG['rooms'],
        'POLL_FAST_MS' => $CFG['poll_fast_ms'],
        'POLL_SLOW_MS' => $CFG['poll_slow_ms'],
        'STUN'         => ['iceServers' => [['urls' => $CFG['stun_url']]]],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return <<<HTML
<script>
/* ── WebCall Config (injected by config.php) ── */
const WEBCALL_CFG = {$json};
const BASE_URL     = WEBCALL_CFG.BASE_URL;
const WS_URL       = WEBCALL_CFG.WS_URL;
const WS_PORT      = WEBCALL_CFG.WS_PORT;
const STUN         = WEBCALL_CFG.STUN;
const POLL_FAST_MS = WEBCALL_CFG.POLL_FAST_MS;
const POLL_SLOW_MS = WEBCALL_CFG.POLL_SLOW_MS;
</script>
HTML;
}

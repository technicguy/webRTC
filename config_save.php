<?php
/**
 * config_save.php
 * Called by index.php (staff panel) when the WS/Poll toggle is switched.
 * Reads the current webcall_config.json, updates only the changed fields,
 * and writes it back — preserving all other settings.
 *
 * POST body: { "ws_mode": true|false }
 * Returns:   { "ok": true, "ws_mode": …, "updated": … }
 *        or  { "ok": false, "error": "…" }
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');

define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'webcall_config.json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$body = file_get_contents('php://input');
$data = @json_decode($body, true);

if (!isset($data['ws_mode'])) {
    echo json_encode(['ok' => false, 'error' => 'Missing ws_mode']);
    exit;
}

/* ── Read existing config so we don't clobber other fields ── */
$existing = [];
if (file_exists(CONFIG_FILE)) {
    $raw = file_get_contents(CONFIG_FILE);
    $existing = @json_decode($raw, true) ?: [];
}

/* ── Patch only the mode section ── */
if (!isset($existing['mode'])) $existing['mode'] = [];
$existing['mode']['ws_mode'] = (bool) $data['ws_mode'];
$existing['updated'] = date('Y-m-d H:i:s');

$written = file_put_contents(CONFIG_FILE, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

if ($written === false) {
    echo json_encode(['ok' => false, 'error' => 'Cannot write ' . CONFIG_FILE]);
} else {
    echo json_encode([
        'ok'      => true,
        'ws_mode' => $existing['mode']['ws_mode'],
        'updated' => $existing['updated'],
    ]);
}

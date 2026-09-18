<?php
/**
 * poll_state.php  –  Shared state store for HTTP Polling mode
 * WebCall v2.0  –  No WebSocket server required in this mode
 *
 * Uses a JSON file (poll_data.json) as the shared memory between
 * guest.php (phone) and index.php (staff panel).
 *
 * Actions (GET):
 *   staff_read          → returns all rooms state for staff panel
 *   guest_read&room=N   → returns single room state for guest page
 *   clear&room=N        → clears room state (after hangup)
 *   clear_ice&room=N    → clears guest→staff ICE queue
 *   clear_staff_ice&room=N → clears staff→guest ICE queue
 *   ack_answer&room=N   → marks staff SDP as consumed
 *
 * Actions (POST JSON):
 *   guest_call     { room, sdp }         → guest initiates call
 *   guest_ice      { room, candidate }   → guest ICE candidate → staff
 *   guest_hangup   { room }              → guest hangs up
 *   staff_answer   { room, sdp }         → staff answers
 *   staff_ice      { room, candidate }   → staff ICE candidate → guest
 *   staff_hangup   { room }              → staff hangs up
 *   staff_rejected { room }              → staff rejects
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

define('STATE_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'poll_data.json');
define('LOCK_FILE',  __DIR__ . DIRECTORY_SEPARATOR . 'poll_data.lock');

/* ─── Load / save helpers ─── */
function loadState(): array {
    if (!file_exists(STATE_FILE)) return ['rooms' => []];
    $raw = @file_get_contents(STATE_FILE);
    if (!$raw) return ['rooms' => []];
    $d = @json_decode($raw, true);
    return is_array($d) ? $d : ['rooms' => []];
}

function saveState(array $state): void {
    file_put_contents(STATE_FILE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
}

function roomKey(int $room): string { return (string)$room; }

function getRoom(array &$state, int $room): array {
    $k = roomKey($room);
    if (!isset($state['rooms'][$k])) {
        $state['rooms'][$k] = [
            'status'     => 'idle',
            'sdp'        => null,    // guest offer SDP
            'staff_sdp'  => null,    // staff answer SDP
            'ice'        => [],      // guest→staff ICE
            'staff_ice'  => [],      // staff→guest ICE
            'updated'    => time(),
        ];
    }
    return $state['rooms'][$k];
}

/* ─── Route ─── */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$room   = isset($_GET['room']) ? intval($_GET['room']) : 0;

if ($method === 'GET') {
    $state = loadState();

    switch ($action) {

        case 'staff_read':
            // Return all rooms; auto-expire stale entries (> 60 s idle)
            $out = ['rooms' => []];
            foreach ($state['rooms'] as $rk => $rd) {
                // Expire rooms that have been idle / stale for 60 s
                if ($rd['status'] === 'idle' && isset($rd['updated']) && (time() - $rd['updated']) > 60) continue;
                $out['rooms'][$rk] = [
                    'status' => $rd['status'],
                    'sdp'    => $rd['sdp'],
                    'ice'    => $rd['ice'] ?? [],
                ];
            }
            echo json_encode($out);
            break;

        case 'guest_read':
            if (!$room) { echo json_encode(['error' => 'room required']); break; }
            $rd = getRoom($state, $room);
            echo json_encode([
                'room' => [
                    'status'    => $rd['status'],
                    'staff_sdp' => $rd['staff_sdp'],
                    'staff_ice' => $rd['staff_ice'] ?? [],
                ]
            ]);
            break;

        case 'clear':
            if (!$room) { echo json_encode(['ok' => false]); break; }
            $k = roomKey($room);
            $state['rooms'][$k] = ['status' => 'idle', 'sdp' => null, 'staff_sdp' => null, 'ice' => [], 'staff_ice' => [], 'updated' => time()];
            saveState($state);
            echo json_encode(['ok' => true]);
            break;

        case 'clear_ice':
            if (!$room) { echo json_encode(['ok' => false]); break; }
            $k = roomKey($room);
            if (isset($state['rooms'][$k])) { $state['rooms'][$k]['ice'] = []; saveState($state); }
            echo json_encode(['ok' => true]);
            break;

        case 'clear_staff_ice':
            if (!$room) { echo json_encode(['ok' => false]); break; }
            $k = roomKey($room);
            if (isset($state['rooms'][$k])) { $state['rooms'][$k]['staff_ice'] = []; saveState($state); }
            echo json_encode(['ok' => true]);
            break;

        case 'ack_answer':
            if (!$room) { echo json_encode(['ok' => false]); break; }
            $k = roomKey($room);
            if (isset($state['rooms'][$k])) {
                $state['rooms'][$k]['staff_sdp'] = null;
                $state['rooms'][$k]['status']    = 'answered';
                $state['rooms'][$k]['updated']   = time();
                saveState($state);
            }
            echo json_encode(['ok' => true]);
            break;

        default:
            echo json_encode(['error' => 'unknown action']);
    }

} elseif ($method === 'POST') {

    $body   = file_get_contents('php://input');
    $data   = @json_decode($body, true);
    if (!$data) { echo json_encode(['error' => 'bad JSON']); exit; }

    $action = $data['action'] ?? '';
    $room   = isset($data['room']) ? intval($data['room']) : 0;
    if (!$room) { echo json_encode(['error' => 'room required']); exit; }

    $state  = loadState();
    $k      = roomKey($room);
    getRoom($state, $room); // ensure key exists

    switch ($action) {

        case 'guest_call':
            $state['rooms'][$k]['status']  = 'calling';
            $state['rooms'][$k]['sdp']     = $data['sdp'] ?? null;
            $state['rooms'][$k]['ice']     = [];
            $state['rooms'][$k]['staff_sdp'] = null;
            $state['rooms'][$k]['staff_ice'] = [];
            $state['rooms'][$k]['updated'] = time();
            break;

        case 'guest_ice':
            $state['rooms'][$k]['ice'][] = $data['candidate'];
            $state['rooms'][$k]['updated'] = time();
            break;

        case 'guest_hangup':
            $state['rooms'][$k]['status']  = 'hangup';
            $state['rooms'][$k]['updated'] = time();
            break;

        case 'staff_answer':
            $state['rooms'][$k]['status']    = 'answered';
            $state['rooms'][$k]['staff_sdp'] = $data['sdp'] ?? null;
            $state['rooms'][$k]['updated']   = time();
            break;

        case 'staff_ice':
            $state['rooms'][$k]['staff_ice'][] = $data['candidate'];
            $state['rooms'][$k]['updated'] = time();
            break;

        case 'staff_hangup':
            $state['rooms'][$k]['status']  = 'hangup';
            $state['rooms'][$k]['updated'] = time();
            break;

        case 'staff_rejected':
            $state['rooms'][$k]['status']  = 'rejected';
            $state['rooms'][$k]['updated'] = time();
            break;

        default:
            echo json_encode(['error' => 'unknown action']); exit;
    }

    saveState($state);
    echo json_encode(['ok' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

<?php
/**
 * index.php  –  WebCall Staff Panel
 * All settings come from webcall_config.json via config.php.
 * To change IP, port, rooms, or mode — edit webcall_config.json only.
 */
require_once __DIR__ . '/config.php';

// Convenience aliases for PHP template use
$SERVER_IP   = $CFG['server_ip'];
$WS_PORT     = $CFG['ws_port'];
$BASE_URL    = $CFG['base_url'];
$initWsMode  = $CFG['ws_mode'];
$initModeJS  = $initWsMode ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>WebCall – Staff Panel</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <!-- ── All JS config constants injected from webcall_config.json ── -->
  <?= cfg_js() ?>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

    :root {
      --bg:       #0b0f1a;
      --surface:  #131929;
      --border:   #1e2d47;
      --accent:   #00d4ff;
      --accent2:  #7c3aed;
      --danger:   #ff4d6d;
      --success:  #00e096;
      --text:     #e2eaf8;
      --muted:    #5a7098;
      --ring-color: #00d4ff44;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      background-image:
        radial-gradient(ellipse 80% 50% at 50% -20%, #0d2040 0%, transparent 70%),
        repeating-linear-gradient(0deg, transparent, transparent 39px, #1a2540 39px, #1a2540 40px),
        repeating-linear-gradient(90deg, transparent, transparent 39px, #1a2540 39px, #1a2540 40px);
    }

    /* ── Header ── */
    .staff-header {
      background: linear-gradient(135deg, #0d1b33, #131929);
      border-bottom: 1px solid var(--border);
      padding: 1.1rem 2rem;
      display: flex;
      align-items: center;
      gap: .8rem;
      position: sticky; top: 0; z-index: 100;
    }
    .logo {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.5rem;
      letter-spacing: -.5px;
      color: var(--accent);
    }
    .logo span { color: var(--text); }

    .ws-badge {
      font-size: .7rem;
      font-weight: 700;
      padding: .25rem .6rem;
      border-radius: 20px;
      letter-spacing: .5px;
      text-transform: uppercase;
      transition: all .3s;
    }
    .ws-badge.connecting { background: #2a1f00; color: #f59e0b; }
    .ws-badge.online     { background: #00e09618; color: var(--success); }
    .ws-badge.offline    { background: #ff4d6d18; color: var(--danger);  }

    .status-dot {
      width: 9px; height: 9px;
      border-radius: 50%;
      background: var(--muted);
      box-shadow: none;
      transition: all .3s;
    }
    .status-dot.online {
      background: var(--success);
      box-shadow: 0 0 8px var(--success);
      animation: pulse-dot 2s infinite;
    }
    @keyframes pulse-dot {
      0%,100% { opacity:1; transform:scale(1); }
      50%     { opacity:.6; transform:scale(1.3); }
    }

    .incoming-badge {
      display: none;
      align-items: center;
      gap: .5rem;
      background: var(--accent);
      color: #000;
      font-size: .75rem;
      font-weight: 700;
      padding: .3rem .8rem;
      border-radius: 20px;
      animation: badge-pulse 1s infinite alternate;
      margin-left: auto;
    }
    .incoming-badge.show { display: flex; }
    @keyframes badge-pulse { from{opacity:1} to{opacity:.55} }

    /* ── Rooms Grid ── */
    .rooms-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      padding: 2rem;
    }
    @media (max-width: 768px) { .rooms-grid { grid-template-columns: 1fr; padding: 1rem; } }

    .room-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 1.8rem;
      position: relative;
      overflow: hidden;
      transition: border-color .3s, box-shadow .3s, transform .2s;
    }
    .room-card::before {
      content:'';
      position:absolute; inset:0;
      background: linear-gradient(135deg, #ffffff05 0%, transparent 60%);
      pointer-events:none;
    }
    .room-card:hover {
      border-color: #2a3f62;
      box-shadow: 0 8px 40px #00000060;
      transform: translateY(-2px);
    }
    .room-card.ringing {
      border-color: var(--accent) !important;
      box-shadow: 0 0 0 3px var(--ring-color), 0 8px 40px #00d4ff22 !important;
      animation: card-ring 1s infinite alternate;
    }
    .room-card.active-call {
      border-color: var(--success) !important;
      box-shadow: 0 0 0 3px #00e09633, 0 8px 40px #00e09622 !important;
    }
    @keyframes card-ring {
      from { box-shadow: 0 0 0 2px #00d4ff44, 0 8px 30px #00d4ff22; }
      to   { box-shadow: 0 0 0 6px #00d4ff22, 0 8px 50px #00d4ff44; }
    }

    .room-number {
      font-family: 'Syne', sans-serif;
      font-size: 3.5rem;
      font-weight: 800;
      line-height: 1;
      color: #1e2d47;
      position: absolute;
      top: 1rem; right: 1.5rem;
      pointer-events: none; user-select: none;
    }

    .room-label {
      font-family: 'Syne', sans-serif;
      font-size: 1.05rem;
      font-weight: 700;
      letter-spacing: .5px;
      margin-bottom: .25rem;
    }
    .room-sublabel {
      font-size: .78rem;
      color: var(--muted);
      margin-bottom: 1.1rem;
    }

    .room-status {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .8px;
      padding: .3rem .75rem;
      border-radius: 20px;
      margin-bottom: 1.2rem;
    }
    .room-status .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .room-status.idle     { background: #1a2540; color: var(--muted); }
    .room-status.ringing  { background: #00d4ff18; color: var(--accent); }
    .room-status.incall   { background: #00e09618; color: var(--success); }

    .room-actions { display:flex; gap:.7rem; align-items:center; flex-wrap:wrap; }

    .btn-qr {
      width: 42px; height: 42px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: #1a2540;
      color: var(--muted);
      display: flex; align-items:center; justify-content:center;
      cursor: pointer;
      transition: all .2s;
      font-size: .95rem;
    }
    .btn-qr:hover { background: var(--accent); color: #000; border-color: var(--accent); }

    .btn-answer, .btn-reject, .btn-end {
      display: none;
      align-items: center;
      gap: .5rem;
      border: none;
      border-radius: 10px;
      padding: .5rem 1.1rem;
      font-size: .82rem;
      font-weight: 700;
      cursor: pointer;
      transition: opacity .2s, transform .15s;
      animation: btn-pop .35s ease;
    }
    .btn-answer { background: var(--success); color: #000; }
    .btn-reject { background: var(--danger);  color: #fff; }
    .btn-end    { background: var(--danger);  color: #fff; }
    .btn-answer.visible, .btn-reject.visible, .btn-end.visible { display: flex; }
    .btn-answer:hover, .btn-reject:hover, .btn-end:hover { opacity:.85; transform:scale(1.03); }

    @keyframes btn-pop {
      from { transform:scale(.8); opacity:0; }
      to   { transform:scale(1);  opacity:1; }
    }

    .ring-anim {
      display: none;
      align-items: center;
      gap: .5rem;
      color: var(--accent);
      font-size: .8rem;
      font-weight: 600;
    }
    .ring-anim.visible { display: flex; }
    .ring-anim i { animation: ring-shake .35s infinite alternate; }
    @keyframes ring-shake {
      from { transform: rotate(-18deg); }
      to   { transform: rotate(18deg); }
    }

    .call-timer {
      display: none;
      font-family: 'Syne', sans-serif;
      font-size: .9rem;
      color: var(--success);
      font-weight: 700;
    }
    .call-timer.visible { display:block; }

    audio { display: none; }

    /* ── QR Modal ── */
    .qr-modal-backdrop {
      display: none;
      position: fixed; inset: 0;
      background: #000000cc;
      backdrop-filter: blur(6px);
      z-index: 9000;
      align-items: center;
      justify-content: center;
    }
    .qr-modal-backdrop.show { display:flex; }

    .qr-modal-box {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 2rem;
      text-align: center;
      max-width: 330px;
      width: 90%;
      position: relative;
      animation: modal-in .3s ease;
    }
    @keyframes modal-in {
      from { transform:scale(.85) translateY(20px); opacity:0; }
      to   { transform:scale(1)   translateY(0);    opacity:1; }
    }
    .qr-modal-box .close-btn {
      position: absolute; top: 1rem; right: 1rem;
      background: #1a2540; border: none; color: var(--muted);
      width: 30px; height: 30px; border-radius: 50%;
      cursor: pointer; font-size: .75rem;
      display: flex; align-items:center; justify-content:center;
    }
    .qr-modal-box .close-btn:hover { color: var(--text); }

    .qr-modal-box h6 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      margin-bottom: .2rem;
    }
    .qr-modal-box p { font-size:.8rem; color:var(--muted); margin-bottom:1rem; }

    #qr-canvas-wrap {
      background: #fff;
      border-radius: 12px;
      padding: 12px;
      display: inline-block;
      margin-bottom: 1rem;
    }

    .qr-url-text {
      font-size: .65rem;
      color: var(--muted);
      word-break: break-all;
      background: #0b0f1a;
      padding: .5rem;
      border-radius: 8px;
      margin-bottom: .6rem;
    }
    .copy-url-btn {
      background: #1a2540;
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: .72rem;
      border-radius: 8px;
      padding: .3rem .8rem;
      cursor: pointer;
      transition: all .2s;
    }
    .copy-url-btn:hover { color: var(--accent); border-color: var(--accent); }

    /* ── Toast ── */
    .toast-wrap {
      position: fixed;
      bottom: 1.5rem; right: 1.5rem;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: .5rem;
    }
    .toast-msg {
      background: #1a2540;
      border: 1px solid var(--border);
      color: var(--text);
      font-size: .8rem;
      padding: .6rem 1rem;
      border-radius: 10px;
      animation: toast-in .3s ease, toast-out .3s ease 2.7s forwards;
    }
    @keyframes toast-in  { from{transform:translateY(10px);opacity:0} to{transform:translateY(0);opacity:1} }
    @keyframes toast-out { from{opacity:1} to{opacity:0;transform:translateY(10px)} }

    /* ── WebSocket Mode Toggle ── */
    .ws-toggle-wrap {
      display: flex;
      align-items: center;
      gap: .6rem;
      margin-left: auto;
      background: #0d1528;
      border: 1px solid var(--border);
      border-radius: 30px;
      padding: .3rem .7rem .3rem .5rem;
    }
    .ws-toggle-label {
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .5px;
      text-transform: uppercase;
      color: var(--muted);
      white-space: nowrap;
    }
    .ws-toggle-label .mode-text {
      color: var(--accent);
      transition: color .3s;
    }
    .ws-toggle-label .mode-text.nomode { color: var(--muted); }
    .ws-mode-icon {
      font-size: .75rem;
      width: 22px; height: 22px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 50%;
      background: #1a2540;
      color: var(--muted);
      transition: all .3s;
    }
    .ws-mode-icon.active { background: #00d4ff22; color: var(--accent); }

    .toggle-pill {
      position: relative;
      width: 44px; height: 24px;
      cursor: pointer;
    }
    .toggle-pill input { opacity:0; width:0; height:0; position:absolute; }
    .toggle-track {
      position: absolute; inset: 0;
      background: #1a2540;
      border: 1px solid var(--border);
      border-radius: 30px;
      transition: background .3s, border-color .3s;
    }
    .toggle-pill input:checked ~ .toggle-track {
      background: #00d4ff22;
      border-color: var(--accent);
    }
    .toggle-thumb {
      position: absolute;
      top: 3px; left: 3px;
      width: 16px; height: 16px;
      border-radius: 50%;
      background: var(--muted);
      transition: transform .3s, background .3s;
    }
    .toggle-pill input:checked ~ .toggle-track .toggle-thumb {
      transform: translateX(20px);
      background: var(--accent);
      box-shadow: 0 0 8px var(--accent);
    }

    .nowws-banner {
      display: none;
      background: #120f28;
      border-bottom: 1px solid #7c3aed44;
      padding: .65rem 2rem;
      font-size: .78rem;
      color: #c4b5fd;
      align-items: center;
      gap: .75rem;
    }
    .nowws-banner.show { display: flex; }
    .nowws-banner i { color: #7c3aed; font-size: .9rem; }
    .nowws-banner strong { color: #a78bfa; }
    .poll-countdown {
      margin-left: auto;
      font-size: .7rem;
      color: #7c3aed;
      font-weight: 700;
      font-family: 'Syne', sans-serif;
    }

    .ping-dot {
      display: none;
      width: 8px; height: 8px;
      background: #7c3aed;
      border-radius: 50%;
      box-shadow: 0 0 6px #7c3aed;
    }
    .ping-dot.visible { display: inline-block; }

    .diag-banner {
      display: none;
      background: #1a0a10;
      border-bottom: 1px solid #ff4d6d44;
      padding: 1rem 2rem;
      font-size: .8rem;
      color: #ffb3c0;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .diag-banner.show { display: flex; }
    .diag-banner strong { color: var(--danger); display: block; margin-bottom: .2rem; font-size: .82rem; }
    .diag-banner code {
      background: #2a0a10;
      padding: .15rem .5rem;
      border-radius: 5px;
      font-family: monospace;
      font-size: .8rem;
      color: #ffb3c0;
    }
    .diag-step { flex: 1; min-width: 200px; }
    .diag-step .num {
      display: inline-flex; align-items:center; justify-content:center;
      width: 20px; height: 20px;
      border-radius: 50%;
      background: #ff4d6d33;
      color: var(--danger);
      font-weight: 800;
      font-size: .7rem;
      margin-right: .4rem;
    }
  </style>
</head>
<body>

<!-- ═══ HEADER ═══ -->
<header class="staff-header">
  <div class="logo">Web<span>Call</span></div>
  <div class="status-dot" id="ws-dot"></div>
  <div class="ws-badge connecting" id="ws-badge"><i class="fa-solid fa-circle-notch fa-spin me-1"></i>Connecting…</div>
  <small class="text-muted" style="font-size:.72rem;">Staff Panel · <?= htmlspecialchars($SERVER_IP . $CFG['base_path']) ?></small>

  <!-- WebSocket Mode Toggle -->
  <div class="ws-toggle-wrap" title="Switch between WebSocket (real-time) and HTTP Polling (shared-hosting safe) mode">
    <div class="ws-mode-icon" id="mode-icon-poll" title="HTTP Polling mode"><i class="fa-solid fa-arrows-rotate"></i></div>
    <label class="toggle-pill" id="ws-toggle-pill">
      <input type="checkbox" id="ws-mode-toggle" <?= $initWsMode ? 'checked' : '' ?> onchange="toggleWsMode(this.checked)"/>
      <div class="toggle-track"><div class="toggle-thumb"></div></div>
    </label>
    <div class="ws-mode-icon <?= $initWsMode ? 'active' : '' ?>" id="mode-icon-ws" title="WebSocket mode"><i class="fa-brands fa-connectdevelop"></i></div>
    <span class="ws-toggle-label">Mode: <span class="mode-text <?= $initWsMode ? '' : 'nomode' ?>" id="mode-label-text"><?= $initWsMode ? 'WebSocket' : 'HTTP Polling' ?></span></span>
  </div>

  <div class="incoming-badge" id="global-incoming">
    <i class="fa-solid fa-phone-volume fa-shake"></i>&nbsp;Incoming call…
  </div>
</header>

<!-- ═══ NO-WS MODE BANNER ═══ -->
<div class="nowws-banner <?= $initWsMode ? '' : 'show' ?>" id="nowws-banner">
  <i class="fa-solid fa-arrows-rotate fa-spin"></i>
  <span><strong>HTTP Polling Mode</strong> — No WebSocket server required. Works on shared hosting. Refresh interval: <strong><?= $CFG['poll_slow_ms'] / 1000 ?>s</strong> idle / <strong><?= $CFG['poll_fast_ms'] / 1000 ?>s</strong> active.</span>
  <span class="poll-countdown" id="poll-countdown">Next poll: <?= $CFG['poll_slow_ms'] / 1000 ?>s</span>
</div>

<!-- ═══ OFFLINE DIAGNOSTIC BANNER ═══ -->
<div class="diag-banner" id="diag-banner">
  <div>
    <strong><i class="fa-solid fa-triangle-exclamation me-1"></i>Cannot connect to WebSocket signalling server</strong>
    Trying: <code id="diag-ws-url"><?= htmlspecialchars($CFG['ws_url']) ?></code>
  </div>
  <div class="diag-step">
    <span class="num">1</span>
    Install deps (once only):<br>
    <code>cd <?= htmlspecialchars(realpath('.') ?: '/path/to/webRTC') ?> &amp;&amp; npm install</code>
  </div>
  <div class="diag-step">
    <span class="num">2</span>
    Allow port through firewall (Windows):<br>
    <code>netsh advfirewall firewall add rule name="WebCall WS" protocol=TCP dir=in localport=<?= $WS_PORT ?> action=allow</code>
  </div>
  <div class="diag-step">
    <span class="num">3</span>
    Or start manually:<br>
    <code>node <?= htmlspecialchars(realpath('.') ?: '/path/to/webRTC') ?>/server.js</code>
  </div>
</div>

<!-- ═══ ROOMS ═══ -->
<div class="rooms-grid" id="rooms-grid"></div>

<!-- ═══ QR MODAL ═══ -->
<div class="qr-modal-backdrop" id="qr-backdrop">
  <div class="qr-modal-box">
    <button class="close-btn" onclick="closeQR()"><i class="fa fa-xmark"></i></button>
    <h6 id="qr-room-title">Room 1</h6>
    <p>Scan with phone to call staff in this room</p>
    <div id="qr-canvas-wrap"><div id="qr-canvas"></div></div>
    <div class="qr-url-text" id="qr-url-text"></div>
    <button class="copy-url-btn" onclick="copyUrl()"><i class="fa fa-copy me-1"></i>Copy Link</button>
  </div>
</div>

<audio id="ringtone" loop>
  <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg"/>
</audio>
<audio id="remote-audio" autoplay playsinline></audio>
<div class="toast-wrap" id="toast-wrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ═══════════════════════════════════════════════════════
   All constants (BASE_URL, WS_URL, WS_PORT, STUN,
   POLL_FAST_MS, POLL_SLOW_MS) are injected by cfg_js()
   in <head>. WEBCALL_CFG.ROOMS is the room list.
═══════════════════════════════════════════════════════ */
const ROOMS = WEBCALL_CFG.ROOMS;

/* ── Mode: 'ws' | 'poll' — from config ── */
let currentMode      = WEBCALL_CFG.WS_MODE ? 'ws' : 'poll';
let pollTimer        = null;
let pollCountdownInt = null;
let pollActive       = false;
const POLL_FAST      = POLL_FAST_MS;
const POLL_SLOW      = POLL_SLOW_MS;

/* ═══════════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════════ */
const roomState = {};
ROOMS.forEach(r => roomState[r.id] = { status:"idle", pc:null, timerInt:null, pendingOffer:null });

let localStream  = null;
let ws           = null;
let wsReady      = false;
let audioUnlocked = false;
let ringPending   = false;

const iceBuffer = {};
ROOMS.forEach(r => iceBuffer[r.id] = []);

function unlockAudio() {
  if (audioUnlocked) return;
  audioUnlocked = true;
  const ringtone = document.getElementById('ringtone');
  ringtone.play().then(() => {
    ringtone.pause();
    ringtone.currentTime = 0;
    if (ringPending) { ringPending = false; ringtone.play().catch(()=>{}); }
  }).catch(()=>{});
}
['click','touchstart','keydown','mousedown'].forEach(evt =>
  document.addEventListener(evt, unlockAudio, { once: true, capture: true })
);

/* ─────────────────────────────────────────────────────
   MODE TOGGLE
───────────────────────────────────────────────────── */
function toggleWsMode(wsOn) {
  currentMode = wsOn ? 'ws' : 'poll';

  fetch('config_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ws_mode: wsOn })
  }).then(r => r.json()).then(d => {
    if (d.ok) toast(`💾 Mode saved: ${wsOn ? 'WebSocket' : 'HTTP Polling'} (${d.updated})`);
    else toast(`⚠ Config save failed: ${d.error}`);
  }).catch(() => toast('⚠ Could not save config'));

  const labelEl     = document.getElementById('mode-label-text');
  const iconWs      = document.getElementById('mode-icon-ws');
  const iconPoll    = document.getElementById('mode-icon-poll');
  const diagBanner  = document.getElementById('diag-banner');
  const nowwsBanner = document.getElementById('nowws-banner');

  if (wsOn) {
    labelEl.textContent = 'WebSocket';
    labelEl.classList.remove('nomode');
    iconWs.classList.add('active');
    iconPoll.classList.remove('active');
    nowwsBanner.classList.remove('show');
    stopPoll();
    toast('🔌 Switched to WebSocket mode');
    if (ws) { ws.onclose = null; ws.close(); ws = null; wsReady = false; }
    wsRetry = 0;
    setWsBadge('connecting');
    autoStartServer();
  } else {
    labelEl.textContent = 'HTTP Polling';
    labelEl.classList.add('nomode');
    iconWs.classList.remove('active');
    iconPoll.classList.add('active');
    diagBanner.classList.remove('show');
    nowwsBanner.classList.add('show');
    if (ws) { ws.onclose = null; ws.close(); ws = null; wsReady = false; }
    setWsBadge('offline');
    document.getElementById('ws-badge').innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i>Polling';
    document.getElementById('ws-dot').className   = 'status-dot';
    toast('🔄 Switched to HTTP Polling mode — no server needed');
    startPoll();
  }
}

/* ─────────────────────────────────────────────────────
   HTTP POLLING
───────────────────────────────────────────────────── */
function getPollInterval() {
  const anyActive = Object.values(roomState).some(rs => rs.status === 'ringing' || rs.status === 'incall');
  return anyActive ? POLL_FAST : POLL_SLOW;
}

function startPoll() {
  stopPoll();
  (async () => {
    if (currentMode !== 'poll') return;
    await doPoll();
    if (currentMode === 'poll') schedulePoll();
  })();
  startPollCountdown();
}

function stopPoll() {
  clearTimeout(pollTimer);
  clearInterval(pollCountdownInt);
  pollTimer = null;
  pollCountdownInt = null;
}

function schedulePoll() {
  const delay = getPollInterval();
  pollTimer = setTimeout(async () => {
    if (currentMode !== 'poll') return;
    await doPoll();
    if (currentMode === 'poll') schedulePoll();
  }, delay);
}

function startPollCountdown() {
  clearInterval(pollCountdownInt);
  let next = getPollInterval() / 1000;
  document.getElementById('poll-countdown').textContent = `Next poll: ${next.toFixed(1)}s`;
  pollCountdownInt = setInterval(() => {
    next = Math.max(0, next - 0.1);
    document.getElementById('poll-countdown').textContent = `Next poll: ${next.toFixed(1)}s`;
  }, 100);
}

async function doPoll() {
  if (pollActive) return;
  pollActive = true;
  try {
    const res  = await fetch(`poll_state.php?action=staff_read&_=${Date.now()}`);
    const data = await res.json();
    if (!data.rooms) return;

    for (const [ridStr, info] of Object.entries(data.rooms)) {
      const rid = parseInt(ridStr);
      if (!roomState[rid]) continue;
      const rs = roomState[rid];

      if (info.status === 'calling' && rs.status === 'idle') {
        document.getElementById(`ping-dot-${rid}`).classList.add('visible');
        if (info.sdp) rs.pendingOffer = info.sdp;
        startRinging(rid);
        startPollCountdown();
      }
      else if (info.status === 'hangup' && rs.status !== 'idle') {
        endCall(rid, false);
        pollClear(rid).catch(()=>{});
      }
      else if (info.status === 'idle') {
        document.getElementById(`ping-dot-${rid}`).classList.remove('visible');
      }

      if (info.ice && info.ice.length) {
        if (rs.pc) {
          const toApply = [...iceBuffer[rid], ...info.ice];
          iceBuffer[rid] = [];
          for (const c of toApply) {
            await rs.pc.addIceCandidate(new RTCIceCandidate(c)).catch(()=>{});
          }
          await fetch(`poll_state.php?action=clear_ice&room=${rid}`).catch(()=>{});
        } else {
          iceBuffer[rid].push(...info.ice);
          await fetch(`poll_state.php?action=clear_ice&room=${rid}`).catch(()=>{});
        }
      }
    }
  } catch(e) { /* silent */ }
  finally { pollActive = false; }
}

async function pollClear(roomId)              { await fetch(`poll_state.php?action=clear&room=${roomId}`).catch(()=>{}); }
async function pollWriteAnswer(roomId, sdp)   { await fetch('poll_state.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'staff_answer',room:roomId,sdp})}).catch(()=>{}); }
async function pollWriteIce(roomId, c)        { await fetch('poll_state.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'staff_ice',room:roomId,candidate:c})}).catch(()=>{}); }
async function pollWriteHangup(roomId)        { await fetch('poll_state.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'staff_hangup',room:roomId})}).catch(()=>{}); }
async function pollWriteRejected(roomId)      { await fetch('poll_state.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'staff_rejected',room:roomId})}).catch(()=>{}); }

async function handleOfferPoll(roomId, sdp) {
  await ensureLocalStream();
  const pc = createPCPoll(roomId);
  roomState[roomId].pc = pc;
  await pc.setRemoteDescription(new RTCSessionDescription(sdp));
  const answer = await pc.createAnswer();
  await pc.setLocalDescription(answer);
  await pollWriteAnswer(roomId, pc.localDescription);
}

function createPCPoll(roomId) {
  const pc = new RTCPeerConnection(STUN);
  if (localStream) localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
  pc.onicecandidate = e => { if (e.candidate) pollWriteIce(roomId, e.candidate); };
  pc.ontrack = e => { document.getElementById('remote-audio').srcObject = e.streams[0]; };
  pc.onconnectionstatechange = () => {
    if (['disconnected','failed','closed'].includes(pc.connectionState)) endCall(roomId, false);
  };
  return pc;
}

/* ═══════════════════════════════════════════════════════
   RENDER ROOMS  (from config, not hard-coded)
═══════════════════════════════════════════════════════ */
const grid = document.getElementById("rooms-grid");
ROOMS.forEach(r => {
  const el = document.createElement("div");
  el.className = "room-card";
  el.id = `room-card-${r.id}`;
  el.innerHTML = `
    <div class="room-number">${r.id}</div>
    <div class="room-label"><i class="fa-solid fa-door-open me-2" style="color:var(--accent);font-size:.9rem;"></i>${r.label}</div>
    <div class="room-sublabel">${r.sub}</div>
    <div class="room-status idle" id="status-${r.id}">
      <span class="dot"></span>
      <span id="status-text-${r.id}">Idle</span>
    </div>
    <div class="room-actions">
      <button class="btn-qr" title="Show QR" onclick="showQR(${r.id})">
        <i class="fa-solid fa-qrcode"></i>
      </button>
      <span class="ping-dot" id="ping-dot-${r.id}" title="HTTP Poll: pending call request"></span>
      <div class="ring-anim" id="ring-anim-${r.id}">
        <i class="fa-solid fa-phone-volume"></i>&nbsp;Incoming…
      </div>
      <button class="btn-answer" id="btn-answer-${r.id}" onclick="answerCall(${r.id})">
        <i class="fa-solid fa-phone"></i> Answer
      </button>
      <button class="btn-reject" id="btn-reject-${r.id}" onclick="rejectCall(${r.id})">
        <i class="fa-solid fa-phone-slash"></i> Reject
      </button>
      <button class="btn-end" id="btn-end-${r.id}" onclick="endCall(${r.id}, true)">
        <i class="fa-solid fa-phone-slash"></i> End Call
      </button>
      <span class="call-timer" id="timer-${r.id}">00:00</span>
    </div>
  `;
  grid.appendChild(el);
});

/* ═══════════════════════════════════════════════════════
   WEBSOCKET SIGNALLING
═══════════════════════════════════════════════════════ */
let wsRetry = 0;

function connectWS() {
  wsRetry++;
  const label = wsRetry === 1 ? `Connecting to ${WS_URL}` : `Retry #${wsRetry} → ${WS_URL}`;
  toast(`🔌 ${label}`);
  setWsBadge("connecting");

  try { ws = new WebSocket(WS_URL); }
  catch(e) {
    toast(`❌ Cannot create WebSocket: ${e.message}`);
    setWsBadge("offline");
    setTimeout(connectWS, 4000);
    return;
  }

  ws.onopen = () => {
    wsReady = true; wsRetry = 0;
    ws.send(JSON.stringify({ type:"register", role:"staff" }));
    setWsBadge("online");
    toast("✅ Signalling server connected");
    document.getElementById("diag-banner").classList.remove("show");
  };

  ws.onclose = (ev) => {
    wsReady = false;
    setWsBadge("offline");
    const reason = ev.reason ? ` (${ev.reason})` : ` (code ${ev.code})`;
    toast(`⚠ WS closed${reason} – retrying in 3s…`);
    showDiag();
    setTimeout(connectWS, 3000);
  };

  ws.onerror = () => {
    wsReady = false;
    setWsBadge("offline");
    toast(`❌ Cannot reach signalling server on port ${WS_PORT} — is it running?`);
    showDiag();
  };

  ws.onmessage = async (ev) => {
    let msg;
    try { msg = JSON.parse(ev.data); } catch { return; }

    if (msg.type === "call-request")          startRinging(msg.room);
    else if (msg.type === "offer")            await handleOffer(msg.room, msg.sdp);
    else if (msg.type === "ice" && msg.target === "staff") {
      const pc = roomState[msg.room]?.pc;
      if (pc) await pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
    }
    else if (msg.type === "hangup")           endCall(msg.room, false);
    else if (msg.type === "reject")           endCall(msg.room, false);
  };
}

async function autoStartServer() {
  try {
    toast("⚙ Checking WebSocket server…");
    const res  = await fetch("autostart.php");
    const data = await res.json();
    if (data.status === "already_running" || data.status === "started") {
      if (data.status === "started") toast("🚀 WebSocket server started automatically");
      connectWS();
    } else {
      toast(`⚠ Autostart: ${data.message}`);
      connectWS();
    }
  } catch (e) {
    toast("⚠ autostart.php unreachable — connecting anyway…");
    connectWS();
  }
}

// Boot
if (currentMode === 'poll') {
  document.getElementById('mode-label-text').textContent = 'HTTP Polling';
  document.getElementById('mode-label-text').classList.add('nomode');
  document.getElementById('mode-icon-ws').classList.remove('active');
  document.getElementById('mode-icon-poll').classList.add('active');
  document.getElementById('nowws-banner').classList.add('show');
  setWsBadge('offline');
  document.getElementById('ws-badge').innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i>Polling';
  startPoll();
} else {
  autoStartServer();
}

function send(obj) {
  if (currentMode === 'ws' && ws && wsReady) ws.send(JSON.stringify(obj));
}

function setWsBadge(state) {
  const badge = document.getElementById("ws-badge");
  const dot   = document.getElementById("ws-dot");
  badge.className = `ws-badge ${state}`;
  dot.className   = `status-dot ${state === "online" ? "online" : ""}`;
  if (state === "online")       badge.innerHTML = '<i class="fa-solid fa-circle me-1" style="font-size:.55rem;"></i>Live';
  else if (state === "offline") badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Offline';
  else                          badge.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>Connecting…';
}

/* ═══════════════════════════════════════════════════════
   RING / ANSWER / REJECT / END
═══════════════════════════════════════════════════════ */
function startRinging(roomId) {
  if (roomState[roomId].status !== "idle") return;
  roomState[roomId].status = "ringing";
  setCardClass(roomId, "ringing");
  setStatusBadge(roomId, "ringing", "Ringing");
  show(`ring-anim-${roomId}`);
  show(`btn-answer-${roomId}`);
  show(`btn-reject-${roomId}`);
  document.getElementById("global-incoming").classList.add("show");
  const ringtone = document.getElementById("ringtone");
  if (audioUnlocked) ringtone.play().catch(()=>{});
  else ringPending = true;
  toast(`📞 Incoming call – Room ${roomId}`);
}

async function handleOffer(roomId, sdp) {
  roomState[roomId].pendingOffer = sdp;
}

async function answerCall(roomId) {
  roomState[roomId].status = "incall";
  hide(`ring-anim-${roomId}`);
  hide(`btn-answer-${roomId}`);
  hide(`btn-reject-${roomId}`);
  setCardClass(roomId, "active-call");
  setStatusBadge(roomId, "incall", "In Call");
  show(`btn-end-${roomId}`);
  show(`timer-${roomId}`);
  document.getElementById(`ping-dot-${roomId}`).classList.remove('visible');
  ringPending = false; document.getElementById("ringtone").pause();
  document.getElementById("global-incoming").classList.remove("show");

  if (currentMode === 'ws') {
    const offerSdp = roomState[roomId].pendingOffer;
    if (offerSdp) {
      await ensureLocalStream();
      const pc = createPCWs(roomId);
      roomState[roomId].pc = pc;
      await pc.setRemoteDescription(new RTCSessionDescription(offerSdp));
      const answer = await pc.createAnswer();
      await pc.setLocalDescription(answer);
      send({ type:"answer",   room: roomId, sdp: pc.localDescription });
      send({ type:"answered", room: roomId });
      roomState[roomId].pendingOffer = null;
    } else {
      toast(`⚠ No offer SDP for room ${roomId}`);
      resetRoom(roomId); return;
    }
  } else {
    const offerSdp = roomState[roomId].pendingOffer;
    if (offerSdp) {
      await handleOfferPoll(roomId, offerSdp);
      roomState[roomId].pendingOffer = null;
      if (iceBuffer[roomId].length) {
        const pc = roomState[roomId].pc;
        if (pc) for (const c of iceBuffer[roomId]) await pc.addIceCandidate(new RTCIceCandidate(c)).catch(()=>{});
        iceBuffer[roomId] = [];
      }
      startPollCountdown();
    } else {
      toast(`⚠ No offer SDP for room ${roomId}`);
      resetRoom(roomId); return;
    }
  }

  let secs = 0;
  roomState[roomId].timerInt = setInterval(() => {
    secs++;
    const m = String(Math.floor(secs/60)).padStart(2,"0");
    const s = String(secs%60).padStart(2,"0");
    document.getElementById(`timer-${roomId}`).textContent = `${m}:${s}`;
  }, 1000);
}

function rejectCall(roomId) {
  if (currentMode === 'ws') send({ type:"rejected", room: roomId });
  else pollWriteRejected(roomId);
  roomState[roomId].pendingOffer = null;
  resetRoom(roomId);
  document.getElementById(`ping-dot-${roomId}`).classList.remove('visible');
  ringPending = false; document.getElementById("ringtone").pause();
  document.getElementById("global-incoming").classList.remove("show");
  toast(`Rejected call – Room ${roomId}`);
}

function endCall(roomId, sendSignal) {
  if (sendSignal) {
    if (currentMode === 'ws') send({ type:"hangup", room: roomId });
    else pollWriteHangup(roomId);
  }
  if (roomState[roomId].pc) { roomState[roomId].pc.close(); roomState[roomId].pc = null; }
  roomState[roomId].pendingOffer = null;
  iceBuffer[roomId] = [];
  const anyOtherActive = Object.entries(roomState).some(([id, rs]) => parseInt(id) !== roomId && rs.pc !== null);
  if (!anyOtherActive && localStream) { localStream.getTracks().forEach(t=>t.stop()); localStream = null; }
  document.getElementById(`ping-dot-${roomId}`).classList.remove('visible');
  resetRoom(roomId);
  ringPending = false; document.getElementById("ringtone").pause();
  document.getElementById("global-incoming").classList.remove("show");
  if (currentMode === 'poll' && sendSignal) pollClear(roomId);
}

function resetRoom(roomId) {
  roomState[roomId].status = "idle";
  if (roomState[roomId].timerInt) { clearInterval(roomState[roomId].timerInt); roomState[roomId].timerInt = null; }
  document.getElementById(`timer-${roomId}`).textContent = "00:00";
  setCardClass(roomId, "");
  setStatusBadge(roomId, "idle", "Idle");
  hide(`ring-anim-${roomId}`); hide(`btn-answer-${roomId}`);
  hide(`btn-reject-${roomId}`); hide(`btn-end-${roomId}`); hide(`timer-${roomId}`);
}

/* ═══════════════════════════════════════════════════════
   WEBRTC
═══════════════════════════════════════════════════════ */
async function ensureLocalStream() {
  if (!localStream) localStream = await navigator.mediaDevices.getUserMedia({ audio:true, video:false });
}

function createPCWs(roomId) {
  const pc = new RTCPeerConnection(STUN);
  if (localStream) localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
  pc.onicecandidate = e => { if (e.candidate) send({ type:"ice", target:"guest", room:roomId, candidate:e.candidate }); };
  pc.ontrack = e => { document.getElementById("remote-audio").srcObject = e.streams[0]; };
  pc.onconnectionstatechange = () => {
    if (["disconnected","failed","closed"].includes(pc.connectionState)) endCall(roomId, false);
  };
  return pc;
}

/* ═══════════════════════════════════════════════════════
   QR CODE
═══════════════════════════════════════════════════════ */
let currentQrUrl = "";
function showQR(roomId) {
  currentQrUrl = `${BASE_URL}/guest.php?room=${roomId}`;
  document.getElementById("qr-room-title").textContent = `Room ${roomId} – QR Code`;
  document.getElementById("qr-url-text").textContent   = currentQrUrl;
  const wrap = document.getElementById("qr-canvas");
  wrap.innerHTML = "";
  new QRCode(wrap, {
    text: currentQrUrl, width:190, height:190,
    colorDark:"#000000", colorLight:"#ffffff",
    correctLevel: QRCode.CorrectLevel.H
  });
  document.getElementById("qr-backdrop").classList.add("show");
}
function closeQR() { document.getElementById("qr-backdrop").classList.remove("show"); }
function copyUrl() { navigator.clipboard.writeText(currentQrUrl).then(() => toast("✓ Link copied!")); }
document.getElementById("qr-backdrop").addEventListener("click", function(e){ if(e.target===this) closeQR(); });

/* ═══════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════ */
function show(id){ const e=document.getElementById(id); if(e) e.classList.add("visible"); }
function hide(id){ const e=document.getElementById(id); if(e) e.classList.remove("visible"); }
function setCardClass(roomId, cls) {
  const card = document.getElementById(`room-card-${roomId}`);
  card.classList.remove("ringing","active-call");
  if (cls) card.classList.add(cls);
}
function setStatusBadge(roomId, cls, label) {
  document.getElementById(`status-${roomId}`).className = `room-status ${cls}`;
  document.getElementById(`status-text-${roomId}`).textContent = label;
}
function showDiag() { document.getElementById("diag-banner").classList.add("show"); }
function toast(msg) {
  const wrap = document.getElementById("toast-wrap");
  const div  = document.createElement("div");
  div.className = "toast-msg";
  div.textContent = msg;
  wrap.appendChild(div);
  setTimeout(() => div.remove(), 4000);
}
</script>
</body>
</html>

<?php
/**
 * guest.php  –  WebCall Guest Page (phone/kiosk)
 * All settings come from webcall_config.json via config.php.
 */
require_once __DIR__ . '/config.php';

$ROOM       = isset($_GET['room']) ? intval($_GET['room']) : 1;

// Build room name map from config
$ROOM_NAMES = [];
foreach ($CFG['rooms'] as $r) {
    $ROOM_NAMES[$r['id']] = $r['label'];
}
$ROOM_LABEL = $ROOM_NAMES[$ROOM] ?? "Room {$ROOM}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <title>WebCall – <?= htmlspecialchars($ROOM_LABEL) ?></title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>

  <!-- ── All JS config constants injected from webcall_config.json ── -->
  <?= cfg_js() ?>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

    :root {
      --bg:      #0b0f1a;
      --surface: #131929;
      --border:  #1e2d47;
      --accent:  #00d4ff;
      --danger:  #ff4d6d;
      --success: #00e096;
      --text:    #e2eaf8;
      --muted:   #5a7098;
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

    html, body { height: 100%; overflow: hidden; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed; inset: 0;
      background: radial-gradient(ellipse 80% 60% at 50% 50%, #0d2e55 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }

    .brand {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--accent);
      position: fixed;
      top: 1.4rem; left: 50%;
      transform: translateX(-50%);
      z-index: 10;
      white-space: nowrap;
    }
    .brand span { color: var(--text); }

    .screen {
      position: relative; z-index: 1;
      display: none;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
      width: 100%;
    }
    .screen.active { display: flex; }

    .room-badge {
      background: #1a2540;
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: .35rem 1rem;
      font-size: .72rem;
      font-weight: 700;
      color: var(--muted);
      letter-spacing: .6px;
      text-transform: uppercase;
      margin-bottom: 2.2rem;
    }

    .call-btn-wrap {
      position: relative;
      display: flex; align-items:center; justify-content:center;
      margin-bottom: 2rem;
    }

    .ring {
      position: absolute;
      border-radius: 50%;
      border: 2px solid var(--accent);
      opacity: 0;
      pointer-events: none;
    }
    .ring-1 { width:148px; height:148px; }
    .ring-2 { width:192px; height:192px; }
    .ring-3 { width:236px; height:236px; }

    .ringing .ring-1 { animation: ripple 1.8s 0.0s infinite ease-out; }
    .ringing .ring-2 { animation: ripple 1.8s 0.4s infinite ease-out; }
    .ringing .ring-3 { animation: ripple 1.8s 0.8s infinite ease-out; }

    .incall .ring-1 { animation: ripple-slow 2.5s 0.0s infinite ease-out; border-color:var(--success); }
    .incall .ring-2 { animation: ripple-slow 2.5s 0.6s infinite ease-out; border-color:var(--success); }

    @keyframes ripple      { 0%{transform:scale(.55);opacity:.8} 100%{transform:scale(1);opacity:0} }
    @keyframes ripple-slow { 0%{transform:scale(.6);opacity:.5}  100%{transform:scale(1);opacity:0} }

    .call-btn {
      width: 108px; height: 108px;
      border-radius: 50%;
      border: none;
      display: flex; align-items:center; justify-content:center;
      cursor: pointer;
      font-size: 2.6rem;
      position: relative; z-index: 2;
      transition: transform .15s, box-shadow .3s;
      -webkit-tap-highlight-color: transparent;
    }

    .call-btn.idle {
      background: linear-gradient(145deg, #00d4ff, #0099cc);
      color: #000;
      box-shadow: 0 0 50px #00d4ff55;
    }
    .call-btn.idle:active { transform: scale(.93); }

    .call-btn.calling {
      background: linear-gradient(145deg, #0d3d52, #081e2a);
      color: var(--accent);
      box-shadow: 0 0 40px #00d4ff33;
      animation: btn-ring-pulse 1s infinite alternate;
    }
    @keyframes btn-ring-pulse {
      from { box-shadow: 0 0 30px #00d4ff33; }
      to   { box-shadow: 0 0 60px #00d4ff77; }
    }

    .call-btn.in-call {
      background: linear-gradient(145deg, #0f5c38, #082a1a);
      color: var(--success);
      box-shadow: 0 0 40px #00e09633;
    }

    .call-btn.ending {
      background: linear-gradient(145deg, var(--danger), #c0263c);
      color: #fff;
      box-shadow: 0 0 40px #ff4d6d44;
    }
    .call-btn.ending:active { transform: scale(.93); }

    .call-label {
      font-family: 'Syne', sans-serif;
      font-size: 1.35rem;
      font-weight: 800;
      margin-bottom: .4rem;
    }
    .call-sub {
      font-size: .85rem;
      color: var(--muted);
      max-width: 260px;
      line-height: 1.5;
    }

    .call-status-pill {
      display: none;
      align-items: center;
      gap: .6rem;
      background: #00e09614;
      border: 1px solid #00e09633;
      border-radius: 30px;
      padding: .5rem 1.2rem;
      margin-top: 1.5rem;
      font-size: .82rem;
      font-weight: 600;
      color: var(--success);
    }
    .call-status-pill.visible { display: flex; }
    .call-status-pill .dot {
      width: 8px; height: 8px;
      background: var(--success);
      border-radius: 50%;
      box-shadow: 0 0 8px var(--success);
      animation: dot-pulse 1.4s infinite;
    }
    @keyframes dot-pulse { 0%,100%{opacity:1}50%{opacity:.3} }

    .timer {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .95rem;
    }

    .ws-notice {
      position: fixed; bottom: 1.2rem; left: 50%; transform: translateX(-50%);
      background: #ff4d6d18;
      border: 1px solid #ff4d6d44;
      color: var(--danger);
      font-size: .72rem;
      font-weight: 600;
      padding: .35rem 1rem;
      border-radius: 20px;
      display: none;
      z-index: 100;
    }
    .ws-notice.visible { display: block; }

    #screen-busy .icon-wrap {
      width: 88px; height: 88px;
      border-radius: 50%;
      background: #1a2540;
      border: 2px solid var(--border);
      display: flex; align-items:center; justify-content:center;
      font-size: 2rem;
      color: var(--muted);
      margin-bottom: 1.5rem;
    }
    .retry-btn {
      margin-top: 1.2rem;
      background: #1a2540;
      border: 1px solid var(--border);
      color: var(--muted);
      padding: .55rem 1.4rem;
      border-radius: 12px;
      font-size: .82rem;
      cursor: pointer;
      transition: all .2s;
    }
    .retry-btn:hover { color: var(--accent); border-color: var(--accent); }

    .mode-pill {
      position: fixed;
      top: 1.4rem; right: 1rem;
      background: #1a2540;
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: .25rem .65rem;
      font-size: .62rem;
      font-weight: 700;
      color: var(--muted);
      letter-spacing: .5px;
      text-transform: uppercase;
      z-index: 10;
    }
    .mode-pill.poll { border-color: #7c3aed44; color: #a78bfa; background: #120f28; }

    audio { display: none; }
  </style>
</head>
<body>

<div class="brand">Web<span>Call</span></div>
<div class="mode-pill" id="mode-pill">
  <i class="fa-solid fa-circle-notch fa-spin me-1"></i>Detecting…
</div>

<!-- ─── MAIN SCREEN ─── -->
<div class="screen active" id="screen-main">
  <div class="room-badge">
    <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($ROOM_LABEL) ?>
  </div>

  <div class="call-btn-wrap" id="call-btn-wrap">
    <div class="ring ring-1"></div>
    <div class="ring ring-2"></div>
    <div class="ring ring-3"></div>
    <button class="call-btn idle" id="call-btn" onclick="handleBtn()">
      <i class="fa-solid fa-bell" id="call-icon"></i>
    </button>
  </div>

  <div class="call-label" id="call-label">Call to Staff</div>
  <div class="call-sub"   id="call-sub">Tap the bell to call for assistance</div>

  <div class="call-status-pill" id="status-pill">
    <div class="dot"></div>
    <span>Connected</span>
    <span class="timer" id="timer-text">00:00</span>
  </div>
</div>

<!-- ─── BUSY SCREEN ─── -->
<div class="screen" id="screen-busy">
  <div class="icon-wrap"><i class="fa-solid fa-phone-slash"></i></div>
  <div class="call-label" style="font-size:1.05rem;">Staff is currently busy</div>
  <div class="call-sub">All staff members are occupied.<br>Please try again shortly.</div>
  <button class="retry-btn" onclick="location.reload()">
    <i class="fa-solid fa-rotate-right me-1"></i> Try Again
  </button>
</div>

<!-- ─── WS Notice ─── -->
<div class="ws-notice" id="ws-notice">
  <i class="fa-solid fa-wifi me-1"></i> Reconnecting to server…
</div>

<audio id="local-audio"  autoplay playsinline muted></audio>
<audio id="remote-audio" autoplay playsinline></audio>

<script>
/* ═══════════════════════════════════════════════════
   CONFIG — from WEBCALL_CFG injected by cfg_js()
   WS_URL, WS_PORT, STUN, POLL_FAST_MS, POLL_SLOW_MS
   are all global constants set by config.php.
═══════════════════════════════════════════════════ */
const ROOM = <?= $ROOM ?>;

/* ═══════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════ */
let ws          = null;
let wsReady     = false;
let pc          = null;
let localStream = null;
let callState   = "idle";
let timerSec    = 0;
let timerInt    = null;
let pollTimer   = null;
let pollActive  = false;
let MODE        = null;
let pendingIncall  = false;
let remoteDescSet  = false;

/* ═══════════════════════════════════════════════════
   BOOT — read mode from WEBCALL_CFG (already in page),
   no extra fetch needed unless you want live override.
═══════════════════════════════════════════════════ */
(async function boot() {
  const pill = document.getElementById('mode-pill');

  // Try fetching config for live mode override (staff can toggle without reloading guest)
  try {
    const res    = await fetch(`webcall_config.json?_=${Date.now()}`);
    const config = await res.json();
    // Support both old flat format and new nested format
    if (config.mode && typeof config.mode.ws_mode !== 'undefined') {
      MODE = config.mode.ws_mode === false ? 'poll' : 'ws';
    } else {
      MODE = config.ws_mode === false ? 'poll' : 'ws';
    }
  } catch(e) {
    // Fallback: use what PHP already injected
    MODE = WEBCALL_CFG.WS_MODE ? 'ws' : 'poll';
  }

  if (MODE === 'poll') {
    pill.classList.add('poll');
    pill.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i>Polling';
  } else {
    pill.innerHTML = '<i class="fa-brands fa-connectdevelop me-1"></i>WebSocket';
  }

  if (MODE === 'poll') startGuestPoll();
  else                 connectWS();
})();

/* ═══════════════════════════════════════════════════
   WEBSOCKET
═══════════════════════════════════════════════════ */
function connectWS() {
  ws = new WebSocket(WS_URL);

  ws.onopen = () => {
    wsReady = true;
    ws.send(JSON.stringify({ type:"register", role:"guest", room: ROOM }));
    document.getElementById("ws-notice").classList.remove("visible");
  };

  ws.onclose = () => {
    wsReady = false;
    document.getElementById("ws-notice").innerHTML =
      `<i class="fa-solid fa-wifi me-1"></i> Reconnecting to <b>${WS_URL}</b>…`;
    document.getElementById("ws-notice").classList.add("visible");
    setTimeout(connectWS, 3000);
  };

  ws.onerror = () => {
    wsReady = false;
    document.getElementById("ws-notice").innerHTML =
      `<i class="fa-solid fa-triangle-exclamation me-1"></i> Server offline — run <b>node server.js</b> on port ${WS_PORT}`;
    document.getElementById("ws-notice").classList.add("visible");
  };

  ws.onmessage = async (ev) => {
    let msg;
    try { msg = JSON.parse(ev.data); } catch { return; }

    if (msg.type === "answered" && msg.room === ROOM) {
      if (pc && pc.remoteDescription) setState("incall");
      else pendingIncall = true;
    }
    else if (msg.type === "answer" && msg.room === ROOM) {
      if (pc) {
        await pc.setRemoteDescription(new RTCSessionDescription(msg.sdp)).catch(()=>{});
        if (pendingIncall) { pendingIncall = false; setState("incall"); }
      }
    }
    else if (msg.type === "ice" && msg.target === "guest" && msg.room === ROOM) {
      if (pc) await pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(()=>{});
    }
    else if (msg.type === "hangup"   && msg.room === ROOM) { cleanup(); setState("idle"); }
    else if (msg.type === "rejected" && msg.room === ROOM) { cleanup(); showBusy(); }
  };
}

function send(obj) { if (ws && wsReady) ws.send(JSON.stringify(obj)); }

/* ═══════════════════════════════════════════════════
   HTTP POLLING
═══════════════════════════════════════════════════ */
function getPollMs() {
  return (callState === 'calling' || callState === 'incall') ? POLL_FAST_MS : POLL_SLOW_MS;
}

function startGuestPoll() {
  const notice = document.getElementById("ws-notice");
  notice.innerHTML  = '<i class="fa-solid fa-arrows-rotate me-1"></i> HTTP Polling mode — no server required';
  notice.classList.add("visible");
  notice.style.background  = '#120f2880';
  notice.style.borderColor = '#7c3aed44';
  notice.style.color       = '#a78bfa';
  scheduleGuestPoll();
}

function scheduleGuestPoll() {
  clearTimeout(pollTimer);
  pollTimer = setTimeout(async () => {
    if (MODE !== 'poll') return;
    await guestPollTick();
    if (MODE === 'poll') scheduleGuestPoll();
  }, getPollMs());
}

async function guestPollTick() {
  if (pollActive) return;
  pollActive = true;
  try {
    const res  = await fetch(`poll_state.php?action=guest_read&room=${ROOM}&_=${Date.now()}`);
    const data = await res.json();
    const info = data.room || {};

    if (info.staff_sdp && callState === 'calling' && pc && !remoteDescSet) {
      const ok = await pc.setRemoteDescription(new RTCSessionDescription(info.staff_sdp)).then(()=>true).catch(()=>false);
      if (ok) {
        remoteDescSet = true;
        await fetch(`poll_state.php?action=ack_answer&room=${ROOM}`).catch(()=>{});
      }
    }

    if (info.status === 'answered' && callState === 'calling' && remoteDescSet) setState("incall");

    if (info.status === 'hangup'   && callState !== 'idle') {
      cleanup(); setState("idle");
      fetch(`poll_state.php?action=clear&room=${ROOM}`).catch(()=>{});
    }
    if (info.status === 'rejected' && callState !== 'idle') {
      cleanup(); showBusy();
      fetch(`poll_state.php?action=clear&room=${ROOM}`).catch(()=>{});
    }

    if (info.staff_ice && info.staff_ice.length && pc && remoteDescSet) {
      for (const c of info.staff_ice) await pc.addIceCandidate(new RTCIceCandidate(c)).catch(()=>{});
      fetch(`poll_state.php?action=clear_staff_ice&room=${ROOM}`).catch(()=>{});
    }
  } catch(e) { /* silent */ }
  finally { pollActive = false; }
}

async function pollWrite(payload) {
  await fetch('poll_state.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).catch(()=>{});
}

/* ═══════════════════════════════════════════════════
   CALL FLOW
═══════════════════════════════════════════════════ */
async function handleBtn() {
  if (callState === "idle")    { await startCall(); return; }
  if (callState === "calling") { hangUp(); return; }
  if (callState === "incall")  { hangUp(); return; }
}

async function startCall() {
  if (MODE === 'ws' && !wsReady) { alert("Not connected to server. Please wait."); return; }
  setState("calling");

  try {
    localStream = await navigator.mediaDevices.getUserMedia({ audio:true, video:false });
    document.getElementById("local-audio").srcObject = localStream;
  } catch(e) {
    alert("Microphone permission is required to make a call.");
    setState("idle"); return;
  }

  pc = new RTCPeerConnection(STUN);
  localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

  pc.onicecandidate = e => {
    if (e.candidate) {
      if (MODE === 'ws') send({ type:"ice", target:"staff", room:ROOM, candidate:e.candidate });
      else pollWrite({ action:'guest_ice', room:ROOM, candidate:e.candidate });
    }
  };
  pc.ontrack = e => { document.getElementById("remote-audio").srcObject = e.streams[0]; };
  pc.onconnectionstatechange = () => {
    if (["disconnected","failed","closed"].includes(pc.connectionState)) {
      cleanup(); setState("idle");
    }
  };

  if (MODE === 'ws') {
    send({ type:"call-request", room:ROOM });
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    send({ type:"offer", room:ROOM, sdp:pc.localDescription });
  } else {
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    await pollWrite({ action:'guest_call', room:ROOM, sdp:pc.localDescription });
    scheduleGuestPoll();
  }
}

function hangUp() {
  if (MODE === 'ws') send({ type:"hangup", room:ROOM });
  else pollWrite({ action:'guest_hangup', room:ROOM });
  cleanup();
  setState("idle");
}

function cleanup() {
  if (pc) { pc.close(); pc = null; }
  if (localStream) { localStream.getTracks().forEach(t=>t.stop()); localStream = null; }
  pendingIncall = false;
  remoteDescSet = false;
  clearTimeout(pollTimer);
  clearInterval(timerInt); timerSec = 0;
  document.getElementById("timer-text").textContent = "00:00";
  if (MODE === 'poll') scheduleGuestPoll();
}

function showBusy() {
  document.getElementById("screen-main").classList.remove("active");
  document.getElementById("screen-busy").classList.add("active");
}

/* ═══════════════════════════════════════════════════
   STATE MACHINE
═══════════════════════════════════════════════════ */
function setState(state) {
  callState = state;
  const btn   = document.getElementById("call-btn");
  const icon  = document.getElementById("call-icon");
  const label = document.getElementById("call-label");
  const sub   = document.getElementById("call-sub");
  const wrap  = document.getElementById("call-btn-wrap");
  const pill  = document.getElementById("status-pill");

  btn.className  = "call-btn";
  wrap.className = "call-btn-wrap";
  pill.classList.remove("visible");
  clearInterval(timerInt);

  if (state === "idle") {
    btn.classList.add("idle");
    icon.className    = "fa-solid fa-bell";
    label.textContent = "Call to Staff";
    sub.textContent   = "Tap the bell to call for assistance";

  } else if (state === "calling") {
    btn.classList.add("calling");
    icon.className    = "fa-solid fa-phone-volume fa-shake";
    label.textContent = "Calling…";
    sub.textContent   = "Waiting for staff to answer";
    wrap.classList.add("ringing");

  } else if (state === "incall") {
    btn.classList.add("ending");
    icon.className    = "fa-solid fa-phone-slash";
    label.textContent = "Connected";
    sub.textContent   = "Tap to end the call";
    wrap.classList.add("incall");
    pill.classList.add("visible");

    timerSec = 0;
    timerInt = setInterval(() => {
      timerSec++;
      const m = String(Math.floor(timerSec/60)).padStart(2,"0");
      const s = String(timerSec%60).padStart(2,"0");
      document.getElementById("timer-text").textContent = `${m}:${s}`;
    }, 1000);
  }
}
</script>
</body>
</html>

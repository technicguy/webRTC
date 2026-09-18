/**
 * WebCall Signalling Server  (Node.js)
 * Requires:  npm install ws
 * Run:       node server.js
 * Listens:   0.0.0.0:9090
 */

const { WebSocketServer } = require('ws');

const WS_HOST = '0.0.0.0';
const WS_PORT = 9090;

// clients map: ws → { role, room }
const clients = new Map();

const wss = new WebSocketServer({ host: WS_HOST, port: WS_PORT }, () => {
  console.log('╔══════════════════════════════════════╗');
  console.log('║   WebCall Signalling Server v2.0     ║');
  console.log('╠══════════════════════════════════════╣');
  console.log(`║  Listening on ws://${WS_HOST}:${WS_PORT}      ║`);
  console.log('╚══════════════════════════════════════╝');
  console.log('[server] Ready – waiting for connections…\n');
});

wss.on('connection', (ws, req) => {
  const ip = req.socket.remoteAddress;
  clients.set(ws, { role: null, room: null });
  log('connect', ws, `from ${ip}`);

  ws.on('message', (raw) => {
    let msg;
    try { msg = JSON.parse(raw); } catch { return; }

    const { type, room } = msg;
    const roomId = room != null ? parseInt(room) : null;

    log('msg', ws, `type=${type} room=${roomId}`);

    switch (type) {

      case 'register':
        clients.get(ws).role = msg.role || 'guest';
        clients.get(ws).room = roomId;
        log('register', ws, `role=${msg.role} room=${roomId}`);
        safeSend(ws, { type: 'registered', ok: true });
        break;

      // Guest → Staff
      case 'call-request':
      case 'offer':
      case 'hangup':
        clients.get(ws).room = roomId;
        relay(findStaff(), msg);
        break;

      case 'ice': {
        const target = msg.target === 'staff' ? findStaff() : findGuest(roomId);
        relay(target, msg);
        break;
      }

      // Staff → Guest
      case 'answered':
      case 'answer':
      case 'rejected':
        relay(findGuest(roomId), msg);
        break;

      case 'hangup_staff':
        relay(findGuest(roomId), { ...msg, type: 'hangup' });
        break;
    }
  });

  ws.on('close', () => {
    const c = clients.get(ws);
    log('disconnect', ws, `role=${c?.role} room=${c?.room}`);
    clients.delete(ws);
  });

  ws.on('error', (err) => {
    log('error', ws, err.message);
    clients.delete(ws);
  });
});

/* ── Helpers ── */
function findStaff() {
  const staff = [];
  for (const [sock, info] of clients)
    if (info.role === 'staff') staff.push(sock);
  return staff;
}

function findGuest(room) {
  for (const [sock, info] of clients)
    if (info.role === 'guest' && info.room === room) return sock;
  return null;
}

function safeSend(ws, data) {
  if (ws && ws.readyState === ws.OPEN)
    ws.send(JSON.stringify(data));
}

function relay(dest, msg) {
  if (Array.isArray(dest)) {
    dest.forEach(d => safeSend(d, msg));
  } else {
    safeSend(dest, msg);
  }
}

function log(event, ws, detail = '') {
  const time = new Date().toTimeString().slice(0, 8);
  const id   = ws ? clients.get(ws)?.role ?? '?' : '-';
  console.log(`[${time}] [${event}] ${id}${detail ? ' – ' + detail : ''}`);
}

# 📞 WebCall WebRTC Intercom System (v2.1)

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Node.js](https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=nodedotjs&logoColor=white)](https://nodejs.org/)
[![WebRTC](https://img.shields.io/badge/WebRTC-333333?style=for-the-badge&logo=webrtc&logoColor=white)](https://webrtc.org/)
[![WebSockets](https://img.shields.io/badge/WebSockets-010101?style=for-the-badge&logo=socketdotio&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

**WebCall** is a real-time **WebRTC Audio/Video Intercom & Signalling System** designed for reception desks, waiting rooms, kiosks, and multi-room facility calling. It features a single centralized JSON configuration, dual-signaling mode support (**WebSocket** or **HTTP Polling** for shared hosting), QR code guest auto-pairing, and an automated background process manager.

---

## 🌟 Key Features

| Feature | Description |
| :--- | :--- |
| 🎛️ **Centralized Configuration** | Single JSON configuration (`webcall_config.json`) controls IP, ports, STUN servers, rooms, and signaling mode. |
| ⚡ **Dual Signaling Modes** | Toggle seamlessly between **WebSocket Mode** (low latency Node.js `ws`) and **HTTP Polling Mode** (shared hosting compatible). |
| 🖥️ **Staff Panel (`index.php`)** | Dashboard for staff to monitor rooms, accept/reject calls, switch signaling modes, and generate QR code links. |
| 📱 **Guest Kiosk/Phone (`guest.php`)** | Mobile-friendly interface allowing guests to initiate video/audio calls by scanning QR codes. |
| 🚀 **Auto-Launcher (`autostart.php`)** | Reads configuration and automatically launches `node server.js` in the background when running on WAMP/local servers. |
| 🔒 **HTTPS & WSS Ready** | Seamless switching between HTTP/WS and HTTPS/WSS for secure browser camera/microphone access. |

---

## 🏗️ System Architecture

```
 +--------------------------------------------------------------------+
 |                    Central Configuration                            |
 |                   webcall_config.json                              |
 +--------------------------------------------------------------------+
                                   |
                  +----------------+----------------+
                  |                                 |
                  v                                 v
   +------------------------------+  +------------------------------+
   |  Staff Dashboard (index.php) |  |   Guest Kiosk (guest.php)   |
   +------------------------------+  +------------------------------+
                  |                                 |
                  +----------------+----------------+
                                   |
                         Signaling Connection
                                   |
            +----------------------+----------------------+
            |                                             |
            v (ws_mode: true)                             v (ws_mode: false)
 +------------------------------+              +------------------------------+
 |  WebSocket Server (Node.js)  |              |    HTTP Polling Fallback     |
 |          server.js           |              |       poll_state.php         |
 +------------------------------+              +------------------------------+
            |                                             |
            +----------------------+----------------------+
                                   |
                                   v
 +--------------------------------------------------------------------+
 |                   Peer-to-Peer WebRTC Media Stream                 |
 |            (Audio/Video Stream via Google STUN / TURN)            |
 +--------------------------------------------------------------------+
```

---

## 📁 Project Structure

```
webRTC/
├── webcall_config.json  # ★ Central configuration file (IP, ports, rooms, mode)
├── config.php           # Config loader (included by PHP scripts)
├── config_save.php      # Mode toggle endpoint (WS vs Polling)
├── index.php            # Staff Management Dashboard & Room Monitor
├── guest.php            # Guest Kiosk / Mobile Call Interface
├── autostart.php        # Auto-launcher for Node.js WebSocket signaling server
├── poll_state.php       # HTTP Polling state engine for shared hosting
├── poll_data.json       # Polling state cache storage
├── server.js            # Node.js WebSocket Signaling Server
├── package.json         # Node.js dependencies (`ws`)
└── README.md            # System documentation
```

---

## ⚙️ Configuration (`webcall_config.json`)

All system settings are controlled from **`webcall_config.json`**:

```json
{
    "server": {
        "ip":       "192.168.18.72",   
        "protocol": "http",            
        "path":     "/webRTC"          
    },
    "websocket": {
        "port":     9090,              
        "protocol": "ws"               
    },
    "mode": {
        "ws_mode": true                
    },
    "rooms": [
        { "id": 1, "label": "Reception",  "sub": "Main lobby area"   },
        { "id": 2, "label": "Lounge",     "sub": "2nd floor waiting" },
        { "id": 3, "label": "Info Desk",  "sub": "Ground level"      },
        { "id": 4, "label": "Exit Gate",  "sub": "Parking level B1"  }
    ],
    "polling": {
        "fast_ms": 600,   
        "slow_ms": 2000   
    },
    "stun": {
        "urls": "stun:stun.l.google.com:19302"
    }
}
```

---

## 🚀 Modes of Operation

### 1. ⚡ WebSocket Mode (`ws_mode: true`)
- Provides real-time, ultra-low latency WebRTC signaling.
- Requires Node.js (`server.js`) running on the server.
- Automatically started on local WAMP via `autostart.php`.

### 2. 🌐 HTTP Polling Mode (`ws_mode: false`)
- **Shared Hosting Compatible**: Requires no Node.js environment.
- Guest and Staff exchange SDP offers/answers and ICE candidates via `poll_state.php`.
- Dynamic polling speeds (`fast_ms` during active call, `slow_ms` while idle).

---

## 🛠️ Quick Start Guide

### 1. Installation

Clone or extract to your web server root directory (e.g. `C:\wamp64\www\webRTC`):

```bash
cd D:\wamp64\www\webRTC
npm install
```

### 2. Running the Signaling Server

#### On WAMP / Windows:
Simply navigate to `http://localhost/webRTC/autostart.php` or open the Staff Panel (`index.php`) which initiates the process.

#### Manual Startup:
```bash
node server.js
```

### 3. Firewall Rule (Windows - Port 9090)
```powershell
netsh advfirewall firewall add rule name="WebCall WS" protocol=TCP dir=in localport=9090 action=allow
```

---

## 🔒 Shared Hosting Deployment (No Node.js)

1. Upload all files via FTP to your shared host directory (e.g., `public_html/webRTC`).
2. Edit `webcall_config.json`:
   - Set `"ws_mode": false` under `"mode"`.
   - Set `"ip": "yourdomain.com"` under `"server"`.
   - Set `"protocol": "https"` if SSL is enabled.
3. Open `https://yourdomain.com/webRTC/index.php` — HTTP Polling mode is active automatically!

---

## 📬 Contact & Support

For support, inquiries, or collaboration, feel free to reach out across any of these channels:

- 📧 **Email:** [technicguy@gmail.com](mailto:technicguy@gmail.com)
- 🌐 **Website:** [https://esanshar.com.np/](https://esanshar.com.np/)
- 📞 **Phone:** [+977 986 445 0173](tel:+9779864450173)
- 💬 **WhatsApp:** [+977 984 470 7950](https://wa.me/9779844707950)
- 💼 **LinkedIn:** [linkedin.com/in/technicguy](https://www.linkedin.com/in/technicguy/)
- 👤 **Facebook:** [facebook.com/imakashgc](https://www.facebook.com/imakashgc)
- 📺 **YouTube Channels:**
  - 🎵 **Sound & Frequency:** [Mystic Sound Journeys](https://www.youtube.com/@MysticSoundJourneys?sub_confirmation=1)
  - 👶 **Kids Content:** [MummaBaba](https://www.youtube.com/@MummaBaba?sub_confirmation=1)

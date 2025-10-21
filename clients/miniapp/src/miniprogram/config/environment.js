// 🎯 三端统一环境配置（线上 / localhost / 局域网）
// 本地模式：允许走 localhost/局域网，不强制线上
const FORCE_ONLINE = false;

// 缓存键名
const KEY_ENV_MODE = 'API_ENV_MODE';
const KEY_ENV_LAN_IP = 'API_LAN_IP';

// 环境预设
const envs = {
  online: {
    name: '线上环境',
    // 微信小程序仅允许 443 端口的 wss，使用 Nginx 反代到 3001
    websocketUrl: 'wss://yiqilishi.com.cn/socket.io/',
    // 固定统一入口：使用 index.php/api，避免 api.php 404
    apiBaseUrl: 'https://yiqilishi.com.cn/index.php/api/'
  },
  localhost: {
    name: '本机localhost',
    websocketUrl: 'ws://127.0.0.1:3001/socket.io/',
    apiBaseUrl: 'http://127.0.0.1:8000/index.php/api/'
  },
  lanDefault: {
    name: '局域网（默认IP）',
    websocketUrl: 'ws://192.168.1.2:3001/socket.io/',
    apiBaseUrl: 'http://192.168.1.2:8000/index.php/api/'
  }
};

function formatLan(ip) {
  const safeIp = (ip || '').trim() || '192.168.1.2';
  return {
    name: `局域网（${safeIp}）`,
    websocketUrl: `ws://${safeIp}:3001/socket.io/`,
    apiBaseUrl: `http://${safeIp}:8000/index.php/api/`
  };
}

function getStored(key, def) {
  try { return wx.getStorageSync(key) || def; } catch (_) { return def; }
}

function setStored(key, val) {
  try { wx.setStorageSync(key, val); } catch (_) {}
}

// 设置当前环境（已禁用动态切换，统一由代理/后端配置）
function setEnv(mode, ip) {
  // 切换到指定模式；开发默认使用 localhost，发布版仍强制 online
  try {
    const m = (mode || 'localhost').trim();
    setStored(KEY_ENV_MODE, m);
    if (ip) setStored(KEY_ENV_LAN_IP, ip);
  } catch (_) {}
}

function resolveMode() {
  // 默认以存储为准（开发默认 localhost），发布版（release）强制使用 online，避免误改导致走测试兜底
  const stored = getStored(KEY_ENV_MODE, 'localhost');
  let mode = stored;
  try {
    const info = wx.getAccountInfoSync && wx.getAccountInfoSync();
    const ver = info && info.miniProgram && info.miniProgram.envVersion;
    if (ver === 'release') {
      mode = 'online';
      // 若之前被改为 localhost/lan，发布版启动时自动纠正
      if (stored !== 'online') {
        setStored(KEY_ENV_MODE, 'online');
      }
    } else {
      // 开发/体验版本均默认使用 localhost，便于本地联调
      mode = 'localhost';
      if (stored !== 'localhost') {
        setStored(KEY_ENV_MODE, 'localhost');
      }
    }
  } catch (_) {}
  // 若开启强制线上模式，始终返回 online 并写入缓存
  if (FORCE_ONLINE === true) {
    if (stored !== 'online') {
      setStored(KEY_ENV_MODE, 'online');
    }
    return 'online';
  }
  return mode;
}

function getCurrentConfig() {
  const mode = resolveMode();
  if (mode === 'online') return envs.online;
  if (mode === 'localhost') return envs.localhost;
  // 局域网：优先使用已存IP，否则默认IP
  const ip = getStored(KEY_ENV_LAN_IP, '192.168.1.2');
  return formatLan(ip);
}

// 生成用于 Socket.IO 握手的完整 wsUrl（附加 Engine.IO 必需参数）
function getWebSocketUrl(deviceCode) {
  const cfg = getCurrentConfig();
  const base = cfg.websocketUrl || '';
  const dcParam = deviceCode ? `deviceCode=${encodeURIComponent(deviceCode)}` : '';
  const query = ['EIO=4', 'transport=websocket', dcParam].filter(Boolean).join('&');
  return `${base}?${query}`;
}

const cur = getCurrentConfig();
// 仅记录日志，不进行提示或交互
console.log(`🌍 当前环境: ${cur.name}`);
console.log(`🔗 WebSocket: ${cur.websocketUrl}`);
console.log(`🔗 API地址: ${cur.apiBaseUrl}`);

// 检测当前小程序版本（develop / trial / release），用于审核阶段开关
let ENV_VERSION = 'develop';
try {
  const info = wx.getAccountInfoSync && wx.getAccountInfoSync();
  const ver = info && info.miniProgram && info.miniProgram.envVersion;
  ENV_VERSION = ver || 'develop';
} catch (_) {}

module.exports = {
  // 供其他模块（如 api.js）判定是否线上
  IS_ONLINE: cur === envs.online,
  // 是否处于提审/审核版本（trial），用于启用审核兜底策略
  IS_AUDIT: ENV_VERSION === 'trial',
  getCurrentConfig,
  getWebSocketUrl,
  // 快捷导出当前值（避免旧代码改动过多）
  websocketUrl: cur.websocketUrl,
  apiBaseUrl: cur.apiBaseUrl,
  // 环境切换API
  setEnv,
  formatLan
};

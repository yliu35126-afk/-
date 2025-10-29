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
    // 绑定到 127.0.0.1，避免 Windows/DevTools 使用 IPv6 ::1 导致超时
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

// 获取当前环境配置（支持缓存切换）
function getCurrentConfig() {
  try {
    const mode = getStored(KEY_ENV_MODE, 'localhost');
    if (FORCE_ONLINE) return envs.online;
    if (mode === 'localhost') return envs.localhost;
    if (mode === 'lan') {
      const ip = getStored(KEY_ENV_LAN_IP, '192.168.1.2');
      return formatLan(ip);
    }
    return envs.online;
  } catch (_) {
    return envs.localhost;
  }
}

// 切换环境并持久化到本地存储（供设置页/调试入口调用）
function setEnv(mode, opts) {
  try {
    // 允许的取值：online / localhost / lan
    const allowed = ['online', 'localhost', 'lan'];
    const next = allowed.includes(mode) ? mode : 'localhost';
    wx.setStorageSync(KEY_ENV_MODE, next);

    if (next === 'lan') {
      const ip = (opts && opts.ip) || getStored(KEY_ENV_LAN_IP, '192.168.1.2');
      wx.setStorageSync(KEY_ENV_LAN_IP, ip);
    }

    const cur = getCurrentConfig();
    console.log(`[ENV] 已切换到: ${cur.name}`);
    console.log(`[ENV] WebSocket: ${cur.websocketUrl}`);
    console.log(`[ENV] API地址: ${cur.apiBaseUrl}`);
    return cur;
  } catch (e) {
    console.warn('[ENV] setEnv 失败:', e && e.message);
    return getCurrentConfig();
  }
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

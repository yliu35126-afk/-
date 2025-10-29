// 加载环境变量配置（仅在未显式提供关键环境变量时才加载 .env）
if (!process.env.WS_PORT && !process.env.API_BASE_URL && !process.env.DB_HOST && !process.env.DB_USER) {
  require('dotenv').config();
} else {
  try { require('dotenv').config({ override: false }); } catch (_) {}
}

// 先解析 API 相关，再判定环境模式（避免 .env 覆盖导致误判）
const API_HOST = process.env.API_HOST || '127.0.0.1';
const API_PORT = process.env.API_PORT || '8000';
const API_BASE_URL = process.env.API_BASE_URL || (
  (API_HOST === '127.0.0.1' || API_HOST === 'localhost' || API_PORT === '9100')
    ? `http://${API_HOST}:${API_PORT}/index.php/api`
    : 'https://yiqilishi.com.cn/index.php/api'
);

// 动态环境模式：优先 ENV_MODE；若 API 明显指向本地，则强制 local
const ENV_MODE = (() => {
  const m = (process.env.ENV_MODE || '').trim();
  const apiIsLocal = API_BASE_URL.includes('127.0.0.1') || API_BASE_URL.includes('localhost') || String(API_PORT) === '9100';
  if (apiIsLocal) return 'local';
  if (m) return m;
  return 'prod';
})();

// 端口
const WS_PORT = parseInt(process.env.WS_PORT || process.env.PORT || '3001', 10);

const EXTRA_ORIGIN = (process.env.EXTRA_CORS_ORIGIN || '').trim();
const CORS_ORIGINS_ENV = (process.env.CORS_ORIGINS || '').trim();

const baseCorsList = (() => {
  let list;
  if (ENV_MODE === 'local') {
    list = [
      `http://localhost:${API_PORT}`,
      `http://127.0.0.1:${API_PORT}`,
      `http://192.168.1.3:${API_PORT}`,
      'https://servicewechat.com'
    ];
  } else {
    list = [
      'https://3.yiqilishi.com.cn',
      'http://3.yiqilishi.com.cn',
      'https://yiqilishi.com.cn',
      'https://servicewechat.com'
    ];
  }
  // 始终追加本地联调来源
  list.push(`http://127.0.0.1:${API_PORT}`);
  list.push(`http://localhost:${API_PORT}`);
  if (EXTRA_ORIGIN) list.push(EXTRA_ORIGIN);
  return list;
})();

const envCorsList = CORS_ORIGINS_ENV
  ? CORS_ORIGINS_ENV.split(',').map(s => s.trim()).filter(Boolean)
  : [];

const config = {
  port: WS_PORT,
  apiBaseUrl: API_BASE_URL,
  corsOrigins: Array.from(new Set([ ...baseCorsList, ...envCorsList ])),
  database: {
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'niushop',
    password: process.env.DB_PASSWORD || 'niushop',
    database: process.env.DB_NAME || 'niushop',
    port: parseInt(process.env.DB_PORT || '3306', 10)
  },
  socketOptions: {
    transports: ['websocket', 'polling'],
    pingInterval: parseInt(process.env.PING_INTERVAL_MS || '25000', 10),
    pingTimeout: parseInt(process.env.PING_TIMEOUT_MS || '120000', 10),
    connectionStateRecovery: {
      maxDisconnectionDuration: parseInt(process.env.RECOVERY_MAX_MS || '120000', 10),
      skipMiddlewares: true
    },
    perMessageDeflate: false
  },
  enablePhpHeartbeatCallback: (
    process.env.ENABLE_PHP_HEARTBEAT === 'true' ||
    process.env.ENABLE_PHP_HEARTBEAT === '1' ||
    (typeof process.env.ENABLE_PHP_HEARTBEAT === 'undefined')
  ),
  enableNodeDirectHeartbeatWrite: (
    process.env.ENABLE_NODE_HEARTBEAT_WRITE === '1' ||
    true
  ),
  heartbeatEndpoint: '/device/heartbeat',
  lotteryCallbackEndpoint: '/lottery/callback',
  reservationSeconds: parseInt(process.env.RESERVATION_SECONDS || '30', 10),
  progressTimeoutSeconds: parseInt(process.env.PROGRESS_TIMEOUT_SECONDS || '60', 10),
  heartbeatMinWriteIntervalSeconds: parseInt(process.env.HEARTBEAT_WRITE_INTERVAL || '30', 10),
  heartbeatOfflineTimeoutSeconds: parseInt(process.env.HEARTBEAT_OFFLINE_TIMEOUT || '300', 10),
  heartbeatSweepIntervalSeconds: parseInt(process.env.HEARTBEAT_SWEEP_INTERVAL || '60', 10),
  heartbeatEnforceDisconnect: (
    process.env.HEARTBEAT_ENFORCE_DISCONNECT === 'true' ||
    process.env.HEARTBEAT_ENFORCE_DISCONNECT === '1' ||
    (
      (typeof process.env.HEARTBEAT_ENFORCE_DISCONNECT === 'undefined' || process.env.HEARTBEAT_ENFORCE_DISCONNECT === '') &&
      (ENV_MODE !== 'local')
    )
  )
};

console.log(`🌍 WebSocket服务器环境: ${ENV_MODE}`);
console.log(`🚀 端口: ${config.port}`);
console.log(`🔗 API地址: ${config.apiBaseUrl}`);
console.log(`✅ 允许的Origin: ${config.corsOrigins.join(', ')}`);

module.exports = {
  port: config.port,
  apiBaseUrl: config.apiBaseUrl,
  corsOrigins: config.corsOrigins,
  database: config.database,
  socketOptions: config.socketOptions,
  enablePhpHeartbeatCallback: config.enablePhpHeartbeatCallback,
  enableNodeDirectHeartbeatWrite: config.enableNodeDirectHeartbeatWrite,
  heartbeatEndpoint: config.heartbeatEndpoint,
  lotteryCallbackEndpoint: config.lotteryCallbackEndpoint,
  reservationSeconds: config.reservationSeconds,
  progressTimeoutSeconds: config.progressTimeoutSeconds,
  heartbeatMinWriteIntervalSeconds: config.heartbeatMinWriteIntervalSeconds,
  heartbeatOfflineTimeoutSeconds: config.heartbeatOfflineTimeoutSeconds,
  heartbeatSweepIntervalSeconds: config.heartbeatSweepIntervalSeconds,
  heartbeatEnforceDisconnect: config.heartbeatEnforceDisconnect
};
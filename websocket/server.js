// config.js — 支持多环境的统一配置
// ----------------------------------------------------
// 自动加载 .env 文件中的环境变量（如 WS_PORT、DB_HOST 等）
require('dotenv').config();

const toBool = (val) => {
    if (typeof val === 'string') return ['1', 'true', 'yes', 'on'].includes(val.toLowerCase());
    return !!val;
};

module.exports = {
    // 环境模式
    IS_ONLINE: process.env.ENV_MODE === 'prod',

    // WebSocket 端口
    port: process.env.WS_PORT ? parseInt(process.env.WS_PORT) : 3001,

    // API 主地址
    apiBaseUrl: process.env.API_BASE_URL || 'https://yiqilishi.com.cn/index.php/api',

    // CORS 允许来源
    corsOrigins: process.env.CORS_ORIGINS
        ? process.env.CORS_ORIGINS.split(',').map(x => x.trim())
        : '*',

    // ✅ 数据库配置（这就是 server.js 想要的字段）
    database: {
        host: process.env.DB_HOST || '127.0.0.1',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || 'test',
        port: process.env.DB_PORT ? parseInt(process.env.DB_PORT) : 3306,
        charset: 'utf8mb4'
    },

    // 心跳机制参数
    heartbeatEndpoint: '/device/heartbeat',
    heartbeatMinWriteIntervalSeconds: parseInt(process.env.HEARTBEAT_WRITE_INTERVAL || 30),
    heartbeatOfflineTimeoutSeconds: parseInt(process.env.HEARTBEAT_OFFLINE_TIMEOUT || 300),
    heartbeatSweepIntervalSeconds: parseInt(process.env.HEARTBEAT_SWEEP_INTERVAL || 60),
    heartbeatEnforceDisconnect: toBool(process.env.HEARTBEAT_ENFORCE_DISCONNECT),

    // 是否启用 PHP 回调与 Node 直连数据库写库
    enablePhpHeartbeatCallback: true,
    enableNodeDirectHeartbeatWrite: toBool(process.env.ENABLE_NODE_HEARTBEAT_WRITE),

    // 新设备自动绑定配置
    autoBindNewDevice: toBool(process.env.AUTO_BIND_NEW_DEVICE),
    autoBindDefaultConfigId: parseInt(process.env.AUTO_BIND_CONFIG_ID || 26),

    // 抽奖回调接口
    lotteryCallbackEndpoint: '/lottery/callback',

    // socket.io 参数
    socketOptions: {
        transports: ['websocket', 'polling'],
        pingInterval: parseInt(process.env.PING_INTERVAL_MS || 25000),
        pingTimeout: parseInt(process.env.PING_TIMEOUT_MS || 120000),
        connectionStateRecovery: {
            maxDisconnectionDuration: parseInt(process.env.RECOVERY_MAX_MS || 120000),
            skipMiddlewares: true
        },
        perMessageDeflate: false
    }
};

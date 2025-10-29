const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const axios = require('axios');

const config = require('./config');
const { handleSocketEvents, deviceCache } = require('./socketHandlers');
const { getAllQueueStatus, clearDeviceQueue } = require('./queueManager');
const db = require('./database');
const { devices, deviceRooms, setDeviceBusy, releaseDevice, isDeviceBusy, getDeviceStatus, startHeartbeatCheck } = require('./deviceManager');
const { updateDeviceOnlineStatus, getDeviceConfigId, getProfitConfigPreview } = require('./database');

const app = express();
const server = http.createServer(app);

// 配置CORS
app.use(cors({
    origin: config.corsOrigins,
    credentials: true
}));

// ✅ 统一服务端 Socket.IO 初始化，打开 EIO3 兼容并指定路径/传输
const io = socketIo(server, {
    cors: {
        origin: config.corsOrigins,
        methods: ['GET', 'POST'],
        credentials: true
    },
    // 兼容旧版 Android Java 客户端（engine.io v3）
    allowEIO3: true,
    // 明确设置传输与心跳参数（来自 config.socketOptions 或默认值）
    transports: (config.socketOptions && config.socketOptions.transports) ? config.socketOptions.transports : ['websocket', 'polling'],
    pingInterval: (config.socketOptions && config.socketOptions.pingInterval) ? config.socketOptions.pingInterval : 25000,
    pingTimeout: (config.socketOptions && config.socketOptions.pingTimeout) ? config.socketOptions.pingTimeout : 120000,
    connectionStateRecovery: (config.socketOptions && config.socketOptions.connectionStateRecovery) ? config.socketOptions.connectionStateRecovery : { maxDisconnectionDuration: 120000, skipMiddlewares: true },
    perMessageDeflate: (config.socketOptions && typeof config.socketOptions.perMessageDeflate !== 'undefined') ? config.socketOptions.perMessageDeflate : false,
    // 服务端统一路径（与客户端 BuildConfig.SOCKET_PATH 保持一致）
    path: '/socket.io/'
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 基础路由
app.get('/', (req, res) => {
    res.json({ 
        message: 'TV Game WebSocket Server',
        status: 'running',
        timestamp: new Date().toISOString()
    });
});

// 健康检查端点
app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        uptime: process.uptime(),
        timestamp: new Date().toISOString()
    });
});

// --- 启动时全量预热设备缓存 ---
async function preloadDeviceConfigs() {
    console.log('🚀 开始预热设备配置缓存...');
    let connection;
    try {
        connection = await db.pool.getConnection();
        // 从 device_price_bind 读取各设备当前启用的价档（tier_id）
        const [rows] = await connection.execute(
            `SELECT device_id, tier_id FROM device_price_bind WHERE status = 1 ORDER BY start_time DESC`
        );
        let loaded = 0;
        const seen = new Set();
        for (const row of rows) {
            const device_id = row.device_id;
            if (seen.has(device_id)) continue; // 仅取每设备最新一条
            seen.add(device_id);
            const config_id_raw = row.tier_id;
            const config_id = config_id_raw === null ? null : parseInt(config_id_raw, 10);
            if (!config_id || Number.isNaN(config_id)) continue;
            const [cfgRows] = await connection.execute(
                'SELECT price, profit_json FROM lottery_price_tier WHERE tier_id = ? LIMIT 1',
                [config_id]
            );
            if (cfgRows && cfgRows[0]) {
                let profitCfg;
                try { profitCfg = JSON.parse(cfgRows[0].profit_json || '{}'); } catch (_) { profitCfg = {}; }
                const supplier = (profitCfg.prizes && Array.isArray(profitCfg.prizes.supplier)) ? profitCfg.prizes.supplier : [];
                const merchant = (profitCfg.prizes && Array.isArray(profitCfg.prizes.merchant)) ? profitCfg.prizes.merchant : [];
                const totalCount = supplier.length + merchant.length;
                deviceCache[device_id] = {
                    config_id,
                    config: {
                        config_name: profitCfg.name || '',
                        lottery_amount: parseFloat(cfgRows[0].price || 0),
                        prizes: { supplier: { type: 'supplier', data: supplier }, merchant: { type: 'merchant', data: merchant } }
                    },
                    updated_at: new Date(),
                };
                loaded++;
                console.log(`✅ 预热设备缓存 ${device_id} → 价档ID=${config_id} (奖品${totalCount}项)`);
            }
        }
        console.log(`🚀 预热完成，共加载 ${loaded} 台设备。`);
    } catch (err) {
        console.error('❌ 预热设备配置缓存失败:', err);
    } finally {
        if (connection) connection.release();
    }
}

// 抽奖启动HTTP入口（兼容两种路径）
async function handleStartLottery(req, res) {
    try {
        const { device_id, config_id, order_id, amount, user_id, openid, lottery_type = 1, user_name = '' } = req.body || {};

        if (!device_id) {
            return res.status(400).json({ success: false, message: '缺少device_id' });
        }

        // 查找设备房间
        const roomName = deviceRooms.get(device_id) || `device_${device_id}`;

        // 辅助：按设备ID查找设备信息
        const getDeviceInfoByDeviceId = (deviceId) => {
            for (const [, info] of devices.entries()) {
                if (info && info.device_id === deviceId) return info;
            }
            return null;
        };

        const devInfo = getDeviceInfoByDeviceId(device_id);
        let cfgId = parseInt(config_id, 10);
        if (Number.isNaN(cfgId) || cfgId <= 0) {
            const boundCfg = devInfo && devInfo.config_id ? devInfo.config_id : (await getDeviceConfigId(device_id));
            cfgId = boundCfg || 26; // 最终回退到26
        }

        // 推送分润配置预览，保证前端一致
        try {
            const preview = await getProfitConfigPreview(cfgId);
            if (preview) {
                io.to(roomName).emit('config_preview', preview);
            }
        } catch (e) {
            console.warn('⚠️ 获取分润配置预览失败：', e.message);
        }

        // 将抽奖请求广播到指定设备房间（包含旧客户端别名字段）
        io.to(roomName).emit('lottery_start', {
            device_id,
            config_id: cfgId,
            order_id,
            orderId: order_id, // 旧Android客户端可能读取该字段
            amount,
            user_id,
            openid,
            lottery_type,
            user_name,
            timestamp: new Date().toISOString()
        });

        return res.json({ success: true, message: 'lottery start pushed', device_id, config_id: cfgId });
    } catch (error) {
        console.error('❌ 抽奖启动失败:', error);
        return res.status(500).json({ success: false, message: error.message || 'server error' });
    }
}

// 兼容旧接口路径
app.post('/api/start-lottery', handleStartLottery);
app.post('/lottery-start', handleStartLottery);

// Socket.IO 连接事件
io.on('connection', (socket) => {
    console.log(`⚡ 客户端连接: ${socket.id}`);

    // 绑定事件处理
    handleSocketEvents(io, socket);
});

module.exports = { server, io, preloadDeviceConfigs };

// 获取设备队列状态的API
app.get('/api/queue-status', (req, res) => {
    try {
        const queueStatus = getAllQueueStatus();
        return res.json({ 
            success: true, 
            data: queueStatus,
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        console.error('❌ 获取队列状态失败:', error);
        return res.status(500).json({ success: false, message: error.message || 'server error' });
    }
});

// 清空设备队列的API（紧急情况使用）
app.post('/api/clear-device-queue', (req, res) => {
    try {
        const { device_id } = req.body || {};
        if (!device_id) {
            return res.status(400).json({ success: false, message: '缺少 device_id' });
        }
        
        clearDeviceQueue(device_id);
        console.log(`🧹 管理员清空设备${device_id}队列`);
        
        return res.json({ 
            success: true, 
            message: `设备${device_id}队列已清空`
        });
    } catch (error) {
        console.error('❌ 清空设备队列失败:', error);
        return res.status(500).json({ success: false, message: error.message || 'server error' });
    }
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 获取在线设备信息 - 给后端管理系统使用
app.get('/devices', (req, res) => {
    try {
        const onlineDevices = [];
        
        // 将devices Map转换为数组格式
        for (const [socketId, deviceInfo] of devices.entries()) {
            onlineDevices.push({
                device_id: deviceInfo.device_id,
                connected_at: deviceInfo.registered_at.toISOString(),
                last_heartbeat: deviceInfo.last_heartbeat.toISOString(),
                device_type: deviceInfo.device_type || 'unknown',
                socket_id: socketId,
                device_name: deviceInfo.device_name || ''
            });
        }
        
        res.json({
            success: true,
            count: onlineDevices.length,
            devices: onlineDevices,
            timestamp: new Date().toISOString()
        });
        
        console.log(`📊 返回在线设备信息: ${onlineDevices.length}台设备`);
    } catch (error) {
        console.error('❌ 获取设备信息失败:', error);
        res.status(500).json({
            success: false,
            error: error.message,
            devices: []
        });
    }
});

const PORT = config.port;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 WebSocket 服务器启动在端口 ${PORT}`);
    console.log('🔧 CORS Origins:', config.corsOrigins);
    console.log('🔧 Socket Options:', {
        transports: (config.socketOptions && config.socketOptions.transports) ? config.socketOptions.transports : ['websocket', 'polling'],
        pingInterval: (config.socketOptions && config.socketOptions.pingInterval) ? config.socketOptions.pingInterval : 25000,
        pingTimeout: (config.socketOptions && config.socketOptions.pingTimeout) ? config.socketOptions.pingTimeout : 120000,
        path: '/socket.io/'
    });

    // 启动心跳检查
    try {
        startHeartbeatCheck(io);
        console.log('❤️ 心跳检查已启动');
    } catch (e) {
        console.warn('⚠️ 启动心跳检查失败：', e.message);
    }

    // 启动时预热配置缓存
    preloadDeviceConfigs().catch(err => console.warn('⚠️ 预热失败：', err.message));
});

process.on('uncaughtException', (err) => {
    console.error('❌ 未捕获异常:', err);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ 未处理的 Promise 拒绝:', reason);
});

process.on('SIGTERM', () => {
    console.log('🛑 收到 SIGTERM，准备关闭服务器...');
    server.close(() => {
        console.log('✅ 服务器已关闭');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('🛑 收到 SIGINT（Ctrl+C），准备关闭服务器...');
    server.close(() => {
        console.log('✅ 服务器已关闭');
        process.exit(0);
    });
});

module.exports = { app, server, io };

// 额外管理接口：清除设备忙碌/状态（用于紧急恢复）
app.post('/api/clear-device-status', (req, res) => {
    try {
        const { device_id } = req.body || {};
        if (!device_id) {
            return res.status(400).json({ success: false, message: '缺少 device_id' });
        }
        releaseDevice(device_id);
        return res.json({ success: true, message: `设备 ${device_id} 状态已清除` });
    } catch (error) {
        return res.status(500).json({ success: false, message: error.message || 'server error' });
    }
});

// 广播测试接口
app.post('/api/broadcast', (req, res) => {
    try {
        const { event = 'test_event', payload = {} } = req.body || {};
        io.emit(event, payload);
        return res.json({ success: true, message: 'broadcasted', event });
    } catch (error) {
        return res.status(500).json({ success: false, message: error.message || 'server error' });
    }
});

// -------------------- 抽奖联调编排（订单→支付回调→抽奖） --------------------
// POST /api/turntable/draw-flow
// body: { device_sn?|device_id?, tier_id, site_id? }
// header: Authorization: Bearer <member token>
// 流程：调用 PHP create → 使用返回 attach 调用 paynotify → 直接返回 paynotify 的抽奖结果
app.post('/api/turntable/draw-flow', async (req, res) => {
    // 规范 API 基址：去除多余 /api 前缀，确保 /addons 路由可达
    let apiBase = (config.apiBaseUrl || '').replace(/\/$/, '');
    if (apiBase.endsWith('/index.php/api')) apiBase = apiBase.slice(0, -4); // 去掉末尾 /api
    else if (apiBase.endsWith('/api')) apiBase = apiBase.slice(0, -4);      // 去掉末尾 /api
    const createUrl = `${apiBase}/addons/device_turntable/api/order/create`;
    const notifyUrl = `${apiBase}/addons/device_turntable/api/order/paynotify`;

    const { device_sn = '', device_id = 0, tier_id = 0, site_id = 0 } = req.body || {};
    const auth = req.headers['authorization'] || '';
    // 支持从 Authorization: Bearer <token> 或 body.token 提取 token
    const tokenFromHeader = auth.replace(/^Bearer\s+/i, '');
    const token = (req.body && req.body.token) ? String(req.body.token) : (tokenFromHeader || '');

    if ((!device_sn && !device_id) || !tier_id) {
        return res.status(400).json({ code: -1, msg: '缺少必要参数: device_sn|device_id 或 tier_id' });
    }
    if (!token) {
        return res.status(401).json({ code: -1, msg: '缺少会员 token（请先登录获取 token）' });
    }

    try {
        // 1) 创建抽奖支付订单（需要会员 token）
        const createResp = await axios.post(createUrl, { token, device_sn, device_id, tier_id, site_id });
        const cdata = (createResp.data && createResp.data.data) || {};
        if (!cdata.order_no || !cdata.attach) {
            return res.status(500).json({ code: -1, msg: '创建订单失败', data: createResp.data });
        }

        // 2) 模拟支付成功回调 → 触发抽奖
        const payload = {
            order_no: cdata.order_no,
            total_amount: cdata.amount || 0,
            trade_status: 'success',
            attach: cdata.attach
        };

        // 可选：签名（当后端配置 LOTTERY_PAY_SECRET 时）
        const secret = process.env.LOTTERY_PAY_SECRET || '';
        if (secret) {
            const ts = Math.floor(Date.now() / 1000);
            const raw = `${payload.order_no}|${Number(payload.total_amount).toFixed(2)}|${payload.trade_status}|${ts}`;
            const crypto = require('crypto');
            const sign = crypto.createHmac('sha256', secret).update(raw).digest('hex');
            payload.ts = ts;
            payload.sign = sign;
        }

        const notifyResp = await axios.post(notifyUrl, payload);
        return res.json(notifyResp.data);
    } catch (err) {
        const status = err.response && err.response.status;
        const data = err.response && err.response.data;
        console.error('❌ 抽奖联调失败:', status, data || err.message);
        return res.status(status || 500).json(data || { code: -1, msg: err.message || 'server error' });
    }
});
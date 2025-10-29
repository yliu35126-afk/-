const mysql = require('mysql2/promise');
const config = require('./config');

// 创建数据库连接池
const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    port: Number(config.database.port) || 3306,
    // 明确字符集，避免乱码（遵守天条：导入导出不允许乱码）
    charset: 'utf8',
    waitForConnections: true,
    connectionLimit: parseInt(process.env.DB_CONN_LIMIT || '10', 10),
    queueLimit: 0
});

// 数据库操作函数
async function saveOrderToDB(orderData) {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();
        
        const [result] = await connection.execute(
            `INSERT INTO fa_order (
                openid, user_id, device_id, order_amount, lottery_amount, 
                pay_status, order_status, createtime, updatetime, config_id
            ) VALUES (?, ?, ?, ?, ?, 'unpaid', 0, ?, ?, ?)`,
            [
                orderData.openid,
                orderData.user_id || 'anonymous',
                orderData.device_id,
                orderData.order_amount,
                orderData.lottery_amount || orderData.order_amount,
                Math.floor(Date.now() / 1000),
                Math.floor(Date.now() / 1000),
                orderData.config_id || 0
            ]
        );
        
        await connection.commit();
        console.log(`✅ 订单保存成功: ID=${result.insertId}`);
        
        return { success: true, order_id: result.insertId };
    } catch (error) {
        await connection.rollback();
        console.error('❌ 保存订单到数据库失败:', error);
        return { success: false, error: error.message };
    } finally {
        connection.release();
    }
}

async function updateOrderPrize(orderId, prizeInfo) {
    const connection = await pool.getConnection();
    try {
        await connection.execute(
            `UPDATE fa_order SET 
                prize_id = ?, prize_name = ?, order_status = 3, updatetime = ?
             WHERE id = ?`,
            [
                prizeInfo.id,
                prizeInfo.name,
                Math.floor(Date.now() / 1000),
                orderId
            ]
        );
        console.log(`🎁 订单奖品更新成功: 订单=${orderId}, 奖品=${prizeInfo.name}`);
        return { success: true };
    } catch (error) {
        console.error('❌ 更新订单奖品失败:', error);
        return { success: false, error: error.message };
    } finally {
        connection.release();
    }
}

async function getLotteryAmount() {
    try {
        const connection = await pool.getConnection();
        const [rows] = await connection.execute(
            'SELECT option_value FROM fa_config WHERE name = "lottery_amount"'
        );
        connection.release();
        
        if (rows.length > 0) {
            const amount = parseFloat(rows[0].option_value);
            return isNaN(amount) ? 10 : amount;
        }
        return 10; // 默认金额
    } catch (error) {
        console.error('❌ 获取抽奖金额失败:', error);
        return 10; // 返回默认值
    }
}

// 更新设备在线状态（改为 device_info 表）
async function updateDeviceOnlineStatus(deviceId, isOnline, lastHeartbeat = null) {
    const connection = await pool.getConnection();
    try {
        const currentTime = Math.floor(Date.now() / 1000);
        // device_info 没有 last_heartbeat_time/last_online_time 字段，使用 update_time 记录更新时间
        await connection.execute(
            `UPDATE device_info SET update_time = ? WHERE device_id = ?`,
            [currentTime, deviceId]
        );
        console.log(`📱 设备${deviceId}状态更新: ${isOnline ? '在线' : '离线'}`);
        return { success: true };
    } catch (error) {
        console.error('❌ 更新设备在线状态失败:', error);
        return { success: false, error: error.message };
    } finally {
        connection.release();
    }
}

// 验证设备是否存在（改为 device_info 表）
async function verifyDevice(deviceId) {
    const connection = await pool.getConnection();
    try {
        const [rows] = await connection.execute(
            'SELECT device_id FROM device_info WHERE device_id = ? LIMIT 1',
            [deviceId]
        );
        const isValid = rows.length > 0;
        console.log(`🔍 设备验证: ${deviceId} - ${isValid ? '有效' : '无效'}`);
        return isValid;
    } catch (error) {
        console.error(`❌ 验证设备${deviceId}失败:`, error);
        return false;
    } finally {
        connection.release();
    }
}

module.exports = {
    pool,
    saveOrderToDB,
    updateOrderPrize,
    getLotteryAmount,
    updateDeviceOnlineStatus,
    verifyDevice,
    // 读取设备当前绑定的价档ID（tier_id），来自 device_price_bind
    async getDeviceConfigId(deviceId) {
        const connection = await pool.getConnection();
        try {
            // 选择状态为1的最新绑定
            const [rows] = await connection.execute(
                `SELECT tier_id FROM device_price_bind 
                 WHERE device_id = ? AND status = 1 
                 ORDER BY start_time DESC LIMIT 1`,
                [deviceId]
            );
            if (rows && rows.length > 0) {
                const raw = rows[0].tier_id;
                const cfgId = raw === null ? null : parseInt(raw, 10);
                return Number.isNaN(cfgId) ? null : cfgId;
            }
            return null;
        } catch (error) {
            console.error(`❌ 读取设备${deviceId}绑定价档失败:`, error);
            return null;
        } finally {
            connection.release();
        }
    },
    // 获取价档详情（price + profit_json 原始）
    async getTierDetail(tierId) {
        const connection = await pool.getConnection();
        try {
            const id = parseInt(tierId, 10);
            if (Number.isNaN(id) || id <= 0) return null;
            const [rows] = await connection.execute(
                'SELECT tier_id, price, profit_json FROM lottery_price_tier WHERE tier_id = ? LIMIT 1',
                [id]
            );
            if (!rows || rows.length === 0) return null;
            const row = rows[0];
            let profitCfg = {};
            try { profitCfg = JSON.parse(row.profit_json || '{}') || {}; } catch (_) { profitCfg = {}; }
            return { id: row.tier_id, price: parseFloat(row.price || 0), profit: profitCfg };
        } catch (error) {
            console.error('❌ 获取价档详情失败:', error);
            return null;
        } finally {
            connection.release();
        }
    },
    // 根据 tier_id 取配置预览（price + profit_json，保持原有返回结构的兼容）
    async getProfitConfigPreview(configId) {
        const connection = await pool.getConnection();
        try {
            const tierId = parseInt(configId, 10);
            if (Number.isNaN(tierId) || tierId <= 0) return null;
            const [rows] = await connection.execute(
                'SELECT tier_id, price, profit_json FROM lottery_price_tier WHERE tier_id = ? LIMIT 1',
                [tierId]
            );
            if (!rows || rows.length === 0) return null;
            const row = rows[0];
            let profitCfg = {};
            try { profitCfg = JSON.parse(row.profit_json || '{}') || {}; } catch (_) { profitCfg = {}; }
            // 维持 prizes 字段结构以兼容现有前端/日志
            const supplier = (profitCfg.prizes && Array.isArray(profitCfg.prizes.supplier)) ? profitCfg.prizes.supplier : [];
            const merchant = (profitCfg.prizes && Array.isArray(profitCfg.prizes.merchant)) ? profitCfg.prizes.merchant : [];
            return {
                config_id: row.tier_id, // 此处为 tier_id
                config_name: profitCfg.name || '',
                lottery_amount: parseFloat(row.price || 0),
                prizes: {
                    supplier: { type: 'supplier', data: supplier },
                    merchant: { type: 'merchant', data: merchant }
                }
            };
        } catch (error) {
            console.error(`❌ 获取价档配置预览失败:`, error);
            return null;
        } finally {
            connection.release();
        }
    },
    // 设备简要信息（用于落库记录）
    async getDeviceInfoMini(deviceId) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.execute(
                'SELECT device_id, board_id, site_id FROM device_info WHERE device_id = ? LIMIT 1',
                [deviceId]
            );
            return rows && rows[0] ? rows[0] : null;
        } catch (error) {
            console.error('❌ 获取设备信息失败:', error);
            return null;
        } finally {
            connection.release();
        }
    },
    // 计算分润金额（支持百分比或小数）
    computeProfitShares(amount, profitCfg) {
        const amt = parseFloat(amount || 0);
        const getRatio = v => {
            const n = parseFloat(v || 0);
            if (Number.isNaN(n)) return 0;
            // >1 认为是百分比
            return n > 1 ? (n / 100) : n;
        };
        const platform = getRatio((profitCfg.platform && profitCfg.platform.percent) || profitCfg.platform_percent);
        const supplier = getRatio((profitCfg.supplier && profitCfg.supplier.percent) || profitCfg.supplier_percent);
        const promoter = getRatio((profitCfg.promoter && profitCfg.promoter.percent) || profitCfg.promoter_percent);
        const installer = getRatio((profitCfg.installer && profitCfg.installer.percent) || profitCfg.installer_percent);
        const owner = getRatio((profitCfg.owner && profitCfg.owner.percent) || profitCfg.owner_percent);
        const res = {
            amount_total: amt,
            platform_money: +(amt * platform).toFixed(2),
            supplier_money: +(amt * supplier).toFixed(2),
            promoter_money: +(amt * promoter).toFixed(2),
            installer_money: +(amt * installer).toFixed(2),
            owner_money: +(amt * owner).toFixed(2)
        };
        return res;
    },
    // 落库：抽奖记录
    async saveLotteryRecord(payload) {
        const connection = await pool.getConnection();
        try {
            const now = Math.floor(Date.now() / 1000);
            const [ret] = await connection.execute(
                `INSERT INTO lottery_record (
                    member_id, device_id, board_id, slot_id, prize_type, goods_id,
                    tier_id, amount, round_no, result, order_id, ext, create_time
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    parseInt(payload.member_id || 0, 10),
                    parseInt(payload.device_id || 0, 10),
                    parseInt(payload.board_id || 0, 10),
                    parseInt(payload.slot_id || 0, 10),
                    String(payload.prize_type || 'thanks'),
                    parseInt(payload.goods_id || 0, 10),
                    parseInt(payload.tier_id || 0, 10),
                    parseFloat(payload.amount || 0),
                    parseInt(payload.round_no || 0, 10),
                    String(payload.result || 'miss'),
                    parseInt(payload.order_id || 0, 10),
                    payload.ext ? String(payload.ext) : null,
                    now
                ]
            );
            return ret.insertId || 0;
        } catch (error) {
            console.error('❌ 写入 lottery_record 失败:', error);
            return 0;
        } finally {
            connection.release();
        }
    },
    // 落库：分润记录
    async saveLotteryProfit(payload) {
        const connection = await pool.getConnection();
        try {
            const now = Math.floor(Date.now() / 1000);
            const [ret] = await connection.execute(
                `INSERT INTO lottery_profit (
                    record_id, device_id, tier_id, site_id, amount_total,
                    platform_money, supplier_money, promoter_money, installer_money, owner_money,
                    create_time
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    parseInt(payload.record_id || 0, 10),
                    parseInt(payload.device_id || 0, 10),
                    parseInt(payload.tier_id || 0, 10),
                    parseInt(payload.site_id || 0, 10),
                    parseFloat(payload.amount_total || 0),
                    parseFloat(payload.platform_money || 0),
                    parseFloat(payload.supplier_money || 0),
                    parseFloat(payload.promoter_money || 0),
                    parseFloat(payload.installer_money || 0),
                    parseFloat(payload.owner_money || 0),
                    now
                ]
            );
            return ret.insertId || 0;
        } catch (error) {
            console.error('❌ 写入 lottery_profit 失败:', error);
            return 0;
        } finally {
            connection.release();
        }
    }
};

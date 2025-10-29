const { pool } = require('./database');

// 获取设备角色用户（基于 device_info 字段，而非旧 fa_device/fa_auth_group）
async function getDeviceRoleUsers(deviceId) {
    const connection = await pool.getConnection();
    try {
        const [rows] = await connection.execute(
            `SELECT device_id, owner_id, installer_id, supplier_id, promoter_id, site_id
             FROM device_info WHERE device_id = ? LIMIT 1`,
            [deviceId]
        );
        if (!rows || rows.length === 0) return null;
        const d = rows[0];
        return {
            device_id: d.device_id,
            site_id: d.site_id || 0,
            owner: d.owner_id || 0,
            installer: d.installer_id || 0,
            supplier: d.supplier_id || 0,
            promoter: d.promoter_id || 0
        };
    } catch (error) {
        console.error('❌ 获取设备角色失败:', error);
        return null;
    } finally {
        connection.release();
    }
}

// 根据设备与价档金额获取分润配置（价档视角）
async function getProfitConfigByDeviceAndAmount(deviceId, amount) {
    const connection = await pool.getConnection();
    try {
        // 通过设备绑定查找对应 tier
        const [bindRows] = await connection.execute(
            `SELECT b.tier_id FROM device_price_bind b
             WHERE b.device_id = ? AND b.status = 1
             ORDER BY b.start_time DESC LIMIT 1`,
            [deviceId]
        );
        if (!bindRows || bindRows.length === 0) return null;
        const tierId = bindRows[0].tier_id;
        // 取 tier 详情（修正为 tier_id）
        const [tierRows] = await connection.execute(
            `SELECT tier_id, price, profit_json FROM lottery_price_tier WHERE tier_id = ? LIMIT 1`,
            [tierId]
        );
        if (!tierRows || tierRows.length === 0) return null;
        const row = tierRows[0];
        const tierAmount = parseFloat(row.price || 0);
        // 金额不匹配则认为无效
        if (parseFloat(amount || 0) !== tierAmount) return null;
        let profitCfg = {};
        try { profitCfg = JSON.parse(row.profit_json || '{}') || {}; } catch (_) { profitCfg = {}; }
        return { tier_id: row.tier_id, amount: tierAmount, profit: profitCfg };
    } catch (error) {
        console.error('❌ 获取分润配置失败:', error);
        return null;
    } finally {
        connection.release();
    }
}

// 根据 tier_id 获取分润配置（用户可能主动传 config_id，此处视为 tier_id）
async function getProfitConfigById(deviceId, configId) {
    const connection = await pool.getConnection();
    try {
        const tierId = parseInt(configId, 10);
        if (Number.isNaN(tierId) || tierId <= 0) return null;
        // 可选：校验该 tier 与设备是否有关联（存在绑定）。若无绑定也允许继续，以便前端传入 config_id 测试
        const [tierRows] = await connection.execute(
            `SELECT id, price, profit_json FROM lottery_price_tier WHERE id = ? LIMIT 1`,
            [tierId]
        );
        if (!tierRows || tierRows.length === 0) return null;
        const row = tierRows[0];
        let profitCfg = {};
        try { profitCfg = JSON.parse(row.profit_json || '{}') || {}; } catch (_) { profitCfg = {}; }
        return {
            tier_id: row.id,
            amount: parseFloat(row.price || 0),
            profit: profitCfg
        };
    } catch (error) {
        console.error('❌ 根据ID获取分润配置失败:', error);
        return null;
    } finally {
        connection.release();
    }
}

module.exports = {
    getDeviceRoleUsers,
    getProfitConfigByDeviceAndAmount,
    getProfitConfigById
};
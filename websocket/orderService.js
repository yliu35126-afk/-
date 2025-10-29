const { pool } = require('./database');

// 订单服务模块

async function findAvailableOrder(openid, deviceId = null, orderId = null) {
    const connection = await pool.getConnection();
    let existingOrder = null;
    try {
        await connection.beginTransaction();
        
        // 查找可用的已支付订单
        console.log(`🔍 查找订单条件: openid=${openid}, deviceId=${deviceId || 'null'}, orderId=${orderId || 'null'}`);
        
        // 如果显式传入 orderId，优先按订单锁定（不再过滤 prize_id）
        if (orderId) {
            const [byOrderId] = await connection.execute(
                `SELECT id, order_id, pay_status, order_status, prize_id, order_amount, config_id, openid, device_id 
                 FROM fa_order 
                 WHERE (order_id = ? OR id = ?) 
                 LIMIT 1 FOR UPDATE`,
                [orderId, orderId]
            );
            console.log('🧾 指定订单查询结果:', byOrderId);
            if (byOrderId && byOrderId.length > 0) {
                const ord = byOrderId[0];
                if (String(ord.pay_status) === 'paid') {
                    if (ord.openid && openid && String(ord.openid) !== String(openid)) {
                        console.warn(`⚠️ 指定订单openid不匹配: order.openid=${ord.openid}, current.openid=${openid}`);
                    }
                    if (ord.device_id && deviceId && String(ord.device_id) !== String(deviceId)) {
                        console.warn(`⚠️ 指定订单device不匹配: order.device_id=${ord.device_id}, current.deviceId=${deviceId}`);
                    }
                    // 放宽条件：允许已锁定(2)的订单在同一次会话内复用
                    if ([0,1,2].includes(parseInt(ord.order_status))) {
                        await connection.execute(
                            `UPDATE fa_order SET order_status = 2, updatetime = ? WHERE id = ?`,
                            [Math.floor(Date.now() / 1000), ord.id]
                        );
                        existingOrder = {
                            id: ord.id,
                            order_id: ord.order_id,
                            order_amount: ord.order_amount,
                            config_id: ord.config_id
                        };
                        console.log(`🔒 按指定订单锁定: ID=${ord.id}, 外部订单号=${ord.order_id}, 金额=${ord.order_amount}元 (原状态=${ord.order_status})`);
                        await connection.commit();
                        return existingOrder;
                    } else {
                        console.warn(`⚠️ 指定订单状态不可用: order_status=${ord.order_status}`);
                    }
                } else {
                    console.warn(`⚠️ 指定订单未支付或状态异常: pay_status=${ord.pay_status}, order_status=${ord.order_status}`);
                }
            }
            // 未返回，在后续按 openid/deviceId 继续回退查询
        }
        
        // 先查看该用户的所有订单
        const [allOrders] = await connection.execute(
            `SELECT id, order_id, pay_status, order_status, prize_id, order_amount, config_id, createtime FROM fa_order 
             WHERE openid = ? ORDER BY createtime DESC LIMIT 5`,
            [openid]
        );
        console.log(`📋 该用户所有订单:`, allOrders);
        
        // 再查找可用订单
        const [rows] = await connection.execute(
            `SELECT id, order_id, order_amount, config_id FROM fa_order 
             WHERE openid = ? 
               AND (device_id = ? OR ? IS NULL) 
               AND pay_status = 'paid' 
               AND order_status IN (0, 1) 
               AND (prize_id = 0 OR prize_id IS NULL) 
             ORDER BY createtime DESC LIMIT 1 FOR UPDATE`,
            [openid, deviceId, deviceId]
        );
        console.log(`🎯 可用订单查询结果:`, rows);
        
        if (rows.length > 0) {
            existingOrder = rows[0];
            
            // 立即标记该订单为正在使用
            await connection.execute(
                `UPDATE fa_order SET order_status = 2, updatetime = ? WHERE id = ?`,
                [Math.floor(Date.now() / 1000), existingOrder.id]
            );
            
            console.log(`🔒 锁定订单: ID=${existingOrder.id}, 外部订单号=${existingOrder.order_id}, 金额=${existingOrder.order_amount}元`);
        }
        
        await connection.commit();
        return existingOrder;
    } catch (error) {
        // 目标库可能不存在 fa_order，容错返回空以不中断抽奖流程
        try { await connection.rollback(); } catch (_) {}
        console.error('❌ 查询或锁定订单失败(容错为无可用订单):', error);
        return null;
    } finally {
        connection.release();
    }
}

async function updateOrderStatus(orderId, status, prizeId = 0) {
    const connection = await pool.getConnection();
    try {
        await connection.execute(
            `UPDATE fa_order SET order_status = ?, prize_id = ?, updatetime = ? WHERE id = ?`,
            [status, prizeId, Math.floor(Date.now() / 1000), orderId]
        );
        console.log(`📝 订单状态更新: 订单ID=${orderId}, 状态=${status}`);
        return { success: true };
    } catch (error) {
        console.error('❌ 更新订单状态失败:', error);
        return { success: false, error: error.message };
    } finally {
        connection.release();
    }
}

async function getOrderStatus(orderIdOrExternal) {
    const connection = await pool.getConnection();
    try {
        const [rows] = await connection.execute(
            'SELECT order_status, prize_id FROM fa_order WHERE (order_id = ? OR id = ?) LIMIT 1',
            [orderIdOrExternal, orderIdOrExternal]
        );
        
        if (rows.length > 0) {
            return rows[0];
        }
        return null;
    } catch (error) {
        console.error('❌ 查询订单状态失败:', error);
        return null;
    } finally {
        connection.release();
    }
}

module.exports = {
    findAvailableOrder,
    updateOrderStatus,
    getOrderStatus
};
const { devices, users, deviceRooms, userMap, deviceMap, setDeviceBusy, releaseDevice, isDeviceBusy, getDeviceStatus } = require('./deviceManager');
const { findAvailableOrder, updateOrderStatus } = require('./orderService');
const { getProfitConfigById } = require('./profitService');
const { updateDeviceOnlineStatus, verifyDevice, getDeviceConfigId, getProfitConfigPreview } = require('./database');
const { enqueueLottery, getQueueStatus, removeUserFromQueues } = require('./queueManager');
const axios = require('axios');
const config = require('./config');
const database = require('./database');

// 启动级设备配置缓存（供 server_new.js 预热写入）
const deviceCache = {};

// 新增：后台管理端连接跟踪
const adminClients = new Set();

// 新增：构建在线设备列表（复用 /devices 接口的结构）
function buildOnlineDeviceList() {
    const onlineDevices = [];
    for (const [socketId, deviceInfo] of devices.entries()) {
        if (!deviceInfo) continue;
        onlineDevices.push({
            device_id: deviceInfo.device_id,
            connected_at: deviceInfo.registered_at ? deviceInfo.registered_at.toISOString() : '',
            last_heartbeat: deviceInfo.last_heartbeat ? deviceInfo.last_heartbeat.toISOString() : '',
            device_type: deviceInfo.device_type || 'unknown',
            socket_id: socketId,
            device_name: deviceInfo.device_name || ''
        });
    }
    return onlineDevices;
}

// Socket事件处理模块

function handleSocketEvents(io, socket) {
    // 辅助：按设备ID查找设备信息（devices为socketId->deviceInfo）
    const getDeviceInfoByDeviceId = (deviceId) => {
        for (const [, info] of devices.entries()) {
            if (info && info.device_id === deviceId) return info;
        }
        return null;
    };

    // 新增：后台管理端注册
    socket.on('admin_register', (payload) => {
        try {
            adminClients.add(socket.id);
            socket.emit('admin_register_response', { success: true, message: 'registered', timestamp: new Date().toISOString() });
            // 初次注册立即推送一次完整设备列表
            socket.emit('device_list_update', buildOnlineDeviceList());
        } catch (e) {
            try { socket.emit('admin_register_response', { success: false, message: e && e.message ? e.message : 'internal_error' }); } catch (_) {}
        }
    });

    // 设备注册
    socket.on('device_register', async (data) => {
        const { device_id, device_name, device_type } = data;
        
        if (!device_id) {
            socket.emit('error', { message: '设备ID不能为空' });
            return;
        }
        
        console.log(`设备注册请求: ${device_id} (${device_name})`);
        
        // 验证设备是否存在
        const isValidDevice = await verifyDevice(device_id);
        const forceRegister = (
            process.env.FORCE_DEVICE_REGISTER === 'true' ||
            process.env.FORCE_DEVICE_REGISTER === '1' ||
            process.env.NODE_ENV === 'development'
        );
        if (!isValidDevice && !forceRegister) {
            socket.emit('device_register_response', {
                success: false,
                message: '设备未注册或不存在'
            });
            return;
        }
        
        // 读取设备绑定的配置ID
        let dbConfigId = await getDeviceConfigId(device_id);
        if (!dbConfigId && forceRegister) {
            dbConfigId = parseInt(process.env.AUTO_BIND_CONFIG_ID || '26', 10);
        }

        // 保存设备信息（包含当前配置ID）
        devices.set(socket.id, {
            device_id,
            device_name,
            device_type,
            socket_id: socket.id,
            registered_at: new Date(),
            last_heartbeat: new Date(),
            config_id: dbConfigId || null
        });
        // 快速映射：设备ID -> socket.id
        deviceMap.set(device_id, socket.id);
        
        // 创建或加入设备房间
        const roomName = `device_${device_id}`;
        socket.join(roomName);
        deviceRooms.set(device_id, roomName);
        
        // 更新数据库中的设备在线状态
        await updateDeviceOnlineStatus(device_id, true, new Date());

        // 推送当前配置预览（若存在）到房间，保证前端与后台一致
        if (dbConfigId) {
            const preview = await getProfitConfigPreview(dbConfigId);
            if (preview) {
                io.to(roomName).emit('config_preview', preview);
                const supplierCount = (preview.prizes && preview.prizes.supplier && Array.isArray(preview.prizes.supplier.data)) ? preview.prizes.supplier.data.length : 0;
                const merchantCount = (preview.prizes && preview.prizes.merchant && Array.isArray(preview.prizes.merchant.data)) ? preview.prizes.merchant.data.length : 0;
                console.log(`🔄 推送配置预览至设备房间 ${roomName}: config_id=${dbConfigId}, supplier=${supplierCount}, merchant=${merchantCount}`);
            } else {
                console.warn(`⚠️ 配置ID=${dbConfigId} 无法生成预览或未启用，暂不推送`);
            }
        } else {
            console.warn(`⚠️ 设备 ${device_id} 未在DB绑定配置ID（config_id为空），前端可能使用默认缓存`);
        }
        
        // 确认注册成功
        socket.emit('device_register_response', {
            success: true,
            device_id,
            message: '设备注册成功',
            room: roomName
        });
        
        // 新增：通知管理端有设备上线并推送最新列表
        try {
            io.emit('device_registered', { device_id, device_name, device_type, timestamp: new Date().toISOString() });
            io.emit('device_list_update', buildOnlineDeviceList());
        } catch (e) { console.warn('广播设备注册事件失败（忽略）:', e && e.message ? e.message : e); }
        
        console.log(`✅ 设备 ${device_id} 注册成功，房间: ${roomName}`);
    });

    // 用户加入设备
    socket.on('user_join', async (data) => {
        const { device_id, user_id, user_name, openid, game_type = 'lottery', lottery_type, user_phone, order_id, config_id } = data || {};
        
        console.log('🧩 user_join payload:', data);
        console.log(`用户加入: ${user_id} -> 设备 ${device_id}`);
        
        // 加入设备房间
        const roomName = `device_${device_id}`;
        socket.join(roomName);
        console.log(`🧩 房间加入: ${roomName}, socket=${socket.id}`);
        
        // 创建用户信息
        const userInfo = {
            device_id: device_id,
            user_id: user_id || 'user_' + Date.now(),
            openid: openid,
            game_type: game_type,
            // config_id 会在后续流程中赋值
            order_id: order_id,
            socket_id: socket.id,
            joined_at: new Date(),
            reservation_only: true  // 标记为预约状态，允许同一用户开始抽奖
        };
        
        // 设置设备为忙碌状态（接续预约或直接占用）
        setDeviceBusy(device_id, userInfo, io);
        
        // 保存用户信息
        users.set(socket.id, userInfo);
        if (userInfo && userInfo.user_id) {
            userMap.set(userInfo.user_id, socket.id);
        }
        
        // 确认加入成功
        socket.emit('joined_device', {
            success: true,
            device_id,
            user_id: userInfo.user_id,
            message: `已加入设备 ${device_id}`,
            room: roomName
        });

        // 新增：广播设备占用状态变更
        try { io.emit('device_status_change', { device_id, status: 'busy', timestamp: new Date().toISOString() }); } catch (_) {}

        // 通知设备有新用户加入
        socket.to(roomName).emit('user_joined', {
            user_id: userInfo.user_id,
            user_name: user_name || 'Anonymous',
            device_id,
            timestamp: new Date().toISOString()
        });
        
        console.log(`✅ 用户 ${userInfo.user_id} 加入设备 ${device_id} 成功`);
    });

    // 兼容小程序端事件名：join_device -> user_join
    socket.on('join_device', async (data) => {
        const { device_id, user_id, user_name, openid, game_type = 'lottery', lottery_type, user_phone, order_id, config_id } = data || {};
        console.log('🧩 join_device payload:', data);
        const roomName = `device_${device_id}`;
        socket.join(roomName);
        console.log(`🧩 房间加入: ${roomName}, socket=${socket.id}`);
        const userInfo = {
            device_id: device_id,
            user_id: user_id || 'user_' + Date.now(),
            openid: openid,
            game_type: game_type,
            order_id: order_id,
            socket_id: socket.id,
            joined_at: new Date(),
            reservation_only: true
        };
        setDeviceBusy(device_id, userInfo, io);
        users.set(socket.id, userInfo);
        if (userInfo && userInfo.user_id) {
            userMap.set(userInfo.user_id, socket.id);
        }
        socket.emit('joined_device', {
            success: true,
            device_id,
            user_id: userInfo.user_id,
            message: `已加入设备 ${device_id}`,
            room: roomName
        });
        try { io.emit('device_status_change', { device_id, status: 'busy', timestamp: new Date().toISOString() }); } catch (_) {}
        socket.to(roomName).emit('user_joined', {
            user_id: userInfo.user_id,
            user_name: user_name || 'Anonymous',
            device_id,
            timestamp: new Date().toISOString()
        });
        console.log(`✅ 用户 ${userInfo.user_id} 通过 join_device 加入设备 ${device_id} 成功`);
    });

    // 用户发起抽奖 - 使用队列机制替代设备锁
    socket.on('start_lottery', async (data) => {
        console.log('收到抽奖请求:', data);
        
        const { device_id, user_id, openid, game_type = 'lottery' } = data;
        
        // 基础参数验证
        if (!device_id) {
            socket.emit('error', { message: '缺少设备ID' });
            return;
        }
        
        if (!openid) {
            socket.emit('error', { message: '缺少openid' });
            return;
        }

        // 将抽奖请求加入队列，而不是直接检查设备锁
        enqueueLottery(device_id, socket, data, handleLotteryRequest);
    });

    /**
     * 实际的抽奖处理逻辑（从队列中调用）
     */
    async function handleLotteryRequest(socket, data) {
        const { device_id, user_id, openid, game_type = 'lottery' } = data;
        // config_id 在生产流程中可能需要置空回退，因此使用可变变量
        let config_id = data && data.config_id;
        
        // 验证config_id（如果提供）；无效则仅警告并继续，不阻断抽奖流程
        if (config_id) {
            const configResult = await getProfitConfigById(device_id, config_id);
            if (!configResult) {
                console.warn(`⚠️ 分润配置ID=${config_id}无效或不属于设备${device_id}，将按缺省策略继续`);
                // 置空以便后续按订单或金额回退策略处理
                config_id = null;
            } else {
                console.log(`✅ 验证分润配置成功: ID=${config_id}, 名称=${configResult.config_name}, 金额=${configResult.lottery_amount}元`);
            }
        }
        
        // 自动将用户加入到设备房间（简化流程）
        const roomName = `device_${device_id}`;
        socket.join(roomName);
        
        console.log(`用户 ${user_id} (openid: ${openid}) 自动加入设备 ${device_id}${config_id ? ', config_id=' + config_id : ''}`);
        
        try {
            // 查找已支付的订单（优先使用显式 order_id，其次匹配 openid+device_id）
            const existingOrder = await findAvailableOrder(openid, device_id, data && data.order_id);
            
            // 如果没有找到已支付订单，返回错误
            if (!existingOrder) {
                socket.emit('error', { 
                    message: '未找到可用的已支付订单，请先支付再开始游戏',
                    code: 'NO_PAID_ORDER'
                });
                console.warn(`⚠️ 用户${user_id}未找到可用订单，openid=${openid}, device_id=${device_id}, order_id=${(data && data.order_id) || '未提供'}`);
                return;
            }
            
            // 使用已支付订单（统一为外部订单号）
            const saveResult = { success: true, order_id: existingOrder.order_id };
            const orderAmount = parseFloat(existingOrder.order_amount);
            const orderConfigId = existingOrder.config_id || config_id; // 优先使用订单中的config_id
            console.log(`🎮 使用已支付订单: 订单ID=${existingOrder.id}, 金额=${orderAmount}元, 配置ID=${orderConfigId}`);
            
            // 创建用户信息
            const userInfo = {
                device_id: device_id,
                user_id: user_id || 'user_' + Date.now(),
                openid: openid,
                game_type: game_type,
                // 先使用订单config_id；若无则后续按设备绑定ID或默认26回退
                config_id: orderConfigId,
                order_id: saveResult.order_id,
                order_amount: orderAmount,
                socket_id: socket.id,
                joined_at: new Date()
            };
            
            // 设置设备为忙碌状态（接续预约或直接占用）
            setDeviceBusy(device_id, userInfo, io);
            
            // 保存用户信息
            users.set(socket.id, userInfo);
            // 快速映射：用户ID -> socket.id
            if (userInfo && userInfo.user_id) {
                userMap.set(userInfo.user_id, socket.id);
            }
            
            const { lottery_type = 1, user_name, user_phone } = data;
            
            // 获取设备房间
            let targetRoomName = deviceRooms.get(device_id);
            
            // 如果设备不在线，仍然发送指令（可能是离线处理）
            if (!targetRoomName) {
                targetRoomName = roomName; // 使用当前房间名
                console.log(`设备 ${device_id} 离线，尝试发送指令到房间 ${targetRoomName}`);
            }

            // 计算设备级回退配置：订单指定优先→设备绑定→默认26
            const devInfo = getDeviceInfoByDeviceId(device_id);
            const deviceConfigId = devInfo && devInfo.config_id ? devInfo.config_id : null;
            const configToSend = orderConfigId || deviceConfigId || 26;
            // 同步用户状态使用的配置ID，避免后续结果处理错配
            userInfo.config_id = configToSend;

            // 在抽奖前广播配置预览，确保前端与设备一致并打印验收日志
            try {
                const preview = await getProfitConfigPreview(configToSend);
                if (preview) {
                    // 仅推送到设备房间，取消用户通道双发
                    io.to(roomName).emit('config_preview', preview);
                    const supplierCount = (preview.prizes && preview.prizes.supplier && Array.isArray(preview.prizes.supplier.data)) ? preview.prizes.supplier.data.length : 0;
                    const merchantCount = (preview.prizes && preview.prizes.merchant && Array.isArray(preview.prizes.merchant.data)) ? preview.prizes.merchant.data.length : 0;
                    console.log(`🔄 抽奖前推送配置预览: device=${device_id}, config_id=${configToSend}, supplier=${supplierCount}, merchant=${merchantCount}`);
                    // 记录设备当前配置的预览到内存缓存，便于结果阶段补齐图片
                    deviceCache[configToSend] = preview;
                } else {
                    console.warn(`⚠️ 抽奖前未能生成配置预览: config_id=${configToSend}`);
                }
            } catch (e) {
                console.error('❌ 抽奖前推送配置预览失败:', e && e.message ? e.message : e);
            }
            const lotteryData = {
                user_id: userInfo.user_id,
                device_id: device_id,
                lottery_type,
                user_name,
                user_phone,
                order_id: saveResult.order_id,
                orderId: saveResult.order_id, // 兼容旧Android客户端字段
                order_amount: orderAmount,
                config_id: configToSend,
                timestamp: new Date().toISOString()
            };

            console.log(`用户发起抽奖: ${device_id}`, lotteryData);
            console.info(`[lottery_start] 使用配置ID=${configToSend}`);
            // 统一进度态：已支付→等待设备响应
            io.to(userInfo.user_id).emit('progress_update', {
                status: 'paid',
                device_id,
                order_id: saveResult.order_id,
                ts: new Date().toISOString()
            });

            // 发送抽奖指令给设备（统一事件名为 lottery_start）
            socket.to(targetRoomName).emit('lottery_start', lotteryData);
            // 统一进度态：设备已开始→抽奖中
            io.to(userInfo.user_id).emit('progress_update', { status: 'device_started', device_id, order_id: saveResult.order_id, ts: new Date().toISOString() });
            io.to(userInfo.user_id).emit('progress_update', { status: 'lottery_running', device_id, order_id: saveResult.order_id, ts: new Date().toISOString() });
            
            // 同时发送给所有连接到该设备的客户端
            io.to(roomName).emit('game_started', {
                device_id: device_id,
                user_id: userInfo.user_id,
                order_id: saveResult.order_id,
                order_amount: orderAmount,
                message: '抽奖已开始，设备已被占用，等待抽奖结果'
            });

            // 确认用户抽奖请求已发送
            socket.emit('lottery_sent', {
                success: true,
                message: '抽奖指令已发送到设备，设备已被占用，等待抽奖结果',
                order_id: saveResult.order_id,
                order_amount: orderAmount,
                data: lotteryData
            });
            
        } catch (error) {
            console.error('❌ 处理抽奖请求失败:', error);
            socket.emit('error', { message: '订单处理失败，请稍后再试' });
        }
    }
    
    // 设备返回抽奖结果（透传真实结果到后端与前端）
    socket.on('lottery_result', async (data) => {
        const device = devices.get(socket.id);
        if (!device) {
            socket.emit('error', { message: '设备未注册' });
            return;
        }

        const { user_id, result, prize_info, lottery_record_id } = data;
        const roomName = deviceRooms.get(device.device_id);

        console.log(`设备返回抽奖结果: ${device.device_id}`, data);

        try {
            // 查找对应的用户信息，获取订单ID、订单金额和config_id
            let targetOrderId = null;
            let orderAmount = null;
            let userInfo = null;
            let configId = null;
            let userSocketId = null;
            
            // 从设备状态中获取当前用户信息
            const deviceStatus = getDeviceStatus(device.device_id);
            if (deviceStatus && deviceStatus.currentUser.user_id === user_id) {
                userInfo = deviceStatus.currentUser;
                targetOrderId = userInfo.order_id;
                orderAmount = userInfo.order_amount;
                configId = userInfo.config_id;
                userSocketId = userInfo.socket_id;
            } else {
                // 如果设备状态中没有找到，回退到遍历用户列表
                for (const [socketId, info] of users.entries()) {
                    if (info.user_id === user_id && info.device_id === device.device_id) {
                        targetOrderId = info.order_id;
                        orderAmount = info.order_amount;
                        userInfo = info;
                        configId = info.config_id;
                        userSocketId = socketId;
                        break;
                    }
                }
            }

            console.log(`🎯 找到用户信息: 用户=${user_id}, 订单=${targetOrderId}, 金额=${orderAmount}, 配置=${configId}`);

            // 检查订单是否已经处理过
            if (targetOrderId) {
                const orderStatus = await require('./orderService').getOrderStatus(targetOrderId);
                
                if (orderStatus && orderStatus.prize_id > 0) {
                    console.warn(`⚠️ 订单${targetOrderId}已经处理过，跳过重复处理`);
                    return;
                }
            }

            // 统一POST到PHP回调；按设备真实结果透传
            const payload = {
                device_id: device.device_id,
                order_id: targetOrderId || 0,
                user_id: user_id || 0,
                lottery_record_id,
                prize_id: (prize_info && prize_info.id) ? prize_info.id : 0,
                prize_name: (prize_info && prize_info.name) ? prize_info.name : '',
                amount: orderAmount || 0,
                result: result || 'error'
            };
            try {
                console.log(`📨 调用后端回调: ${config.apiBaseUrl}${config.lotteryCallbackEndpoint}`, payload);
                const resp = await axios.post(`${config.apiBaseUrl}${config.lotteryCallbackEndpoint}`, payload, { timeout: 10000 });
                if (resp && resp.data && resp.data.order_id) {
                    payload.order_id = resp.data.order_id;
                }
            } catch (err) {
                console.error('❌ 回调后端失败:', err && err.message ? err.message : err);
            }

            // 新增：基于价档的本地落库（lottery_record + lottery_profit）
            let localRecordId = 0;
            try {
                const tierId = configId ? parseInt(configId, 10) : await database.getDeviceConfigId(device.device_id);
                const tierDetail = tierId ? await database.getTierDetail(tierId) : null;
                const devMini = await database.getDeviceInfoMini(device.device_id);
                if (tierDetail && devMini) {
                    localRecordId = await database.saveLotteryRecord({
                        member_id: user_id || 0,
                        device_id: device.device_id,
                        board_id: devMini.board_id || 0,
                        slot_id: 0,
                        prize_type: (prize_info && prize_info.type) || 'thanks',
                        goods_id: (prize_info && prize_info.goods_id) || 0,
                        tier_id: tierId,
                        amount: parseFloat(orderAmount || tierDetail.price || 0),
                        round_no: 0,
                        result: String(result || 'miss'),
                        order_id: payload.order_id || targetOrderId || 0,
                        ext: prize_info ? JSON.stringify(prize_info) : null
                    });
                    const shares = database.computeProfitShares(orderAmount || tierDetail.price || 0, tierDetail.profit || {});
                    await database.saveLotteryProfit({
                        record_id: localRecordId,
                        device_id: device.device_id,
                        tier_id: tierId,
                        site_id: devMini.site_id || 0,
                        amount_total: shares.amount_total,
                        platform_money: shares.platform_money,
                        supplier_money: shares.supplier_money,
                        promoter_money: shares.promoter_money,
                        installer_money: shares.installer_money,
                        owner_money: shares.owner_money
                    });
                }
            } catch (e) {
                console.warn('⚠️ 本地落库失败（不影响流程）:', e && e.message ? e.message : e);
            }

            // 清理用户状态和释放设备
            if (userSocketId && users.has(userSocketId)) {
                const userData = users.get(userSocketId);
                users.set(userSocketId, userData);
                console.log(`🏁 用户抽奖状态已清理: ${user_id}`);
            }
            
            // 释放设备状态（设备独占模式）
            releaseDevice(device.device_id);
            console.log(`🔓 设备${device.device_id}已释放，可接受新的抽奖请求`);

            // 根据预览补齐 prize 嵌套对象，保持与HTTP智能抽奖一致
            let nestedPrize = {
                id: (prize_info && prize_info.id) ? prize_info.id : 0,
                name: (prize_info && prize_info.name) ? prize_info.name : '',
                image: (prize_info && (prize_info.image || prize_info.prize_image)) ? (prize_info.image || prize_info.prize_image) : '',
                price: (prize_info && (prize_info.activity_price || prize_info.price)) ? (prize_info.activity_price || prize_info.price) : 0
            };
            try {
                const preview = deviceCache[configId];
                if (preview && preview.prizes) {
                    const supplier = (preview.prizes.supplier && Array.isArray(preview.prizes.supplier.data)) ? preview.prizes.supplier.data : [];
                    const merchant = (preview.prizes.merchant && Array.isArray(preview.prizes.merchant.data)) ? preview.prizes.merchant.data : [];
                    const list = supplier.concat(merchant);
                    const found = list.find(p => String(p.id) === String(nestedPrize.id));
                    if (found) {
                        nestedPrize.name = found.name || nestedPrize.name;
                        nestedPrize.image = found.image || nestedPrize.image;
                        nestedPrize.price = (found.activity_price || found.price || nestedPrize.price);
                    }
                }
                console.log(`[lottery_result_nest] id=${nestedPrize.id} name=${nestedPrize.name} price=${nestedPrize.price} image=${nestedPrize.image}`);
            } catch (e) {
                console.warn('⚠️ 构建嵌套奖品对象失败:', e && e.message ? e.message : e);
            }

            // 发送结果给指定用户
            if (userSocketId) {
                // 直接向用户的socket发送消息，透传真实结果
                io.to(userSocketId).emit('lottery_result', {
                    success: true,
                    result,
                    prize_info,
                    prize: nestedPrize,
                    lottery_record_id: localRecordId || lottery_record_id,
                    device_id: device.device_id,
                    order_id: payload.order_id || targetOrderId || 0,
                    timestamp: new Date().toISOString()
                });
                console.log(`🎯 抽奖结果已发送给用户: ${user_id} (socket: ${userSocketId})`);
            } else {
                // 兜底：尝试通过 userMap 定位用户socket
                const mappedSocket = userMap.get(user_id);
                if (mappedSocket) {
                    io.to(mappedSocket).emit('lottery_result', {
                        success: true,
                        result,
                        prize_info,
                        prize: nestedPrize,
                        lottery_record_id: localRecordId || lottery_record_id,
                        device_id: device.device_id,
                        order_id: payload.order_id || targetOrderId || 0,
                        timestamp: new Date().toISOString()
                    });
                    console.log(`📤 兜底推送中奖结果到 userMap 映射: ${user_id} (socket: ${mappedSocket})`);
                } else {
                    // 再兜底：取设备房间最后一个加入的socket
                    const clients = await io.in(roomName).allSockets();
                    const latestSocket = clients && clients.size ? Array.from(clients).pop() : null;
                    if (latestSocket) {
                        io.to(latestSocket).emit('lottery_result', {
                            success: true,
                            result,
                            prize_info,
                            prize: nestedPrize,
                            lottery_record_id: localRecordId || lottery_record_id,
                            device_id: device.device_id,
                            order_id: payload.order_id || targetOrderId || 0,
                            timestamp: new Date().toISOString()
                        });
                        console.log(`📤 兜底推送中奖结果到最后一个连接: ${latestSocket}`);
                    } else {
                        console.warn(`⚠️ 设备${device.device_id}房间内无用户连接，中奖结果未推送`);
                    }
                }

                // 调试输出在线映射，便于排查绑定问题
                try {
                    console.log('当前在线用户:', Array.from(userMap.keys()));
                    console.log('当前在线设备:', Array.from(deviceMap.keys()));
                } catch (e) {}
            }

            // 广播给房间内所有用户（用于大屏显示）
            socket.to(roomName).emit('lottery_broadcast', {
                result,
                prize_info,
                prize: nestedPrize,
                device_id: device.device_id,
                timestamp: new Date().toISOString()
            });
            
        } catch (error) {
            console.error('❌ 处理抽奖结果失败:', error);
        }
    });

    // 心跳检测
    socket.on('heartbeat', async () => {
        const device = devices.get(socket.id);
        if (device) {
            device.last_heartbeat = new Date();
            
            // 更新数据库中的心跳时间
            await updateDeviceOnlineStatus(device.device_id, true, device.last_heartbeat);

            // 自动刷新设备配置：当DB绑定ID变化时，更新缓存并推送最新预览
            try {
                const dbConfigId = await getDeviceConfigId(device.device_id);
                const roomName = deviceRooms.get(device.device_id);
                if (dbConfigId && dbConfigId !== device.config_id) {
                    console.log(`🔄 检测到设备 ${device.device_id} 配置更新: ${device.config_id || 'null'} → ${dbConfigId}`);
                    device.config_id = dbConfigId;
                    const preview = await getProfitConfigPreview(dbConfigId);
                    if (preview && roomName) {
                        io.to(roomName).emit('config_preview', preview);
                        const supplierCount = (preview.prizes && preview.prizes.supplier && Array.isArray(preview.prizes.supplier.data)) ? preview.prizes.supplier.data.length : 0;
                        const merchantCount = (preview.prizes && preview.prizes.merchant && Array.isArray(preview.prizes.merchant.data)) ? preview.prizes.merchant.data.length : 0;
                        console.log(`🟢 已推送最新配置预览: device=${device.device_id}, config_id=${dbConfigId}, supplier=${supplierCount}, merchant=${merchantCount}`);
                    }
                }
            } catch (e) {
                console.warn(`⚠️ 心跳同步设备配置失败: ${e && e.message ? e.message : e}`);
            }
        }
        socket.emit('heartbeat_response', { timestamp: new Date().toISOString() });
    });

    // 获取设备状态
    socket.on('get_device_status', () => {
        const device = devices.get(socket.id);
        if (device) {
            const roomName = deviceRooms.get(device.device_id);
            const roomUsers = Array.from(users.values()).filter(u => u.device_id === device.device_id);
            
            socket.emit('device_status', {
                device: device,
                room: roomName,
                users: roomUsers,
                timestamp: new Date().toISOString()
            });
        } else {
            socket.emit('error', { message: '设备未注册' });
        }
    });

    // 连接断开处理
    socket.on('disconnect', async () => {
        const device = devices.get(socket.id);
        const user = users.get(socket.id);
        
        // 清理管理端注册
        try { adminClients.delete(socket.id); } catch (_) {}
        
        if (device) {
            console.log(`设备断开连接: ${device.device_id}`);
            
            // 更新数据库中的设备离线状态
            await updateDeviceOnlineStatus(device.device_id, false);
            
            devices.delete(socket.id);
            deviceRooms.delete(device.device_id);
            // 同步清理设备映射
            try { deviceMap.delete(device.device_id); } catch (e) {}
            
            // 如果设备断开，释放其状态
            releaseDevice(device.device_id);

            // 新增：广播给管理端（设备下线 + 列表更新）
            try {
                io.emit('device_disconnected', { device_id: device.device_id, timestamp: new Date().toISOString() });
                io.emit('device_list_update', buildOnlineDeviceList());
            } catch (_) {}
        }
        
        if (user) {
            console.log(`用户断开连接: ${user.user_id}`);
            
            // 从队列中移除用户的排队任务
            removeUserFromQueues(socket.id);
            
            users.delete(socket.id);
            // 同步清理用户映射
            try { if (user.user_id) userMap.delete(user.user_id); } catch (e) {}
            
            // 如果用户断开且设备正在被其占用，释放设备
            if (user.device_id) {
                const deviceStatus = getDeviceStatus(user.device_id);
                if (deviceStatus && deviceStatus.currentUser.socket_id === socket.id) {
                    releaseDevice(user.device_id);
                    console.log(`🔓 用户断开连接，释放设备${user.device_id}`);
                    try { io.emit('device_status_change', { device_id: user.device_id, status: 'idle', timestamp: new Date().toISOString() }); } catch (_) {}
                }
            }
        }
    });
}

module.exports = { handleSocketEvents, deviceCache };


async function handleLotteryResult(io, deviceId, data) {
    try {
        // 原有处理：查询用户、订单状态、回调等
        const { userId, orderId, result, prize_info, amount, config_id, slot_id, round_no } = data;
        // 新增：价档 & 设备信息
        const tierId = config_id ? parseInt(config_id, 10) : await database.getDeviceConfigId(deviceId);
        const tierDetail = tierId ? await database.getTierDetail(tierId) : null;
        const dev = await database.getDeviceInfoMini(deviceId);
        // 计算分润
        const shares = tierDetail ? database.computeProfitShares(amount || (tierDetail.price || 0), tierDetail.profit || {}) : null;
        // 写入抽奖记录
        let recordId = 0;
        if (dev && tierDetail) {
            recordId = await database.saveLotteryRecord({
                member_id: userId || 0,
                device_id: deviceId,
                board_id: dev.board_id || 0,
                slot_id: slot_id || 0,
                prize_type: (prize_info && prize_info.type) || 'thanks',
                goods_id: (prize_info && prize_info.goods_id) || 0,
                tier_id: tierId,
                amount: parseFloat(amount || tierDetail.price || 0),
                round_no: round_no || 0,
                result: String(result || 'miss'),
                order_id: orderId || 0,
                ext: prize_info ? JSON.stringify(prize_info) : null
            });
        }
        // 写入分润记录
        if (recordId && shares) {
            await database.saveLotteryProfit({
                record_id: recordId,
                device_id: deviceId,
                tier_id: tierId,
                site_id: dev && dev.site_id ? dev.site_id : 0,
                amount_total: shares.amount_total,
                platform_money: shares.platform_money,
                supplier_money: shares.supplier_money,
                promoter_money: shares.promoter_money,
                installer_money: shares.installer_money,
                owner_money: shares.owner_money
            });
        }
        console.log('lottery_result 处理异常:', err);
    } catch (err) {
        console.error('lottery_result 处理异常:', err);
    }
}
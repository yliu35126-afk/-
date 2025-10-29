// 设备状态管理模块 - 设备独占模式

// 存储设备游戏状态
const deviceGameStatus = new Map();
// 存储设备超时定时器
const deviceTimeouts = new Map();

// 设置设备为忙碌状态
function setDeviceBusy(deviceId, userInfo, io = null) {
    // 清除之前的超时定时器（如果存在）
    if (deviceTimeouts.has(deviceId)) {
        clearTimeout(deviceTimeouts.get(deviceId));
        deviceTimeouts.delete(deviceId);
    }
    
    deviceGameStatus.set(deviceId, {
        isBusy: true,
        currentUser: userInfo,
        startTime: Date.now()
    });
    console.log(`🔒 设备${deviceId}被用户${userInfo.user_id}占用`);
    
    // 设置60秒自动解锁定时器
    const timeoutId = setTimeout(() => {
        if (deviceGameStatus.has(deviceId) && deviceGameStatus.get(deviceId).isBusy) {
            console.log(`⏰ [AutoUnlock] 设备${deviceId}超时自动释放 (60秒)`);
            releaseDevice(deviceId);
            
            // 广播设备状态变更
            if (io) {
                try {
                    io.emit('device_status_change', {
                        device_id: deviceId,
                        status: 'idle',
                        from: 'auto_unlock',
                        timestamp: new Date().toISOString()
                    });
                } catch (e) {
                    console.warn('广播设备状态变更失败:', e.message);
                }
            }
        }
        deviceTimeouts.delete(deviceId);
    }, 60000); // 60秒超时
    
    deviceTimeouts.set(deviceId, timeoutId);
}

// 释放设备状态
function releaseDevice(deviceId) {
    // 清除超时定时器
    if (deviceTimeouts.has(deviceId)) {
        clearTimeout(deviceTimeouts.get(deviceId));
        deviceTimeouts.delete(deviceId);
    }
    
    if (deviceGameStatus.has(deviceId)) {
        const status = deviceGameStatus.get(deviceId);
        const duration = Math.round((Date.now() - status.startTime) / 1000);
        console.log(`🔓 设备${deviceId}释放，占用时长${duration}秒`);
        deviceGameStatus.delete(deviceId);
    }
}

// 检查设备是否忙碌
function isDeviceBusy(deviceId) {
    return deviceGameStatus.has(deviceId) && deviceGameStatus.get(deviceId).isBusy;
}

// 获取设备状态
function getDeviceStatus(deviceId) {
    return deviceGameStatus.get(deviceId) || null;
}

// 获取所有设备状态
function getAllDeviceStatus() {
    const status = {};
    for (const [deviceId, info] of deviceGameStatus.entries()) {
        status[deviceId] = {
            isBusy: info.isBusy,
            currentUser: info.currentUser.user_id,
            duration: Math.round((Date.now() - info.startTime) / 1000)
        };
    }
    return status;
}

// 存储设备和用户连接信息
const devices = new Map(); // 设备连接信息
const users = new Map();   // 用户连接信息
const deviceRooms = new Map(); // 设备房间管理
// 新增：用户与设备的快速映射
const userMap = new Map();   // 用户ID -> socket.id
const deviceMap = new Map(); // 设备ID -> socket.id
// 引入配置，用于按环境控制心跳超时的断开策略
const config = require('./config.js');

// 心跳管理 - 仅监控客户端发送的心跳
function startHeartbeatCheck(io, updateDeviceOnlineStatus) {
    setInterval(async () => {
        const now = new Date();
        const timeout = 5 * 60 * 1000; // 5分钟超时
        
        for (const [socketId, device] of devices.entries()) {
            const timeSinceLastHeartbeat = now - device.last_heartbeat;
            
            if (timeSinceLastHeartbeat > timeout) {
                console.log(`💀 设备心跳超时，断开连接: ${device.device_id} (${Math.round(timeSinceLastHeartbeat/1000)}秒)`);
                
                // 更新数据库状态为离线
                await updateDeviceOnlineStatus(device.device_id, false);
                
                // 释放设备状态
                releaseDevice(device.device_id);
                
                // 断开Socket连接（按配置决定是否强制断开，生产默认保持原有逻辑）
                const socket = io.sockets.sockets.get(socketId);
                if (socket) {
                    const enforce = (typeof config.heartbeatEnforceDisconnect === 'boolean') ? config.heartbeatEnforceDisconnect : true;
                    try {
                        if (enforce) {
                            // 保持原生产逻辑：强制关闭（客户端可能显示 1005/no status rcvd）
                            socket.disconnect(true);
                        } else {
                            // 开发/联调：安全断开，发出原因并正常关闭
                            socket.emit('server_disconnect', { reason: 'heartbeat_timeout' });
                            socket.disconnect();
                        }
                    } catch (e) {
                        console.warn('断开失败（忽略）:', e && e.message ? e.message : e);
                    }
                }
                
                // 清理设备信息
                devices.delete(socketId);
                deviceRooms.delete(device.device_id);
                deviceMap.delete(device.device_id);
            }
        }
    }, 60000); // 每分钟检查一次
    
    console.log('⏰ 心跳监控服务已启动 (客户端主动发送模式，5分钟超时)');
}

module.exports = {
    // 设备独占模式函数
    setDeviceBusy,
    releaseDevice,
    isDeviceBusy,
    getDeviceStatus,
    getAllDeviceStatus,
    
    // 连接管理
    devices,
    users,
    deviceRooms,
    userMap,
    deviceMap,
    
    // 心跳管理
    startHeartbeatCheck
};
/**
 * 设备抽奖队列管理器
 * 替代设备锁机制，实现排队而非阻塞的抽奖处理
 */

// 存储每个设备的抽奖队列
const deviceQueues = new Map();

// 存储每个设备当前正在处理的任务
const processingTasks = new Map();

// 队列任务超时时间（毫秒）
const TASK_TIMEOUT = 30000; // 30秒

/**
 * 将抽奖请求加入设备队列
 * @param {string} deviceId 设备ID
 * @param {object} socket Socket连接对象
 * @param {object} data 抽奖请求数据
 * @param {function} handler 抽奖处理函数
 */
function enqueueLottery(deviceId, socket, data, handler) {
    if (!deviceQueues.has(deviceId)) {
        deviceQueues.set(deviceId, []);
    }

    const queue = deviceQueues.get(deviceId);
    const task = {
        socket,
        data,
        handler,
        timestamp: Date.now(),
        timeout: null
    };

    queue.push(task);

    console.log(`🎯 设备 ${deviceId} 抽奖任务入队，当前排队数: ${queue.length}`);

    // 如果队列只有一个任务且没有正在处理的任务，立即执行
    if (queue.length === 1 && !processingTasks.has(deviceId)) {
        processNext(deviceId);
    } else {
        // 通知用户排队状态
        socket.emit('queue_status', {
            device_id: deviceId,
            position: queue.length,
            message: `您在设备 ${deviceId} 的抽奖队列中排第 ${queue.length} 位`
        });
    }
}

/**
 * 处理设备队列中的下一个任务
 * @param {string} deviceId 设备ID
 */
async function processNext(deviceId) {
    const queue = deviceQueues.get(deviceId);
    if (!queue || queue.length === 0) {
        processingTasks.delete(deviceId);
        return;
    }

    const task = queue[0];
    processingTasks.set(deviceId, task);

    console.log(`🎮 正在执行设备 ${deviceId} 抽奖任务，剩余排队数: ${queue.length - 1}`);

    // 设置任务超时保护
    task.timeout = setTimeout(() => {
        console.warn(`⚠️ 设备 ${deviceId} 抽奖任务超时，自动跳过`);
        task.socket.emit('error', {
            message: '抽奖处理超时，请重试',
            code: 'TASK_TIMEOUT'
        });
        completeTask(deviceId);
    }, TASK_TIMEOUT);

    try {
        // 执行抽奖处理逻辑
        await task.handler(task.socket, task.data);
    } catch (err) {
        console.error(`❌ 设备 ${deviceId} 抽奖错误:`, err);
        task.socket.emit('error', {
            message: '抽奖处理失败，请重试',
            code: 'LOTTERY_ERROR',
            error: err.message
        });
    } finally {
        completeTask(deviceId);
    }
}

/**
 * 完成当前任务并处理下一个
 * @param {string} deviceId 设备ID
 */
function completeTask(deviceId) {
    const queue = deviceQueues.get(deviceId);
    const currentTask = processingTasks.get(deviceId);

    if (currentTask && currentTask.timeout) {
        clearTimeout(currentTask.timeout);
    }

    if (queue && queue.length > 0) {
        // 移除已完成的任务
        queue.shift();
        
        // 处理下一个任务
        if (queue.length > 0) {
            setTimeout(() => processNext(deviceId), 100); // 短暂延迟避免过快处理
        } else {
            // 队列清空，删除设备队列
            deviceQueues.delete(deviceId);
            processingTasks.delete(deviceId);
            console.log(`✅ 设备 ${deviceId} 队列清空`);
        }
    } else {
        processingTasks.delete(deviceId);
    }
}

/**
 * 获取设备队列状态
 * @param {string} deviceId 设备ID
 * @returns {object} 队列状态信息
 */
function getQueueStatus(deviceId) {
    const queue = deviceQueues.get(deviceId);
    const isProcessing = processingTasks.has(deviceId);
    
    return {
        device_id: deviceId,
        queue_length: queue ? queue.length : 0,
        is_processing: isProcessing,
        status: isProcessing ? 'processing' : (queue && queue.length > 0 ? 'queued' : 'idle')
    };
}

/**
 * 获取所有设备的队列状态
 * @returns {array} 所有设备队列状态
 */
function getAllQueueStatus() {
    const allDevices = new Set([...deviceQueues.keys(), ...processingTasks.keys()]);
    return Array.from(allDevices).map(deviceId => getQueueStatus(deviceId));
}

/**
 * 清空指定设备的队列（紧急情况使用）
 * @param {string} deviceId 设备ID
 */
function clearDeviceQueue(deviceId) {
    const queue = deviceQueues.get(deviceId);
    const currentTask = processingTasks.get(deviceId);

    if (currentTask && currentTask.timeout) {
        clearTimeout(currentTask.timeout);
    }

    if (queue) {
        // 通知队列中的所有用户
        queue.forEach(task => {
            task.socket.emit('error', {
                message: '设备队列已清空，请重新开始抽奖',
                code: 'QUEUE_CLEARED'
            });
        });
    }

    deviceQueues.delete(deviceId);
    processingTasks.delete(deviceId);
    
    console.log(`🧹 设备 ${deviceId} 队列已清空`);
}

/**
 * 移除用户的排队任务（用户主动取消或断线）
 * @param {string} socketId Socket ID
 */
function removeUserFromQueues(socketId) {
    let removed = false;
    
    for (const [deviceId, queue] of deviceQueues.entries()) {
        const originalLength = queue.length;
        const newQueue = queue.filter(task => task.socket.id !== socketId);
        
        if (newQueue.length !== originalLength) {
            deviceQueues.set(deviceId, newQueue);
            removed = true;
            console.log(`🚪 用户 ${socketId} 已从设备 ${deviceId} 队列中移除`);
            
            // 如果队列变空，清理
            if (newQueue.length === 0) {
                deviceQueues.delete(deviceId);
            }
        }
    }
    
    return removed;
}

module.exports = {
    enqueueLottery,
    processNext,
    completeTask,
    getQueueStatus,
    getAllQueueStatus,
    clearDeviceQueue,
    removeUserFromQueues
};
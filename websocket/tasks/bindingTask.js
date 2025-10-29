const { pool } = require('../database');

// 简单异步任务队列：用于“用户角色绑定 / 区域绑定”直连数据库更新
const queue = [];
let running = false;

function enqueueBindingTask(deviceId, roleBindings = {}, areaBindings = {}) {
  queue.push({ deviceId, roleBindings, areaBindings, enqueuedAt: Date.now() });
  processQueue();
}

async function processQueue() {
  if (running) return;
  running = true;
  while (queue.length) {
    const task = queue.shift();
    try {
      await applyBindings(task.deviceId, task.roleBindings, task.areaBindings);
      console.log(`🔗 绑定任务完成: 设备=${task.deviceId}`);
    } catch (err) {
      console.error(`❌ 绑定任务失败: 设备=${task.deviceId}`, err && err.message ? err.message : err);
    }
  }
  running = false;
}

async function applyBindings(deviceId, roleBindings, areaBindings) {
  const connection = await pool.getConnection();
  try {
    const now = Math.floor(Date.now() / 1000);
    const fields = {};

    // 角色绑定（按已有设备表字段进行更新）
    const roleFieldWhitelist = [
      'sheng_daili_id', 'shi_daili_id', 'quxian_daili_id',
      'shebei_goumaizhe_id', 'shebei_pushezhe_id', 'shiti_shangjia_id',
      'jiangpin_gongyingshang_ids'
    ];
    for (const [key, value] of Object.entries(roleBindings || {})) {
      if (roleFieldWhitelist.includes(key)) {
        fields[key] = value;
      }
    }

    // 区域绑定（允许自定义字段名，统一写入）
    for (const [key, value] of Object.entries(areaBindings || {})) {
      fields[key] = value;
    }

    if (Object.keys(fields).length === 0) {
      console.log(`ℹ️ 绑定字段为空，跳过设备=${deviceId}`);
      connection.release();
      return;
    }

    // 构造动态更新 SQL
    const setClause = Object.keys(fields).map(k => `${k} = ?`).join(', ');
    const params = [...Object.values(fields), now, deviceId];

    await connection.execute(
      `UPDATE fa_device SET ${setClause}, updatetime = ? WHERE device_id = ?`,
      params
    );
  } catch (err) {
    throw err;
  } finally {
    connection.release();
  }
}

module.exports = { enqueueBindingTask };
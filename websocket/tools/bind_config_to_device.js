// Bind a profit_config id to a device's profit_config_ids JSON list
// Usage: node ./tools/bind_config_to_device.js DEV_XXXX 26
const { pool } = require('../database');

async function bindConfigToDevice(deviceId, configId) {
  const conn = await pool.getConnection();
  try {
    const now = Math.floor(Date.now() / 1000);
    const [devRows] = await conn.query('SELECT device_id, profit_config_ids FROM fa_device WHERE device_id = ? LIMIT 1', [deviceId]);
    if (!devRows.length) {
      throw new Error(`Device ${deviceId} not found`);
    }
    let ids = [];
    try { ids = JSON.parse(devRows[0].profit_config_ids || '[]') || []; } catch (_) { ids = []; }
    const has = ids.includes(Number(configId)) || ids.includes(String(configId));
    if (!has) {
      // put configId to head for default pick
      ids.unshift(Number(configId));
      await conn.query('UPDATE fa_device SET profit_config_ids = ?, updatetime = ? WHERE device_id = ?', [JSON.stringify(ids), now, deviceId]);
      console.log(`✅ 已为设备 ${deviceId} 绑定配置ID=${configId}. 绑定列表: ${JSON.stringify(ids)}`);
    } else {
      console.log(`ℹ️ 设备 ${deviceId} 已包含配置ID=${configId}. 绑定列表: ${JSON.stringify(ids)}`);
    }
  } finally {
    conn.release();
  }
}

const [,, deviceIdArg, configIdArg] = process.argv;
if (!deviceIdArg || !configIdArg) {
  console.error('用法: node ./tools/bind_config_to_device.js <device_id> <config_id>');
  process.exit(1);
}
bindConfigToDevice(deviceIdArg, parseInt(configIdArg, 10)).catch(err => { console.error('❌ 绑定失败:', err.message); process.exit(1); });
// Clear a device's profit_config_ids to force default device fallback
// Usage: node ./tools/unbind_device_configs.js DEV_XXXX
const { pool } = require('../database');

async function unbindDevice(deviceId) {
  const conn = await pool.getConnection();
  try {
    const now = Math.floor(Date.now() / 1000);
    const [rows] = await conn.query('SELECT device_id, profit_config_ids FROM fa_device WHERE device_id = ? LIMIT 1', [deviceId]);
    if (!rows.length) {
      throw new Error(`Device ${deviceId} not found`);
    }
    const before = rows[0].profit_config_ids || '[]';
    await conn.query('UPDATE fa_device SET profit_config_ids = ?, updatetime = ? WHERE device_id = ?', ['[]', now, deviceId]);
    console.log(`✅ 已清空设备 ${deviceId} 的绑定列表。之前: ${before}, 现在: []`);
  } finally {
    conn.release();
  }
}

const [,, deviceIdArg] = process.argv;
if (!deviceIdArg) {
  console.error('用法: node ./tools/unbind_device_configs.js <device_id>');
  process.exit(1);
}
unbindDevice(deviceIdArg).catch(err => { console.error('❌ 解除失败:', err.message); process.exit(1); });
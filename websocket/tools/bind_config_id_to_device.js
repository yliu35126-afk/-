// 为设备设置 fa_device.config_id 字段
// 用法： node ./tools/bind_config_id_to_device.js <device_id> <config_id>

const mysql = require('mysql2/promise');
const config = require('../config');

(async () => {
  const [,, deviceIdArg, configIdArg] = process.argv;
  if (!deviceIdArg || !configIdArg) {
    console.error('用法: node ./tools/bind_config_id_to_device.js <device_id> <config_id>');
    process.exit(1);
  }
  const deviceId = deviceIdArg.trim();
  const configId = parseInt(configIdArg, 10);
  if (!deviceId || Number.isNaN(configId)) {
    console.error('参数错误: 设备ID或配置ID不合法');
    process.exit(1);
  }

  const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    charset: 'utf8'
  });

  let conn;
  try {
    conn = await pool.getConnection();
    const now = Math.floor(Date.now() / 1000);
    // 检查配置是否存在
    const [cfg] = await conn.execute('SELECT id FROM fa_profit_config WHERE id = ? LIMIT 1', [configId]);
    if (!cfg.length) {
      console.error(`❌ 分润配置ID=${configId} 不存在`);
      process.exit(2);
    }
    // 检查设备是否存在
    const [dev] = await conn.execute('SELECT device_id FROM fa_device WHERE device_id = ? LIMIT 1', [deviceId]);
    if (!dev.length) {
      console.error(`❌ 设备 ${deviceId} 不存在`);
      process.exit(3);
    }
    // 更新 config_id 字段
    await conn.execute('UPDATE fa_device SET config_id = ?, updatetime = ? WHERE device_id = ?', [configId, now, deviceId]);
    console.log(`✅ 已为设备 ${deviceId} 设置 config_id=${configId}`);
  } catch (err) {
    console.error('❌ 绑定失败:', err.message || err);
    process.exit(4);
  } finally {
    if (conn) conn.release();
    await pool.end();
  }
})();
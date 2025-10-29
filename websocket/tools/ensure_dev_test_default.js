// 一次性修复脚本：确保兜底设备 DEV_TEST_DEFAULT 绑定分润配置ID=26，并校正ID=26配置金额为0.01
// 用法：在 websocket 目录执行
//   node ./tools/ensure_dev_test_default.js

const mysql = require('mysql2/promise');
const config = require('../config');

(async () => {
  const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    charset: 'utf8'
  });

  const now = Math.floor(Date.now() / 1000);

  try {
    console.log('✅ 连接数据库成功');

    // 1) 校正 profit_config id=26
    const [cfgRows] = await pool.execute('SELECT id, status, lottery_amount, config_data FROM fa_profit_config WHERE id = 26 LIMIT 1');
    if (cfgRows.length > 0) {
      const row = cfgRows[0];
      let needUpdate = false;
      let cfgData = {};
      if (row.config_data) {
        try { cfgData = JSON.parse(row.config_data) || {}; } catch (e) { cfgData = {}; }
      }
      if (String(row.lottery_amount) !== '0.01') needUpdate = true;
      if (!cfgData.roles) { cfgData.roles = []; needUpdate = true; }
      if (!cfgData.prizes) {
        cfgData.prizes = { supplier: { data: [] }, merchant: { data: [] } };
        needUpdate = true;
      }
      if (needUpdate) {
        await pool.execute(
          'UPDATE fa_profit_config SET status = 1, lottery_amount = 0.01, config_data = ?, updatetime = ? WHERE id = 26',
          [JSON.stringify(cfgData), now]
        );
        console.log('🛠️ 已校正 profit_config id=26 为 0.01 并补全结构');
      } else {
        console.log('ℹ️ profit_config id=26 已符合要求');
      }
    } else {
      const cfgData = {
        roles: [],
        prizes: { supplier: { data: [] }, merchant: { data: [] } },
      };
      await pool.execute(
        'INSERT INTO fa_profit_config (id, config_name, lottery_amount, config_data, status, createtime, updatetime) VALUES (26, ?, 0.01, ?, 1, ?, ?)',
        ['系统演示模板', JSON.stringify(cfgData), now, now]
      );
      console.log('🧱 已插入 profit_config id=26 (0.01)');
    }

    // 2) 确保兜底设备 DEV_TEST_DEFAULT 绑定 config_id=26
    const [devRows] = await pool.execute('SELECT device_id, profit_config_ids FROM fa_device WHERE device_id = ? LIMIT 1', ['DEV_TEST_DEFAULT']);
    if (devRows.length > 0) {
      const row = devRows[0];
      let ids = [];
      try { ids = JSON.parse(row.profit_config_ids || '[]') || []; } catch (e) { ids = []; }
      const has26 = ids.includes(26) || ids.includes('26');
      if (!has26) {
        ids.unshift(26);
        await pool.execute(
          'UPDATE fa_device SET profit_config_ids = ?, updatetime = ? WHERE device_id = ?',
          [JSON.stringify(ids), now, 'DEV_TEST_DEFAULT']
        );
        console.log('🛠️ 已为 DEV_TEST_DEFAULT 追加 config_id=26');
      } else {
        console.log('ℹ️ DEV_TEST_DEFAULT 已包含 config_id=26');
      }
    } else {
      const ids = JSON.stringify([26]);
      // 插入尽量少字段，避免非空约束冲突，如有缺省列请根据实际表结构补充
      await pool.execute(
        'INSERT INTO fa_device (device_id, profit_config_ids, status, createtime, updatetime) VALUES (?, ?, 1, ?, ?)',
        ['DEV_TEST_DEFAULT', ids, now, now]
      );
      console.log('🧱 已插入兜底设备 DEV_TEST_DEFAULT 并绑定 config_id=26');
    }

    console.log('✅ 修复完成：DEV_TEST_DEFAULT + config_id=26 就位');
    process.exit(0);
  } catch (e) {
    console.error('❌ 修复失败:', e && e.message ? e.message : e);
    process.exit(2);
  }
})();
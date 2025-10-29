// 自动检查并修复 fa_device.config_id 字段缺失问题
// 用法：在 websocket 目录执行
//   node ./tools/check_and_add_config_id_column.js

const mysql = require('mysql2/promise');
const config = require('../config');

(async () => {
  console.log('🔧 数据库配置:', JSON.stringify({
    host: config.database.host,
    user: config.database.user,
    database: config.database.database
  }));

  const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    port: parseInt(process.env.DB_PORT || '3307', 10),
    charset: 'utf8'
  });

  let conn;
  try {
    conn = await pool.getConnection();
    console.log('✅ 已连接数据库');

    const schema = config.database.database;
    // 先检查表是否存在
    const [tbl] = await conn.execute(
      `SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'fa_device'`,
      [schema]
    );
    if (!tbl || !tbl[0] || parseInt(tbl[0].cnt, 10) === 0) {
      console.warn('⚠️ 未找到表 fa_device，跳过字段巡检。');
      return;
    }

    const [rows] = await conn.execute(
      `SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
       WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'fa_device' AND COLUMN_NAME = 'config_id'`,
      [schema]
    );
    if (rows.length > 0) {
      console.log('✅ 检查通过：fa_device.config_id 已存在，无需变更');
    } else {
      console.log('⚠️ 未发现 fa_device.config_id 字段，准备执行补充...');
      await conn.execute(
        `ALTER TABLE fa_device 
         ADD COLUMN config_id INT(11) NULL DEFAULT NULL COMMENT '绑定的分润配置ID' AFTER status`
      );
      console.log('🎉 已成功添加字段：fa_device.config_id');
    }

    // 验证输出
    const [desc] = await conn.execute('DESC fa_device');
    const has = desc.find(r => r.Field === 'config_id');
    if (has) {
      console.log(`🔍 验证成功：config_id ${has.Type} ${has.Null} ${has.Default}`);
    } else {
      console.warn('❌ 验证失败：未能在 DESC 结果中找到 config_id');
    }
  } catch (err) {
    console.error('❌ 字段检查/修复失败：', err);
    process.exitCode = 1;
  } finally {
    if (conn) conn.release();
    await pool.end();
  }
})();
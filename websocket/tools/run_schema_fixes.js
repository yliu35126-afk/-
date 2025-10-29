// 执行数据库结构修复：fa_prize 及相关兼容字段
// 依赖：mysql2（已在 websocket.backup-20251015-050507/package.json 中声明）
// 用法：在当前目录执行
//   node ./tools/run_schema_fixes.js

const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');
const config = require('../config');

const files = [
  'f://fuwuqi09-26//extracted//backend//prod_minimal//application//common//sql//修复//database-fix.sql',
  'f://fuwuqi09-26//extracted//backend//prod_minimal//scripts//fix_structure_2239.sql',
  'f://fuwuqi09-26//extracted//backend//prod_minimal//patches//20251018_add_prize_fields.sql',
].filter(p => fs.existsSync(p));

async function run() {
  console.log('🔧 将执行以下 SQL 脚本：');
  files.forEach(f => console.log(' -', f));
  if (files.length === 0) {
    console.error('❌ 未找到任何 SQL 修复文件');
    process.exit(2);
  }

  const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    charset: 'utf8mb4',
    multipleStatements: true,
  });

  let conn;
  try {
    conn = await pool.getConnection();
    for (const f of files) {
      const sql = fs.readFileSync(f, 'utf8');
      console.log(`➡️  执行: ${path.basename(f)} (长度 ${sql.length} 字符)`);
      // 简单拆分，避免客户端禁用多语句时出错
      const chunks = sql
        .replace(/\/\*[^*]*\*+(?:[^/*][^*]*\*+)*\//g, '') // 去注释
        .split(/;\s*\n|;\r?\n|;\s*$/g)
        .map(s => s.trim())
        .filter(Boolean);
      for (const stmt of chunks) {
        try {
          await conn.query(stmt);
        } catch (e) {
          // 容忍重复列/索引等错误，继续执行
          const msg = (e && e.message) || String(e);
          const ignorable = /(Duplicate column name|already exists|Duplicate key name|Unknown column)/i.test(msg);
          if (!ignorable) {
            console.warn('⚠️  语句执行警告：', msg);
          }
        }
      }
      console.log(`✅ 完成: ${path.basename(f)}`);
    }

    // 输出 fa_prize 列清单
    const [cols] = await conn.query('SHOW COLUMNS FROM fa_prize');
    console.log('📋 当前 fa_prize 列：', cols.map(c => c.Field));

    // 返回一个可用的插入模板（避免 #1136）
    const baseCols = ['prize_name', 'status', 'createtime', 'updatetime'];
    const available = cols.map(c => c.Field);
    const insertCols = baseCols.filter(c => available.includes(c));
    const placeholders = insertCols.map(() => '?').join(',');
    console.log('\n🧩 建议使用的 INSERT 模板：');
    console.log(`INSERT INTO fa_prize (${insertCols.join(',')}) VALUES (${placeholders});`);

    console.log('\n🎉 结构修复执行完成');
    process.exit(0);
  } catch (e) {
    console.error('❌ 执行修复失败：', e && e.message ? e.message : e);
    process.exit(3);
  } finally {
    if (conn) conn.release();
  }
}

run();
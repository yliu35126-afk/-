// Seed demo prizes for profit_config id=26 and update config_data
// Reuses websocket database pool
const { pool } = require('../database');

async function getColumns(table) {
  const conn = await pool.getConnection();
  try {
    const [rows] = await conn.query(`SHOW COLUMNS FROM ${table}`);
    return rows.map(r => r.Field);
  } finally {
    conn.release();
  }
}

async function ensureDemoPrizes() {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    // Ensure config 26 exists and enabled
    const [cfgRows] = await conn.query('SELECT id, config_data FROM fa_profit_config WHERE id=?', [26]);
    if (cfgRows.length === 0) {
      throw new Error('profit_config id=26 not found');
    }

    const now = Math.floor(Date.now() / 1000);
    const prizeCols = await getColumns('fa_prize');
    const can = col => prizeCols.includes(col);
    const baseCols = [];
    if (can('prize_name')) baseCols.push('prize_name');
    if (can('prize_image')) baseCols.push('prize_image');
    if (can('description')) baseCols.push('description');
    if (can('original_price')) baseCols.push('original_price');
    if (can('activity_price')) baseCols.push('activity_price');
    if (can('stock_quantity')) baseCols.push('stock_quantity');
    if (can('used_quantity')) baseCols.push('used_quantity');
    if (can('status')) baseCols.push('status');
    if (can('profit_config_id')) baseCols.push('profit_config_id');
    if (can('supplier_user_id')) baseCols.push('supplier_user_id');
    if (can('merchant_user_id')) baseCols.push('merchant_user_id');
    if (can('createtime')) baseCols.push('createtime');
    if (can('updatetime')) baseCols.push('updatetime');

    const makeInsert = async (row) => {
      const cols = [];
      const vals = [];
      for (const c of baseCols) {
        cols.push(c);
        vals.push(row[c] !== undefined ? row[c] : null);
      }
      const placeholders = cols.map(() => '?').join(',');
      const sql = `INSERT INTO fa_prize (${cols.join(',')}) VALUES (${placeholders})`;
      const [res] = await conn.query(sql, vals);
      return res.insertId;
    };

    // Check existing prizes for config 26
    const [existRows] = await conn.query('SELECT id, prize_name FROM fa_prize WHERE profit_config_id=? AND status=1', [26]);
    const existingCount = existRows.length;

    const createdIds = [];
    if (existingCount === 0) {
      // Seed 4 demo prizes: 2 supplier, 2 merchant
      const demoPrizes = [
        { prize_name: '演示-优惠券5元', prize_image: '', description: '演示奖品', original_price: 5.00, activity_price: 0.01, stock_quantity: 999, used_quantity: 0, status: 1, profit_config_id: 26, supplier_user_id: 0, merchant_user_id: 1, createtime: now, updatetime: now },
        { prize_name: '演示-小夜灯', prize_image: '', description: '演示奖品', original_price: 19.90, activity_price: 0.01, stock_quantity: 500, used_quantity: 0, status: 1, profit_config_id: 26, supplier_user_id: 1, merchant_user_id: 0, createtime: now, updatetime: now },
        { prize_name: '演示-贴纸礼包', prize_image: '', description: '演示奖品', original_price: 9.90, activity_price: 0.01, stock_quantity: 800, used_quantity: 0, status: 1, profit_config_id: 26, supplier_user_id: 1, merchant_user_id: 0, createtime: now, updatetime: now },
        { prize_name: '演示-满减券10元', prize_image: '', description: '演示奖品', original_price: 10.00, activity_price: 0.01, stock_quantity: 999, used_quantity: 0, status: 1, profit_config_id: 26, supplier_user_id: 0, merchant_user_id: 1, createtime: now, updatetime: now },
      ];
      for (const p of demoPrizes) {
        const id = await makeInsert(p);
        createdIds.push(id);
      }
    }

    // Build prizes section for config_data using existing + created
    const [allRows] = await conn.query('SELECT id, prize_name, prize_image, original_price, activity_price FROM fa_prize WHERE profit_config_id=? AND status=1 ORDER BY id ASC', [26]);
    const supplier = [];
    const merchant = [];
    // split by presence of merchant_user_id vs supplier_user_id where possible
    const [rolesRows] = await conn.query('SELECT id, merchant_user_id, supplier_user_id FROM fa_prize WHERE profit_config_id=? AND status=1', [26]);
    const roleMap = new Map(rolesRows.map(r => [r.id, r]));
    for (const r of allRows) {
      const role = roleMap.get(r.id) || {};
      const entry = {
        id: r.id,
        name: r.prize_name || '',
        image: r.prize_image || '',
        original_price: Number(r.original_price || 0),
        activity_price: Number(r.activity_price || 0),
        probability: 10.0,
        weight: 1,
        round_quota: 0
      };
      if ((role.merchant_user_id || 0) > 0) {
        merchant.push(entry);
      } else {
        supplier.push(entry);
      }
    }

    // Merge into config_data
    let cfg = {};
    try { cfg = JSON.parse(cfgRows[0].config_data || '{}') || {}; } catch (_) { cfg = {}; }
    if (!cfg.prizes) cfg.prizes = {};
    cfg.prizes.supplier = { type: 'supplier', data: supplier };
    cfg.prizes.merchant = { type: 'merchant', data: merchant };
    cfg.isDefault = true;

    await conn.query('UPDATE fa_profit_config SET config_data=?, status=1, lottery_amount=0.01, updatetime=? WHERE id=?', [JSON.stringify(cfg), now, 26]);

    await conn.commit();
    console.log(`✅ Demo prizes ensured for config_id=26. New prizes inserted: ${createdIds.length}. Total active prizes: ${supplier.length + merchant.length}`);
  } catch (err) {
    await conn.rollback();
    console.error('❌ Failed to ensure demo prizes for id=26:', err.message);
    throw err;
  } finally {
    conn.release();
  }
}

ensureDemoPrizes().catch(() => process.exit(1));
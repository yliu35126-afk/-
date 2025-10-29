// 用法:
//   node ./tools/seed_real_device_and_bind.js <device_id> <tier_id> [price] [board_id]
// 示例:
//   node ./tools/seed_real_device_and_bind.js 10001 1 1.00 1
// 说明:
//   - 将在 device_info 中插入/更新设备记录（含 board_id）
//   - 将在 lottery_price_tier 中更新/创建该 tier 的 profit_json（含演示奖品与分润比例）
//   - 将在 device_price_bind 建立设备与价档绑定（status=1，start_time=now）

const { pool } = require('../database');

function nowSec() { return Math.floor(Date.now() / 1000); }

// 构造一个演示用的 profit_json（含奖品与分润比例）
function buildProfitJson(price) {
  const p = parseFloat(price || 1.0);
  const profit = {
    name: '联调专用配置',
    platform_percent: 5,    // 5%
    supplier_percent: 10,   // 10%
    promoter_percent: 5,    // 5%
    installer_percent: 5,   // 5%
    owner_percent: 75,      // 75%
    prizes: {
      supplier: [
        { id: 10001, name: '供应商-优惠券', price: p, image: 'https://via.placeholder.com/120x120?text=SUP' }
      ],
      merchant: [
        { id: 20001, name: '商户-小礼品', price: p, image: 'https://via.placeholder.com/120x120?text=MER' }
      ]
    }
  };
  return JSON.stringify(profit);
}

async function ensureDeviceInfo(conn, deviceId, boardId) {
  const did = parseInt(deviceId, 10);
  const bid = parseInt(boardId || 1, 10);
  const [rows] = await conn.execute(
    'SELECT device_id FROM device_info WHERE device_id = ? LIMIT 1',
    [did]
  );
  const now = nowSec();
  if (!rows || rows.length === 0) {
    await conn.execute(
      `INSERT INTO device_info (
        device_id, device_sn, site_id, owner_id, installer_id, supplier_id, promoter_id,
        agent_code, board_id, status, prob_mode, prob_json, auto_reset, device_secret,
        create_time, update_time
      ) VALUES (?, ?, 0, 0, 0, 0, 0, '', ?, 1, 'round', NULL, 0, '', ?, ?)`,
      [did, `DEMO-${did}`, bid, now, now]
    );
    console.log(`✅ 设备已创建 device_info: device_id=${did}, board_id=${bid}`);
  } else {
    await conn.execute(
      'UPDATE device_info SET board_id = ?, update_time = ? WHERE device_id = ?',
      [bid, now, did]
    );
    console.log(`ℹ️ 设备已存在，已更新 board_id=${bid}`);
  }
}

async function ensureTier(conn, tierId, price) {
  const tid = parseInt(tierId, 10);
  const [rows] = await conn.execute(
    'SELECT tier_id, price FROM lottery_price_tier WHERE tier_id = ? LIMIT 1',
    [tid]
  );
  const now = nowSec();
  const pj = buildProfitJson(price);
  if (!rows || rows.length === 0) {
    await conn.execute(
      `INSERT INTO lottery_price_tier (tier_id, site_id, title, price, status, profit_json, create_time, update_time)
       VALUES (?, 0, '联调专用价档', ?, 1, ?, ?, ?)`,
      [tid, parseFloat(price || 1.0), pj, now, now]
    );
    console.log(`✅ 价档已创建: tier_id=${tid}, price=${price || 1.0}`);
  } else {
    await conn.execute(
      'UPDATE lottery_price_tier SET price = ?, profit_json = ?, update_time = ? WHERE tier_id = ?',
      [parseFloat(price || rows[0].price || 1.0), pj, now, tid]
    );
    console.log(`ℹ️ 价档已存在，已更新 price/profit_json: tier_id=${tid}`);
  }
}

async function bindDeviceTier(conn, deviceId, tierId) {
  const did = parseInt(deviceId, 10);
  const tid = parseInt(tierId, 10);
  const now = nowSec();
  // 先插入新的绑定（最新start_time优先）
  await conn.execute(
    `INSERT INTO device_price_bind (device_id, tier_id, start_time, end_time, status, create_time)
     VALUES (?, ?, ?, 0, 1, ?)`,
    [did, tid, now, now]
  );
  console.log(`✅ 设备绑定已新增: device_id=${did} -> tier_id=${tid}`);
}

async function main() {
  const [, , deviceArg, tierArg, priceArg, boardArg] = process.argv;
  const deviceId = deviceArg ? parseInt(deviceArg, 10) : 10001;
  const tierId = tierArg ? parseInt(tierArg, 10) : 1;
  const price = priceArg ? parseFloat(priceArg) : 1.0;
  const boardId = boardArg ? parseInt(boardArg, 10) : 1;

  const conn = await pool.getConnection();
  try {
    await ensureDeviceInfo(conn, deviceId, boardId);
    await ensureTier(conn, tierId, price);
    await bindDeviceTier(conn, deviceId, tierId);
    console.log('🎉 播种完成：设备与价档绑定已建立。');
  } catch (err) {
    console.error('❌ 播种失败:', err && err.message ? err.message : err);
    process.exitCode = 1;
  } finally {
    conn.release();
  }
}

main();
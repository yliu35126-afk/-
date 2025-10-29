const { io } = require('socket.io-client');
const axios = require('axios');
const config = require('../config');

function nowMs() { return Number(process.hrtime.bigint() / 1000000n); }

const WS_URL = `http://localhost:${config.port}`;
const SOCKET_OPTIONS = { transports: ['websocket', 'polling'], path: '/socket.io/', reconnection: false };

const DEVICE_ID = process.env.DEVICE_ID || 'DEV_SELFTEST_001';
const DEVICE_NAME = process.env.DEVICE_NAME || '自测设备_001';
const TIER_ID = process.env.TIER_ID ? parseInt(process.env.TIER_ID, 10) : null; // 可选：若未设置将使用设备绑定

console.log(`[SELFTEST] target: ${WS_URL}, device_id: ${DEVICE_ID}, tier_id: ${TIER_ID || '(auto)'}`);

// 管理端（观察房间推送）
const adminSocket = io(WS_URL, SOCKET_OPTIONS);
adminSocket.on('connect', () => { console.log(`[ADMIN] connected: ${adminSocket.id}`); adminSocket.emit('admin_register', {}); });
adminSocket.on('device_list_update', (list) => { console.log(`[ADMIN] device_list_update: ${Array.isArray(list)?list.length:0}`); });

// 设备客户端
const deviceSocket = io(WS_URL, SOCKET_OPTIONS);
let lastPreview = null;

deviceSocket.on('connect', () => {
  console.log(`[DEVICE] connected: ${deviceSocket.id}`);
  deviceSocket.emit('device_register', { device_id: DEVICE_ID, device_name: DEVICE_NAME, device_type: 'tv_game' });
});

deviceSocket.on('device_register_response', (resp) => {
  console.log('[DEVICE] register_resp:', resp);
});

// 心跳：每3秒
const hb = setInterval(() => { try { deviceSocket.emit('heartbeat'); } catch(_) {} }, 3000);

deviceSocket.on('config_preview', (preview) => {
  lastPreview = preview;
  const supplierCount = preview?.prizes?.supplier?.data?.length || 0;
  const merchantCount = preview?.prizes?.merchant?.data?.length || 0;
  console.log(`[DEVICE] config_preview supplier=${supplierCount}, merchant=${merchantCount}`);
});

// 启动抽奖（HTTP触发）
async function triggerStartLottery() {
  const url = `${WS_URL}/lottery-start`;
  const payload = { device_id: DEVICE_ID };
  if (TIER_ID) payload.config_id = TIER_ID; // 使用指定tier
  console.log('[SELFTEST] POST', url, payload);
  try {
    const r = await axios.post(url, payload, { timeout: 5000 });
    console.log('[SELFTEST] start_lottery resp:', r.data);
  } catch (e) {
    console.error('[SELFTEST] start_lottery error:', e?.message || e);
  }
}

// 收到设备房间的抽奖启动
deviceSocket.on('lottery_start', async (payload) => {
  console.log('[DEVICE] lottery_start:', payload);
  // 1秒后模拟设备回传 lottery_result
  setTimeout(() => {
    const prize = (lastPreview?.prizes?.supplier?.data?.[0]) || (lastPreview?.prizes?.merchant?.data?.[0]) || null;
    const fakePrizeInfo = prize ? { id: prize.id, name: prize.name, price: prize.price, image: prize.image } : { id: 0, name: '谢谢参与', price: 0 };
    const data = {
      user_id: payload?.user_id || 0,
      result: prize ? 'win' : 'miss',
      prize_info: fakePrizeInfo,
      lottery_record_id: 0
    };
    console.log('[DEVICE] emit lottery_result:', data);
    deviceSocket.emit('lottery_result', data);
  }, 1000);
});

// 设备端收到最终结果
deviceSocket.on('lottery_result', (msg) => {
  console.log('[DEVICE] lottery_result echo:', msg);
});

// 错误
deviceSocket.on('error', (e) => { console.log('[DEVICE] error:', e); });

// 运行流程：注册后3秒触发一次抽奖
setTimeout(triggerStartLottery, 3000);

// 退出控制：40秒结束
setTimeout(() => {
  clearInterval(hb);
  try { deviceSocket.close(); } catch(_) {}
  try { adminSocket.close(); } catch(_) {}
  console.log('[SELFTEST] done.');
  process.exit(0);
}, 40000);
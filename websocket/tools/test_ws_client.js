const { io } = require('socket.io-client');

function nowMs() {
  return Number(process.hrtime.bigint() / 1000000n);
}

const WS_URL = 'http://localhost:3001';
const SOCKET_OPTIONS = {
  transports: ['websocket'],
  path: '/socket.io/',
  reconnection: false,
};

const DEVICE_ID = process.env.DEVICE_ID || 'DEV_NEW_TEST_ABC123';
const DEVICE_NAME = process.env.DEVICE_NAME || '测试设备_ABC';

console.log(`[WS TEST] target: ${WS_URL}, device_id: ${DEVICE_ID}`);

// 管理端客户端（可选，用于观察列表推送）
const adminSocket = io(WS_URL, SOCKET_OPTIONS);
adminSocket.on('connect', () => {
  console.log(`[ADMIN] connected: ${adminSocket.id}`);
  adminSocket.emit('admin_register', {});
});
adminSocket.on('admin_register_response', (resp) => {
  console.log('[ADMIN] register_resp:', resp);
});
adminSocket.on('device_list_update', (list) => {
  console.log(`[ADMIN] device_list_update count=${Array.isArray(list)?list.length:0}`);
});
adminSocket.on('disconnect', () => {
  console.log('[ADMIN] disconnected');
});

// 设备客户端
const t0 = nowMs();
const deviceSocket = io(WS_URL, SOCKET_OPTIONS);
let tConnect = 0;
let tRegisterSend = 0;

deviceSocket.on('connect', () => {
  tConnect = nowMs();
  console.log(`[DEVICE] connected: ${deviceSocket.id}, handshake_latency=${tConnect - t0}ms`);

  tRegisterSend = nowMs();
  deviceSocket.emit('device_register', {
    device_id: DEVICE_ID,
    device_name: DEVICE_NAME,
    device_type: 'tv_game'
  });
});

deviceSocket.on('device_register_response', (resp) => {
  const tResp = nowMs();
  console.log('[DEVICE] register_resp:', resp, `latency=${tResp - tRegisterSend}ms`);
});

// 配置预览到设备房间
deviceSocket.on('config_preview', (preview) => {
  const tRecv = nowMs();
  const supplierCount = preview?.prizes?.supplier?.data?.length || 0;
  const merchantCount = preview?.prizes?.merchant?.data?.length || 0;
  console.log(`[DEVICE] config_preview received in ${tRecv - tRegisterSend}ms, supplier=${supplierCount}, merchant=${merchantCount}`);
});

// 抽奖广播
deviceSocket.on('lottery_start', (payload) => {
  const tRecv = nowMs();
  const serverTs = (() => { try { return new Date(payload?.timestamp).getTime(); } catch(_) { return 0; } })();
  const deliveryLatency = serverTs ? (tRecv - serverTs) : -1;
  console.log(`[DEVICE] lottery_start received:`, payload, `recv_after=${tRecv - tRegisterSend}ms`, `delivery_latency=${deliveryLatency}ms`);
});

// 设备房间内用户加入事件（用于确认房间广播）
deviceSocket.on('user_joined', (payload) => {
  console.log('[DEVICE] user_joined:', payload);
});

// 错误事件
deviceSocket.on('error', (e) => {
  console.log('[DEVICE] error:', e);
});

// 结束控制：30秒后退出
setTimeout(() => {
  try { deviceSocket.close(); } catch(_) {}
  try { adminSocket.close(); } catch(_) {}
  console.log('[WS TEST] done.');
  process.exit(0);
}, 30000);
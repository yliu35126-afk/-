// WebSocket 集成测试脚本（在微信开发者工具控制台运行）
const WS = require('./websocket.js');

function log(tag, msg) { console.log(`[WS-TEST] ${tag}:`, msg || ''); }

// 测试流程：
// 1. 连接
// 2. 5秒后主动断开底层连接，观察指数退避重连
// 3. 监听 lottery_result，验证消息分发
// 4. 30秒后手动关闭并结束测试
function runAll() {
  log('start', '开始 WebSocket 断线重连集成测试');

  // 订阅抽奖结果
  WS.on('lottery_result', (payload) => {
    log('lottery_result', payload);
  });

  // 发起连接
  WS.connect();

  // 5 秒后模拟断线
  setTimeout(() => {
    try {
      log('simulate_disconnect', '调用底层 close()');
      const st = WS.socket;
      if (st && typeof st.close === 'function') st.close();
      else if (st && typeof st.disconnect === 'function') st.disconnect();
    } catch (e) { log('error', e); }
  }, 5000);

  // 发送心跳/业务消息
  const heartbeat = setInterval(() => {
    try { WS.send({ type: 'ping' }); } catch(_) {}
  }, 10000);

  // 30 秒后结束测试
  setTimeout(() => {
    clearInterval(heartbeat);
    log('finish', '结束测试，手动断开');
    WS.disconnect();
  }, 30000);
}

module.exports = { runAll };
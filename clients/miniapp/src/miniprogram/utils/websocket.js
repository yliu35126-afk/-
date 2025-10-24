// utils/websocket.js
const config = require('../config/api.js');
const env = require('../config/environment.js');

class WebSocketManager {
  constructor() {
    this.socket = null;
    this.isConnected = false;
    this.reconnectTimer = null;
    this.reconnectCount = 0;
    this.manualClose = false;
    this.isConnecting = false;
    this.heartbeatTimer = null;
    this.watchdogTimer = null;
    this.lastMessageAt = 0;
    this.listeners = {};
    this.maxReconnectCount = (config.websocket && config.websocket.maxReconnectAttempts) || 5;
    this.baseReconnectInterval = (config.websocket && config.websocket.reconnectInterval) || 3000;
  }
  
  // 连接WebSocket
  connect() {
    // 防止并发连接和重复连接
    if (this.isConnecting || this.isConnected) return;
    this.manualClose = false;
    this.isConnecting = true;
    const token = wx.getStorageSync('token');
    if (!token) {
      console.log('未登录，无法连接WebSocket');
      this.isConnecting = false;
      return;
    }
    const base = env.getWebSocketUrl();
    const url = `${base}&token=${encodeURIComponent(token)}`;
    this.socket = wx.connectSocket({
      url,
      success: () => { console.log('WebSocket连接发起'); },
      fail: (error) => { console.error('WebSocket连接失败:', error); this.isConnecting = false; }
    });
    if (this.socket && typeof this.socket.onOpen === 'function') {
      this.socket.onOpen(() => {
        console.log('WebSocket已打开');
        this.isConnected = true;
        this.isConnecting = false;
        this.reconnectCount = 0;
        this.lastMessageAt = Date.now();
        this.startHeartbeat();
      });
    }
    
    if (this.socket && typeof this.socket.onMessage === 'function') {
      this.socket.onMessage((message) => {
        try {
          const data = JSON.parse(message.data);
          this.lastMessageAt = Date.now();
          this.handleMessage(data);
        } catch (error) {
          console.error('解析WebSocket消息失败:', error);
        }
      });
    }
    
    if (this.socket && typeof this.socket.onClose === 'function') {
      this.socket.onClose(() => {
        console.log('WebSocket连接关闭');
        this.isConnected = false;
        this.isConnecting = false;
        this.stopHeartbeat();
        if (this.manualClose) {
          console.log('手动关闭，不进行重连');
          return;
        }
        this.reconnect();
      });
    }
    
    if (this.socket && typeof this.socket.onError === 'function') {
      this.socket.onError((error) => {
        console.error('WebSocket错误:', error);
        this.isConnected = false;
      });
    }
  }
  
  // 处理接收到的消息
  handleMessage(data) {
    // 安全检查：确保data存在
    if (!data) {
      console.error('WebSocket消息数据为空:', data);
      return;
    }
    
    const { type, payload } = data;
    // 事件分发
    if (type && this.listeners[type]) {
      try { this.listeners[type].forEach(fn => { try { fn(payload); } catch(_){} }); } catch(_) {}
    }
    switch (type) {
      case 'lottery_result':
        // 抽奖结果通知
        this.handleLotteryResult(payload);
        break;
      case 'notification':
        // 普通通知
        this.handleNotification(payload);
        break;
      case 'system_message':
        // 系统消息
        this.handleSystemMessage(payload);
        break;
      case 'pong':
        // 服务器心跳响应
        this.lastMessageAt = Date.now();
        break;
      default:
        console.log('未知消息类型:', type);
    }
  }
  
  // 处理抽奖结果 - 仅转发给当前页面处理，不强制弹窗
  handleLotteryResult(result) {
    try {
      const pages = getCurrentPages();
      const currentPage = pages[pages.length - 1];
      // 仅在抽奖页面转发，避免其它页面误弹
      const isGamePage = currentPage && typeof currentPage.handleLotteryMessage === 'function' && String(currentPage.route || '').indexOf('pages/game-launcher/game-launcher') !== -1;
      if (isGamePage) {
        currentPage.handleLotteryMessage('lottery_result', result);
      } else {
        console.log('收到抽奖结果，但当前不在抽奖页面，已忽略');
      }
    } catch (e) {
      console.warn('转发抽奖结果失败', e);
    }
  }
  
  // 处理通知消息
  handleNotification(notification) {
    wx.showToast({
      title: notification.message,
      icon: 'none'
    });
  }
  
  // 处理系统消息
  handleSystemMessage(message) {
    console.log('系统消息:', message);
  }
  
  // 发送消息
  send(data) {
    if (this.isConnected && this.socket && typeof this.socket.send === 'function') {
      this.socket.send({
        data: JSON.stringify(data)
      });
    }
  }
  
  // 心跳
  startHeartbeat() {
    // 看门狗：长时间未收到消息则重连
    if (this.watchdogTimer) { clearInterval(this.watchdogTimer); this.watchdogTimer = null; }
    this.watchdogTimer = setInterval(() => {
      if (!this.isConnected) return;
      const diff = Date.now() - (this.lastMessageAt || 0);
      if (diff > 90000) { // 90秒无消息视为断线
        console.warn('心跳超时，触发重连');
        this.socket && typeof this.socket.close === 'function' && this.socket.close();
      }
    }, 30000);
  }
  stopHeartbeat() {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }
    // 额外清理看门狗，避免遗留定时器
    if (this.watchdogTimer) {
      clearInterval(this.watchdogTimer);
      this.watchdogTimer = null;
    }
  }
  
  // 重连
  reconnect() {
    if (this.reconnectCount >= this.maxReconnectCount) {
      console.log('WebSocket重连次数超限');
      return;
    }
    
    const base = this.baseReconnectInterval;
    const cap = 30000;
    const exp = Math.min(base * Math.pow(2, this.reconnectCount), cap);
    const jitter = Math.floor(Math.random() * 500);
    const delay = exp + jitter;
    this.reconnectTimer = setTimeout(() => {
      console.log(`WebSocket重连第${this.reconnectCount + 1}次`);
      this.reconnectCount++;
      this.connect();
    }, delay);
  }
  
  // 断开连接
  disconnect() {
    this.manualClose = true;
    this.isConnected = false;
    this.isConnecting = false;
    this.stopHeartbeat();
    if (this.reconnectTimer) { clearTimeout(this.reconnectTimer); this.reconnectTimer = null; }
    const st = this.socket;
    if (st && typeof st.close === 'function') { st.close(); }
    else if (st && typeof st.disconnect === 'function') { st.disconnect(); }
    this.socket = null;
  }
  // 事件注册/注销
  on(type, handler) {
    if (!type || typeof handler !== 'function') return;
    this.listeners[type] = this.listeners[type] || [];
    this.listeners[type].push(handler);
  }
  off(type, handler) {
    const arr = this.listeners[type] || [];
    this.listeners[type] = handler ? arr.filter(fn => fn !== handler) : [];
  }
}

module.exports = new WebSocketManager();

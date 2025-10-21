// utils/websocket.js
const config = require('../config/api.js');

class WebSocketManager {
  constructor() {
    this.socket = null;
    this.isConnected = false;
    this.reconnectTimer = null;
    this.reconnectCount = 0;
    this.maxReconnectCount = 5;
  }
  
  // 连接WebSocket
  connect() {
    const token = wx.getStorageSync('token');
    if (!token) {
      console.log('未登录，无法连接WebSocket');
      return;
    }
    
    this.socket = wx.connectSocket({
      url: `${config.wsUrl}?token=${token}`,
      success: () => {
        console.log('WebSocket连接成功');
      },
      fail: (error) => {
        console.error('WebSocket连接失败:', error);
      }
    });
    
    this.socket?.onOpen?.(() => {
      console.log('WebSocket已打开');
      this.isConnected = true;
      this.reconnectCount = 0;
      
      // 发送心跳
      this.startHeartbeat();
    });
    
    this.socket?.onMessage?.((message) => {
      try {
        const data = JSON.parse(message.data);
        this.handleMessage(data);
      } catch (error) {
        console.error('解析WebSocket消息失败:', error);
      }
    });
    
    this.socket?.onClose?.(() => {
      console.log('WebSocket连接关闭');
      this.isConnected = false;
      this.stopHeartbeat();
      
      // 自动重连
      this.reconnect();
    });
    
    this.socket?.onError?.((error) => {
      console.error('WebSocket错误:', error);
      this.isConnected = false;
    });
  }
  
  // 处理接收到的消息
  handleMessage(data) {
    // 安全检查：确保data存在
    if (!data) {
      console.error('WebSocket消息数据为空:', data);
      return;
    }
    
    const { type, payload } = data;
    
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
    if (this.isConnected && this.socket) {
      this.socket?.send?.({
        data: JSON.stringify(data)
      });
    }
  }
  
  // 心跳
  startHeartbeat() {
    this.heartbeatTimer = setInterval(() => {
      if (this.isConnected) {
        this.send({ type: 'ping' });
      }
    }, 30000); // 30秒心跳
  }
  
  stopHeartbeat() {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }
  }
  
  // 重连
  reconnect() {
    if (this.reconnectCount >= this.maxReconnectCount) {
      console.log('WebSocket重连次数超限');
      return;
    }
    
    this.reconnectTimer = setTimeout(() => {
      console.log(`WebSocket重连第${this.reconnectCount + 1}次`);
      this.reconnectCount++;
      this.connect();
    }, 3000);
  }
  
  // 断开连接
  disconnect() {
    this.isConnected = false;
    this.stopHeartbeat();
    
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
    
    if (this.socket) {
      this.socket?.close?.();
      this.socket = null;
    }
  }
}

module.exports = new WebSocketManager();

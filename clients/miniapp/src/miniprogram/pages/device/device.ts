// pages/device/device.ts
Page({
  data: {
    // 连接状态
    isConnected: false,
    deviceCode: '',
    deviceStatus: '',
    
    // 手动输入
    inputDeviceCode: '',
    
    // 设备数据
    deviceData: [] as Array<{time: string, content: string}>,
    
    // 连接历史
    connectionHistory: [] as Array<{deviceCode: string, time: string}>
  },

  onLoad(options: any) {
    console.log('设备页面加载', options);
    
    // 检查是否有扫码参数
    this.checkScanParams(options);
    
    // 检查当前连接状态
    this.checkConnectionStatus();
    
    // 加载连接历史
    this.loadConnectionHistory();
  },

  onShow() {
    // 每次显示页面时检查连接状态
    this.checkConnectionStatus();
  },

  // 检查扫码参数
  checkScanParams(options: any) {
    const app = getApp<IAppOption>();
    const scanParams = app.globalData.scanParams || {};
    
    // 合并参数
    const allParams = { ...scanParams, ...options };
    
    // 如果有设备码参数，显示连接状态
    if (allParams.deviceCode || allParams.d) {
      const deviceCode = allParams.deviceCode || allParams.d;
      this.setData({
        deviceCode: deviceCode
      });
      
      wx.showToast({
        title: '检测到设备码',
        icon: 'success'
      });
    }
  },

  // 检查连接状态
  checkConnectionStatus() {
    const app = getApp<IAppOption>();
    const isConnected = !!app.globalData.socketTask;
    const deviceCode = app.globalData.currentDeviceCode || '';
    const deviceStatus = app.globalData.deviceStatus || '';

    this.setData({
      isConnected: isConnected,
      deviceCode: deviceCode,
      deviceStatus: deviceStatus
    });
  },

  // 扫描设备码
  scanDeviceCode() {
    wx.scanCode({
      success: (res) => {
        console.log('扫码结果:', res);
        
        // 解析扫码结果
        let deviceCode = '';
        
        // 如果是包含参数的结果
        if (res.result.includes('d=')) {
          const params = this.parseQRParams(res.result);
          deviceCode = params.d || '';
        } else {
          // 直接是设备码
          deviceCode = res.result;
        }
        
        if (deviceCode) {
          this.connectToDevice(deviceCode);
        } else {
          wx.showToast({
            title: '无效的设备码',
            icon: 'none'
          });
        }
      },
      fail: (error) => {
        console.error('扫码失败:', error);
        wx.showToast({
          title: '扫码失败',
          icon: 'none'
        });
      }
    });
  },

  // 解析二维码参数
  parseQRParams(qrData: string): any {
    const params: any = {};
    
    // 如果包含 ?，说明是URL格式
    if (qrData.includes('?')) {
      const urlParts = qrData.split('?');
      if (urlParts.length > 1) {
        const queryString = urlParts[1];
        queryString.split('&').forEach(pair => {
          const [key, value] = pair.split('=');
          if (key && value) {
            params[key] = decodeURIComponent(value);
          }
        });
      }
    } else {
      // 直接的参数格式，如 d=DEVICE_001
      qrData.split('&').forEach(pair => {
        const [key, value] = pair.split('=');
        if (key && value) {
          params[key] = value;
        }
      });
    }
    
    return params;
  },

  // 设备码输入
  onDeviceCodeInput(e: any) {
    this.setData({
      inputDeviceCode: e.detail.value
    });
  },

  // 手动连接
  manualConnect() {
    const { inputDeviceCode } = this.data;
    if (!inputDeviceCode.trim()) {
      wx.showToast({
        title: '请输入设备码',
        icon: 'none'
      });
      return;
    }

    this.connectToDevice(inputDeviceCode.trim());
  },

  // 连接到设备
  connectToDevice(deviceCode: string) {
    console.log('连接设备:', deviceCode);
    
    const app = getApp<IAppOption>();
    if (app.connectWebSocket) {
      app.connectWebSocket(deviceCode);
      
      // 添加到连接历史
      this.addToHistory(deviceCode);
      
      // 更新状态
      this.setData({
        deviceCode: deviceCode,
        inputDeviceCode: ''
      });
      
      // 延迟检查连接状态
      setTimeout(() => {
        this.checkConnectionStatus();
      }, 1000);
    } else {
      wx.showToast({
        title: '连接功能不可用',
        icon: 'none'
      });
    }
  },

  // 发送命令
  sendCommand(e: any) {
    const command = e.currentTarget.dataset.cmd;
    console.log('发送命令:', command);
    
    const app = getApp<IAppOption>();
    if (app.globalData.socketTask) {
      const message = {
        type: 'command',
        command: command,
        timestamp: Date.now()
      };
      
      app.globalData.socketTask?.send?.({
        data: JSON.stringify(message),
        success: () => {
          wx.showToast({
            title: `${command}命令已发送`,
            icon: 'success'
          });
        },
        fail: (error) => {
          console.error('发送命令失败:', error);
          wx.showToast({
            title: '命令发送失败',
            icon: 'none'
          });
        }
      });
    } else {
      wx.showToast({
        title: '设备未连接',
        icon: 'none'
      });
    }
  },

  // 断开连接
  disconnect() {
    wx.showModal({
      title: '确认断开',
      content: '确定要断开设备连接吗？',
      success: (res) => {
        if (res.confirm) {
          const app = getApp<IAppOption>();
          if (app.globalData.socketTask) {
            const st = app.globalData.socketTask as any;
            if (st && typeof st.close === 'function') {
              st.close({
                code: 1000,
                reason: '用户主动断开'
              });
            } else if (st && typeof st.disconnect === 'function') {
              st.disconnect();
            }
            
            // 清理全局状态
            app.globalData.socketTask = undefined;
            app.globalData.currentDeviceCode = undefined;
            app.globalData.deviceStatus = undefined;
            
            // 更新页面状态
            this.setData({
              isConnected: false,
              deviceCode: '',
              deviceStatus: '',
              deviceData: []
            });
            
            wx.showToast({
              title: '已断开连接',
              icon: 'success'
            });
          }
        }
      }
    });
  },

  // 重连设备
  reconnectDevice(e: any) {
    const deviceCode = e.currentTarget.dataset.device;
    this.connectToDevice(deviceCode);
  },

  // 添加到连接历史
  addToHistory(deviceCode: string) {
    const history = wx.getStorageSync('deviceHistory') || [];
    const now = new Date();
    const timeString = `${now.getMonth() + 1}/${now.getDate()} ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
    
    // 检查是否已存在
    const existingIndex = history.findIndex((item: any) => item.deviceCode === deviceCode);
    if (existingIndex >= 0) {
      // 更新时间
      history[existingIndex].time = timeString;
    } else {
      // 添加新记录
      history.unshift({
        deviceCode: deviceCode,
        time: timeString
      });
    }
    
    // 只保留最近10条记录
    if (history.length > 10) {
      history.splice(10);
    }
    
    wx.setStorageSync('deviceHistory', history);
    this.setData({
      connectionHistory: history
    });
  },

  // 加载连接历史
  loadConnectionHistory() {
    const history = wx.getStorageSync('deviceHistory') || [];
    this.setData({
      connectionHistory: history
    });
  },

  // 处理设备数据（由app.js调用）
  onDeviceData(data: any) {
    console.log('页面收到设备数据:', data);
    
    const now = new Date();
    const timeString = `${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;
    
    const newData = {
      time: timeString,
      content: JSON.stringify(data)
    };
    
    // 添加到数据列表
    const deviceData = [...this.data.deviceData];
    deviceData.unshift(newData);
    
    // 只保留最近20条数据
    if (deviceData.length > 20) {
      deviceData.splice(20);
    }
    
    this.setData({
      deviceData: deviceData
    });
  },

  onUnload() {
    console.log('设备页面卸载');
  }
});

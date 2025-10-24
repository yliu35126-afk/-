// app.ts
// 生产环境 - 禁用模拟器
// const deviceSimulator = require('./utils/mock-device.js');

console.log("当前模式：生产模式（非模拟）✅");

App<IAppOption>({
  globalData: {
    userInfo: undefined, // 用户信息
    isLoggedIn: false, // 登录状态
    token: '', // 登录 Token
    scanParams: {}, // 扫码进入的参数
    socketTask: undefined, // WebSocket任务
    currentDeviceCode: undefined, // 当前设备码
    deviceStatus: undefined, // 设备状态
    configId: null, // 当前使用的分润/奖品配置ID（兜底为26）
    // isSimulationMode: false, // 模拟模式标志 - 生产环境禁用模拟模式
    heartbeatTimer: undefined, // 心跳定时器
    isConnecting: false, // 连接中的防抖标志
    lastConnectAt: 0, // 上次尝试连接时间戳
    // 全局登录就绪信号
    loginReady: Promise.resolve(),
    loginReadyResolve: null,
  },

  onLaunch(options) {
    console.log('App onLaunch - options:', options);
    
    // 🧠 加固建议：防止任何时候回到默认设备，每次打开小程序都要求重新扫码绑定设备
    wx.removeStorageSync('currentDeviceCode');
    console.log('🔧 已清除缓存的设备码，确保重新扫码绑定');
    
    // 处理扫码入口
    if (this.handleScanEntry) {
      this.handleScanEntry(options);
      // 扫码进入后立刻确保已登录，修复"扫码未登录"问题
      this.ensureLogin && this.ensureLogin();
    }
    
    // 展示本地存储能力
    const logs = wx.getStorageSync('logs') || []
    logs.unshift(Date.now())
    wx.setStorageSync('logs', logs)

    // 全局登录就绪信号：无论成功/失败，完成即 resolve
    this.globalData.loginReady = new Promise((resolve) => {
      this.globalData.loginReadyResolve = resolve;
    });
    if (this.autoLogin) {
      this.autoLogin().finally(() => {
        // 显式守卫调用，避免 TS2722（不能调用可能是未定义的对象）
        if (typeof this.globalData.loginReadyResolve === 'function') {
          try { this.globalData.loginReadyResolve(); } catch(_) {}
        }
      });
    }

    // 启动时切至本地环境（FORCE_ONLINE 关闭时生效），方便联调
    try {
      const env = require('./config/environment.js');
      env.setEnv && env.setEnv('localhost');
      const _cur = env.getCurrentConfig && env.getCurrentConfig();
      console.log('当前环境配置（已切至本地）：', _cur);
    } catch (e) { /* 忽略环境日志异常 */ }
  },

  onShow(options) {
    console.log('App onShow - options:', options);
    // 如果是从后台切回前台，也可能携带新的扫码参数
    if (this.handleScanEntry) {
      this.handleScanEntry(options);
      // 扫码进入后立刻确保已登录，修复“扫码未登录”问题
      this.ensureLogin && this.ensureLogin();
    }
  },

  // 静默自动登录：仅发送code，无用户授权弹窗
  async autoLogin() {
    const request = require('./utils/request.js');
    const apiConfig = require('./config/api.js');
    try {
      // 若已有 token，直接标记就绪
      const tokenInStore = wx.getStorageSync('token');
      if (tokenInStore) {
        this.globalData.token = tokenInStore;
        this.globalData.isLoggedIn = true;
        return;
      }

      const code: string = await new Promise((resolve, reject) => {
        wx.login({
          success: (res) => res.code ? resolve(res.code) : reject(new Error('NO_CODE')),
          fail: (e) => reject(e)
        })
      });

      const resp = await request.request({
        url: apiConfig.api.wechatLogin,
        method: 'POST',
        data: { code },
        showLoading: false,
        needAuth: false
      });
      // 兼容响应结构 {code:1, data:{...}} 或直接 {...}
      const payload = resp?.data || resp || {};
      const token = payload.token || '';
      const openid = payload.openid || '';
      const userInfo = payload.userInfo || {
        nickName: payload.nickname || '',
        avatarUrl: payload.avatar || '',
        userId: payload.user_id || '',
        openid: openid || ''
      };

      if (token) wx.setStorageSync('token', token);
      if (openid) wx.setStorageSync('openid', openid);
      if (userInfo) wx.setStorageSync('userInfo', userInfo);
      this.globalData.userInfo = userInfo;
      this.globalData.token = token;
      this.globalData.isLoggedIn = !!token;
      // 若仅拿到token，主动补拉一次用户信息，确保 userId 等字段齐全
      if (token) {
        try {
          const info = await request.request({
            url: apiConfig.api.getUserInfo,
            method: 'GET',
            needAuth: true,
            showLoading: false
          });
          const d = (info && info.data) || {};
          const mergedUser = {
            nickName: d.nickname || d.nickName || (userInfo && (userInfo as any).nickName) || '',
            avatarUrl: d.avatar || d.avatarUrl || (userInfo && (userInfo as any).avatarUrl) || '',
            userId: d.user_id || d.id || (userInfo && (userInfo as any).userId) || '',
            openid: d.openid || (userInfo && (userInfo as any).openid) || ''
          };
          wx.setStorageSync('userInfo', mergedUser);
          this.globalData.userInfo = mergedUser as any;
        } catch (e) {
          console.warn('补拉用户信息失败（忽略）：', e);
        }
      }
      console.log('自动登录完成');
    } catch (e) {
      console.warn('autoLogin failed', e);
    }
  },

  // 强制确保已登录（按钮拦截调用）
  async ensureLogin() {
    if (this.globalData.token || wx.getStorageSync('token')) return true;
    // 显式守卫调用，避免 TS2722
    if (this.autoLogin) {
      await this.autoLogin();
    }
    return !!(this.globalData.token || wx.getStorageSync('token'));
  },

  // 处理扫码进入
  handleScanEntry(options: any) {
    console.log('处理扫码进入 - options:', options);
    
    // 检查是否是扫码进入 (包括小程序码)
    if (options.scene === 1011 || options.scene === 1012 || options.scene === 1013 || options.scene === 1047 || options.scene === 1048 || options.scene === 1049) {
      console.log('用户通过扫码进入小程序，场景值:', options.scene);
      
      // 特殊处理扫描小程序码的情况
      if (options.scene === 1047) {
        console.log('扫描小程序码进入');
        const scene = decodeURIComponent((options.query && options.query.scene) ? options.query.scene : '');
        console.log('扫码参数:', scene); // 例如: d=DEVICE_001

        // 兼容：若 scene 直接是设备码（如 DEV_XXXX），不含键值对
        if (scene && /^DEV_[A-F0-9]+$/i.test(scene)) {
          console.log('识别到纯设备码格式scene，直接使用:', scene);
          this.globalData.scanParams = { ...this.globalData.scanParams, device_id: scene, device: scene, deviceCode: scene };
          this.globalData.currentDeviceCode = scene;
          try {
            wx.setStorageSync('currentDeviceCode', scene);
            wx.setStorageSync('deviceCode', scene);
          } catch (e) { /* 忽略存储异常 */ }
          if (this.connectWebSocket) {
            this.connectWebSocket(scene);
          }
          console.log('最终扫码参数(纯设备码):', this.globalData.scanParams);
          return;
        }

        // 解析设备码
        const params = this.parseScene ? this.parseScene(scene) : {};
        if (params.d) {
          console.log('检测到设备码:', params.d);
          // 存储设备码到全局数据
          this.globalData.scanParams = { ...this.globalData.scanParams, device_id: params.d, device: params.d, deviceCode: params.d };
          this.globalData.currentDeviceCode = params.d;
          try {
            wx.setStorageSync('currentDeviceCode', params.d);
            wx.setStorageSync('deviceCode', params.d);
          } catch (e) { /* 忽略存储异常 */ }
          // 自动连接WebSocket
          if (this.connectWebSocket) {
            this.connectWebSocket(params.d);
          }
        } else {
          // 普通小程序码参数处理
          const sceneParams = this.parseSceneParams ? this.parseSceneParams(scene) : {};
          console.log('普通小程序码参数:', sceneParams);
          this.globalData.scanParams = { ...this.globalData.scanParams, ...sceneParams };
        }
      } else {
        // 处理其他扫码场景
        
        // 处理 scene 参数（方式一）
        if (options.query && options.query.scene && this.parseSceneParams) {
          const sceneParams = this.parseSceneParams(options.query.scene);
          console.log('Scene 参数:', sceneParams);
          this.globalData.scanParams = { ...this.globalData.scanParams, ...sceneParams };
        }
        
        // 处理直接的 query 参数（方式二）
        if (options.query) {
          console.log('Query 参数:', options.query);
          this.globalData.scanParams = { ...this.globalData.scanParams, ...options.query };
        }
        
        // 处理路径参数
        if (options.path && this.parsePathParams) {
          const pathParams = this.parsePathParams(options.path);
          console.log('Path 参数:', pathParams);
          this.globalData.scanParams = { ...this.globalData.scanParams, ...pathParams };
        }
      }
      
      console.log('最终扫码参数:', this.globalData.scanParams);
    }
  },

  // 解析场景参数 (专门用于设备码等简单参数)
  parseScene(scene: string): any {
    const params: any = {};
    if (scene) {
      scene.split('&').forEach(item => {
        const parts = item.split('=');
        const key = parts[0];
        const value = parts[1];
        if (key && value) {
          params[key] = value;
        }
      });
    }
    return params;
  },

  // WebSocket连接设备
  connectWebSocket(deviceCode: string) {
    console.log('开始连接设备WebSocket:', deviceCode);
    
    // 生产环境 - 直接使用真实WebSocket连接
    if (this.connectRealWebSocket) {
      this.connectRealWebSocket(deviceCode);
    }
  },

  // 连接真实WebSocket（生产环境使用）
  connectRealWebSocket(deviceCode: string) {
    console.log('🌐 连接真实WebSocket设备:', deviceCode);

    // 防抖：避免 onLaunch/onShow 等多次触发导致并发连接
    const nowTs = Date.now();
    if (this.globalData.isConnecting && (nowTs - (this.globalData.lastConnectAt || 0) < 4000)) {
      console.log('⏳ 正在连接中，忽略重复请求');
      return;
    }
    this.globalData.isConnecting = true;
    this.globalData.lastConnectAt = nowTs;

    // 关闭之前的连接
    if (this.globalData.socketTask) {
      try {
        // 兼容 weapp.socket.io 或原生 SocketTask
        if ((this.globalData.socketTask as any)?.disconnect) {
          const st: any = this.globalData.socketTask as any;
          if (typeof st.disconnect === 'function') {
            try { st.disconnect(); } catch (e) { console.log('断开失败（忽略）:', e); }
          }
        } else if ((this.globalData.socketTask as any)?.close) {
          const st: any = this.globalData.socketTask as any;
          if (typeof st.close === 'function') {
            try { st.close({ code: 1000, reason: '主动关闭' }); } catch (e) { console.log('关闭失败（忽略）:', e); }
          }
        }
      } catch (e) {
        console.log('关闭旧连接异常（忽略）:', e);
      }
      // 清理全局状态
      this.globalData.socketTask = undefined;
      this.globalData.currentDeviceCode = undefined;
    }

    const envConfig = require('./config/environment.js');
    const apiConfig = require('./config/api.js');

    // 统一使用完整 Socket.IO 地址，不强制附加 Engine.IO 参数
    const baseUrl = apiConfig.wsUrl || envConfig.websocketUrl || 'ws://localhost:3001/socket.io/';
    // 补齐 Engine.IO 必需的握手参数，避免服务端拒绝连接
    const wsUrl = `${baseUrl}?EIO=4&transport=websocket&deviceCode=${encodeURIComponent(deviceCode)}`;
    
    console.log('🔗 尝试连接WebSocket:', wsUrl);

    // 添加连接超时处理
    let connectTimeout: any;
    let isConnected = false;

    const socketTask = wx.connectSocket({
      url: wsUrl,
      success: () => {
        console.log('WebSocket连接请求发送成功');
        // 设置连接超时（10秒）
        connectTimeout = setTimeout(() => {
          if (!isConnected) {
            console.error('WebSocket连接超时');
            // 错误分支不主动关闭连接，改为提示并尝试轻量重连
            wx.showToast({ title: '设备连接超时', icon: 'none' });
            // 复位连接标志
            this.globalData.isConnecting = false;
            // 延迟重连，避免并发与抖动
            setTimeout(() => {
              if (!this.globalData.socketTask) {
                try {
                  this.connectRealWebSocket(deviceCode);
                } catch (e) {
                  console.log('重连失败（忽略）:', e);
                }
              }
            }, 2000);
          }
        }, 10000);
      },
      fail: (error) => {
        console.error('WebSocket连接失败:', error);
        wx.showToast({ title: '设备连接失败', icon: 'none' });
        this.globalData.isConnecting = false;
        return;
      }
    }) as unknown as WechatMiniprogram.SocketTask;

    // 加强类型保护：若返回空对象，立即终止并提示
    if (!socketTask) {
      console.error('connectSocket 返回空对象');
      this.globalData.isConnecting = false;
      wx.showToast({ title: '设备连接失败', icon: 'none' });
      return;
    }

    // 绑定事件：使用显式守卫替代可选调用，避免 TS2722
    if (socketTask.onOpen) {
      socketTask.onOpen(() => {
        console.log('✅ WebSocket连接已打开');
        isConnected = true;
        clearTimeout(connectTimeout);
      });
    }

    if (socketTask.onMessage) {
      socketTask.onMessage((res) => {
        console.log('📨 收到WebSocket消息:', res.data);
        try {
          const data = res.data as string;
          // 处理Engine.IO / Socket.IO协议
          if (data.startsWith('0')) {
            // Engine.IO连接消息
            const engineData = JSON.parse(data.substring(1));
            console.log('🤝 Engine.IO握手:', engineData.sid);
            // 发送Socket.IO连接消息
            socketTask.send({ data: '40' });
          } else if (data === '2') {
            // ping -> pong（仅被动响应，不主动发送 ping）
            socketTask.send({ data: '3' });
          } else if (data === '3') {
            // pong
          } else if (data.startsWith('40')) {
            // Socket.IO连接确认
            console.log('🔗 Socket.IO连接已建立');
            if (this.startHeartbeat) this.startHeartbeat(socketTask);

            // 加入设备房间（用户加入），确保接收设备广播
            try {
              const joinPayload = ['user_join', {
                device_id: deviceCode,
                user_info: {
                  nickName: (this.globalData.userInfo && (this.globalData.userInfo as any).nickName) || '',
                  openid: (this.globalData.userInfo && (this.globalData.userInfo as any).openid) || ''
                }
              }];
              const joinMessage = `42${JSON.stringify(joinPayload)}`;
              socketTask.send({ data: joinMessage });
              console.log('➡️ 已发送 user_join 加入设备房间');
            } catch (e) {
              console.log('发送 user_join 失败（忽略）:', e);
            }

            // 发送设备认证消息
            const authMessage = `42${JSON.stringify(['device_auth', {
              device_id: deviceCode,
              type: 'client',
              timestamp: Date.now()
            }])}`;
            try {
              socketTask.send({ data: authMessage });
              console.log('✅ 已发送 device_auth 认证消息');
            } catch (e) {
              console.log('发送认证消息失败（忽略）:', e);
            }
          } else if (data.startsWith('42')) {
            const eventData = JSON.parse(data.substring(2));
            const event = eventData[0];
            const eventPayload = eventData[1];
            console.log('🎯 收到Socket.IO事件:', event, eventPayload);
            if (this.handleDeviceMessage) {
              this.handleDeviceMessage({ event, data: eventPayload });
            }
          } else {
            console.log('ℹ️ 其他消息:', data);
          }
        } catch (error) {
          console.error('❌ 解析WebSocket消息失败:', error, '原始数据:', res.data);
        }
      });
    }

    if ((socketTask as any).onError) {
      (socketTask as any).onError((error: any) => {
        console.error('WebSocket错误:', error);
        // 允许轻量重连：错误发生后复位状态
        this.globalData.isConnecting = false;
      });
    }

    if ((socketTask as any).onClose) {
      (socketTask as any).onClose((res: any) => {
        console.log('WebSocket连接关闭:', res);
        this.globalData.isConnecting = false;
        this.globalData.socketTask = undefined;
      });
    }
  },

  handleDeviceMessage(data: any) {
    console.log('处理设备消息:', data);
    
    // 处理新的事件格式
    if (data.event) {
      console.log('📨 处理WebSocket事件:', data.event, data.data);
      
      // 将事件转发给当前页面处理
      const pages = getCurrentPages();
      const currentPage = pages[pages.length - 1];
      
      if (currentPage && currentPage.handleLotteryMessage) {
        currentPage.handleLotteryMessage(data.event, data.data);
      }
      return;
    }
    
    // 处理旧的消息格式
    switch (data.type) {
      case 'status':
        // 设备状态更新
        this.globalData.deviceStatus = data.status;
        console.log('设备状态更新:', data.status);
        
        // 显示状态提示
        if (data.message) {
          wx.showToast({
            title: data.message,
            icon: 'none',
            duration: 2000
          });
        }
        break;
        
      case 'data':
        // 设备数据
        console.log('收到设备数据:', data.payload);
        // 触发全局事件，页面可以监听
        if (this.triggerGlobalEvent) {
          this.triggerGlobalEvent('deviceData', data.payload);
        }
        break;
        
      case 'error':
        // 设备错误
        console.error('设备错误:', data.message);
        wx.showToast({
          title: data.message || '设备错误',
          icon: 'none'
        });
        break;
        
      default:
        console.log('未知设备消息类型:', data.type);
    }
  },

  reconnectWebSocket(deviceCode: string, retryCount = 0) {
    const maxRetries = 3;
    if (retryCount >= maxRetries) {
      console.log('WebSocket重连次数已达上限，连接失败');
      wx.showToast({
        title: '设备连接失败，请重新扫码',
        icon: 'error'
      });
      return;
    }

    console.log(`🔄 尝试重连WebSocket，第${retryCount + 1}次，设备码: ${deviceCode}`);
    
    // 使用递增延迟策略：2秒、4秒、6秒
    const delay = (retryCount + 1) * 2000;
    
    setTimeout(() => {
      // 直接调用真实连接方法，避免模拟器判断
      if (this.connectRealWebSocket) {
        this.connectRealWebSocket(deviceCode);
      }
    }, delay);
  },

  triggerGlobalEvent(eventName: string, data: any) {
    // 可以通过全局事件总线或者直接调用页面方法
    const pages = getCurrentPages();
    if (pages.length > 0) {
      const currentPage = pages[pages.length - 1];
      const methodName = `on${eventName.charAt(0).toUpperCase() + eventName.slice(1)}`;
      if (currentPage && typeof (currentPage as any)[methodName] === 'function') {
        (currentPage as any)[methodName](data);
      }
    }
  },

  parseSceneParams(scene: string): any {
    const params: any = {};
    if (scene) {
      const pairs = scene.split('&');
      pairs.forEach(pair => {
        const parts = pair.split('=');
        const key = parts[0];
        const value = parts[1];
        if (key && value) {
          params[key] = decodeURIComponent(value);
        }
      });
    }
    return params;
  },

  parsePathParams(path: string): any {
    const params: any = {};
    const queryIndex = path.indexOf('?');
    if (queryIndex !== -1) {
      const query = path.substring(queryIndex + 1);
      const pairs = query.split('&');
      pairs.forEach(pair => {
        const [key, value] = pair.split('=');
        if (key && value) {
          params[key] = decodeURIComponent(value);
        }
      });
    }
    return params;
  },

  startHeartbeat(socketTask: WechatMiniprogram.SocketTask) {
    // 标记读取以避免 TS6133 未使用参数告警
    void socketTask;
    // 清除之前的心跳定时器
    if (this.stopHeartbeat) {
      this.stopHeartbeat();
    }
    
    console.log('💓 心跳改为被动模式：收到 ping(2) 时回复 pong(3)，不再主动发送 ping');
    // 不设置主动心跳定时器
    this.globalData.heartbeatTimer = undefined as any;
  },

  stopHeartbeat() {
    if (this.globalData.heartbeatTimer) {
      console.log('💓 停止心跳定时器');
      clearInterval(this.globalData.heartbeatTimer);
      this.globalData.heartbeatTimer = undefined;
    }
  }
});

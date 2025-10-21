// pages/game-launcher/game-launcher.ts
const config = require('../../config/api.js');
import { resolveImageUrl } from '../../utils/image';
import { DEFAULT_LOTTERY_AMOUNT } from '../../utils/defaults';
const envConfig = require('../../config/environment.js');
const request = require('../../utils/request.js');

// 统一日志项结构类型，避免 string[] 与对象混用
type LogEntry = { time: string; message: string };

Page({
  data: {
    // 支付相关信息
    orderId: '',           // 订单ID
    orderNumber: '',       // 订单编号
    paymentAmount: String(DEFAULT_LOTTERY_AMOUNT), // 支付金额
    paymentTime: '',       // 支付时间
    
    // 用户信息
    userId: '',
    openid: '',
    userSocketId: '', // Socket.IO会话ID（sid），用于点对点消息
    consistentUserId: '', // 统一用户标识（与 userSocketId/userId 兜底匹配）
    
    // 分享弹窗控制
    showShareModal: false,
    showFullScreenGuide: false, // 全屏分享引导
    
    // 中奖结果相关
    showWinModal: false,    // 中奖弹窗
    winResult: null as any, // 中奖结果数据
    isLotteryFinished: false, // 是否已完成抽奖
    
    // 抽奖loading状态
    showLotteryLoading: false, // 抽奖loading动画
    
    // 分润配置相关
    profitConfigId: '',     // 分润配置ID
    profitInfo: [] as any[], // 分润信息
    orderAmount: 0,         // 订单金额
    
    // 分享内容相关
    shareContent: null as any, // 分享内容数据
    mediaLoading: false, // 媒体加载状态
    videoMuted: false, // 视频静音状态
    
    // 设备连接相关
    deviceCode: '',        // 设备码
    connectionStatus: 'disconnected', // 连接状态: disconnected, connecting, connected
    lastJoinAttemptAt: 0,  // 最近一次手动重试加入时间戳
    socketTask: null as any,      // WebSocket任务
    
    // 游戏状态相关
    gameStatus: 'stopped', // 游戏状态: stopped, starting, running, paused
    gameStatusText: '待抽奖',
    gameType: 'lottery',    // 游戏类型
    
    // 游戏计时相关
    gameTimer: null as any,  // 游戏计时器
    startTime: 0,           // 游戏开始时间
    
    // 日志相关
    logMessages: [] as LogEntry[],  // 日志消息（对象数组）
    messageLogs: [] as LogEntry[],  // 兼容WXML绑定的日志字段（对象数组）
    scrollTop: 0,                 // 日志滚动位置

    // 抽奖重试与守护
    lastLotterySentAt: 0,        // 最近一次发送 start_lottery 的时间戳
    reconnectAttempts: 0,        // 重连尝试次数（2/4/6s 退避）
    reconnectTimer: null as any  // 重连定时器句柄
    ,
    // 抽奖触发标志
    waitingToStart: false,
    hasSentStart: false,
    receivedStartEvent: false,
    // 结果接收节流与校验
    lastStartEventTs: 0,
    minAcceptDelayMs: 1500,
    pendingLotteryResult: null as any
  },

  onLoad(options: any) {
    console.log('支付成功页面加载', options);
    
    // 解析订单ID和支付金额
    if (options.orderId) {
      this.setData({
        orderId: options.orderId,
        orderNumber: options.orderId // 使用真实订单ID作为订单编号
      });
    }
    
    if (options.amount) {
      this.setData({
        paymentAmount: options.amount
      });
    }
    
    // 解析支付时间戳
    if (options.paymentTime) {
      const timestampSec = parseInt(options.paymentTime);
      // 检查时间戳是否有效（大于0且不是1970年）
      if (timestampSec > 0 && timestampSec > 946684800) { // 946684800 = 2000-01-01的时间戳
        const timestamp = timestampSec * 1000; // 转换为毫秒
        const paymentDate = new Date(timestamp);
        const formattedTime = this.formatDateTime(paymentDate);
        
        this.setData({
          paymentTime: formattedTime
        });
        
        console.log('使用API返回的支付时间:', formattedTime, '(时间戳:', options.paymentTime, ')');
      } else {
        console.log('无效的支付时间戳:', options.paymentTime, '使用当前时间');
        // 使用当前时间作为默认值
        this.setData({
          paymentTime: this.formatDateTime(new Date())
        });
      }
    }
    
    // 解析分润配置ID
    if (options.configId) {
      this.setData({
        profitConfigId: options.configId
      });
      console.log('分润配置ID已设置:', options.configId);
    }
    
    // 获取设备码 - 从全局app或本地存储（防止默认设备覆盖）
    const app = getApp<IAppOption>();
    let deviceCode = wx.getStorageSync('currentDeviceCode') || wx.getStorageSync('deviceCode');
    if (!deviceCode && app.globalData.currentDeviceCode) {
      deviceCode = app.globalData.currentDeviceCode;
      wx.setStorageSync('currentDeviceCode', deviceCode);
    }
    if (!deviceCode) {
      console.warn('⚠️ 未检测到扫码设备，拒绝回退至默认设备');
      wx.showToast({
        title: '未识别设备，请重新扫码',
        icon: 'none'
      });
      return; // 不再默认使用 DEV_TEST_DEFAULT
    }
    console.log('✅ 最终设备码:', deviceCode);
    
    console.log('调试设备码获取:');
    console.log('- app.globalData.currentDeviceCode:', app.globalData.currentDeviceCode);
    console.log('- wx.getStorageSync(currentDeviceCode):', wx.getStorageSync('currentDeviceCode'));
    console.log('- wx.getStorageSync(deviceCode):', wx.getStorageSync('deviceCode'));
    console.log('- 最终deviceCode:', deviceCode);
    
    this.setData({
      deviceCode: deviceCode
    });
    console.log('设备码设置成功:', deviceCode);
    
    // 初始化页面数据
    this.initPageData();
    
    // 获取分享内容（不需要等待结果）
    this.getShareContent().catch((error) => {
      console.log('初始化时获取分享内容失败:', error);
    });
    
    // 启用分享功能
    wx.showShareMenu({
      withShareTicket: true,
      menus: ['shareAppMessage', 'shareTimeline']
    });
  },

  // 初始化页面数据
  initPageData() {
    // 获取当前时间
    const now = new Date();
    const paymentTime = this.formatDateTime(now);
    
    // 从本地存储获取用户信息
    const userInfo = wx.getStorageSync('userInfo');
    const openid = wx.getStorageSync('openid');
    
    // 准备要更新的数据
    const updateData: any = {
      userId: userInfo && userInfo.nickName ? userInfo.nickName : '微信用户',
      openid: openid || ''
    };
    
    // 只有在没有真实订单编号时才生成模拟订单编号
    if (!this.data.orderNumber) {
      updateData.orderNumber = this.generateOrderNumber();
    }
    
    // 只有在没有真实支付时间时才使用当前时间
    if (!this.data.paymentTime) {
      updateData.paymentTime = paymentTime;
    }
    
    this.setData(updateData);
  },

  // 生成订单编号
  generateOrderNumber() {
    // 基于时间戳和随机数生成订单编号
    const timestamp = Date.now().toString();
    const random = Math.random().toString(36).substr(2, 8);
    return timestamp.substr(-8) + random;
  },

  // 格式化日期时间
  formatDateTime(date: Date) {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().length === 1 ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1).toString();
    const day = date.getDate().toString().length === 1 ? '0' + date.getDate() : date.getDate().toString();
    const hours = date.getHours().toString().length === 1 ? '0' + date.getHours() : date.getHours().toString();
    const minutes = date.getMinutes().toString().length === 1 ? '0' + date.getMinutes() : date.getMinutes().toString();
    const seconds = date.getSeconds().toString().length === 1 ? '0' + date.getSeconds() : date.getSeconds().toString();
    
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  },

  // 开始抽奖（按钮级强拦截）
  async startLottery() {
    console.log('🎮 点击抽奖按钮 - 开始第一步');
    
    // 若已收到自动启动事件且当前抽奖未结束，则拦截重复启动
    if (this.data.receivedStartEvent && !this.data.isLotteryFinished) {
      wx.showToast({ title: '抽奖进行中，请稍候结果', icon: 'none' });
      this.addLog('⛔ 已在抽奖中，拦截二次启动');
      return;
    }

    // 检查设备码
    if (!this.data.deviceCode) {
      console.log('❌ 设备码为空，无法开始抽奖');
      wx.showToast({
        title: '设备码为空',
        icon: 'none'
      });
      return;
    }
    
    console.log('🔎 先进行支付状态轮询，确认已入账');
    console.log('设备码:', this.data.deviceCode);
    console.log('用户ID:', this.data.userId);
    console.log('OpenID:', this.data.openid);
    console.log('订单ID:', this.data.orderId);
    
    // 添加日志记录
    this.addLog('🎮 抽奖按钮被点击');
    this.addLog('🔎 开始轮询订单支付状态');

    // 登录就绪与强拦截
    const app = getApp<IAppOption>();
    try { await app.globalData.loginReady; } catch(_) {}
    let token = app.globalData.token || wx.getStorageSync('token');
    if (!token) {
      wx.showLoading({ title: '正在登录…' });
      const ok = await app.ensureLogin?.();
      wx.hideLoading();
      if (!ok) {
        wx.showToast({ title: '请先登录后再抽奖', icon: 'none' });
        return;
      }
      token = app.globalData.token || wx.getStorageSync('token');
    }

    // 标记本次点击需要在连接并加入房间后自动发起抽奖
    this.setData({ waitingToStart: true, hasSentStart: false });
    // 支付状态防护：确认 paid 后再连接并发起抽奖
    this.ensurePaidThenStart();
  },

  // 申请退款
  requestRefund() {
    console.log('用户点击退款按钮');
    
    const orderId = this.data.orderId;
    const amount = this.data.paymentAmount;
    
    if (!orderId) {
      wx.showToast({ title: '订单信息不存在', icon: 'none' });
      return;
    }
    
    wx.showModal({
      title: '申请退款',
      content: `确定要申请退款吗？订单金额：¥${amount}\n退款后将无法参与抽奖。`,
      confirmText: '确定退款',
      cancelText: '取消',
      success: (res) => {
        if (res.confirm) {
          this.processRefund(orderId);
        }
      }
    });
  },

  // 处理退款
  processRefund(orderId: string) {
    const openid = wx.getStorageSync('openid');
    
    if (!openid) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    
    console.log('=== 游戏页面退款API调用 ===');
    console.log('订单ID:', orderId);
    console.log('用户openid:', openid);
    console.log('========================');
    
    wx.showLoading({ title: '处理退款中...', mask: true });
    
    wx.request({
      url: `${config.adminUrl}/${config.api.orderAction}`,
      method: 'POST',
      data: {
        openid: openid,
        order_id: orderId,
        action: 'refund',
        reason: '用户在抽奖页面申请退款'
      },
      success: (res: any) => {
        wx.hideLoading();
        console.log('=== 游戏页面退款API响应 ===');
        console.log('完整响应:', res);
        console.log('==========================');
        
        if (res.data.code === 0 || res.data.code === 1) {
          wx.showToast({
            title: '退款申请已提交',
            icon: 'success',
            duration: 2000,
            success: () => {
              // 退款成功后返回首页
              setTimeout(() => {
                wx.navigateBack({
                  delta: 2  // 返回到首页
                });
              }, 2000);
            }
          });
        } else {
          wx.showToast({ 
            title: res.data.msg || '退款申请失败', 
            icon: 'none' 
          });
        }
      },
      fail: (error: any) => {
        wx.hideLoading();
        console.error('游戏页面退款API请求失败:', error);
        wx.showToast({ 
          title: '网络请求失败', 
          icon: 'none' 
        });
      }
    });
  },

  // 获取分享内容
  getShareContent(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (!this.data.deviceCode) {
        console.log('设备码为空，无法获取分享内容');
        reject(new Error('设备码为空'));
        return;
      }

      console.log('获取分享内容，设备码:', this.data.deviceCode);
      
      wx.request({
        url: `${config.adminUrl}/${config.api.getShareContent}`,
        method: 'GET',
        data: {
          device_id: this.data.deviceCode
        },
        success: (res: any) => {
          console.log('分享内容API响应:', res);
          
          if (res.statusCode === 200 && res.data && res.data.code === 1) {
            const shareData = res.data.data;
            
            // 处理媒体文件URL（图片或视频）
            let shareMediaUrl = '';
            if (shareData.share_media_url && !shareData.share_media_url.startsWith('http')) {
              shareMediaUrl = `${config.resourceDomain}${shareData.share_media_url}`;
            } else {
              shareMediaUrl = shareData.share_media_url || '';
            }
            
          const shareContent = {
            ...shareData,
            share_media_url: shareMediaUrl // 更新为完整URL
          };
          
          // 推荐文案统一使用设备编辑页的自定义分享文案（share_text）
          if (shareContent.share_text && !shareContent.recommend_text) {
            (shareContent as any).recommend_text = shareContent.share_text;
          }
            
            this.setData({
              shareContent: shareContent,
              mediaLoading: true // 开始加载媒体
            });

            // 设置5秒超时，自动显示视频
            setTimeout(() => {
              if (this.data.mediaLoading) {
                console.log('视频加载超时，强制显示');
                this.setData({
                  mediaLoading: false
                });
                // 超时后只在分享引导显示时才播放
                if (this.data.showFullScreenGuide) {
                  setTimeout(() => {
                    this.playShareVideo();
                  }, 1000);
                }
              }
            }, 5000);
            
            console.log('分享内容已获取:', shareContent);
            console.log('媒体类型:', shareContent.media_type, '媒体URL:', shareContent.share_media_url);
            resolve();
          } else {
            console.error('获取分享内容失败:', res.data);
            reject(new Error('获取分享内容失败'));
          }
        },
        fail: (err: any) => {
          console.error('获取分享内容请求失败:', err);
          reject(err);
        }
      });
    });
  },

  // 分享到朋友圈按钮点击
  shareToTimeline() {
    console.log('用户点击分享朋友圈按钮');
    
    // 构建跳转URL，传递必要的参数（手动构建，因为小程序不支持URLSearchParams）
    const params: string[] = [];
    
    // 传递设备码（用于调用分享内容API）
    if (this.data.deviceCode) {
      params.push(`deviceCode=${encodeURIComponent(this.data.deviceCode)}`);
    }
    
    // 传递订单信息
    if (this.data.orderId) {
      params.push(`orderId=${encodeURIComponent(this.data.orderId)}`);
    }
    if (this.data.paymentAmount) {
      params.push(`amount=${encodeURIComponent(this.data.paymentAmount)}`);
    }
    
    // 传递中奖信息（如果有）
    if (this.data.winResult) {
      const prizeName = this.data.winResult.prize_name || (this.data.winResult.prize_info && this.data.winResult.prize_info.name) || '';
      const prizeImage = this.data.winResult.image || '';
      
      if (prizeName) {
        params.push(`prizeName=${encodeURIComponent(prizeName)}`);
      }
      if (prizeImage) {
        params.push(`prizeImage=${encodeURIComponent(prizeImage)}`);
      }
      params.push(`prizeInfo=${encodeURIComponent(JSON.stringify(this.data.winResult))}`);
    }
    
    const url = `/pages/share-guide/share-guide${params.length > 0 ? '?' + params.join('&') : ''}`;
    console.log('跳转到分享引导页面，URL:', url);
    
    // 跳转到分享引导页面
    wx.navigateTo({
      url: url
    });
  },

  // 分享功能
  onShareAppMessage() {
    let title = '我刚刚完成了支付，快来一起抽奖吧！';
    let desc = '异企趣玩抽奖活动，好运连连！';
    let imageUrl = '';
    let path = '/pages/index/index';
    
    // 优先使用API返回的分享内容
    if (this.data.shareContent && this.data.shareContent.has_share_content) {
      title = this.data.shareContent.share_title || this.data.shareContent.share_text || title;
      desc = this.data.shareContent.share_text || desc;
      
      console.log('🎬 游戏页分享 - 媒体类型:', this.data.shareContent.media_type);
      
      // 处理视频分享
      if (this.data.shareContent.media_type === 'video') {
        console.log('⚠️  游戏页检测到视频，微信分享不支持视频，使用默认图片');
        imageUrl = ''; // 微信分享不支持视频
        
        // 在标题中标注视频内容
        if (!title.includes('视频')) {
          title = `🎬 ${title}`;
        }
      } else {
        imageUrl = this.data.shareContent.share_media_url || '';
        console.log('📷 游戏页使用图片分享:', imageUrl);
      }
      
      // 如果有设备ID，传递给分享页面
      if (this.data.deviceCode) {
        path = `/pages/index/index?device_id=${this.data.deviceCode}`;
      }
    }
    // 如果有中奖信息，使用中奖相关的分享内容
    else if (this.data.winResult && (this.data.winResult.prize_name || this.data.winResult.prize_info)) {
      const prizeName = this.data.winResult.prize_name || this.data.winResult.prize_info.name;
      title = `🎉 我刚在异企趣玩抽奖活动中中了${prizeName}！快来试试你的运气吧！`;
      desc = `恭喜我中了${prizeName}，你也来试试运气吧！`;
      imageUrl = this.data.winResult.image || '';
      
      if (this.data.deviceCode) {
        path = `/pages/index/index?device_id=${this.data.deviceCode}`;
      }
    }
    
    console.log('📤 游戏页最终分享参数:');
    console.log('   标题:', title);
    console.log('   描述:', desc);
    console.log('   图片:', imageUrl);
    console.log('   路径:', path);
    
    return {
      title: title,
      desc: desc,
      path: '/pages/index/index', // 强制跳转到首页
      imageUrl: imageUrl
    };
  },

  // 分享到朋友圈
  onShareTimeline() {
    let title = '异企趣玩 - 抽奖活动火热进行中！';
    let imageUrl = '';
    let query = '';
    
    // 优先使用API返回的分享内容
    if (this.data.shareContent && this.data.shareContent.has_share_content) {
      // 朋友圈分享可以组合标题和内容
      const shareTitle = this.data.shareContent.share_title || '';
      const shareText = this.data.shareContent.share_text || '';
      
      if (shareTitle && shareText) {
        title = `${shareTitle} - ${shareText}`;
      } else {
        title = shareTitle || shareText || title;
      }
      
      console.log('🎬 游戏页朋友圈 - 媒体类型:', this.data.shareContent.media_type);
      
      // 处理视频分享
      if (this.data.shareContent.media_type === 'video') {
        console.log('⚠️  游戏页朋友圈不支持视频，使用默认图片');
        imageUrl = ''; // 朋友圈不支持视频
        
        // 在标题中标注视频内容
        if (!title.includes('视频')) {
          title = `🎬 ${title}`;
        }
      } else {
        imageUrl = this.data.shareContent.share_media_url || '';
        console.log('📷 游戏页朋友圈使用图片:', imageUrl);
      }
      
      // 如果有设备ID，传递给分享页面
      if (this.data.deviceCode) {
        query = `device_id=${this.data.deviceCode}`;
      }
    }
    // 如果有中奖信息，使用中奖相关的分享标题
    else if (this.data.winResult && (this.data.winResult.prize_name || this.data.winResult.prize_info)) {
      const prizeName = this.data.winResult.prize_name || this.data.winResult.prize_info.name;
      title = `🎉 我在异企趣玩抽奖活动中中了${prizeName}！快来试试你的运气吧！`;
      imageUrl = this.data.winResult.image || '';
      
      if (this.data.deviceCode) {
        query = `device_id=${this.data.deviceCode}`;
      }
    }
    
    console.log('📤 游戏页朋友圈最终分享参数:');
    console.log('   标题:', title);
    console.log('   图片:', imageUrl);
    console.log('   查询参数:', query);
    
    return {
      title: title,
      imageUrl: imageUrl,
      query: query
      // 注意：朋友圈分享不支持自定义path，只能进入当前页面的单页模式
    };
  },

  // 关闭中奖弹窗
  closeWinModal() {
    this.setData({
      showWinModal: false
    });
  },

  // 获取分润配置ID
  getProfitConfigId() {
    console.log('🆔 当前分润配置ID:', this.data.profitConfigId);
    console.log('💰 当前分润信息:', this.data.profitInfo);
    console.log('💵 当前订单金额:', this.data.orderAmount);
    
    return {
      profitConfigId: this.data.profitConfigId,
      profitInfo: this.data.profitInfo,
      orderAmount: this.data.orderAmount
    };
  },

  // 显示分享引导
  showShareGuide() {
    // 关闭中奖弹窗
    this.closeWinModal();
    
    // 构建跳转URL，传递必要的参数（手动构建，因为小程序不支持URLSearchParams）
    const params: string[] = [];
    
    // 传递设备码（用于调用分享内容API）
    if (this.data.deviceCode) {
      params.push(`deviceCode=${encodeURIComponent(this.data.deviceCode)}`);
    }
    
    // 传递订单信息
    if (this.data.orderId) {
      params.push(`orderId=${encodeURIComponent(this.data.orderId)}`);
    }
    if (this.data.paymentAmount) {
      params.push(`amount=${encodeURIComponent(this.data.paymentAmount)}`);
    }
    
    // 传递中奖信息（如果有）
    if (this.data.winResult) {
      const prizeName = this.data.winResult.prize_name || (this.data.winResult.prize_info && this.data.winResult.prize_info.name) || '';
      const prizeImage = this.data.winResult.image || '';
      
      if (prizeName) {
        params.push(`prizeName=${encodeURIComponent(prizeName)}`);
      }
      if (prizeImage) {
        params.push(`prizeImage=${encodeURIComponent(prizeImage)}`);
      }
      params.push(`prizeInfo=${encodeURIComponent(JSON.stringify(this.data.winResult))}`);
    }
    
    const url = `/pages/share-guide/share-guide${params.length > 0 ? '?' + params.join('&') : ''}`;
    console.log('跳转到分享引导页面，URL:', url);
    
    // 跳转到分享引导页面
    wx.navigateTo({
      url: url
    });
  },

  // 关闭分享引导弹窗
  closeShareModal() {
    this.setData({
      showShareModal: false
    });
  },

  // 关闭全屏分享引导
  closeFullScreenGuide() {
    // 关闭分享引导弹窗时停止视频播放
    this.stopShareVideo();
    
    this.setData({
      showFullScreenGuide: false
    });
  },

  // 隐藏分享弹窗 (兼容旧版本命名)
  hideShareModal() {
    this.closeShareModal();
  },

  // 初始化用户信息
  initUserInfo() {
    const app = getApp<IAppOption>();
    try {
      const storedUserInfo = wx.getStorageSync('userInfo');
      if (storedUserInfo) {
        app.globalData.userInfo = storedUserInfo;
        console.log('从本地存储读取用户信息成功:', storedUserInfo);
      }
    } catch (error) {
      console.error('读取本地用户信息失败:', error);
    }
  },

  onUnload() {
    // 页面卸载：抽奖等待中则不主动断开，避免中断流程
    if (!this.data.showLotteryLoading) {
      this.disconnectDevice();
    } else {
      this.addLog('🔒 抽奖进行中，暂不在 onUnload 主动断开连接');
    }
    if (this.data.gameTimer) {
      clearInterval(this.data.gameTimer);
    }
  },

  // 解析页面参数
  parseParams(options: any) {
    // 从页面参数中获取设备ID、用户ID等信息
    const deviceId = options.device_id || this.data.deviceCode;
    const userId = options.user_id || this.getUserNickname();
    const gameType = options.game_type || 'lottery';
    const orderId = options.orderId || ''; // 支付成功后传入的订单ID
    
    // 获取OpenID
    const openid = wx.getStorageSync('openid') || '';
    
    this.setData({
      deviceCode: deviceId,
      userId: userId,
      openid: openid,
      orderId: orderId,
      gameType: gameType
    });
    
    this.addLog(`控制器初始化 - 设备: ${deviceId}, 用户: ${userId}, 订单: ${orderId || '无'}, OpenID: ${openid ? openid.substr(0, 8) + '...' : '未获取'}, 游戏类型: ${gameType}`);
  },

  // 获取用户昵称
  getUserNickname() {
    // 尝试从全局数据获取用户信息
    const app = getApp<IAppOption>();
    if (app.globalData.userInfo && app.globalData.userInfo.nickName) {
      return app.globalData.userInfo.nickName;
    }
    
    // 尝试从本地存储获取
    try {
      const storedUserInfo = wx.getStorageSync('userInfo');
      if (storedUserInfo && storedUserInfo.nickName) {
        return storedUserInfo.nickName;
      }
    } catch (error) {
      console.error('读取本地用户信息失败:', error);
    }
    
    // 如果都没有，返回默认值
    return '微信用户_' + Math.random().toString(36).substr(2, 4);
  },

  // 获取微信用户信息
  getWechatUserInfo() {
    return new Promise((resolve, reject) => {
      wx.getUserProfile({
        desc: '用于游戏控制器显示用户昵称',
        success: (userRes) => {
          console.log('获取微信用户信息成功:', userRes);
          
          // 保存到全局数据
          const app = getApp<IAppOption>();
          app.globalData.userInfo = userRes.userInfo;
          
          // 保存到本地存储
          try {
            wx.setStorageSync('userInfo', userRes.userInfo);
          } catch (error) {
            console.error('保存用户信息到本地失败:', error);
          }
          
          // 更新页面显示的用户ID
          this.setData({
            userId: userRes.userInfo.nickName
          });
          
          this.addLog(`用户信息已更新: ${userRes.userInfo.nickName}`);
          resolve(userRes.userInfo);
        },
        fail: (error) => {
          console.error('获取微信用户信息失败:', error);
          wx.showToast({
            title: '获取用户信息失败',
            icon: 'none'
          });
          reject(error);
        }
      });
    });
  },

  // 初始化游戏状态
  initGameStatus() {
    this.setData({
      runTime: '00:00:00',
      lastMessage: '',
      messageLogs: []
    });
  },

  // 处理设备码输入
  onDeviceCodeInput(e: any) {
    const value = e.detail.value;
    this.setData({
      deviceCode: value
    });
    this.addLog(`设备码已更新: ${value}`);
  },

  // 切换连接状态
  toggleConnection() {
    if (this.data.connectionStatus === 'connected') {
      this.disconnectDevice();
    } else {
      this.connectDevice();
    }
  },

  // 手动重试加入设备房间（弱网兜底）
  retryJoinRoom() {
    const now = Date.now();
    const last = (this.data as any).lastJoinAttemptAt || 0;
    // 简单的节流，避免短时间内频繁重试
    if (now - last < 1500) {
      wx.showToast({ title: '请稍候再试', icon: 'none' });
      return;
    }
    this.setData({ lastJoinAttemptAt: now });

    if (!this.data.deviceCode) {
      wx.showToast({ title: '设备码未设置', icon: 'none' });
      return;
    }
    this.addLog('🔄 手动重试加入设备房间');
    // 复用既有加入逻辑
    try {
      (this as any).sendUserJoin();
      wx.showToast({ title: '正在重试加入...', icon: 'none' });
    } catch (e) {
      console.error('重试加入失败:', e);
      wx.showToast({ title: '重试失败', icon: 'error' });
    }
  },

  // 连接设备
  connectDevice() {
    console.log('连接游戏设备:', this.data.deviceCode);
    
    this.setData({
      connectionStatus: 'connecting'
    });

    // 使用环境配置的WebSocket连接路径
    const wsUrl = envConfig.getWebSocketUrl(this.data.deviceCode);
    console.log(`🌍 当前环境: ${envConfig.CURRENT_ENV}, WebSocket URL: ${wsUrl}`);
    
    const socketTask = wx.connectSocket({
      url: wsUrl,
      success: () => {
        console.log('WebSocket连接发起成功');
        this.addLog(`正在连接到 ${envConfig.getCurrentConfig().name} - ${wsUrl}`);
      },
      fail: (error) => {
        console.error('WebSocket连接失败:', error);
        this.setData({
          connectionStatus: 'disconnected'
        });

        // 禁止自动启用模拟模式，保持与生产一致
        this.addLog('连接失败，请检查网络或设备状态');
      }
    });

    // 监听连接打开
    socketTask?.onOpen?.(() => {
      console.log('WebSocket连接已建立');
      this.setData({
        connectionStatus: 'connected',
        socketTask: socketTask
      });
      
      this.addLog('WebSocket连接已建立，等待Socket.IO握手...');
      // 不再在 onOpen 主动发送 Socket.IO 连接帧(40)，统一在收到 Engine.IO 握手(0) 后发送
      
      // 不要在这里发送认证消息，等待Socket.IO握手完成后再发送
    });

    // 监听消息
    socketTask?.onMessage?.((res) => {
      console.log('收到服务器消息:', res.data);
      this.handleServerMessage(res.data as string);
    });

    // 监听连接关闭
    socketTask?.onClose?.((res) => {
      console.log('WebSocket连接已关闭:', res);
      try {
        const code = (res as any)?.code;
        const reason = (res as any)?.reason;
        console.log(`连接关闭详情: code=${code ?? ''}, reason=${reason ?? ''}`);
      } catch (_) { /* 忽略 */ }
      this.addLog(`连接关闭: code=${(res as any).code || ''}, reason=${(res as any).reason || ''}`);

      // 抽奖等待守护：等待中保持UI并自动重连
      if (this.data.showLotteryLoading && !this.data.isLotteryFinished) {
        this.addLog('网络波动，保持“等待结果”，正在自动重连...');
        // 增加指数退避：2/4/6 秒，最多3次
        const attempt = (this.data.reconnectAttempts || 0) + 1;
        const delay = Math.min(6000, 2000 * attempt);
        // 清理之前的定时器
        if (this.data.reconnectTimer) {
          clearTimeout(this.data.reconnectTimer);
        }
        const timer = setTimeout(() => {
          try {
            this.connectDevice();
          } catch (e) {
            console.warn('自动重连失败（忽略）:', e);
          }
        }, delay);
        this.setData({
          reconnectAttempts: attempt,
          reconnectTimer: timer,
          // 保持等待UI不变
          connectionStatus: 'disconnected',
          socketTask: null
        });
      } else {
        // 非等待状态，正常清理
        this.setData({
          connectionStatus: 'disconnected',
          socketTask: null
        });
        this.addLog('设备连接已断开');
      }
    });

    // 监听连接错误
    (socketTask as any)?.onError?.((error: any) => {
      console.error('WebSocket连接错误:', error);
      try {
        console.log('错误详情: errMsg=', (error as any)?.errMsg || '');
      } catch (_) { /* 忽略 */ }
      this.setData({
        connectionStatus: 'disconnected'
      });
      this.addLog('连接错误: ' + JSON.stringify(error));
    });
  },

  // 启用模拟连接（演示用） - 生产环境禁用
  /*
  startMockConnection() {
    setTimeout(() => {
      this.setData({
        connectionStatus: 'connected'
      });
      this.addLog('模拟连接已建立');
      
      // 模拟socketTask
      this.setData({
        socketTask: {
          send: (options: any) => {
            console.log('模拟发送:', options.data);
            this.addLog('发送: ' + options.data);
            
            // 模拟服务器响应
            setTimeout(() => {
              const message = JSON.parse(options.data);
              this.simulateServerResponse(message);
            }, 500);
            
            if (options.success) options.success();
          },
          close: () => {
            console.log('模拟关闭连接');
            this.setData({
              connectionStatus: 'disconnected',
              socketTask: null
            });
            this.addLog('模拟连接已关闭');
          }
        }
      });
    }, 2000);
  },

  // 模拟服务器响应
  simulateServerResponse(message: any) {
    let response: any = {};
    
    switch (message.type) {
      case 'auth':
        response = {
          type: 'auth_response',
          status: 'success',
          message: '设备认证成功'
        };
        break;
        
      case 'game_start':
        response = {
          type: 'game_response',
          action: 'start',
          status: 'success',
          message: '游戏启动成功'
        };
        break;
        
      case 'game_stop':
        response = {
          type: 'game_response',
          action: 'stop',
          status: 'success',
          message: '游戏已停止'
        };
        break;
        
      case 'game_pause':
        response = {
          type: 'game_response',
          action: 'pause',
          status: 'success',
          message: '游戏已暂停'
        };
        break;
        
      case 'game_reset':
        response = {
          type: 'game_response',
          action: 'reset',
          status: 'success',
          message: '游戏已重置'
        };
        break;
    }
    
    if (response.type) {
      this.handleServerMessage(JSON.stringify(response));
    }
  },
  */

  // 断开设备连接
  disconnectDevice() {
    if (this.data.socketTask) {
      this.data.socketTask?.close?.();
    }
    
    this.setData({
      connectionStatus: 'disconnected',
      socketTask: null
    });
    
    // 停止计时器
    if (this.data.gameTimer) {
      clearInterval(this.data.gameTimer);
      this.setData({ gameTimer: null });
    }
    
    this.addLog('设备已断开连接');
  },

  // 发送消息到服务器
  sendMessage(message: any) {
    if (this.data.socketTask) {
      // Socket.IO消息格式: "42" + JSON.stringify([event, data])
      // 42表示Engine.IO的MESSAGE类型和Socket.IO的EVENT类型
      const socketIOMessage = `42${JSON.stringify(['game_control', message])}`;
      
      this.data.socketTask?.send?.({
        data: socketIOMessage,
        success: () => {
          console.log('消息发送成功:', socketIOMessage);
          this.addLog('发送: ' + JSON.stringify(message));
        },
        fail: (error: any) => {
          console.error('消息发送失败:', error);
          this.addLog('消息发送失败: ' + JSON.stringify(error));
        }
      });
    }
  },

  // 处理服务器消息
  handleServerMessage(data: string) {
    try {
      let message: any = {};
      
      // 处理Socket.IO消息格式
      if (data.startsWith('0')) {
        // Engine.IO连接消息，需要解析并响应
        const engineData = JSON.parse(data.substring(1));
        this.addLog('收到Engine.IO握手: ' + engineData.sid);
        // 记录会话ID，便于服务端点对点回推
        if (engineData && engineData.sid) {
          this.setData({ userSocketId: engineData.sid });
        }
        
        // 发送Socket.IO连接消息
        this.sendSocketIOConnect();
        return;
      } else if (data.startsWith('40')) {
        // Socket.IO连接成功消息
        const nsData = JSON.parse(data.substring(2));
        if (nsData && nsData.sid) {
          this.setData({ userSocketId: nsData.sid });
        }
        this.addLog('Socket.IO连接成功');
        // 先声明用户加入设备房间
        this.sendUserJoin();
        // 若用户已点击抽奖，则在加入房间确认后自动发送抽奖指令
        if (this.data.waitingToStart && !this.data.hasSentStart) {
          this.addLog('✅ 已连接房间，等待加入确认后自动抽奖');
        } else {
          this.addLog('✅ 已连接房间，等待用户点击抽奖');
        }
        return;
      } else if (data.startsWith('2')) {
        // Engine.IO ping消息，需要回复pong
        if (data === '2') {
          this.sendPong();
          return;
        }
      } else if (data.startsWith('42')) {
        // Socket.IO事件消息，格式: "42" + JSON.stringify([event, data])
        const jsonStr = data.substring(2);
        const [event, eventData] = JSON.parse(jsonStr);
        message = eventData;
        console.log(`🔥 解析到事件: ${event}, 数据:`, eventData);
        this.addLog(`收到事件: ${event}`);
        
        // 处理特定的抽奖事件
        console.log(`🔥 准备调用 handleLotteryEvent: event=${event}`);
        this.handleLotteryEvent(event, eventData);
      } else {
        // 尝试直接解析为JSON
        message = JSON.parse(data);
      }
      
      console.log('处理服务器消息:', message);
      
      if (message && Object.keys(message).length > 0) {
        this.setData({
          lastMessage: message.message || '收到消息'
        });
        
        this.addLog('收到: ' + (message.message || JSON.stringify(message)));
        
        // 检查是否是抽奖结果数据
        if (message.result && (message.result === 'win' || message.result === 'lose')) {
          console.log('🎯 检测到抽奖结果数据，直接处理');
          this.handleLotteryResult(message);
        }
        
        // 不再更新游戏状态，统一在handleLotterySent中处理
      }
      
    } catch (error) {
      console.error('解析服务器消息失败:', error);
      this.addLog('消息解析失败: ' + data);
    }
  },

  // 小程序用户加入设备房间
  sendUserJoin() {
    const sid = this.data.userSocketId;
    if (!sid) {
      console.warn('尚未获取到Socket会话ID，暂不发送user_join');
      return;
    }
    
    // 生成一致的用户ID：优先使用Socket会话ID，确保整个会话中保持一致
    const consistentUserId = sid;
    
    const userInfo = {
      user_id: consistentUserId,
      openid: this.data.openid,
      user_name: this.data.userId || this.getUserNickname()
    };
    
    // 将一致的用户ID保存到data中，供后续抽奖使用
    this.setData({ 
      consistentUserId: consistentUserId 
    });
    
    const payload = { device_id: this.data.deviceCode, user_info: userInfo };
    this.sendSocketIOEvent('user_join', payload);
    this.addLog(`用户加入设备房间: ${this.data.deviceCode}, 用户ID: ${consistentUserId}`);
  },

  // 发送Socket.IO连接消息
  sendSocketIOConnect() {
    if (this.data.socketTask) {
      this.data.socketTask?.send?.({
        data: '40',
        success: () => {
          console.log('Socket.IO连接消息发送成功');
          this.addLog('发送Socket.IO连接请求');
        },
        fail: (error: any) => {
          console.error('Socket.IO连接消息发送失败:', error);
        }
      });
    }
  },

  // 发送pong响应
  sendPong() {
    if (this.data.socketTask) {
      this.data.socketTask?.send?.({
        data: '3',
        success: () => {
          console.log('Pong发送成功');
        },
        fail: (error: any) => {
          console.error('Pong发送失败:', error);
        }
      });
    }
  },

  // 发送启动抽奖消息
  sendStartLottery() {
    // 避免重复发送：已发送或已收到自动启动事件时不再重复
    if (this.data.hasSentStart || this.data.receivedStartEvent) {
      this.addLog('⛔ 已存在启动状态，跳过重复发送');
      return;
    }
    // 使用一致的用户ID：优先使用已保存的consistentUserId，确保与user_join时相同
    const consistentUserId = this.data.consistentUserId || this.data.userSocketId || this.data.userId;
    
    const lotteryMessage: any = {
      device_id: this.data.deviceCode,
      user_id: consistentUserId,
      openid: this.data.openid, // 使用data中的OpenID
      game_type: this.data.gameType,
      lottery_type: 1, // 抽奖类型，默认为1
      user_name: this.data.userId, // 用户昵称/姓名（展示用）
      order_id: this.data.orderId // 指定已支付订单ID，提升匹配准确性
    };
    // 仅在存在有效配置ID时才传递，避免与设备不匹配
    if (this.data.profitConfigId) {
      lotteryMessage.config_id = this.data.profitConfigId;
    }
    
    // 立即进入“等待结果”状态，防止在弱网下出现空白
    this.setData({
      showLotteryLoading: true,
      gameStatus: 'starting',
      gameStatusText: '抽奖已开始，等待结果',
      isLotteryFinished: false,
      lastLotterySentAt: Date.now(),
      reconnectAttempts: 0
    });
    this.addLog('已发送抽奖指令，进入等待结果状态');

    // 标记本次启动已发送，避免重复
    this.setData({ hasSentStart: true, waitingToStart: false });

    this.sendSocketIOEvent('start_lottery', lotteryMessage);
    this.addLog(`发送启动抽奖请求 - 设备: ${this.data.deviceCode}, 用户: ${this.data.userId}, OpenID: ${this.data.openid ? this.data.openid.substr(0, 8) + '...' : '未获取'}, 配置ID: ${lotteryMessage.config_id}`);

    // 结果超时守护：设备离线或未返回结果时，15s后友好提示并结束等待
    try { if (this.data.gameTimer) { clearTimeout(this.data.gameTimer); } } catch (_) {}
    const watchdog = setTimeout(() => {
      if (!this.data.isLotteryFinished) {
        this.addLog('⌛ 结果超时，设备可能未响应');
        this.setData({
          showLotteryLoading: false,
          gameStatusText: '设备未响应，请稍后重试',
          isLotteryFinished: true
        });
        try {
          wx.showModal({
            title: '设备未响应',
            content: '设备可能离线或未返回结果，请稍后重试或联系工作人员。',
            showCancel: false,
            confirmText: '知道了'
          });
        } catch (_) {}
      }
    }, 15000);
    this.setData({ gameTimer: watchdog });
  },

  /**
   * 支付状态轮询：确认订单已入账后再开始抽奖
   * - 成功：设置服务器返回的支付时间与分润配置，连接设备并发送 start_lottery
   * - 失败：提示重试
   */
  async ensurePaidThenStart() {
    // 再次兜底：等待登录就绪并确保 token
    const app = getApp<IAppOption>();
    try { await app.globalData.loginReady; } catch(_) {}
    const openid = wx.getStorageSync('openid') || this.data.openid;
    const orderId = this.data.orderId;
    let token = app.globalData.token || wx.getStorageSync('token');
    if (!openid || !orderId) {
      wx.showToast({ title: '缺少openid或订单ID', icon: 'none' });
      return;
    }
    if (!token) {
      const ok = await app.ensureLogin?.();
      if (!ok) {
        wx.showToast({ title: '请先登录后再抽奖', icon: 'none' });
        this.addLog('❌ 未登录，无法确认支付状态');
        return;
      }
      token = app.globalData.token || wx.getStorageSync('token');
    }

    // 抽奖前，主动调用后端支付查询接口，同步订单支付状态（避免回调未达）
    try {
      await request.request({
        url: config.api.queryPayOrder,
        method: 'POST',
        data: { orderId },
        needAuth: true,
        showLoading: false
      });
      this.addLog('🔄 已向后台同步支付状态(queryPayOrder)');
    } catch (e) {
      this.addLog('⚠️ 同步支付状态失败，继续通过订单详情轮询确认');
    }

    // 轮询配置
    const maxAttempts = 15; // 最多尝试15次（约18秒）
    const intervalMs = 1200;
    let attempts = 0;

    const poll = () => {
      attempts++;
      request.request({
        url: config.api.getOrderDetail,
        method: 'GET',
        // 本地环境后台需要 openid + order_id；线上仅凭 token 也可
        data: { openid, order_id: orderId },
        needAuth: true,
        showLoading: false
      }).then((resp: any) => {
        const ok = resp && (resp.code === 1 || resp.code === 0);
        const data = ok ? (resp.data || {}) : {};

        // 更稳健的“已支付”判断：pay_status、order_status、pay_time/交易号、金额一致
        const payStatusPaid = (data.pay_status || '').toLowerCase() === 'paid';
        const orderStatusPaid = String(data.order_status || '') === '1';
        // 兼容秒级时间戳和字符串时间（如 "2025-10-05 12:34:56"）
        const hasPayTime = (() => {
          if (!data.pay_time) return false;
          if (typeof data.pay_time === 'number') {
            return data.pay_time > 946684800; // 秒级比较（2000-01-01）
          }
          const dt = new Date(String(data.pay_time).replace(/-/g, '/'));
          return !isNaN(dt.getTime()) && dt.getTime() > 946684800000; // 毫秒级比较
        })();
        const hasTxn = !!data.wx_transaction_id || !!data.transaction_id;
        const amountMatches = !!data.amount && String(data.amount) === String(this.data.paymentAmount);
        // 记录金额匹配情况以便调试，避免未使用变量产生TS警告
        this.addLog(`💰 金额匹配: ${amountMatches ? '是' : '否'}，后台=${data.amount || '未知'}，前端=${this.data.paymentAmount}`);
        const paid = payStatusPaid || orderStatusPaid || hasPayTime || hasTxn;

        this.addLog(`🔎 订单支付状态: ${data.pay_status || '未知'} (尝试 ${attempts}/${maxAttempts})`);

        if (paid) {
          // 优先使用服务器的 pay_time，兼容数字秒和字符串时间格式，避免显示1970
          let formattedTime = '';
          if (data.pay_time) {
            try {
              if (typeof data.pay_time === 'number') {
                const tsSec = data.pay_time;
                if (tsSec > 946684800) { // 2000-01-01
                  formattedTime = this.formatDateTime(new Date(tsSec * 1000));
                }
              } else if (typeof data.pay_time === 'string') {
                const dt = new Date(String(data.pay_time).replace(/-/g, '/'));
                if (!isNaN(dt.getTime())) {
                  formattedTime = this.formatDateTime(dt);
                }
              }
            } catch (e) { /* 忽略格式化错误 */ }
          }

          this.setData({
            paymentTime: formattedTime || this.data.paymentTime,
            orderAmount: parseFloat(data.amount || this.data.paymentAmount) || this.data.orderAmount,
            // 使用服务器返回的配置ID；若无则不默认兜底，避免错误绑定
            profitConfigId: (data.config_id ? String(data.config_id) : (this.data.profitConfigId ? String(this.data.profitConfigId) : ''))
          });

          this.addLog('✅ 已确认支付成功，准备连接设备并发起抽奖');
          this.connectDevice();
          return;
        }

        // 本地开发兜底：如果后台返回 code=1 但字段不一致，也直接进入抽奖，避免阻塞
        if (ok && attempts >= 2) {
          this.addLog('⚠️ 后台返回成功但状态字段不一致，放行进入抽奖');
          this.connectDevice();
          return;
        }

        if (attempts < maxAttempts) {
          setTimeout(poll, intervalMs);
        } else {
          wx.showToast({ title: '支付状态未确认，请稍后重试', icon: 'none' });
          this.addLog('❌ 轮询超时，未确认支付成功');
        }
      }).catch((err: any) => {
        if (attempts < maxAttempts) {
          setTimeout(poll, intervalMs);
        } else {
          console.error('订单详情轮询失败:', err);
          wx.showToast({ title: '网络异常，请稍后重试', icon: 'none' });
        }
      });
    };

    poll();
  },

  // 发送Socket.IO事件消息
  sendSocketIOEvent(event: string, data: any) {
    // 使用当前页面的socketTask，而不是全局的
    if (this.data.socketTask) {
      const socketIOMessage = `42${JSON.stringify([event, data])}`;
      
      console.log(`📤 发送 ${event} 事件:`, data);
      console.log(`📤 Socket.IO消息:`, socketIOMessage);
      
      this.data.socketTask?.send?.({
        data: socketIOMessage,
        success: () => {
          console.log(`✅ ${event} 事件发送成功:`, data);
          this.addLog(`${event} 发送成功`);
        },
        fail: (error: any) => {
          console.error(`❌ ${event} 事件发送失败:`, error);
          this.addLog(`${event} 发送失败: ` + JSON.stringify(error));
        }
      });
    } else {
      console.error('❌ WebSocket连接不存在，无法发送事件:', event);
      this.addLog(`WebSocket未连接，${event} 发送失败`);
    }
  },

  // 处理来自app.ts转发的WebSocket消息
  handleLotteryMessage(event?: string, eventData?: any) {
    this.handleLotteryEvent(event, eventData);
  },

  // 处理抽奖相关事件
  handleLotteryEvent(event?: string, eventData?: any) {
    if (!event || !eventData) {
      console.log('🔥 handleLotteryEvent 被调用，但无事件数据');
      return;
    }
    
    console.log('🎯 处理抽奖事件:', event, eventData);
    this.addLog(`🎯 处理事件: ${event}`);
    
    // 根据事件类型处理不同的抽奖结果
    switch (event) {
      case 'lottery_result':
        this.handleLotteryResult(eventData);
        break;
      case 'user_join_response':
        this.handleUserJoinResponse(eventData);
        break;
      case 'user_joined': // 兼容服务端事件名
      case 'joined_device': // 兼容服务端事件名
        this.handleUserJoinResponse(eventData);
        break;
      case 'lottery_start':
        this.setData({ receivedStartEvent: true, lastStartEventTs: Date.now() });
        this.handleLotteryStart(eventData);
        break;
      case 'game_started':
        this.setData({ receivedStartEvent: true, lastStartEventTs: Date.now() });
        this.handleGameStarted(eventData);
        break;
      case 'lottery_sent':
        this.setData({ receivedStartEvent: true, lastStartEventTs: Date.now() });
        this.handleLotterySent(eventData);
        break;
      case 'config_preview':
        this.handleConfigPreview(eventData);
        break;
      case 'progress_update':
        this.handleProgressUpdate(eventData);
        break;
      case 'lottery_broadcast':  // 添加 lottery_broadcast 事件处理
        this.handleLotteryResult(eventData);
        break;
      case 'user_connected':
        // 设备端/服务端告知有用户加入房间，前端仅记录日志并友好提示
        this.handleUserConnected(eventData);
        break;
      case 'game_result':
        this.handleLotteryResult(eventData);
        break;
      case 'win_result':
        this.handleLotteryResult(eventData);
        break;
      case 'start_lottery_response':
        this.addLog('🎮 抽奖已启动');
        console.log('抽奖启动响应:', eventData);
        break;
      default:
        console.log('🔍 未处理的事件类型:', event, eventData);
        this.addLog(`🔍 收到事件: ${event}`);
        break;
    }
  },

  // 处理用户连接事件（仅提示，不改变抽奖流程）
  handleUserConnected(data?: any) {
    try {
      console.log('👤 有用户加入游戏:', data);
      const name = (data && (data.user_info && (data.user_info.nickname || data.user_info.username))) || (data && data.user_id) || '用户';
      this.addLog(`👤 ${String(name)} 加入设备房间`);
    } catch (e) {
      console.warn('处理 user_connected 事件异常', e);
    }
  },
  
  // 处理配置预览（抽奖前）
  handleConfigPreview(preview?: any) {
    try {
      console.log('🧩 收到配置预览:', preview);
      const supplier = (preview && preview.prizes && preview.prizes.supplier && Array.isArray(preview.prizes.supplier.data)) ? preview.prizes.supplier.data : [];
      const merchant = (preview && preview.prizes && preview.prizes.merchant && Array.isArray(preview.prizes.merchant.data)) ? preview.prizes.merchant.data : [];
      const list = [...supplier, ...merchant].map((p: any) => ({
        id: p.id,
        name: p.name,
        image: resolveImageUrl(p.image, p.id || p.name),
        price: (p.activity_price || p.price || 0)
      }));
      this.setData({ configPreview: preview, previewPrizes: list });
      this.addLog(`🧩 配置预览就绪: 奖品数=${list.length}`);
    } catch (e) {
      console.warn('处理配置预览异常', e);
    }
  },

  // 处理进度更新（抽奖状态流转）
  handleProgressUpdate(data?: any) {
    try {
      const status = data && data.status ? String(data.status) : '';
      const map: Record<string, string> = {
        paid: '已支付，等待设备响应',
        device_started: '设备已开始，抽奖进行中',
        lottery_running: '抽奖进行中...'
      };
      const text = map[status] || (`状态更新: ${status}`);
      console.log('⏱ 进度更新:', data);
      this.addLog(`⏱ 进度更新: ${status}`);
      this.setData({ gameStatusText: text, showLotteryLoading: true });
    } catch (e) {
      console.warn('处理进度更新异常', e);
    }
  },
  
  // 处理抽奖结果
  handleLotteryResult(data: any) {
    // 处理发起状态；若未标记启动但结果属于当前订单，则兜底接受
    const started = !!(this.data.hasSentStart || this.data.receivedStartEvent);
    const belongsToCurrent = !!(data && (
      String(data.order_id || data.orderId || '') === String(this.data.orderId || '') ||
      (data.openid && String(data.openid) === String(this.data.openid || '')) ||
      (data.user_id && String(data.user_id) === String(this.data.consistentUserId || this.data.userSocketId || this.data.userId || ''))
    ));
    if (!started && !belongsToCurrent) {
      console.log('⚠️ 抽奖未启动且非本订单结果，忽略:', data);
      this.addLog('⚠️ 非本次抽奖结果，已忽略');
      return;
    }
    if (!started && belongsToCurrent) {
      this.addLog('✅ 接受属于本订单的结果（兜底）');
      this.setData({ receivedStartEvent: true, lastStartEventTs: Date.now() });
    }

    // 基于启动事件的最小延迟，防止“指令刚发出就弹结果”
    const now = Date.now();
    const lastTs = Number(this.data.lastStartEventTs || 0);
    const minDelay = Number(this.data.minAcceptDelayMs || 1500);
    if (!lastTs || (now - lastTs) < minDelay) {
      const delay = Math.max(minDelay - (now - lastTs), minDelay);
      this.setData({ pendingLotteryResult: data });
      setTimeout(() => {
        const pending = this.data.pendingLotteryResult;
        this.setData({ pendingLotteryResult: null });
        if (pending) {
          this.handleLotteryResult(pending);
        }
      }, delay);
      console.log('⏳ 结果过早，已延迟处理:', { delay, lastTs });
      this.addLog(`⏳ 结果过早，延迟${delay}ms处理`);
      return;
    }

    // 仅对中奖结果尝试使用奖品信息；未中奖可无奖品信息
    // 结果到达，清除超时守护
    if (this.data.gameTimer) {
      try { clearTimeout(this.data.gameTimer); } catch (_) {}
      this.setData({ gameTimer: null });
    }

    // 仅对中奖结果尝试使用奖品信息；未中奖可无奖品信息

    // 设备异常记录：lottery_record_id 以 ERROR_ 开头一律按失败处理
    try {
      const rid = String(data.lottery_record_id || '');
      if (rid.startsWith('ERROR_')) {
        const msg = (data && data.message) ? String(data.message) : '该设备返回异常记录，无法领取奖品';
        console.warn('⚠️ 设备异常记录，按失败处理:', rid, data);
        this.addLog(`⚠️ 设备异常记录: ${rid}`);
        this.setData({
          showLotteryLoading: false,
          gameStatusText: '抽奖结束',
          isLotteryFinished: true
        });
        wx.showModal({ title: '抽奖失败', content: msg, showCancel: false, confirmText: '知道了' });
        return;
      }
    } catch (_e) {}
    // 去重保护：同一设备+同一记录（或时间戳）的相同结果只处理一次
    try {
      const sig = `${String(data.result || '')}|${String(data.device_id || '')}|${String(data.lottery_record_id || data.timestamp || '')}`;
      if ((this as any)._lastLotterySig === sig) {
        console.log('⚠️ 重复的抽奖结果，忽略本次处理:', sig);
        return;
      }
      (this as any)._lastLotterySig = sig;
    } catch (_) {}

    console.log('🎲 抽奖结果:', data);
    this.addLog(`🎲 抽奖结果: ${JSON.stringify(data)}`);
    
    // 检查抽奖结果
    if (data.result === 'win') {
      // 中奖处理
      console.log('🎉 恭喜中奖!', data.prize_info);
      this.addLog('🎉 恭喜中奖!');
      this.handleWinResult(data);
    } else if (data.result === 'lose') {
      // 未中奖处理
      console.log('😞 很遗憾，未中奖');
      this.addLog('😞 很遗憾，未中奖');
      this.showNoLuckMessage();
    } else if (data.result === 'error') {
      // 异常结果：显示提示并结束等待
      const msg = (data && data.message) ? String(data.message) : '抽奖出现异常，请稍后重试';
      console.warn('⚠️ 抽奖异常:', msg, data);
      this.addLog(`⚠️ 抽奖异常: ${msg}`);
      // 如果服务端带有分润信息，也记录到日志，便于追踪
      if (data.profit_info) {
        try {
          this.addLog(`分润信息: ${JSON.stringify(data.profit_info)}`);
        } catch (_) {}
      }
      // 结束Loading并标记抽奖完成
      this.setData({
        showLotteryLoading: false,
        gameStatusText: '抽奖结束',
        isLotteryFinished: true
      });
      // 轻量提示用户
      wx.showModal({
        title: '抽奖异常',
        content: msg,
        showCancel: false,
        confirmText: '好的'
      });
    } else {
      // 其他结果类型
      console.log('❓ 未知的抽奖结果类型:', data.result);
      this.addLog(`❓ 未知结果: ${data.result}`);
    }
  },

  // 显示未中奖消息
  showNoLuckMessage() {
    wx.showModal({
      title: '抽奖结果',
      content: '很遗憾，这次没有中奖，再接再厉！',
      showCancel: false,
      confirmText: '确定',
      success: () => {
        // 可以在这里添加未中奖后的逻辑
        this.setData({
          gameStatusText: '抽奖结束',
          isLotteryFinished: true
        });
      }
    });
  },

  // 处理游戏开始事件
  handleGameStarted(data?: any) {
    console.log('🎮 设备确认游戏已开始:', data);
    this.addLog('🎮 设备已开始抽奖');
    this.setData({
      gameStatus: 'running',
      gameStatusText: '抽奖进行中...',
      showLotteryLoading: true
    });
  },

  // 处理用户加入设备房间的响应
  handleUserJoinResponse(data?: any) {
    console.log('👤 用户加入设备房间响应:', data);
    const success = data && data.success !== undefined ? !!data.success : true;
    const message = (data && data.message) ? String(data.message) : (success ? '用户已加入设备房间' : '加入设备房间失败');
    this.addLog(`👤 用户加入响应: ${message}`);

    if (!success) {
      // 设备不在线或不存在等提示，但不阻断后续抽奖流程
      wx.showToast({ title: message, icon: 'none', duration: 2500 });
      // 保持当前等待或进行中的状态，避免影响后续 lottery_start / game_started / lottery_sent
      return;
    }

    // 成功时仅记录日志，状态由后续事件驱动
    wx.showToast({ title: '已加入设备房间', icon: 'success', duration: 1200 });

    // 如果用户已点击抽奖且尚未发送，则立即发送抽奖指令
    if (this.data.waitingToStart && !this.data.hasSentStart) {
      try {
        this.sendStartLottery();
        this.setData({ hasSentStart: true, waitingToStart: false });
      } catch (e) {
        console.error('自动发送抽奖指令失败:', e);
        this.addLog('❌ 自动发送抽奖失败，请重试');
      }
    }
  },

  // 处理抽奖开始事件（服务端广播）
  handleLotteryStart(data?: any) {
    console.log('🚦 收到 lottery_start 事件:', data);
    this.addLog('🚦 抽奖开始事件已广播');
    const orderId = (data && (data.order_id || data.orderId)) ? (data.order_id || data.orderId) : this.data.orderId;
    const deviceId = data && data.device_id ? data.device_id : this.data.deviceCode;

    // 进入启动中的状态，等待设备确认与结果
    this.setData({
      gameStatus: 'starting',
      gameStatusText: '抽奖已开始，等待设备响应',
      showLotteryLoading: true,
      orderId: orderId || this.data.orderId,
      deviceCode: deviceId || this.data.deviceCode
    });
  },

  // 处理抽奖指令已发送事件
  handleLotterySent(data?: any) {
    console.log('📨 抽奖指令已发送到设备:', data);
    this.addLog('📨 抽奖指令已发送，等待设备开始');
    this.setData({
      gameStatus: 'starting',
      gameStatusText: '抽奖已开始，等待结果',
      showLotteryLoading: true
    });
  },

  // 处理中奖结果
  handleWinResult(data: any) {
    console.log('🚀 handleWinResult 被调用, data:', data);
    this.addLog(`🎉 中奖结果: ${JSON.stringify(data)}`);
    
    console.log('🚀 准备设置弹窗数据...');
    
    // 处理prize_info数据结构 - 中奖弹窗使用WebSocket返回的数据
    let winResult = data;

    // 格式化时间戳
    let formattedTime = '';
    if (data.timestamp) {
      const date = new Date(data.timestamp);
      formattedTime = this.formatDateTime(date);
    }

    // 优先使用统一嵌套 prize，其次回退 prize_info
    const prize = data.prize || data.prize_info;
    if (prize) {
      const imageUrl = resolveImageUrl(prize.image, prize.id || prize.name);
      const valueStr = (prize.activity_price || prize.price) ? `¥${Number(prize.activity_price || prize.price).toFixed(2)}` : '';
      winResult = {
        ...data,
        prize_name: prize.name,
        description: `恭喜您抽中了奖品：${prize.name}`,
        image: imageUrl,
        value: valueStr,
        id: prize.id,
        result: data.result,
        timestamp: data.timestamp,
        formattedTime
      };
    }
    
    console.log('🚀 处理后的中奖数据:', winResult);
    
    // 设置中奖信息，同时隐藏抽奖loading动画，重启心跳
    this.setData({
      winResult: winResult,
      showWinModal: true,
      isLotteryFinished: true,
      showLotteryLoading: false  // 隐藏抽奖loading动画
    });
    
    // 抽奖结束，重启心跳
    const appInstanceWin = getApp<IAppOption>();
    if (appInstanceWin.startHeartbeat && appInstanceWin.globalData.socketTask) {
      console.log('🎮 中奖处理完成，重启心跳保持连接');
      appInstanceWin.startHeartbeat(appInstanceWin.globalData.socketTask);
    }
    
    // 中奖后重新获取分享内容（分享时用后台API的内容）
    this.getShareContent().catch((error: any) => {
      console.log('中奖后获取分享内容失败:', error);
    });
    
    console.log('🚀 弹窗数据设置完成!');
    console.log('🚀 当前数据状态:', {
      showWinModal: this.data.showWinModal,
      isLotteryFinished: this.data.isLotteryFinished,
      gameStatusText: this.data.gameStatusText,
      winResult: this.data.winResult
    });
  },

  // 更新游戏状态 - 已删除，只保留handleLotterySent中的单一控制点
  updateGameStatus() {
    // 不再设置任何游戏状态，所有状态控制统一在handleLotterySent中处理
  },

  // 启动抽奖
  startGame() {
    // 所有逻辑已删除
  },

  // 发送启动抽奖消息
  sendStartLotteryMessage() {
    // 所有逻辑已删除
  },

  // 添加日志
  addLog(message: string) {
    const now = new Date();
    const hours = now.getHours().toString().length === 1 ? '0' + now.getHours() : now.getHours().toString();
    const minutes = now.getMinutes().toString().length === 1 ? '0' + now.getMinutes() : now.getMinutes().toString();
    const seconds = now.getSeconds().toString().length === 1 ? '0' + now.getSeconds() : now.getSeconds().toString();
    const timeStr = `${hours}:${minutes}:${seconds}`;
    
    const logs = [{
      time: timeStr,
      message: message
    }, ...this.data.logMessages];
    
    // 只保留最近50条日志
    if (logs.length > 50) {
      logs.splice(50);
    }
    
    this.setData({
      messageLogs: logs,
      scrollTop: 0 // 滚动到顶部显示最新日志
    });
  },

  // 触发分享功能
  triggerShare() {
    // 显示提示，引导用户使用右上角分享
    wx.showModal({
      title: '分享提示',
      content: '请点击页面右上角的 ••• 按钮，选择"分享到朋友圈"来分享您的中奖喜悦！',
      showCancel: false,
      confirmText: '我知道了'
    });
  },

  // 保存分享图片
  saveShareImage() {
    let imageUrl = '';
    
    // 优先使用API返回的分享图片
    if (this.data.shareContent && this.data.shareContent.has_share_content && this.data.shareContent.share_media_url) {
      imageUrl = this.data.shareContent.share_media_url;
    }
    // 其次使用中奖奖品图片
    else if (this.data.winResult && this.data.winResult.image) {
      imageUrl = this.data.winResult.image;
    } 
    // 备用：如果有原始的prize_info图片，需要检查是否需要拼接域名
    else if (this.data.winResult && this.data.winResult.prize_info && this.data.winResult.prize_info.image) {
      // 统一使用 resolveImageUrl 解析相对/局域网地址，确保在微信环境可达
      const originalImage = this.data.winResult.prize_info.image;
      imageUrl = resolveImageUrl(originalImage, this.data.winResult.prize_info.id || this.data.winResult.prize_info.name);
    }
    
    console.log('🖼️ 准备保存的图片URL:', imageUrl);
    
    if (!imageUrl) {
      wx.showToast({
        title: '暂无分享图片',
        icon: 'none'
      });
      return;
    }

    wx.downloadFile({
      url: imageUrl,
      success: (res) => {
        if (res.statusCode === 200) {
          wx.saveImageToPhotosAlbum({
            filePath: res.tempFilePath,
            success: () => {
              wx.showToast({
                title: '图片已保存',
                icon: 'success'
              });
            },
            fail: (err) => {
              console.error('保存图片失败:', err);
              wx.showToast({
                title: '保存失败',
                icon: 'error'
              });
            }
          });
        }
      },
      fail: (err) => {
        console.error('下载图片失败:', err);
        wx.showToast({
          title: '下载失败',
          icon: 'error'
        });
      }
    });
  },

  // 复制推荐文案
  copyRecommendText() {
    let text = '';
    
    // 优先使用分享标题（标题优先，再正文）
    if (this.data.shareContent && this.data.shareContent.share_title) {
      text = this.data.shareContent.share_title;
    }
    // 其次使用设备编辑页的自定义分享文案（share_text）
    else if (this.data.shareContent && this.data.shareContent.share_text) {
      text = this.data.shareContent.share_text;
    }
    // 再次使用后台返回的推荐文案
    else if (this.data.shareContent && this.data.shareContent.recommend_text) {
      text = this.data.shareContent.recommend_text;
    }
    // 如果有中奖信息，使用包含中奖信息的文案
    else if (this.data.winResult && (this.data.winResult.prize_name || this.data.winResult.prize_info)) {
      const prizeName = this.data.winResult.prize_name || this.data.winResult.prize_info.name;
      text = `🎊刚参与了异企趣玩抽奖活动，支付¥${this.data.paymentAmount}就有机会获得大奖！我竟然中了${prizeName}！运气不错的话说不定你也能中大奖呢～有兴趣的朋友可以一起来试试！`;
    } else {
      // 没有API内容和中奖信息时使用通用文案
      text = `🎊刚参与了异企趣玩抽奖活动，支付¥${this.data.paymentAmount}就有机会获得大奖！运气不错的话说不定能中大奖呢～有兴趣的朋友可以一起来试试！`;
    }
    
    console.log('复制的推荐文案:', text);
    
    wx.setClipboardData({
      data: text,
      success: () => {
        wx.showToast({
          title: '文案已复制',
          icon: 'success'
        });
      },
      fail: () => {
        wx.showToast({
          title: '复制失败',
          icon: 'error'
        });
      }
    });
  },

  // 去朋友圈
  gotoMoments() {
    // 关闭分享弹窗
    this.closeShareModal();
    
    // 提示用户
    wx.showModal({
      title: '分享提示',
      content: '即将跳转到微信，请在朋友圈发布刚保存的图片',
      showCancel: false,
      success: () => {
        // 尝试跳转到微信（如果支持的话）
        wx.navigateBackMiniProgram({
          extraData: {},
          fail: () => {
            // 如果跳转失败，显示提示
            console.log('无法跳转到微信，用户需手动操作');
          }
        });
      }
    });
  },

  // 防止弹窗关闭
  preventClose() {
    // 空方法，防止点击内容区域关闭弹窗
  },

  // 清空日志
  clearLogs() {
    this.setData({
      logMessages: [],
      messageLogs: [],
      scrollTop: 0
    });
  },

  // 视频播放错误处理
  onVideoError(e: any) {
    console.error('视频播放失败:', e);
    wx.showToast({
      title: '视频加载失败',
      icon: 'none',
      duration: 2000
    });
  },

  // 图片加载错误处理
  onImageError(e: any) {
    console.error('图片加载失败:', e);
    wx.showToast({
      title: '图片加载失败',
      icon: 'none',
      duration: 1500
    });
  },

  // 图片加载成功
  onImageLoad(e: any) {
    console.log('图片加载成功:', e);
    this.setData({
      mediaLoading: false
    });
  },

  // 视频开始加载
  onVideoLoadStart(e: any) {
    console.log('视频开始加载:', e);
    this.setData({
      mediaLoading: true
    });
  },

  // 视频元数据加载完成
  onVideoLoad(e: any) {
    console.log('视频元数据加载完成:', e);
    this.setData({
      mediaLoading: false
    });
    
    // 只有在分享引导显示时才自动播放
    if (this.data.showFullScreenGuide) {
      setTimeout(() => {
        this.playShareVideo();
      }, 200);
    }
  },

  // 视频可以播放
  onVideoCanPlay(e: any) {
    console.log('视频可以播放:', e);
    this.setData({
      mediaLoading: false
    });
    
    // 只有在分享引导显示时才播放
    if (this.data.showFullScreenGuide) {
      setTimeout(() => {
        this.playShareVideo();
      }, 500);
    }
  },

  // 视频播放时间更新
  onVideoTimeUpdate() {
    // 当视频开始播放时，确保隐藏加载状态
    if (this.data.mediaLoading) {
      this.setData({
        mediaLoading: false
      });
    }
  },

  // 强制显示视频（调试用）
  forceShowVideo() {
    console.log('强制显示视频，URL:', this.data.shareContent?.share_media_url);
    this.setData({
      mediaLoading: false
    });
  },

  // 播放分享视频
  playShareVideo() {
    try {
      const videoContext = wx.createVideoContext('shareVideo', this);
      if (videoContext) {
        console.log('尝试播放视频...');
        videoContext.play();
      }
    } catch (error) {
      console.error('播放视频失败:', error);
    }
  },

  // 停止分享视频
  stopShareVideo() {
    try {
      const videoContext = wx.createVideoContext('shareVideo', this);
      if (videoContext) {
        console.log('停止视频播放...');
        videoContext.pause();
        // 将视频时间重置到开始位置
        videoContext.seek(0);
      }
    } catch (error) {
      console.error('停止视频失败:', error);
    }
  },

  // 视频播放事件
  onVideoPlay() {
    console.log('视频开始播放');
  },

  // 视频暂停事件
  onVideoPause() {
    console.log('视频暂停播放');
  },

  // 视频缓冲事件：网络波动时标记缓冲，避免音画不同步体感
  onVideoWaiting(e: any) {
    console.log('视频缓冲中:', e);
    this.setData({
      mediaLoading: true
    });
  },

  // 切换视频静音状态
  toggleVideoMute() {
    try {
      const videoContext = wx.createVideoContext('shareVideo', this);
      const newMutedState = !this.data.videoMuted;
      
      if (videoContext) {
        // 注意：小程序的video组件没有直接的mute方法
        // 我们需要通过重新设置video的属性来实现静音
        console.log(newMutedState ? '静音视频' : '开启声音');
        this.setData({
          videoMuted: newMutedState
        });
        
        // 通过重新渲染来应用静音状态
        const shareContent = this.data.shareContent;
        if (shareContent) {
          this.setData({
            shareContent: { ...shareContent }
          });
        }
        
        wx.showToast({
          title: newMutedState ? '已静音' : '已开声',
          icon: 'none',
          duration: 1000
        });
      }
    } catch (error) {
      console.error('切换静音失败:', error);
    }
  }
});

export {};

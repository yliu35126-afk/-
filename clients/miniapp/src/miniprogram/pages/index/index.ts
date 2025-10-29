// pages/index/index.ts - 抽奖页面
const config = require('../../config/api.js');
import { resolveImageUrl, mapPrizeImage } from '../../utils/image';
import { FALLBACK_DEVICE_ID, DEFAULT_LOTTERY_AMOUNT } from '../../utils/defaults';

interface PayOrderData {
  orderId: string;
  timeStamp: string;
  nonceStr: string;
  package: string;
  signType: string;
  paySign: string;
}

interface PrizeItem {
  id: number;
  name: string;
  price: number;
  original_price?: string;
  activity_price?: string;
  image: string;
  description: string;
  stock_quantity?: number;
  is_depleted?: number; // 0: 有库存, 1: 已耗尽
}

// 明确页面内的用户信息结构，避免将对象赋给 null 类型
interface UserInfo {
  nickName: string;
  avatarUrl: string;
  userId?: string;
  openid?: string;
}

Page({
  data: {
    userInfo: null as UserInfo | null,
    isLoggedIn: false,
    showLoginCard: true,
    isRelease: false,
    isAudit: false,
    deviceId: '', // 真实设备码：用于显示与分享路径（不被兜底覆盖）
    originalDeviceId: '', // 原始扫码真实设备码：用于支付请求
    uiDeviceId: '', // UI用设备码：用于金额/奖品/分享内容请求，可兜底
    lotteryAmount: 100, // 默认金额，单位分
    showAmountSelector: false, // 是否显示金额选择器
    availableAmounts: [] as number[], // 可选金额列表
    selectedAmount: null as number | null, // 当前选中的金额
    currentPrizes: [] as PrizeItem[], // 当前选中金额对应的奖品列表
    // 新增：配置信息
    configId: null as number | null, // 配置ID
    configName: '', // 配置名称
    totalSupplierPrizes: 0, // 供应商奖品总数
    totalMerchantPrizes: 0, // 商家奖品总数
    totalPrizes: 0, // 总奖品数
    isDemoMode: false, // 无奖品时演示模式提示开关
    // 分享内容相关
    shareContent: null as any // 分享内容数据
  },

  onLoad(options: any) {
    console.log('首页加载', options)
    
    // 检查小程序版本更新（强制更新）
    this.checkForUpdate();
    
    // 记录是否为发布版与是否为线上环境，用于隐藏环境入口
    try {
      const info = wx.getAccountInfoSync && wx.getAccountInfoSync();
      const ver = info && info.miniProgram && info.miniProgram.envVersion;
      const env = require('../../config/environment.js');
      this.setData({ isRelease: ver === 'release', isOnline: env.IS_ONLINE, isAudit: !!env.IS_AUDIT });
    } catch (_) {}

    // 处理扫码进入的参数
    this.handleScanParams(options);

    // 统一兜底：无设备码时启用 DEV_TEST_DEFAULT + config_id=26
    this.ensureUniversalFallbackDevice();
    // 若已有设备码，校验配置可用性（不强制切换）
    if (this.data.deviceId) { this.ensureFallbackDeviceIfNeeded(); }
    
    this.checkLoginStatus()
  },

  onShow() {
    // 强制清除奖品相关缓存，确保每次进入页面都获取最新数据
    wx.removeStorageSync('prizes_cache');
    wx.removeStorageSync('device_prizes_cache');
    
    // 清除当前奖品状态，强制重新获取
    this.setData({
      currentPrizes: [],
      selectedAmount: null
    });
    
    console.log('页面显示：已清除奖品缓存，将重新获取最新数据');
    
    this.checkLoginStatus()
  },

  // 处理扫码参数
  handleScanParams(options: any) {
    console.log('首页处理扫码参数:', options);
    // 获取 app 中的扫码参数
    const app = getApp();
    const scanParams = app.globalData.scanParams || {};
    // 合并页面参数和 app 参数
    const allParams = { ...scanParams, ...options };
    console.log('首页合并后的参数:', allParams);
    // 支持 device_id 或 device 参数（包括 options.query）
    let deviceId = '';
    // 兼容：scene 为纯设备码（如 DEV_XXXX）
    if (!deviceId && typeof allParams.scene === 'string' && /^DEV_[A-F0-9]+$/i.test(allParams.scene)) {
      deviceId = allParams.scene;
    }
    if (!deviceId && options && options.query && options.query.device_id) {
      deviceId = options.query.device_id;
    }
    if (allParams.device_id) {
      deviceId = allParams.device_id;
    } else if (allParams.device) {
      deviceId = allParams.device;
    }
    // 无扫码参数的情况，兜底逻辑由 ensureUniversalFallbackDevice 统一处理
    this.setData({ deviceId, originalDeviceId: deviceId, uiDeviceId: deviceId });
    console.log('检测到设备ID:', deviceId);
    
    // 保存设备码到全局和本地存储
    app.globalData.currentDeviceCode = deviceId;
    wx.setStorageSync('currentDeviceCode', deviceId);
    wx.setStorageSync('deviceCode', deviceId); // 兼容性
    // 额外保存原始扫码设备码，避免被兜底逻辑覆盖
    app.globalData.scannedDeviceCode = deviceId;
    wx.setStorageSync('scannedDeviceCode', deviceId);
    console.log('设备码已保存到存储:', deviceId);
    
    // 获取设备分享内容（线上环境要求必须有有效设备码）：仅使用 UI 设备码
    if (this.data.uiDeviceId) {
      this.getShareContent(this.data.uiDeviceId);
    } else {
      console.warn('设备码为空，跳过分享内容获取');
    }
  },

  // 移除统一兜底设备逻辑：不再自动使用 DEV_TEST_DEFAULT
  ensureUniversalFallbackDevice() {
    try {
      const currentId = String(this.data.deviceId || this.data.uiDeviceId || '').trim();
      if (currentId) { return; }

      let deviceId = wx.getStorageSync('device_id');
      if (!deviceId) {
        console.warn(`⚙️ 未检测到扫码设备，自动使用系统设备 ${FALLBACK_DEVICE_ID}`);
        deviceId = FALLBACK_DEVICE_ID;
        wx.setStorageSync('device_id', deviceId);
      }

      const app = getApp();
      this.setData({ deviceId: deviceId, uiDeviceId: deviceId });
      app.globalData.currentDeviceCode = deviceId;
      wx.setStorageSync('currentDeviceCode', deviceId);
      wx.setStorageSync('deviceCode', deviceId);
    } catch (e) {
      console.warn('设备检查异常:', e);
    }
  },

  // 校验当前设备是否有可用分润配置；如无则切换到兜底设备
  ensureFallbackDeviceIfNeeded() {
    const request = require('../../utils/request.js');
    const currentId = String(this.data.uiDeviceId || this.data.deviceId || '').trim();
    if (!currentId) { return; }

    request.request({
      // 后端暂不提供 device/profitconfig，改为查询设备是否绑定抽奖盘奖品
      url: config.api.getDeviceConfig, // 实际为 device/profitprizes
      method: 'GET',
      data: { device_id: currentId },
      needAuth: false
    }).then((res: any) => {
      if (res.code === 1) {
        // 新逻辑：根据返回的奖品列表判断设备是否已绑定抽奖盘
        const list = (res.data && (res.data.list || res.data.items || res.data)) || [];
        const hasPrizes = Array.isArray(list) ? list.length > 0 : false;
        if (!hasPrizes) {
          // 不再强制切换到测试兜底设备，保留当前设备并提示
          console.warn('当前设备暂无奖品配置，保持当前设备，不切换兜底设备');
        }
      }
    }).catch(() => {
      // 网络错误不强制切换；由后续流程重试
    });
  },

  // 检查登录状态
  checkLoginStatus() {
    const token = wx.getStorageSync('token');
    const userInfo = wx.getStorageSync('userInfo') as UserInfo | null;
    
    if (token && userInfo) {
      this.setData({
        isLoggedIn: true,
        userInfo: userInfo,
        showLoginCard: false
      });
    } else {
      this.setData({
        isLoggedIn: false,
        userInfo: null,
        showLoginCard: true
      });
    }
  },

  // 直接登录（在用户点击时调用）
  doDirectLogin() {
    // 直接获取用户信息，因为这是在用户点击事件中
    wx.getUserProfile({
      desc: '用于完善用户资料',
      success: (userRes) => {
        console.log('获取用户信息成功:', userRes);
        
        // 获取登录code
        wx.login({
          success: (loginRes) => {
            if (loginRes.code) {
              console.log('获取code成功:', loginRes.code);
              // 调用登录接口
              this.doLogin(loginRes.code, userRes.userInfo);
            } else {
              console.error('获取code失败:', loginRes);
              wx.showToast({
                title: '登录失败，请重试',
                icon: 'none'
              });
            }
          },
          fail: (err) => {
            console.error('微信登录失败:', err);
            wx.showToast({
              title: '登录失败，请重试',
              icon: 'none'
            });
          }
        });
      },
      fail: (err) => {
        console.error('获取用户信息失败:', err);
        wx.showToast({
          title: '需要授权才能使用',
          icon: 'none'
        });
      }
    });
  },

  // 微信登录
  wxLogin() {
    // 先获取登录code
    wx.login({
      success: (res) => {
        if (res.code) {
          console.log('获取code成功:', res.code);
          
          // 获取用户信息
          wx.getUserProfile({
            desc: '用于完善用户资料',
            success: (userRes) => {
              console.log('获取用户信息成功:', userRes);
              
              // 调用登录接口
              this.doLogin(res.code, userRes.userInfo);
            },
            fail: (err) => {
              console.error('获取用户信息失败:', err);
              wx.showToast({
                title: '需要授权才能使用',
                icon: 'none'
              });
            }
          });
        } else {
          console.error('获取code失败:', res);
        }
      },
      fail: (err) => {
        console.error('微信登录失败:', err);
      }
    });
  },

  // 执行登录
  doLogin(code: string, userInfo: any) {
    wx.showLoading({ title: '登录中...' });
    
    // 调用已有的后端登录接口（使用顶部已引入的 config）
    
    wx.request({
      url: config.apiUrl + config.api.wechatLogin,
      method: 'POST',
      data: {
        code: code,
        userInfo: userInfo
      },
      success: (res: any) => {
        wx.hideLoading();
        
        console.log('=== 首页登录API响应 ===');
        console.log('状态码:', res.statusCode);
        console.log('响应数据:', res.data);
        console.log('========================');
        
        if (res.statusCode === 200 && res.data && res.data.code === 1) {
          const loginData = res.data.data;
          
          console.log('登录成功，返回的数据:', loginData);
          console.log('token:', loginData.token);
          console.log('openid:', loginData.openid);
          console.log('user_id:', loginData.user_id);
          console.log('nickname:', loginData.nickname);
          console.log('avatar:', loginData.avatar);
          
          // 保存完整的登录信息
          wx.setStorageSync('token', loginData.token);
          wx.setStorageSync('openid', loginData.openid);  // 使用后端返回的openid
          
          // 构造用户信息对象
          const savedUserInfo = {
            nickName: loginData.nickname || (userInfo ? userInfo.nickName : ''),
            avatarUrl: loginData.avatar || (userInfo ? userInfo.avatarUrl : ''),
            userId: loginData.user_id,
            openid: loginData.openid
          };
          
          wx.setStorageSync('userInfo', savedUserInfo);
          
          console.log('=== 保存到本地存储的信息 ===');
          console.log('token:', wx.getStorageSync('token'));
          console.log('openid:', wx.getStorageSync('openid'));
          console.log('userInfo:', wx.getStorageSync('userInfo'));
          console.log('==============================');
          
          this.setData({
            isLoggedIn: true,
            userInfo: savedUserInfo,
            showLoginCard: false
          });
          
          wx.showToast({
            title: '登录成功',
            icon: 'success',
            success: () => {
              // 登录成功后重新走抽奖流程（先检测设备和金额）
              setTimeout(() => {
                this.startLottery();
              }, 1000);
            }
          });
        } else {
          console.error('登录失败，API返回:', res.data);
          wx.showToast({
            title: res.data.msg || res.data.message || '登录失败',
            icon: 'none'
          });
        }
      },
      fail: (err) => {
        wx.hideLoading();
        console.error('登录请求失败:', err);
        wx.showToast({
          title: '网络错误，请重试',
          icon: 'none'
        });
      }
    });
  },

  // 启动抽奖 - 主要功能（强制登录拦截）
  async startLottery() {
    console.log('付款抽奖按钮被点击');
    const app = getApp<IAppOption>();

    // 1) 等待全局登录流程完成（防止竞态：点击过快时 token 还未落地）
    try { await app.globalData.loginReady; } catch(_) {}

    // 2) 强制检查 token，没有就先登录
    let token = app.globalData.token || wx.getStorageSync('token');
    if (!token) {
      wx.showLoading({ title: '正在登录…' });
      const ok = typeof (app as any).ensureLogin === 'function' ? await (app as any).ensureLogin() : true;
      wx.hideLoading();
      if (!ok) {
        wx.showToast({ title: '请先登录后再抽奖', icon: 'none' });
        return;
      }
      token = app.globalData.token || wx.getStorageSync('token');
    }

    // 3) 尝试补全用户信息（不阻塞抽奖）
    let user = wx.getStorageSync('userInfo');
    if (!user || !user.userId) {
      try {
        const req = require('../../utils/request.js');
        const apiCfg = require('../../config/api.js');
        const info = await req.request({
          url: apiCfg.api.getUserInfo,
          method: 'GET',
          needAuth: true,
          showLoading: false
        });
        const d = (info && info.data) || {};
        const fixed = {
          nickName: d.nickname || d.nickName || (user && user.nickName) || '',
          avatarUrl: d.avatar || d.avatarUrl || (user && user.avatarUrl) || '',
          userId: d.user_id || d.id || (user && user.userId) || '',
          openid: d.openid || (user && user.openid) || ''
        };
        wx.setStorageSync('userInfo', fixed);
        user = fixed;
      } catch (e) {
        // 不终止流程：有token即可继续
        console.warn('补拉用户信息失败，继续抽奖', e);
      }
    }

    // 4) 到这里才允许：拉取金额/奖品 → 创建支付订单
    this.getAvailableAmounts();
  },

  // 获取可选金额
  getAvailableAmounts() {
    if (!this.data.uiDeviceId && !this.data.deviceId) {
      console.warn('未检测到设备码，自动启用兜底设备');
      this.ensureUniversalFallbackDevice();
    }
    wx.showLoading({ title: '获取金额选项...' });
    
    const request = require('../../utils/request.js');
    
    request.request({
      url: config.api.getDeviceConfig,
      method: 'GET',
      data: { device_id: this.data.uiDeviceId || this.data.deviceId },
      needAuth: false
    }).then((res: any) => {
      wx.hideLoading();
      if (res.code === 1) {
        let amounts = res.data.lottery_amounts;
        // 兼容后端返回为字符串或数组的情况，统一为数字数组
        if (!Array.isArray(amounts)) {
          if (typeof amounts === 'string') {
            try {
              // 优先尝试 JSON 解析
              const parsed = JSON.parse(amounts);
              amounts = Array.isArray(parsed) ? parsed : String(amounts).split(/[,，\s]+/);
            } catch (e) {
              amounts = String(amounts).split(/[,，\s]+/);
            }
          } else if (amounts == null) {
            amounts = [];
          } else {
            amounts = [amounts];
          }
        }
        // 归一化为数字并去重，过滤非法值
        amounts = (amounts as any[])
          .map((v) => Number(v))
          .filter((v) => !isNaN(v) && v > 0);
        // 去重 + 排序（升序，展示友好）
        amounts = Array.from(new Set(amounts as number[])).sort((a: number, b: number) => a - b);
        if (!amounts || amounts.length === 0) {
          // 清空配置时仅显示系统默认金额 0.01
          const defaultAmount = DEFAULT_LOTTERY_AMOUNT;
          this.setData({ 
            availableAmounts: [defaultAmount],
            selectedAmount: defaultAmount,
            showAmountSelector: true 
          });
          // 尝试获取该金额的奖品（可能为空，UI会提示）
          this.getPrizesForAmount(defaultAmount);
          return;
        }
        this.setData({ 
          availableAmounts: amounts,
          showAmountSelector: true 
        });
        // 默认获取第一个金额的奖品
        if (amounts.length > 0) {
          this.getPrizesForAmount(amounts[0]);
        }
      } else {
        wx.showModal({
          title: '获取失败',
          content: res.msg || '获取金额选项失败，请重试',
          showCancel: false
        });
      }
    }).catch((error: any) => {
      wx.hideLoading();
      wx.showModal({
        title: '网络错误',
        content: '网络连接失败，请检查网络后重试',
        showCancel: false
      });
      console.error('获取金额失败:', error);
    });
  },

  // 获取指定金额的奖品信息
  getPrizesForAmount(amount: number) {
    if (!this.data.uiDeviceId && !this.data.deviceId) {
      console.warn('设备ID为空，自动启用兜底设备以获取奖品');
      this.ensureUniversalFallbackDevice();
    }
    
    console.log('获取金额奖品信息:', amount, '元');
    wx.showLoading({ title: '加载奖品...' });
    
    wx.request({
        url: config.apiUrl + config.api.getDevicePrizes,
        method: 'GET',
        data: {
          device_id: this.data.uiDeviceId || this.data.deviceId,
          lottery_amount: amount
        },
      success: (res: any) => {
        wx.hideLoading();
        if (res.statusCode === 200 && res.data && res.data.code === 1) {
          const data = res.data.data;
          
          // 提取新的配置信息
          const configInfo = {
            configId: data.config_id || null,
            configName: data.config_name || '',
            totalSupplierPrizes: data.total_supplier_prizes || 0,
            totalMerchantPrizes: data.total_merchant_prizes || 0,
            totalPrizes: data.total_prizes || 0
          };
          
          // 自动合并：商家+供应商一起合并，去重后限制为16
          const normalizePrize = (prize: any) => {
            const normalizedStock = prize.stock_quantity !== undefined ? Number(prize.stock_quantity) : 0;
            const isDepleted = prize.is_depleted !== undefined
              ? prize.is_depleted
              : (prize.stock_quantity !== undefined ? (normalizedStock <= 0 ? 1 : 0) : 0);
            const processedPrize = mapPrizeImage({
              ...prize,
              image: resolveImageUrl(prize.image || prize.pic_url || prize.prize_image, prize.id || prize.name),
              stock_quantity: normalizedStock,
              is_depleted: isDepleted
            });
            console.log(`奖品 ${prize.id} (${prize.name}): stock=${processedPrize.stock_quantity}, is_depleted=${processedPrize.is_depleted}`);
            return processedPrize;
          };

          const merged = [
            ...(data.merchant_prizes || []).map(normalizePrize),
            ...(data.supplier_prizes || []).map(normalizePrize)
          ];
          const finalList: any[] = [];
          const seen = new Set<number>();
          for (const p of merged) {
            const id = Number(p.id || 0);
            if (id > 0 && !seen.has(id)) { seen.add(id); finalList.push(p); }
            if (finalList.length >= 16) break;
          }
          
          console.log('获取到配置信息:', configInfo);
          console.log('获取到奖品数据(自动合并, 限制16):', finalList);
          console.log('供应商奖品数量:', data.supplier_prizes && data.supplier_prizes.length ? data.supplier_prizes.length : 0);
          console.log('商家奖品数量:', data.merchant_prizes && data.merchant_prizes.length ? data.merchant_prizes.length : 0);
          
          this.setData({ 
            currentPrizes: finalList,
            selectedAmount: amount,
            configId: configInfo.configId,
            configName: configInfo.configName,
            totalSupplierPrizes: configInfo.totalSupplierPrizes,
            totalMerchantPrizes: configInfo.totalMerchantPrizes,
            totalPrizes: configInfo.totalPrizes,
            isDemoMode: finalList.length === 0
          });
          
          // 显示配置信息
          wx.showToast({
            title: `加载完成: ${configInfo.configName}`,
            icon: 'success',
            duration: 2000
          });
          
        } else {
          console.error('获取奖品失败:', res.data);
          wx.showModal({
            title: '获取失败',
            content: (res.data && res.data.msg) || '获取奖品信息失败，请重试',
            showCancel: false
          });
        }
      },
      fail: (err) => {
        wx.hideLoading();
        console.error('获取奖品请求失败:', err);
        wx.showModal({
          title: '网络错误',
          content: '网络连接失败，请检查网络后重试',
          showCancel: false
        });
      }
    });
  },

  // 金额按钮点击事件（新增：获取奖品信息）
  onAmountTap(e: any) {
    const amount = e.currentTarget.dataset.amount;
    console.log('点击金额按钮:', amount, '元');
    // 先设置选中金额，确保可点击确认支付；再获取该金额对应的奖品信息
    this.setData({ selectedAmount: amount });
    // 不关闭弹出层
    this.getPrizesForAmount(amount);
  },
  // 选择金额并确认支付
  selectAmount(e: any) {
    const amount = e.currentTarget.dataset.amount;
    console.log('用户确认选择金额:', amount, '元');
    
    // 将元转为分，传给后端
    const amountInCents = Math.round(amount * 100);
    this.setData({ 
      lotteryAmount: amountInCents,  // 保存分
      showAmountSelector: false 
    });
    console.log('设置支付金额:', amountInCents, '分 (', amount, '元)');
    // 开始支付流程
    this.requestPayment();
  },

  // 取消选择金额
  cancelAmountSelection() {
    this.setData({ 
      showAmountSelector: false,
      selectedAmount: null,
      currentPrizes: [],
      isDemoMode: false
    });
  },

  // 发起微信支付
  requestPayment() {
    wx.showLoading({ title: '正在下单...' });
    // 1. 先创建订单（调用后端接口），用最新的lotteryAmount
    this.createOrder()
      .then((orderData: PayOrderData) => {
        wx.hideLoading();
        console.log('订单创建成功:', orderData);
        // 测试模式：后端已自动确认支付且返回测试参数，前端不调起微信支付
        if ((orderData as any).isTest || (typeof orderData.package === 'string' && orderData.package.indexOf('test') !== -1)) {
          wx.showToast({ title: '测试模式：已确认支付', icon: 'success' });
          setTimeout(() => {
            const configIdForNav = (this.data.uiDeviceId && this.data.deviceId && this.data.uiDeviceId === this.data.deviceId)
              ? (this.data.configId || '')
              : '';
            wx.navigateTo({
              url: `/pages/game-launcher/game-launcher?orderId=${orderData.orderId}&amount=${(this.data.lotteryAmount / 100).toFixed(2)}&paymentTime=${orderData.timeStamp}&configId=${configIdForNav}`
            });
          }, 1200);
          return;
        }
        // 2. 发起微信支付
        console.log('=== 支付参数详情 ===');
        console.log('timeStamp:', orderData.timeStamp);
        console.log('nonceStr:', orderData.nonceStr);
        console.log('package:', orderData.package);
        console.log('signType:', orderData.signType);
        console.log('paySign:', orderData.paySign);
        console.log('==================');
        // 防御：若支付参数缺失，则按测试模式处理，避免错误调起
        if (!orderData.timeStamp || !orderData.nonceStr || !orderData.package || !orderData.paySign) {
          wx.showToast({ title: '支付参数缺失，自动按测试模式处理', icon: 'none' });
          setTimeout(() => {
            const configIdForNav = (this.data.uiDeviceId && this.data.deviceId && this.data.uiDeviceId === this.data.deviceId)
              ? (this.data.configId || '')
              : '';
            wx.navigateTo({
              url: `/pages/game-launcher/game-launcher?orderId=${orderData.orderId || ''}&amount=${(this.data.lotteryAmount / 100).toFixed(2)}&paymentTime=${String(Date.now()/1000)}&configId=${configIdForNav}`
            });
          }, 1200);
          return;
        }
        // 检查运行环境
        const accountInfo = wx.getAccountInfoSync();
        console.log('小程序环境信息:', accountInfo);
        wx.requestPayment({
          timeStamp: orderData.timeStamp,
          nonceStr: orderData.nonceStr,
          package: orderData.package,
          signType: orderData.signType as 'MD5' | 'HMAC-SHA256' | 'RSA',
          paySign: orderData.paySign,
          // 移除 total_fee 参数，微信支付不需要此参数
          success: (res) => {
            console.log('支付成功:', res);
            wx.showToast({
              title: '支付成功，进入抽奖！',
              icon: 'success',
              duration: 2000,
              success: () => {
                // 支付成功后跳转到游戏启动器（抽奖页面）
                setTimeout(() => {
                  const configIdForNav = (this.data.uiDeviceId && this.data.deviceId && this.data.uiDeviceId === this.data.deviceId)
                    ? (this.data.configId || '')
                    : '';
                  wx.navigateTo({
                    url: `/pages/game-launcher/game-launcher?orderId=${orderData.orderId}&amount=${(this.data.lotteryAmount / 100).toFixed(2)}&paymentTime=${orderData.timeStamp}&configId=${configIdForNav}`
                  });
                }, 1500);
              }
            });
          },
          fail: (err) => {
            console.error('支付失败:', err);
            
            let errorMsg = '支付失败';
            if (err.errMsg) {
              if (err.errMsg.includes('cancel')) {
                errorMsg = '支付已取消';
              } else if (err.errMsg.includes('fail')) {
                errorMsg = '支付失败，请重试';
              }
            }
            
            wx.showModal({
              title: '支付失败',
              content: errorMsg,
              showCancel: false
            });
          }
        });
      })
      .catch((error: any) => {
        wx.hideLoading();
        console.error('创建订单失败:', error);
        
        let errorMsg = '创建订单失败';
        if (error.message) {
          errorMsg = error.message;
        } else if (error.errMsg) {
          if (error.errMsg.includes('timeout')) {
            errorMsg = '网络超时，请重试';
          } else if (error.errMsg.includes('fail')) {
            errorMsg = '网络连接失败，请检查网络';
          }
        }
        
        wx.showModal({
          title: '订单创建失败',
          content: errorMsg,
          showCancel: false
        });
      });
  },

  // 创建订单
  createOrder(): Promise<PayOrderData> {
    return new Promise((resolve, reject) => {
      const sendCreate = (authToken: string) => {
        // 金额单位统一为分：selectAmount 已将金额转成分存入 lotteryAmount
        let amount = Number(this.data.lotteryAmount) || 0;
        // 兼容旧逻辑：如果 lotteryAmount 仍是元或未设置，则用 selectedAmount 转分
        if (amount <= 0) {
          const yuan = Number(this.data.selectedAmount || 1);
          amount = Math.round(yuan * 100);
        }

        // 获取设备码
        const app = getApp();
        // 优先使用原始扫码设备码，避免被兜底逻辑覆盖
        const deviceCode = this.data.originalDeviceId || app.globalData.scannedDeviceCode || wx.getStorageSync('scannedDeviceCode') || app.globalData.currentDeviceCode || wx.getStorageSync('currentDeviceCode') || wx.getStorageSync('deviceCode') || '';

        console.log('准备发起支付请求，金额:', amount, '分 (', (amount/100).toFixed(2), '元)');
        console.log('设备码:', deviceCode);
        console.log('请求URL:', config.apiUrl + config.api.createPayOrder);

        // 构造下单参数：仅当 UI 设备与真实设备一致时才传递 config_id，避免设备与配置不匹配
        const payload: any = {
          amount: amount, // 单位分
          body: '抽奖支付', // 商品描述
          user_id: wx.getStorageSync('openid'), // 发送user_id而不是openid
          device_id: deviceCode, // 添加设备码
          // 默认真实支付模式（统一标准）
          force_real: 1
        };
        if (this.data.configId && this.data.uiDeviceId && this.data.deviceId && this.data.uiDeviceId === this.data.deviceId) {
          payload.config_id = this.data.configId;
        }

        wx.request({
          url: config.apiUrl + config.api.createPayOrder,
          method: 'POST',
          header: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          },
          data: payload,
          success: (res: any) => {
            console.log('后端返回数据:', res.data);
            // 如果401，则静默登录续期后重试一次
            if (res.statusCode === 401 || (res.data && res.data.code === 401)) {
              console.warn('Token无效或过期，尝试自动登录并重试创建订单');
              wx.login({
                success: (loginRes) => {
                  const code = loginRes.code;
                  wx.request({
                    url: config.apiUrl + config.api.wechatLogin,
                    method: 'POST',
                    data: { code },
                    success: (loginResp: any) => {
                      const newToken = (loginResp.data && (loginResp.data.token || (loginResp.data.data && loginResp.data.data.token))) || '';
                      if (newToken) {
                        wx.setStorageSync('token', newToken);
                        // 使用新token重试一次
                        sendCreate(newToken);
                      } else {
                        reject(new Error('登录失败，未返回token'));
                      }
                    },
                    fail: () => {
                      reject(new Error('登录失败，请重试'));
                    }
                  });
                },
                fail: () => {
                  reject(new Error('登录失败，请重试'));
                }
              });
              return;
            }

            if (res.data && res.data.code === 1) {
              // 兼容两种返回结构：参数对象可能在 data 或 msg 中
              const paymentData = (res.data.data && typeof res.data.data === 'object')
                ? res.data.data
                : ((res.data.msg && typeof res.data.msg === 'object') ? res.data.msg : null);
              console.log('解析的支付数据:', paymentData);
              if (!paymentData) {
                reject(new Error('支付参数缺失'));
                return;
              }
              const isTest = typeof paymentData.package === 'string' && paymentData.package.indexOf('test') !== -1;
              resolve({
                orderId: paymentData.orderId,
                timeStamp: paymentData.timeStamp,
                nonceStr: paymentData.nonceStr,
                package: paymentData.package,
                signType: paymentData.signType,
                paySign: paymentData.paySign,
                // @ts-ignore
                isTest
              });
            } else {
              console.error('支付订单创建失败:', res.data);
              reject(new Error((res.data && res.data.msg) || '创建订单失败'));
            }
          },
          fail: (err) => {
            console.error('请求失败:', err);
            reject(new Error('网络请求失败'));
          }
        });
      };

      const token = wx.getStorageSync('token');
      if (!token) {
        console.warn('本地token为空，先静默登录再创建订单');
        wx.login({
          success: (loginRes) => {
            const code = loginRes.code;
            wx.request({
              url: config.apiUrl + config.api.wechatLogin,
              method: 'POST',
              data: { code },
              success: (loginResp: any) => {
                const newToken = (loginResp.data && (loginResp.data.token || (loginResp.data.data && loginResp.data.data.token))) || '';
                if (newToken) {
                  wx.setStorageSync('token', newToken);
                  sendCreate(newToken);
                } else {
                  reject(new Error('登录失败，未返回token'));
                }
              },
              fail: () => {
                reject(new Error('登录失败，请重试'));
              }
            });
          },
          fail: () => {
            reject(new Error('登录失败，请重试'));
          }
        });
      } else {
        sendCreate(token);
      }
    });
  },

  // 获取设备分享内容
  getShareContent(deviceId: string): Promise<void> {
    return new Promise((resolve, reject) => {
      if (!deviceId) {
        console.log('设备码为空，无法获取分享内容');
        reject(new Error('设备码为空'));
        return;
      }

      console.log('获取分享内容，设备码:', deviceId);
      
      wx.request({
        url: config.apiUrl + config.api.getShareContent,
        method: 'GET',
        data: {
          device_id: deviceId
        },
        success: (res: any) => {
          console.log('分享内容API响应:', res);
          
          if (res.statusCode === 200 && res.data && res.data.code === 1) {
            const shareData = res.data.data;
            
            // 处理图片/视频URL
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
            
            this.setData({
              shareContent: shareContent
            });
            
            console.log('首页分享内容已获取:', shareContent);
            resolve();
          } else {
            console.error('获取分享内容失败:', res.data);
            // 不再切换到测试兜底设备获取分享内容，直接结束
            resolve();
          }
        },
        fail: (err: any) => {
          console.error('获取分享内容请求失败:', err);
          reject(err);
        }
      });
    });
  },

  // 分享功能
  onShareAppMessage() {
    let title = '异企趣玩 - 抽奖啦！';
    let desc = '快来一起抽奖，好运等着你！';
    let imageUrl = '';
    let path = '/pages/index/index';
    
    // 优先使用API返回的分享内容
    if (this.data.shareContent && this.data.shareContent.has_share_content) {
      title = this.data.shareContent.share_title || this.data.shareContent.share_text || title;
      desc = this.data.shareContent.share_text || desc;
      
      console.log('🎬 检测到媒体类型:', this.data.shareContent.media_type);
      console.log('🔗 媒体URL:', this.data.shareContent.share_media_url);
      
      // 如果是视频，微信分享不支持，需要特殊处理
      if (this.data.shareContent.media_type === 'video') {
        console.log('⚠️  检测到视频文件，微信分享不支持视频，将使用默认图片');
        // 微信分享不支持视频，使用默认的分享图片或者空
        imageUrl = ''; // 可以设置一个默认的分享图片
        
        // 可以在标题或描述中提示这是视频内容
        if (!title.includes('视频')) {
          title = `🎬 ${title}`;
        }
      } else {
        // 图片类型正常使用
        imageUrl = this.data.shareContent.share_media_url || '';
        console.log('📷 使用图片分享:', imageUrl);
      }
      
      // 如果有设备ID，传递给分享页面
      if (this.data.deviceId) {
        path = `/pages/index/index?device_id=${this.data.deviceId}`;
      }
    }
    
    console.log('📤 最终分享参数:');
    console.log('   标题:', title);
    console.log('   描述:', desc); 
    console.log('   图片:', imageUrl);
    console.log('   路径:', path);
    
    return {
      title: title,
      desc: desc,
      path: path,
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
      // 朋友圈分享优先使用分享内容（share_text），而不是标题
      const shareTitle = this.data.shareContent.share_title || '';
      const shareText = this.data.shareContent.share_text || '';
      
      // 优先级：分享内容 > 分享标题 > 默认标题
      if (shareText) {
        title = shareText;
      } else if (shareTitle) {
        title = shareTitle;
      }
      
      console.log('🎬 朋友圈分享 - 媒体类型:', this.data.shareContent.media_type);
      
      // 如果是视频，朋友圈分享也不支持
      if (this.data.shareContent.media_type === 'video') {
        console.log('⚠️  朋友圈分享不支持视频，将使用默认图片');
        imageUrl = ''; // 朋友圈不支持视频，使用默认图片
        
        // 在标题中标注这是视频内容
        if (!title.includes('视频')) {
          title = `🎬 ${title}`;
        }
      } else {
        // 图片类型正常使用
        imageUrl = this.data.shareContent.share_media_url || '';
        console.log('📷 朋友圈使用图片:', imageUrl);
      }
      
      // 如果有设备ID，传递给分享页面
      if (this.data.deviceId) {
        query = `device_id=${this.data.deviceId}`;
      }
    }
    
    console.log('📤 朋友圈最终分享参数:');
    console.log('   标题:', title);
    console.log('   图片:', imageUrl);
    console.log('   查询参数:', query);
    
    return {
      title: title,
      imageUrl: imageUrl,
      query: query,
      path: `/pages/timeline-share/timeline-share?device_id=${this.data.deviceId || ''}` // 跳转到朋友圈单页面
    };
  },

  // 环境设置入口已移除
  goToEnvSettings() {
    // 空实现，避免旧绑定报错
  },

  // 检查小程序版本更新（强制更新）
  checkForUpdate() {
    console.log('🔄 开始检查小程序版本更新...');
    
    // 检查是否支持 UpdateManager
    if (!wx.canIUse('getUpdateManager')) {
      console.log('⚠️ 当前微信版本过低，不支持版本更新检查');
      return;
    }

    const updateManager = wx.getUpdateManager();

    // 监听检查更新结果
    updateManager.onCheckForUpdate((res) => {
      console.log('📋 检查更新结果:', res);
      if (res.hasUpdate) {
        console.log('🆕 发现新版本，准备下载...');
      } else {
        console.log('✅ 当前已是最新版本');
      }
    });

    // 监听新版本下载完成
    updateManager.onUpdateReady(() => {
      console.log('⬇️ 新版本下载完成');
      
      // 强制更新：不给用户选择，直接提示并更新
      wx.showModal({
        title: '版本更新',
        content: '新版本已下载完成，需要重启应用以使用新功能',
        showCancel: false, // 不显示取消按钮，强制更新
        confirmText: '立即更新',
        success: (modalRes) => {
          if (modalRes.confirm) {
            console.log('🔄 用户确认更新，正在重启应用...');
            // 应用新版本并重启
            updateManager.applyUpdate();
          }
        }
      });
    });

    // 监听新版本下载失败
    updateManager.onUpdateFailed(() => {
      console.error('❌ 新版本下载失败');
      
      wx.showModal({
        title: '更新失败',
        content: '新版本下载失败，请检查网络连接后重试。点击确定将尝试重新下载。',
        showCancel: false,
        confirmText: '重新尝试',
        success: (modalRes) => {
          if (modalRes.confirm) {
            // 重新检查更新
            console.log('🔄 重新检查更新...');
            this.checkForUpdate();
          }
        }
      });
    });
  }
});

export {};

// pages/verification/index.ts
const apiConfig = require('../../config/api.js');

Page({
  data: {
    manualCode: '',
    resultMsg: '',
    roleText: '',
    requiresPin: false,
    pin: '',
    isAdmin: false,
    isMerchant: false,
    isSupplier: false
  },

  // 支持从微信「扫一扫」进入核销页的自动核销
  onLoad(options: any) {
    const scene = options && options.scene ? decodeURIComponent(options.scene) : '';
    const needLogin = !wx.getStorageSync('openid') || !wx.getStorageSync('token');
    const proceed = () => {
      // 拉取当前用户角色，便于区分商家/供应商
      this.fetchRole();
      if (scene) {
        const code = this.extractCodeFromText(scene);
        if (code) {
          wx.showToast({ title: '正在自动核销', icon: 'none' });
          this.autoVerifyFromCode(code);
        }
      }
    };
    if (needLogin) {
      // 自动静默登录，贯通后端，避免“身份未登录”
      this.silentLogin().then(proceed).catch(proceed);
    } else {
      proceed();
    }
  },

  // 页面显示时也尝试同步角色（避免首次进入显示“未登录”）
  onShow() {
    const hasCred = !!wx.getStorageSync('openid') && !!wx.getStorageSync('token');
    if (hasCred) {
      this.fetchRole();
    }
  },

  // 静默登录：仅用 code 换取后端 token/openid（无需用户授权头像昵称）
  silentLogin(): Promise<void> {
    return new Promise((resolve, reject) => {
      wx.login({
        success: (res) => {
          if (!res.code) {
            reject(new Error('获取登录凭证失败'));
            return;
          }
          wx.request({
            url: apiConfig.apiUrl + apiConfig.api.wechatLogin,
            method: 'POST',
            data: { code: res.code },
            success: (resp: any) => {
              // 兼容后端返回格式：HTTP 200 且 data.code === 1
              const ok = (resp.statusCode === 200 && resp.data && resp.data.code === 1);
              const data = ok ? (resp.data.data || {}) : (resp.data || {});
              const token = data.token || wx.getStorageSync('token') || '';
              const openid = data.openid || wx.getStorageSync('openid') || '';
              if (token) wx.setStorageSync('token', token);
              if (openid) wx.setStorageSync('openid', openid);
              resolve();
            },
            fail: (err: any) => {
              reject(err);
            }
          });
        },
        fail: (err) => reject(err)
      });
    });
  },

  // 角色检测：通过 openid 判断是否为商家或供应商
  fetchRole() {
    const openid = wx.getStorageSync('openid');
    const token = wx.getStorageSync('token');
    
    if (!openid) {
      this.setData({ roleText: '未登录' });
      return;
    }
    
    // 显示加载状态
    this.setData({ roleText: '获取身份中...' });
    
    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.getRoleByOpenId}`,
      method: 'GET',
      data: { openid },
      header: {
        'Authorization': `Bearer ${token || ''}`
      },
      success: (res: any) => {
        console.log('fetchRole response:', res);
        const resp = res.data || {};
        if (res.statusCode === 401) {
          // 触发一次静默登录并重试，避免用户手动操作
          this.setData({ roleText: '重新登录中...' });
          this.silentLogin()
            .then(() => this.fetchRole())
            .catch(() => {
              this.setData({ roleText: '登录失败' });
              wx.showToast({ title: '登录已过期，请重新登录', icon: 'none' });
            });
          return;
        }
        if (resp.code === 1) {
          const roles = resp.data || {};
          const isAdmin = !!roles.is_admin;
          const isMerchant = !!roles.is_merchant;
          const isSupplier = !!roles.is_supplier;
          const roleText = isAdmin ? '管理员' : (isMerchant ? '商家' : (isSupplier ? '供应商' : '普通用户'));
          console.log('Role updated:', { roleText, isAdmin, isMerchant, isSupplier });
          this.setData({ roleText, requiresPin: isMerchant, isAdmin, isMerchant, isSupplier });
        } else {
          this.setData({ roleText: '身份获取失败' });
          console.error('fetchRole failed:', resp);
        }
      },
      fail: (error: any) => {
        console.error('fetchRole request failed:', error);
        this.setData({ roleText: '网络错误' });
      }
    });
  },

  // 扫码核销
  scanCode() {
    const scanner = require('../../utils/scanner.js');
    wx.showLoading({ title: '正在扫码...' });
    scanner.scanCode()
      .then((resp: any) => {
        wx.hideLoading();
        if (resp.code === 1) {
          const data = resp.data || {};
          if (data.can_verify) {
            const openid = wx.getStorageSync('openid');
            const orderId = data.order_id;
            const verificationCode = data.verification_code;
            this.performVerification(openid, orderId, verificationCode);
          } else {
            wx.showToast({ title: data.tips || '不可核销', icon: 'none' });
          }
        } else {
          wx.showToast({ title: resp.msg || '扫码失败', icon: 'none' });
        }
      })
      .catch((error: any) => {
        wx.hideLoading();
        wx.showToast({ title: error.message || '扫码失败', icon: 'none' });
      });
  },

  // 输入核销码
  onCodeInput(e: any) {
    this.setData({ manualCode: e.detail.value });
  },

  // 手动核销
  verifyManual() {
    const code = (this.data.manualCode || '').trim();
    if (!code) {
      wx.showToast({ title: '请输入核销码', icon: 'none' });
      return;
    }
    if (this.data.requiresPin && !this.data.pin) {
      wx.showToast({ title: '请输入店员PIN', icon: 'none' });
      return;
    }
    const openid = wx.getStorageSync('openid');
    // 手动输入场景下没有订单号，仅按核销码进行核销
    this.performVerification(openid, '', code);
  },

  // 核心核销请求（切换到代理核销接口并返回详情）
  performVerification(openid: string, orderId: string, verificationCode: string) {
    // 权限拦截：仅管理员或商家可核销
    if (!(this.data.isAdmin || this.data.isMerchant)) {
      wx.showToast({ title: '仅商家或管理员可核销', icon: 'none' });
      return;
    }
    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.verifyOrder}`,
      method: 'POST',
      data: {
        verification_code: verificationCode,
        openid: openid,
        pin: this.data.requiresPin ? (this.data.pin || '') : ''
      },
      header: {
        'Authorization': `Bearer ${wx.getStorageSync('token') || ''}`
      },
      success: (res: any) => {
        const resp = res.data || {};
        if (resp.code === 1) {
          const info = resp.data || {};
          const prize = info.prize_name || '';
          const orderIdText = info.order_id || orderId || '';
          const location = info.install_location || '';
          const vtime = info.verification_time_text || '';
          wx.showToast({ title: '核销成功', icon: 'success' });
          this.setData({
            resultMsg: `核销成功\n订单:${orderIdText}\n奖品:${prize}\n位置:${location}\n时间:${vtime}`
          });
        } else {
          wx.showToast({ title: resp.msg || '核销失败', icon: 'none' });
          this.setData({ resultMsg: resp.msg || '核销失败' });
        }
      },
      fail: (error: any) => {
        console.error('核销请求失败:', error);
        wx.showToast({ title: '核销失败', icon: 'none' });
        this.setData({ resultMsg: '核销失败，请重试' });
      }
    });
  }
  ,
  // 店员PIN输入
  onPinInput(e: any) {
    this.setData({ pin: e.detail.value });
  },
  // 从文本或查询串中提取核销码（兼容 verification=CODE 或 ?scene=verification=CODE）
  extractCodeFromText(text: string): string {
    const decoded = decodeURIComponent(text || '');
    try {
      if (/^https?:\/\//i.test(decoded) || decoded.indexOf('?') !== -1) {
        const queryString = decoded.split('?')[1] || decoded;
        const params: Record<string, string> = {};
        queryString.split('&').forEach(kv => {
          const [k, v] = kv.split('=');
          if (k) params[k] = v || '';
        });
        if (params.scene) {
          const sceneParams: Record<string, string> = {};
          decodeURIComponent(params.scene).split('&').forEach(kv => {
            const [k, v] = kv.split('=');
            if (k) sceneParams[k] = v || '';
          });
          if (sceneParams.verification) return (sceneParams.verification || '').toUpperCase();
          if (sceneParams.code) return (sceneParams.code || '').toUpperCase();
          if (sceneParams.v) return (sceneParams.v || '').toUpperCase();
        }
        if (params.verification) return (params.verification || '').toUpperCase();
        if (params.code) return (params.code || '').toUpperCase();
        if (params.v) return (params.v || '').toUpperCase();
      }
      if (decoded.indexOf('=') !== -1) {
        const parts = decoded.split('&');
        for (const p of parts) {
          const [k, v] = p.split('=');
          if (k === 'verification' || k === 'code' || k === 'v') {
            return (v || '').toUpperCase();
          }
        }
      }
      return decoded.toUpperCase();
    } catch (e) {
      return '';
    }
  },

  // 自动核销入口
  autoVerifyFromCode(code: string) {
    const openid = wx.getStorageSync('openid');
    if (!openid) {
      // 缺少 openid 时自动静默登录后再尝试核销
      this.silentLogin().then(() => {
        const newOpenid = wx.getStorageSync('openid');
        if (!newOpenid) {
          wx.showToast({ title: '请先登录后核销', icon: 'none' });
          return;
        }
        this.performVerification(newOpenid, '', code);
      }).catch(() => {
        wx.showToast({ title: '请先登录后核销', icon: 'none' });
      });
      return;
    }
    if (!code) {
      wx.showToast({ title: '未获取到核销码', icon: 'none' });
      return;
    }
    this.performVerification(openid, '', code);
  }
});

export {};
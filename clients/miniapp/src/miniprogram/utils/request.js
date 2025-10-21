// utils/request.js
const config = require('../config/api.js');

class Request {
  constructor() {
    this.baseUrl = config.apiUrl;  // 修改为使用apiUrl
  }
  
  // 通用请求方法
  request(options) {
    const { url, method = 'GET', data = {}, needAuth = false, showLoading = true } = options;
    
    return new Promise((resolve, reject) => {
      const header = {
        'Content-Type': 'application/json'
      };
      // 默认尝试附带 token（后端可忽略空值）
      const tokenInStore = wx.getStorageSync('token') || '';
      if (tokenInStore) {
        header['token'] = tokenInStore;
        header['Authorization'] = `Bearer ${tokenInStore}`;
      }
      
      // 如果需要认证，添加token
      if (needAuth) {
        const token = tokenInStore;
        if (!token) {
          wx.showToast({ title: '请先登录', icon: 'none' });
          reject(new Error('UNAUTHORIZED'));
          return;
        }
        header['Authorization'] = `Bearer ${token}`;
        header['token'] = token;
      }
      
      if (showLoading) {
        wx.showLoading({
          title: '加载中...',
          mask: true
        });
      }
      
      wx.request({
        url: `${this.baseUrl}${url}`,
        method,
        data,
        header,
        success: (response) => {
          if (showLoading) {
            wx.hideLoading();
          }
          
          const { data: responseData } = response;
          
          // 增强错误处理
          if (response.statusCode === 401) {
            // token 失效：清理并尝试一次静默登录后重试（仅重试一次）
            this.handleTokenExpired();
            const app = getApp ? getApp() : null;
            const alreadyRetried = options && options._retry;
            if (app && typeof app.ensureLogin === 'function' && !alreadyRetried) {
              (async () => {
                try {
                  const ok = await app.ensureLogin();
                  if (ok) {
                    const retryOptions = { ...options, _retry: true };
                    // 保持与首次相同的 loading 体验
                    this.request(retryOptions).then(resolve).catch(reject);
                  } else {
                    wx.showToast({ title: '请先登录', icon: 'none' });
                    reject(new Error('UNAUTHORIZED'));
                  }
                } catch (e) {
                  wx.showToast({ title: '请先登录', icon: 'none' });
                  reject(new Error('UNAUTHORIZED'));
                }
              })();
            } else {
              wx.showToast({ title: '请先登录', icon: 'none' });
              reject(new Error('UNAUTHORIZED'));
            }
            return;
          }

          if (response.statusCode !== 200) {
            const errorMsg = `网络错误 (${response.statusCode})`;
            console.error('HTTP错误:', response.statusCode, response);
            wx.showToast({
              title: errorMsg,
              icon: 'none'
            });
            reject(new Error(errorMsg));
            return;
          }
          
          if (!responseData) {
            const errorMsg = '服务器返回数据为空';
            console.error('响应数据为空:', response);
            wx.showToast({
              title: errorMsg,
              icon: 'none'
            });
            reject(new Error(errorMsg));
            return;
          }
          
          const msgStr = String(responseData.msg || '');
          if (responseData.code === 401 || (responseData.code === 0 && /登录/i.test(msgStr))) {
            // 业务侧 token 过期：清理并尝试一次静默登录后重试（仅重试一次）
            this.handleTokenExpired();
            const app = getApp ? getApp() : null;
            const alreadyRetried = options && options._retry;
            if (app && typeof app.ensureLogin === 'function' && !alreadyRetried) {
              (async () => {
                try {
                  const ok = await app.ensureLogin();
                  if (ok) {
                    const retryOptions = { ...options, _retry: true };
                    this.request(retryOptions).then(resolve).catch(reject);
                  } else {
                    wx.showToast({ title: '请先登录', icon: 'none' });
                    reject(new Error('UNAUTHORIZED'));
                  }
                } catch (e) {
                  wx.showToast({ title: '请先登录', icon: 'none' });
                  reject(new Error('UNAUTHORIZED'));
                }
              })();
            } else {
              wx.showToast({ title: '请先登录', icon: 'none' });
              reject(new Error('UNAUTHORIZED'));
            }
          } else if (responseData.code === 1) {
            // 兼容 res.data.list 旧写法：将嵌套的 data.list 平铺到顶层
            const payload = { ...responseData };
            try {
              const inner = payload && payload.data ? payload.data : null;
              if (inner && typeof inner === 'object') {
                // 仅在存在 list/total/page/limit 时同步顶层字段，避免污染其他响应
                if (Array.isArray(inner.list)) {
                  payload.list = inner.list;
                }
                if (typeof inner.total !== 'undefined') {
                  payload.total = inner.total;
                }
                if (typeof inner.page !== 'undefined') {
                  payload.page = inner.page;
                }
                if (typeof inner.limit !== 'undefined') {
                  payload.limit = inner.limit;
                }
              }
            } catch (_) {}
            resolve(payload);
          } else {
            const errorMsg = responseData.msg || '请求失败';
            console.error('业务错误:', responseData);
            // 不在这里显示toast，让调用方决定是否显示
            reject(new Error(errorMsg));
          }
        },
        fail: (error) => {
          if (showLoading) {
            wx.hideLoading();
          }
          
          let errorMsg = '网络错误';
          console.error('请求失败:', error);
          
          if (error.errMsg) {
            if (error.errMsg.includes('timeout')) {
              errorMsg = '请求超时，请重试';
            } else if (error.errMsg.includes('fail')) {
              errorMsg = '网络连接失败';
            } else if (error.errMsg.includes('404')) {
              errorMsg = '接口不存在';
            }
          }
          
          // 不在这里显示toast，让调用方决定是否显示
          reject(new Error(errorMsg));
        }
      });
    });
  }
  
  // 处理token过期
  handleTokenExpired() {
    console.log('Token已过期，清理本地存储');
    wx.removeStorageSync('token');
    wx.removeStorageSync('userInfo');
    
    // 不显示弹窗，直接静默处理
    // 让调用方自行处理token过期的情况
  }
  
  // 跳转到登录页
  redirectToLogin() {
    // 保持兼容：不强制页面跳转，交由业务方决定
    wx.showToast({ title: '请先登录', icon: 'none' });
  }
}

module.exports = new Request();

// utils/wechat.js
const request = require('./request.js');
const config = require('../config/api.js');

class WechatAuth {
  // 微信登录（按照官方推荐流程）
  login() {
    return new Promise((resolve, reject) => {
      console.log('开始微信登录流程');
      
      // 1. 先获取登录凭证（这是必须的）
      wx.login({
        success: (loginRes) => {
          if (loginRes.code) {
            console.log('获取微信登录code成功:', loginRes.code);
            
            // 2. 获取用户授权（这一步会弹出授权窗口）
            wx.getUserProfile({
              desc: '用于完善用户资料',
              success: (userProfileRes) => {
                console.log('用户授权成功，获取到用户信息');
                const userInfo = userProfileRes.userInfo;
                
                // 3. 调用后端登录接口
                console.log('准备调用后端登录接口，发送code和用户信息');
                request.request({
                  url: config.api.wechatLogin,
                  method: 'POST',
                  data: {
                    code: loginRes.code,
                    userInfo: userInfo,
                    rawData: userProfileRes.rawData,
                    signature: userProfileRes.signature,
                    encryptedData: userProfileRes.encryptedData,
                    iv: userProfileRes.iv
                  }
                })
                .then(response => {
                  console.log('后端登录接口调用成功:', response);
                  
                  // 安全检查：确保response和response.data存在
                  if (!response || !response.data) {
                    console.error('登录响应数据异常:', response);
                    reject(new Error('登录响应数据异常'));
                    return;
                  }
                  
                  const { token, userInfo: backendUserInfo, ...userData } = response.data;
                  
                  // 检查必要字段
                  if (!token) {
                    console.error('登录响应缺少token:', response.data);
                    reject(new Error('登录失败：缺少token'));
                    return;
                  }
                  
                  // 保存登录信息
                  wx.setStorageSync('token', token);
                  wx.setStorageSync('userInfo', backendUserInfo || userInfo);
                  
                  resolve({ 
                    token, 
                    userInfo: backendUserInfo || userInfo, 
                    isLogin: false  // 表示是新登录
                  });
                })
                .catch(error => {
                  console.error('后端登录接口调用失败:', error);
                  reject(error);
                });
              },
              fail: (err) => {
                console.error('用户拒绝授权或授权失败:', err);
                if (err.errMsg && err.errMsg.includes('deny')) {
                  wx.showModal({
                    title: '授权提示',
                    content: '需要您的授权才能使用完整功能，请重新点击登录按钮进行授权',
                    showCancel: false
                  });
                  reject(new Error('用户拒绝授权'));
                } else {
                  reject(new Error('授权失败：' + (err.errMsg || '未知错误')));
                }
              }
            });
          } else {
            console.error('获取微信登录code失败');
            reject(new Error('获取登录凭证失败'));
          }
        },
        fail: (err) => {
          console.error('wx.login调用失败:', err);
          reject(new Error('微信登录失败：' + (err.errMsg || '未知错误')));
        }
      });
    });
  }
  
  // 检查登录状态（静默检查）
  checkLogin() {
    const token = wx.getStorageSync('token');
    return !!token;
  }
  
  // 获取本地用户信息
  getLocalUserInfo() {
    return wx.getStorageSync('userInfo');
  }
  
  // 获取用户信息（需要登录）
  getUserInfo() {
    return request.request({
      url: config.api.getUserInfo,
      needAuth: true
    });
  }
  
  // 更新用户信息
  updateUserInfo(data) {
    return request.request({
      url: config.api.updateUserInfo,
      method: 'POST',
      data,
      needAuth: true
    });
  }
  
  // 退出登录
  logout() {
    return request.request({
      url: config.api.logout,
      method: 'POST',
      needAuth: true
    }).then(() => {
      // 清除本地存储
      wx.removeStorageSync('token');
      wx.removeStorageSync('userInfo');
    }).catch((error) => {
      // 即使后端退出失败，也清除本地存储
      console.warn('后端退出登录失败，但仍清除本地存储:', error);
      wx.removeStorageSync('token');
      wx.removeStorageSync('userInfo');
    });
  }
}

module.exports = new WechatAuth();

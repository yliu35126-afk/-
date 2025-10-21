// pages/profile/profile.ts
const defaultAvatarUrl = 'https://mmbiz.qpic.cn/mmbiz/icTdbqWNOwNRna42FI242Lcia07jQodd2FJGIYQfG0LAJGFxM4FbnQP6yfMxBgJ0F3YRqJCJ1aPAK2dQagdusBZg/0'

Page({
  data: {
    avatarUrl: defaultAvatarUrl,
    nickname: ''
  },

  onLoad() {
    // 优先使用本地缓存
    const cached = wx.getStorageSync('userInfo');
    if (cached) {
      this.setData({
        avatarUrl: cached.avatarUrl || defaultAvatarUrl,
        nickname: cached.nickName || ''
      });
    }

    // 若已登录，同步后端最新资料（保证真机与线上一致）
    const token = wx.getStorageSync('token');
    if (token) {
      const request = require('../../utils/request.js');
      const config = require('../../config/api.js');
      request.request({
        url: config.api.getUserInfo,
        method: 'GET',
        needAuth: true
      }).then((res: any) => {
        if (res.code === 1) {
          const serverInfo = res.data || {};
          const merged = {
            nickName: serverInfo.nickname || cached?.nickName || '',
            avatarUrl: serverInfo.avatar || cached?.avatarUrl || defaultAvatarUrl
          };
          this.setData({
            nickname: merged.nickName,
            avatarUrl: merged.avatarUrl
          });
          wx.setStorageSync('userInfo', merged);
        }
      }).catch(() => {
        // 同步失败不影响页面使用
      });
    }
  },

  // 选择头像
  onChooseAvatar(e: any) {
    const { avatarUrl } = e.detail;
    console.log('选择新头像:', avatarUrl);
    this.setData({
      avatarUrl
    });
  },

  // 昵称输入
  onNicknameInput(e: any) {
    const nickname = e.detail.value;
    this.setData({
      nickname
    });
  },

  // 昵称输入完成
  onNicknameBlur(e: any) {
    const nickname = e.detail.value;
    console.log('昵称输入完成:', nickname);
  },

  // 保存个人信息
  saveProfile() {
    const { avatarUrl, nickname } = this.data;
    
    if (!nickname.trim()) {
      wx.showToast({
        title: '请输入昵称',
        icon: 'none'
      });
      return;
    }

    let token = wx.getStorageSync('token');
    if (!token) {
      wx.showToast({
        title: '请先登录',
        icon: 'none'
      });
      return;
    }

    wx.showLoading({
      title: '保存中...'
    });

    // 上传头像（如果是新选择的）
    let avatarUploadPromise = Promise.resolve(avatarUrl);
    
    if (avatarUrl && avatarUrl !== defaultAvatarUrl && avatarUrl.startsWith('http://tmp/')) {
      // 这是新选择的头像，需要上传
      avatarUploadPromise = this.uploadAvatar(avatarUrl);
    }

    const attemptUpdate = (finalAvatarUrl: string, retry: boolean) => {
      const config = require('../../config/api.js');
      const request = require('../../utils/request.js');
      const updateData: any = { nickname };
      if (finalAvatarUrl && finalAvatarUrl !== defaultAvatarUrl) {
        updateData.avatar = finalAvatarUrl;
      }
      
      request.request({
        url: config.api.updateUserInfo,
        method: 'POST',
        data: updateData,
        needAuth: true
      }).then((res: any) => {
        console.log('更新用户信息响应:', res);
        wx.hideLoading();
        
        if (res.code === 1) {
          const userInfo = wx.getStorageSync('userInfo') || {};
          userInfo.nickName = nickname;
          if (finalAvatarUrl && finalAvatarUrl !== defaultAvatarUrl) {
            userInfo.avatarUrl = finalAvatarUrl;
          }
          wx.setStorageSync('userInfo', userInfo);
          wx.showToast({ title: '保存成功', icon: 'success' });
          setTimeout(() => { wx.navigateBack(); }, 1500);
        } else {
          wx.showToast({ title: res.msg || '保存失败', icon: 'none' });
        }
      }).catch((error: any) => {
        wx.hideLoading();
        console.error('请求失败:', error);
        // 如果是401错误且允许重试，尝试刷新token
        if (error.code === 401 && retry) {
          // 重试时不再显示loading，因为已经在上面hideLoading了
          wx.showLoading({ title: '重新登录中...' });
          this.refreshToken().then((newToken: string) => {
            token = newToken;
            attemptUpdate(finalAvatarUrl, false);
          }).catch(() => {
            wx.hideLoading();
            wx.showToast({ title: '登录已过期，请重新登录', icon: 'none' });
          });
        } else {
          wx.showToast({ title: error.msg || '网络错误，请重试', icon: 'none' });
        }
      });
    };

    avatarUploadPromise
      .then((finalAvatarUrl) => {
        attemptUpdate(finalAvatarUrl, true);
      })
      .catch((error) => {
        wx.hideLoading();
        console.error('上传头像失败:', error);
        wx.showToast({
          title: '上传头像失败：' + (error.message || '请重试'),
          icon: 'none'
        });
      });
  },

  // 静默刷新后端登录，返回新 token
  refreshToken(): Promise<string> {
    return new Promise((resolve, reject) => {
      wx.login({
        success: (loginRes) => {
          const code = loginRes.code;
          const request = require('../../utils/request.js');
          const config = require('../../config/api.js');
          request.request({
            url: config.api.wechatLogin,
            method: 'POST',
            data: { code }
          }).then((resp: any) => {
            const newToken = resp.data?.token;
            if (newToken) {
              wx.setStorageSync('token', newToken);
              resolve(newToken);
            } else {
              reject(new Error('未返回token'));
            }
          }).catch(reject);
        },
        fail: reject
      });
    });
  },

  // 上传头像
  uploadAvatar(tempPath: string): Promise<string> {
    return new Promise((resolve, reject) => {
      const config = require('../../config/api.js');
      
      wx.uploadFile({
        url: config.apiUrl + 'upload/avatar',
        filePath: tempPath,
        name: 'avatar',
        header: {
          'Authorization': 'Bearer ' + wx.getStorageSync('token')
        },
        success: (res) => {
          try {
            const data = JSON.parse(res.data);
            // 兼容后端返回 code===1 或 code===0 的成功约定
            if (data.code === 1 || data.code === 0) {
              const url = data.data?.url || data.url;
              if (url) {
                resolve(url);
              } else {
                reject(new Error('上传成功但未返回URL'));
              }
            } else {
              reject(new Error(data.msg || data.message || '上传失败'));
            }
          } catch (e) {
            reject(new Error('上传响应解析失败'));
          }
        },
        fail: reject
      });
    });
  }
});

export {};

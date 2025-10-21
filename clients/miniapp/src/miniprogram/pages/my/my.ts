// pages/my/my.ts - 完整正确的登录流程
const apiConfig = require('../../config/api.js');

Page({
  data: {
    isRelease: false,
    userInfo: null as any,
    isLoggedIn: false,
    loginFromOrders: false, // 标记是否从订单记录点击进来的登录
    
    // 提现记录相关
    showWithdrawModal: false,     // 显示提现记录弹窗
    withdrawRecords: [] as any[], // 提现记录列表
    withdrawLoading: false,       // 提现记录加载状态
    
    // 转账确认相关
    showTransferModal: false,     // 显示转账确认弹窗
    currentTransfer: null as any, // 当前要确认的转账
    transferLoading: false        // 转账确认loading
  },

  onLoad(options: any) {
    console.log('我的页面加载', options)
    
    // 处理扫码进入的参数
    this.handleScanParams(options);
    
    this.checkLoginStatus()
  },

  // 处理扫码参数
  handleScanParams(options: any) {
    console.log('用户中心处理扫码参数:', options);
    
    // 获取 app 中的扫码参数
    const app = getApp<IAppOption>();
    const scanParams = app.globalData.scanParams || {};
    
    // 合并页面参数和 app 参数
    const allParams = { ...scanParams, ...options };
    
    console.log('用户中心合并后的参数:', allParams);
    
    // 处理具体的业务逻辑
    if (allParams.action === 'profile') {
      console.log('检测到查看个人资料请求');
      // 如果已登录直接显示资料，否则提示登录
      if (!this.data.isLoggedIn) {
        wx.showModal({
          title: '需要登录',
          content: '查看个人资料需要先登录',
          confirmText: '立即登录',
          success: (res) => {
            if (res.confirm) {
              this.handleLogin();
            }
          }
        });
      }
    }
    
    // 可以显示扫码进入的提示
    if (Object.keys(allParams).length > 0) {
      wx.showToast({
        title: '扫码进入成功',
        icon: 'success',
        duration: 2000
      });
    }
  },

  onShow() {
    // 检测是否为发布版，用于隐藏环境设置入口
    try {
      const info = wx.getAccountInfoSync && wx.getAccountInfoSync();
      const ver = info && info.miniProgram && info.miniProgram.envVersion;
      const env = require('../../config/environment.js');
      this.setData({ isRelease: ver === 'release', isOnline: env.IS_ONLINE });
    } catch (_) {}
    this.checkLoginStatus()
  },

  // 环境设置入口已移除（审核要求）
  goToEnvSettings() {
    // 保留空实现以兼容旧模板绑定，不执行任何操作
  },

  // 检查登录状态
  checkLoginStatus() {
    const token = wx.getStorageSync('token');
    const userInfo = wx.getStorageSync('userInfo');
    
    if (token && userInfo) {
      this.setData({
        isLoggedIn: true,
        userInfo: userInfo
      });
    } else {
      this.setData({
        isLoggedIn: false,
        userInfo: null
      });
    }
  },

  // 处理登录
  handleLogin() {
    if (this.data.isLoggedIn) {
      this.showUserProfile()
    } else {
      // 直接进行授权登录，不显示选择框
      this.authLogin()
    }
  },

  // 显示登录选项
  showLoginOptions() {
    wx.showModal({
      title: '选择登录方式',
      content: '您可以选择快速登录，或者授权获取您的微信头像和昵称',
      confirmText: '授权登录',
      cancelText: '快速登录',
      success: (modalRes) => {
        if (modalRes.confirm) {
          // 用户选择授权登录
          this.authLogin()
        } else {
          // 用户选择快速登录
          this.silentLogin()
        }
      }
    });
  },

  // 授权登录 - 获取真实微信信息
  authLogin() {
    console.log('=== 授权登录流程 ===')
    
    // 先获取用户信息
    wx.getUserProfile({
      desc: '用于个性化显示您的头像和昵称',
      success: (res) => {
        console.log('用户授权成功，获取到真实信息:', res.userInfo)
        console.log('真实昵称:', res.userInfo.nickName)
        console.log('真实头像:', res.userInfo.avatarUrl)
        
        // 检查地理位置信息
        if (!res.userInfo.country && !res.userInfo.province && !res.userInfo.city) {
          console.log('⚠️ 地理位置信息为空，这是正常现象');
          console.log('原因可能是：');
          console.log('1. 用户在微信中没有设置地理位置');
          console.log('2. 用户隐私设置不公开位置信息');
          console.log('3. 微信隐私保护政策');
        }
        
        // 然后进行登录
        this.loginWithUserInfo(res.userInfo)
      },
      fail: (err) => {
        console.error('用户拒绝授权:', err)
        wx.showModal({
          title: '授权失败',
          content: '您拒绝了授权，是否改用快速登录？',
          confirmText: '快速登录',
          cancelText: '取消',
          success: (modalRes) => {
            if (modalRes.confirm) {
              this.silentLogin()
            }
          }
        });
      }
    });
  },

  // 使用用户信息登录
  loginWithUserInfo(userInfo: any) {
    console.log('使用真实用户信息登录')
    
    wx.showLoading({
      title: '登录中...',
    });

    // 先获取 code
    wx.login({
      success: (res) => {
        if (res.code) {
          console.log('获取 code 成功:', res.code)
          this.sendAuthLoginToBackend(res.code, userInfo)
        } else {
          wx.hideLoading();
          wx.showToast({
            title: '获取登录凭证失败',
            icon: 'none'
          });
        }
      },
      fail: (err) => {
        wx.hideLoading();
        console.error('wx.login 失败:', err);
        wx.showToast({
          title: '登录失败',
          icon: 'none'
        });
      }
    });
  },

  // 发送授权登录信息到后端
  sendAuthLoginToBackend(code: string, userInfo: any) {
    console.log('=== 发送用户信息到后端 ===');
    console.log('完整用户信息:', userInfo);
    console.log('昵称:', userInfo.nickName);
    console.log('头像:', userInfo.avatarUrl);
    console.log('性别:', userInfo.gender); // 0:未知, 1:男, 2:女
    console.log('语言:', userInfo.language);
    console.log('国家:', userInfo.country || '(空)');
    console.log('省份:', userInfo.province || '(空)');
    console.log('城市:', userInfo.city || '(空)');
    console.log('================================');
    
    // 处理空值，给一些默认值
    const processedUserInfo = {
      ...userInfo,
      country: userInfo.country || '未设置',
      province: userInfo.province || '未设置', 
      city: userInfo.city || '未设置'
    };
    
    const request = require('../../utils/request.js');
    
    request.request({
      url: apiConfig.api.wechatLogin,
      method: 'POST',
      data: {
        code: code,
        userInfo: processedUserInfo  // 发送处理后的用户信息
      }
    })
    .then((response: any) => {
      wx.hideLoading();
      console.log('=== 我的页面登录API响应 ===');
      console.log('完整响应:', response);
      console.log('响应数据:', response.data);
      console.log('==============================');
      
      // 检查响应结构
      if (response.code !== 1) {
        wx.showToast({
          title: response.msg || '登录失败',
          icon: 'none'
        });
        return;
      }
      
      const loginData = response.data;
      
      // 保存完整的登录信息
      wx.setStorageSync('token', loginData.token);
      wx.setStorageSync('openid', loginData.openid);  // 保存后端返回的openid
      
      // 构造完整的用户信息对象
      const savedUserInfo = {
        nickName: loginData.nickname || userInfo.nickName,
        avatarUrl: loginData.avatar || userInfo.avatarUrl,
        userId: loginData.user_id,
        openid: loginData.openid,
        gender: userInfo.gender,
        language: userInfo.language,
        country: userInfo.country,
        province: userInfo.province,
        city: userInfo.city
      };
      
      wx.setStorageSync('userInfo', savedUserInfo);
      
      console.log('=== 保存到本地存储的信息 ===');
      console.log('token:', wx.getStorageSync('token'));
      console.log('openid:', wx.getStorageSync('openid'));
      console.log('userInfo:', wx.getStorageSync('userInfo'));
      console.log('==============================');
      
      this.setData({
        userInfo: savedUserInfo,
        isLoggedIn: true
      });

      wx.showToast({
        title: '登录成功',
        icon: 'success',
        duration: 1500,
        success: () => {
          // 如果是从订单记录点击进来的登录，自动跳转到订单页面
          if (this.data.loginFromOrders) {
            console.log('登录成功，自动跳转到订单页面');
            this.setData({ loginFromOrders: false }); // 重置标记
            setTimeout(() => {
              wx.navigateTo({
                url: '/pages/order/order'
              });
            }, 1000);
          }
        }
      });
    })
    .catch((error: any) => {
      wx.hideLoading();
      console.error('授权登录失败:', error);
      wx.showToast({
        title: '登录失败，请重试',
        icon: 'none'
      });
    });
  },

  // 2025年推荐：无感登录（只获取 openid，不获取用户信息）
  silentLogin() {
    console.log('=== 2025年无感登录流程 ===')
    console.log('第一步：wx.login 获取 code')
    
    wx.showLoading({
      title: '登录中...',
    });

    // 第一步：wx.login 获取 code
    wx.login({
      success: (res) => {
        if (res.code) {
          console.log('获取 code 成功:', res.code)
          // 第二步：发送 code 到后端
          this.loginWithCode(res.code)
        } else {
          wx.hideLoading();
          wx.showToast({
            title: '获取登录凭证失败',
            icon: 'none'
          });
        }
      },
      fail: (err) => {
        wx.hideLoading();
        console.error('wx.login 失败:', err);
        wx.showToast({
          title: '登录失败',
          icon: 'none'
        });
      }
    });
  },

  // 第二步：用 code 登录后端（无感登录）
  loginWithCode(code: string) {
    console.log('第二步：发送 code 到后端，完成无感登录')
    
    const request = require('../../utils/request.js');
    
    request.request({
      url: apiConfig.api.wechatLogin,
      method: 'POST',
      data: {
        code: code    // 只发送 code，不发送用户信息
      }
    })
    .then((response: any) => {
      wx.hideLoading();
      console.log('无感登录成功:', response);
      
      // 安全检查：确保response和response.data存在
      if (!response || !response.data) {
        console.error('登录响应数据异常:', response);
        wx.showToast({ title: '登录失败，请重试', icon: 'none' });
        return;
      }
      
      const { token, user_id, nickname, avatar, is_new } = response.data;
      
      // 检查必要字段
      if (!token) {
        console.error('登录响应缺少token:', response.data);
        wx.showToast({ title: '登录失败，请重试', icon: 'none' });
        return;
      }
      
      // 保存登录信息
      wx.setStorageSync('token', token);
      
      // 构造用户信息对象（后端返回的默认信息）
      const userInfo = {
        nickName: nickname,
        avatarUrl: avatar,
        userId: user_id
      };
      
      wx.setStorageSync('userInfo', userInfo);
      
      this.setData({
        userInfo: userInfo,
        isLoggedIn: true
      });

      wx.showToast({
        title: '登录成功',
        icon: 'success'
      });

      // 如果是新用户且使用默认信息，提示可以自定义头像昵称
      if (is_new || nickname === '深圳用户') {
        setTimeout(() => {
          this.promptCustomizeProfile();
        }, 1000);
      }
    })
    .catch((error: any) => {
      wx.hideLoading();
      console.error('登录失败:', error);
      wx.showToast({
        title: '登录失败，请重试',
        icon: 'none'
      });
    });
  },

  // 提示用户自定义个人资料
  promptCustomizeProfile() {
    wx.showModal({
      title: '个性化设置',
      content: '检测到您使用的是默认头像和昵称，是否要设置个性化的头像和昵称？',
      confirmText: '去设置',
      cancelText: '稍后',
      success: (modalRes) => {
        if (modalRes.confirm) {
          // 跳转到个人资料设置页面
          wx.navigateTo({
            url: '/pages/profile/profile'
          });
        }
      }
    });
  },

  // 显示用户资料
  showUserProfile() {
    wx.showActionSheet({
      itemList: ['设置头像昵称', '重置登录状态', '退出登录'],
      success: (res) => {
        if (res.tapIndex === 0) {
          // 设置头像昵称
          wx.navigateTo({
            url: '/pages/profile/profile'
          });
        } else if (res.tapIndex === 1) {
          // 重置登录状态
          this.resetAuthStatus();
        } else if (res.tapIndex === 2) {
          // 退出登录
          this.logout();
        }
      }
    });
  },

  // 重置登录状态
  resetAuthStatus() {
    wx.showModal({
      title: '重置登录',
      content: '这将清除本地存储的登录信息',
      confirmText: '确定重置',
      cancelText: '取消',
      success: (res) => {
        if (res.confirm) {
          wx.removeStorageSync('token');
          wx.removeStorageSync('userInfo');
          
          this.setData({
            isLoggedIn: false,
            userInfo: null
          });
          
          wx.showToast({
            title: '登录状态已重置',
            icon: 'success'
          });
        }
      }
    });
  },

  // 退出登录
  logout() {
    wx.showModal({
      title: '确认退出',
      content: '确定要退出登录吗？',
      success: (res) => {
        if (res.confirm) {
          wx.removeStorageSync('token');
          wx.removeStorageSync('userInfo');
          this.setData({
            isLoggedIn: false,
            userInfo: null
          });
          wx.showToast({
            title: '已退出登录',
            icon: 'success'
          });
        }
      }
    });
  },

  goToOrders() {
    if (!this.data.isLoggedIn) {
      // 标记是从订单记录点击进来的
      this.setData({
        loginFromOrders: true
      });
      this.handleLogin();
    } else {
      wx.navigateTo({
        url: '/pages/order/order'
      });
    }
  },

  // 跳转到用户协议页面
  goToAgreement() {
    wx.navigateTo({
      url: '/pages/agreement/agreement'
    });
  },

  // 跳转到个人信息设置页面
  goToProfile() {
    wx.navigateTo({
      url: '/pages/profile/profile'
    });
  },

  // 跳转到商家核销页面
  goToMerchantVerification() {
    if (!this.data.isLoggedIn) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    wx.navigateTo({
      url: '/pages/verification/index'
    });
  },

  // 打开提现记录
  openWithdrawRecords() {
    if (!this.data.isLoggedIn) {
      wx.showToast({
        title: '请先登录',
        icon: 'none'
      });
      return;
    }

    this.setData({
      showWithdrawModal: true,
      withdrawLoading: true
    });

    this.loadWithdrawRecords();
  },

  // 加载提现记录
  loadWithdrawRecords() {
    const openid = wx.getStorageSync('openid');
    const token = wx.getStorageSync('token');
    
    if (!openid) {
      wx.showToast({
        title: '请先登录',
        icon: 'none'
      });
      this.closeWithdrawModal();
      return;
    }

    if (!token) {
      wx.showToast({
        title: '登录已过期，请重新登录',
        icon: 'none'
      });
      this.closeWithdrawModal();
      return;
    }

    console.log('加载提现记录 - openid:', openid);

    // 使用GET请求获取提现记录
    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.getWithdrawRecords}?user_id=${openid}`,
      method: 'GET',
      header: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      success: (res: any) => {
        console.log('提现记录响应:', res);
        this.setData({ withdrawLoading: false });

        // 适配实际返回的数据结构
        if (res.data.code && res.data.code.records) {
          // 后端返回格式: { "code": { "records": [...] } }
          const records = res.data.code.records || [];
          this.setData({
            withdrawRecords: records
          });
        } else if (res.data.code === 1 && res.data.data?.records) {
          // 标准格式: { "code": 1, "data": { "records": [...] } }
          const records = res.data.data.records || [];
          this.setData({
            withdrawRecords: records
          });
        } else {
          wx.showToast({
            title: res.data.msg || '获取记录失败',
            icon: 'none'
          });
        }
      },
      fail: (err) => {
        console.error('获取提现记录失败:', err);
        this.setData({ withdrawLoading: false });
        wx.showToast({
          title: '网络错误，请重试',
          icon: 'none'
        });
      }
    });
  },

  // 关闭提现记录弹窗
  closeWithdrawModal() {
    this.setData({
      showWithdrawModal: false,
      withdrawRecords: [],
      withdrawLoading: false
    });
  },

  // 确认收款
  confirmWithdraw(e: any) {
    const record = e.currentTarget.dataset.record;
    if (!record || !record.package_info) {
      wx.showToast({
        title: '转账信息不完整',
        icon: 'none'
      });
      return;
    }

    console.log('开始确认收款:', record);
    this.setData({ 
      currentTransfer: record,
      showTransferModal: true 
    });
  },

  // 关闭转账确认弹窗
  closeTransferModal() {
    this.setData({
      showTransferModal: false,
      currentTransfer: null,
      transferLoading: false
    });
  },

  // 执行转账确认
  doConfirmTransfer() {
    const transfer = this.data.currentTransfer;
    if (!transfer || !transfer.package_info) {
      wx.showToast({
        title: '转账信息不完整',
        icon: 'none'
      });
      return;
    }

    this.setData({ transferLoading: true });

    // 检查是否支持商户转账API
    if (wx.canIUse('requestMerchantTransfer')) {
      // 调用微信商户转账确认API
      wx.requestMerchantTransfer({
        mchId: '1726343561', // 你的实际商户号
        appId: wx.getAccountInfoSync().miniProgram.appId,
        package: transfer.package_info,
        success: (res: any) => {
          console.log('确认收款成功:', res);
          this.setData({ transferLoading: false });
          this.closeTransferModal();
          
          wx.showToast({
            title: '收款成功！',
            icon: 'success',
            duration: 2000
          });

          // 更新提现状态
          this.updateWithdrawStatus(transfer.withdraw_id || transfer.id, 'SUCCESS');
          
          // 重新加载提现记录
          setTimeout(() => {
            this.loadWithdrawRecords();
          }, 1000);
        },
        fail: (err: any) => {
          console.error('确认收款失败:', err);
          this.setData({ transferLoading: false });
          
          wx.showModal({
            title: '收款失败',
            content: err.errMsg || '确认收款失败，请重试',
            showCancel: false
          });
        }
      });
    } else {
      // 版本不支持，提示用户升级
      this.setData({ transferLoading: false });
      wx.showModal({
        title: '版本提示',
        content: '你的微信版本过低，请更新至最新版本。',
        showCancel: false
      });
    }
  },

  // 更新提现状态
  updateWithdrawStatus(withdrawId: string, status: string) {
    const token = wx.getStorageSync('token');
    if (!token) {
      console.log('未获取到token，无法更新提现状态');
      return;
    }

    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.updateWithdrawStatus}`,
      method: 'POST',
      header: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      data: {
        withdraw_id: withdrawId,
        status: status
      },
      success: (res: any) => {
        console.log('更新提现状态成功:', res);
      },
      fail: (err) => {
        console.error('更新提现状态失败:', err);
      }
    });
  },

  // 防止弹窗关闭
  preventClose() {
    // 空方法，防止点击内容区域关闭弹窗
  },

  // 分享给朋友 - 统一跳转到首页
  onShareAppMessage() {
    return {
      title: '异企趣玩 - 抽奖活动火热进行中！',
      desc: '快来一起抽奖，好运等着你！',
      path: '/pages/index/index', // 强制跳转到首页
      imageUrl: ''
    };
  },

  // 分享到朋友圈 - 统一跳转到单页面  
  onShareTimeline() {
    return {
      title: '异企趣玩 - 抽奖活动火热进行中！',
      path: '/pages/timeline-share/timeline-share', // 跳转到朋友圈单页面
      imageUrl: ''
    };
  }
});

export {};

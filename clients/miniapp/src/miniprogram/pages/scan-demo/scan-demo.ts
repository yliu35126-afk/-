// pages/scan-demo/scan-demo.ts
Page({
  data: {
    // 参数相关
    hasParams: false,
    paramsList: [] as Array<{key: string, value: string}>,
    
    // 场景相关
    sceneValue: '',
    sceneDesc: '',
    
    // 业务相关
    businessType: '',
    productId: '',
    productType: '',
    userId: '',
    userAction: '',
    eventId: '',
    eventType: ''
  },

  onLoad(options: any) {
    console.log('扫码示例页面加载 - options:', options);
    
    // 获取 app 中的扫码参数
    const app = getApp<IAppOption>();
    const scanParams = app.globalData.scanParams || {};
    
    // 合并参数
    const allParams = { ...scanParams, ...options };
    console.log('合并后的所有参数:', allParams);
    
    // 处理参数显示
    this.processParams(allParams);
    
    // 处理场景信息
    this.processScene(options);
    
    // 处理业务逻辑
    this.processBusiness(allParams);
  },

  // 处理参数显示
  processParams(params: any) {
    const paramsList = Object.keys(params).map(key => ({
      key: key,
      value: String(params[key])
    }));

    this.setData({
      hasParams: paramsList.length > 0,
      paramsList: paramsList
    });
  },

  // 处理场景信息
  processScene(options: any) {
    // 获取场景值映射
    const sceneMap: Record<number, string> = {
      1001: '发现栏小程序主入口',
      1005: '顶部搜索框的搜索结果页',
      1006: '发现栏小程序主入口搜索框的搜索结果页',
      1007: '单人聊天会话中的小程序消息卡片',
      1008: '群聊会话中的小程序消息卡片',
      1011: '扫描二维码',
      1012: '长按图片识别二维码',
      1013: '手机相册选取二维码',
      1014: '小程序模版消息',
      1017: '前往体验版的入口页',
      1019: '微信钱包',
      1020: '公众号 profile 页相关小程序列表',
      1022: '聊天顶部置顶小程序入口',
      1023: '安卓系统桌面图标',
      1024: '小程序 profile 页',
      1025: '扫描一维码',
      1026: '附近小程序列表',
      1027: '索搜结果页"使用过的小程序"列表',
      1028: '我的卡包',
      1029: '卡券详情页',
      1030: '自动化测试下打开小程序',
      1031: '长按图片识别一维码',
      1032: '手机相册选取一维码',
      1034: '微信支付完成页',
      1035: '公众号自定义菜单',
      1036: 'App 分享消息卡片',
      1037: '小程序打开小程序',
      1038: '从另一个小程序返回',
      1039: '摇电视',
      1042: '添加好友搜索框的搜索结果页',
      1043: '公众号模板消息',
      1044: '带 shareTicket 的小程序消息卡片',
      1045: '朋友圈广告',
      1046: '朋友圈广告详情页',
      1047: '扫描小程序码',
      1048: '长按图片识别小程序码',
      1049: '手机相册选取小程序码',
      1052: '卡券的适用门店列表',
      1053: '搜一搜的结果页',
      1054: '顶部搜索框小程序快捷入口',
      1056: '音乐播放器菜单',
      1057: '钱包中的银行卡详情页',
      1058: '公众号文章',
      1059: '体验版小程序绑定邀请页',
      1064: '微信连Wi-Fi状态栏',
      1067: '公众号文章广告',
      1068: '附近小程序列表广告',
      1069: '移动应用',
      1071: '钱包中的银行卡详情页',
      1072: '二维码收款页面',
      1073: '客服消息列表下拉续费',
      1074: '公众号会话下拉',
      1077: '摇周边',
      1078: '连Wi-Fi成功页',
      1079: '微信游戏中心',
      1081: '客服消息下发',
      1082: '从聊天框的小程序历史列表中的某个小程序打开',
      1084: '视频号广告',
      1089: '微信聊天主界面下拉',
      1090: '长按小程序右上角菜单唤出最近使用历史',
      1091: '公众号文章商品卡片',
      1092: '城市服务入口',
      1095: '小程序广告组件',
      1096: '聊天记录',
      1097: '微信支付签约页',
      1099: '页面内嵌插件',
      1102: '公众号 profile 页服务预览',
      1103: '发现-小程序主入口我的小程序列表',
      1104: '微信聊天主界面下拉所有小程序',
      1113: 'PC端微信',
      1114: 'MacOS端微信',
      1117: '前往体验版的入口页'
    };

    const launchOptions = wx.getLaunchOptionsSync && wx.getLaunchOptionsSync();
    const scene = options.scene || (launchOptions && launchOptions.scene) || 0;
    const sceneDesc = sceneMap[scene] || '未知场景';

    this.setData({
      sceneValue: String(scene),
      sceneDesc: sceneDesc
    });
  },

  // 处理业务逻辑
  processBusiness(params: any) {
    let businessType = '';
    
    // 根据参数判断业务类型
    if (params.id && (params.type === 'product' || params.productId)) {
      // 商品类型
      businessType = 'product';
      this.setData({
        businessType: businessType,
        productId: params.id || params.productId || '',
        productType: params.type || params.productType || 'unknown'
      });
    } else if (params.uid || params.userId || params.action === 'profile') {
      // 用户类型
      businessType = 'user';
      this.setData({
        businessType: businessType,
        userId: params.uid || params.userId || '',
        userAction: params.action || 'view'
      });
    } else if (params.event || params.eventId) {
      // 活动类型
      businessType = 'event';
      this.setData({
        businessType: businessType,
        eventId: params.event || params.eventId || '',
        eventType: params.eventType || 'general'
      });
    } else {
      // 默认类型
      businessType = 'default';
      this.setData({
        businessType: businessType
      });
    }

    console.log('业务类型识别结果:', businessType);
  },

  // 查看商品详情
  viewProduct() {
    const { productId, productType } = this.data;
    console.log('查看商品详情:', { productId, productType });
    
    wx.showModal({
      title: '商品详情',
      content: `商品ID: ${productId}\n商品类型: ${productType}`,
      showCancel: false,
      confirmText: '知道了'
    });
    
    // 这里可以跳转到具体的商品详情页面
    // wx.navigateTo({
    //   url: `/pages/product/detail?id=${productId}&type=${productType}`
    // });
  },

  // 查看用户资料
  viewUserProfile() {
    const { userId, userAction } = this.data;
    console.log('查看用户资料:', { userId, userAction });
    
    wx.showModal({
      title: '用户资料',
      content: `用户ID: ${userId}\n操作: ${userAction}`,
      showCancel: false,
      confirmText: '知道了'
    });
    
    // 这里可以跳转到用户资料页面
    // wx.navigateTo({
    //   url: `/pages/user/profile?uid=${userId}&action=${userAction}`
    // });
  },

  // 参与活动
  joinEvent() {
    const { eventId, eventType } = this.data;
    console.log('参与活动:', { eventId, eventType });
    
    wx.showModal({
      title: '活动信息',
      content: `活动ID: ${eventId}\n活动类型: ${eventType}`,
      showCancel: false,
      confirmText: '知道了'
    });
    
    // 这里可以跳转到活动页面
    // wx.navigateTo({
    //   url: `/pages/event/detail?id=${eventId}&type=${eventType}`
    // });
  },

  // 默认处理
  handleDefault() {
    console.log('默认处理');
    
    wx.showModal({
      title: '扫码成功',
      content: '已检测到扫码进入，但未识别具体业务类型',
      showCancel: false,
      confirmText: '知道了'
    });
  },

  // 刷新参数
  refreshParams() {
    console.log('刷新参数');
    
    // 重新获取参数
    const options = getCurrentPages()[getCurrentPages().length - 1].options;
    this.onLoad(options);
    
    wx.showToast({
      title: '参数已刷新',
      icon: 'success'
    });
  },

  // 返回首页
  goHome() {
    wx.switchTab({
      url: '/pages/index/index'
    });
  }
});

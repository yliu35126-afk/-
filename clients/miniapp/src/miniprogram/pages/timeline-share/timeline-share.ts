// pages/timeline-share/timeline-share.ts
// 朋友圈分享单页面
const config = require('../../config/api.js');
import { resolveImageUrl } from '../../utils/image';

Page({
  data: {
    // 分享内容数据
    shareContent: null as any,
    loading: true,
    
    // 设备相关
    deviceCode: '',
    
    // 媒体内容
    mediaType: '', // 'video' | 'image'
    mediaUrl: '',
    shareTitle: '',
    shareText: '',
    
    // 视频控制
    videoContext: null as any,
    isVideoPlaying: false
  },

  onLoad(options: any) {
    console.log('朋友圈单页面加载:', options);
    
    // 获取设备码
    if (options.device_id) {
      this.setData({
        deviceCode: options.device_id
      });
      
      // 加载分享内容
      this.loadShareContent();
    } else {
      // 没有设备码，显示默认内容
      this.showDefaultContent();
    }
  },

  onReady() {
    // 初始化视频上下文
    this.data.videoContext = wx.createVideoContext('shareVideo', this);
  },

  // 加载分享内容
  async loadShareContent() {
    if (!this.data.deviceCode) {
      console.log('设备码为空，显示默认内容');
      this.showDefaultContent();
      return;
    }

    try {
      console.log('获取分享内容，设备码:', this.data.deviceCode);
      
      const res: any = await new Promise((resolve, reject) => {
        wx.request({
        url: config.apiUrl + config.api.getShareContent,
        method: 'GET',
        data: {
          device_id: this.data.deviceCode
        },
          success: resolve,
          fail: reject
        });
      });

      console.log('朋友圈单页 - 分享内容API响应:', res);

      if (res.statusCode === 200 && res.data && res.data.code === 200) {
        const shareData = res.data.data;
        
        this.setData({
          shareContent: shareData,
          mediaType: shareData.media_type || 'image',
          mediaUrl: shareData.share_media_url || '',
          shareTitle: shareData.share_text || shareData.share_title || '异企趣玩 - 抽奖活动',
          shareText: shareData.share_text || '快来参与抽奖活动，好运等着你！',
          loading: false
        });

        console.log('✅ 朋友圈单页 - 分享内容加载成功');
        
        // 如果是视频，自动播放
        if (shareData.media_type === 'video' && shareData.share_media_url) {
          this.playVideo();
        }
      } else {
        console.log('❌ 朋友圈单页 - 分享内容API返回错误:', res.data);
        this.showDefaultContent();
      }
    } catch (error) {
      console.log('❌ 朋友圈单页 - 获取分享内容失败:', error);
      this.showDefaultContent();
    }
  },

  // 显示默认内容
  showDefaultContent() {
    this.setData({
      shareTitle: '异企趣玩 - 抽奖活动火热进行中！',
      shareText: '扫码体验智能娃娃机，精彩奖品等你来抽！',
      mediaType: 'image',
      mediaUrl: '/images/success.svg',
      loading: false
    });
  },

  // 播放视频
  playVideo() {
    if (this.data.videoContext && this.data.mediaType === 'video') {
      this.data.videoContext.play();
      this.setData({
        isVideoPlaying: true
      });
      console.log('🎬 朋友圈单页 - 开始播放视频');
    }
  },

  // 暂停视频  
  pauseVideo() {
    if (this.data.videoContext && this.data.mediaType === 'video') {
      this.data.videoContext.pause();
      this.setData({
        isVideoPlaying: false
      });
      console.log('⏸️ 朋友圈单页 - 暂停视频');
    }
  },

  // 视频播放事件
  onVideoPlay() {
    console.log('🎬 朋友圈单页 - 视频开始播放');
    this.setData({
      isVideoPlaying: true
    });
  },

  // 视频暂停事件
  onVideoPause() {
    console.log('⏸️ 朋友圈单页 - 视频暂停');
    this.setData({
      isVideoPlaying: false
    });
  },

  // 视频结束事件
  onVideoEnded() {
    console.log('🔚 朋友圈单页 - 视频播放结束');
    this.setData({
      isVideoPlaying: false
    });
  },

  // 视频出错事件
  onVideoError(e: any) {
    console.log('❌ 朋友圈单页 - 视频播放出错:', e.detail);
    // 视频出错时显示默认图片
    this.setData({
      mediaType: 'image',
      mediaUrl: '/images/success.svg'
    });
  },

  // 进入小程序
  goToMiniProgram() {
    wx.navigateTo({
      url: '/pages/index/index',
      fail: () => {
        // 如果导航失败，尝试重定向
        wx.redirectTo({
          url: '/pages/index/index'
        });
      }
    });
  },

  // 分享给朋友
  onShareAppMessage() {
    const src = this.data.mediaType === 'image' ? (this.data.mediaUrl || '') : '';
    const imageUrl = resolveImageUrl(src, 'timeline-share');
    return {
      title: this.data.shareTitle || '异企趣玩 - 抽奖活动火热进行中！',
      desc: this.data.shareText || '快来一起抽奖，好运等着你！',
      path: '/pages/index/index', // 分享后进入首页
      imageUrl
    };
  },

  // 分享到朋友圈
  onShareTimeline() {
    const src = this.data.mediaType === 'image' ? (this.data.mediaUrl || '') : '';
    const imageUrl = resolveImageUrl(src, 'timeline-share');
    return {
      title: this.data.shareTitle || '异企趣玩 - 抽奖活动火热进行中！',
      path: '/pages/index/index', // 分享后进入首页
      imageUrl
    };
  }
});

export {};
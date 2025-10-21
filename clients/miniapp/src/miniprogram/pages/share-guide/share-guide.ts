// pages/share-guide/share-guide.ts
// 分享引导页面
const config = require('../../config/api.js');
import { resolveImageUrl } from '../../utils/image';

Page({
  data: {
    // 订单信息
    orderId: '',
    amount: '',
    deviceCode: '',
    
    // 中奖信息
    prizeName: '',
    prizeImage: '',
    prizeInfo: null as any,
    isWinning: false, // 是否是中奖分享
    
    // 分享内容
    shareContent: null as any,
    
    // 媒体控制
    videoContext: null as any,
    isVideoPlaying: false,
    buffering: false,
    videoReady: false,
    videoDuration: 0,
    lastVideoTime: 0,
    
    // 页面状态
    loading: true
  },

  onLoad(options: any) {
    console.log('分享引导页面加载:', options);
    
    // 获取传递的参数
    this.setData({
      orderId: options.orderId || '',
      amount: options.amount || '',
      deviceCode: options.deviceCode || '',
      prizeName: options.prizeName ? decodeURIComponent(options.prizeName) : '',
      prizeImage: options.prizeImage ? decodeURIComponent(options.prizeImage) : '',
      isWinning: !!(options.prizeName || options.prizeImage) // 有奖品信息说明是中奖分享
    });

    // 解析中奖详细信息
    if (options.prizeInfo) {
      try {
        const prizeInfo = JSON.parse(decodeURIComponent(options.prizeInfo));
        this.setData({
          prizeInfo: prizeInfo
        });
      } catch (e) {
        console.log('解析中奖信息失败:', e);
      }
    }

    // 加载分享内容
    this.loadShareContent();
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

      console.log('分享引导页 - 分享内容API响应:', res);

      if (res.statusCode === 200 && res.data && res.data.code === 1) {
        const shareData = res.data.data || {};

        // 统一规范化与净化文案，避免出现设备IP/WiFi等调试信息
        const normalizeUrl = (u: string) => {
          try {
            if (config && typeof config.normalizeUrl === 'function') {
              return config.normalizeUrl(u);
            }
          } catch (_) {}
          if (!u) return '';
          const d = String(config.resourceDomain || '').replace(/\/$/, '');
          const isAbs = /^https?:\/\//.test(u);
          if (isAbs) return u;
          const rel = u.startsWith('/') ? u : ('/' + u);
          return d + rel;
        };
        const sanitizeText = (t: string) => {
          if (!t) return '';
          let s = String(t);

          // 1) 移除常见的设备/网络调试信息
          s = s
            // IPv4 地址
            .replace(/\b(?:\d{1,3}\.){3}\d{1,3}\b/g, '')
            // MAC/BSSID
            .replace(/\b(?:[0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}\b/g, '')
            // WiFi:xxxx / WiFi：xxxx
            .replace(/\bWiFi\s*[:：]?[^\|，。！？\n]*\b/gi, '')
            // IP:xxxx / IP：xxxx
            .replace(/\bIP\s*[:：]?[^\|，。！？\n]*\b/gi, '')
            // 设备相关字段（设备名/设备码/编号/ID/信息等）；连同前导词一起移除（如“Android设备”）
            .replace(/(?:[A-Za-z0-9_\-]+|[\u4e00-\u9fa5]+)?\s*设备(?:名|码|编号|ID|信息|名称)?\s*[:：]?[^\|，。！？\n]*/gi, '')
            // 括号内包含分隔符/冒号的调试内容
            .replace(/\([^)]*[:：\-\|][^)]*\)/g, '')
            // 合并或移除多余的竖线分隔符
            .replace(/\s*\|+\s*/g, ' ')
            // 规范多余的冒号
            .replace(/[:：]{2,}/g, '：')
            // 去除仅由符号组成的残留片段（如 “:-()”）
            .replace(/(?:^|\s)[\-:：|()（）·]+(?:\s|$)/g, ' ')
            // 去除常见品牌/系统词，防止误识别为设备信息
            .replace(/\b(Android|iPhone|iOS|Huawei|Honor|OPPO|Vivo|Xiaomi|Redmi)\b/gi, '')
            .replace(/(华为|荣耀|小米|红米|欧珀|维沃)/g, '')
            // 多余空格
            .replace(/\s{2,}/g, ' ')
            .trim();

          // 2) 清理首尾残留分隔符/符号
          s = s.replace(/^[\|:：\-·\s]+|[\|:：\-·\s]+$/g, '').trim();

          // 2.1) 清理可能因为前缀被移除而残留的连接词（如以“的”开头）
          s = s.replace(/^[\s\-:：|()（）·•、，。！？]*的\s*/g, '').trim();

          // 3) 极端情况下（清理后为空或过短）使用空字符串，促使模板回退到默认文案
          if (s.length < 2) return '';
          return s;
        };

        // 处理媒体URL
        shareData.share_media_url = normalizeUrl(shareData.share_media_url);

        // 判断媒体类型（后端未设置时）
        if (shareData.share_media_url && !shareData.media_type) {
          const url = shareData.share_media_url.toLowerCase();
          if (/(\.mp4|\.mov|\.avi|\.webm)$/i.test(url)) {
            shareData.media_type = 'video';
          } else if (/(\.jpg|\.jpeg|\.png|\.gif|\.webp)$/i.test(url)) {
            shareData.media_type = 'image';
          }
        }

        // 统一文案字段：优先后台配置，并引入商家名称作为兜底
        const merchant = sanitizeText(shareData.merchant || '');
        shareData.share_title = sanitizeText(shareData.share_title || '');
        shareData.share_text = sanitizeText(shareData.share_text || '');

        if (!shareData.share_title) {
          shareData.share_title = merchant ? `${merchant}的精彩抽奖！` : '异企趣玩 - 抽奖活动火热进行中！';
        }
        if (!shareData.share_text) {
          shareData.share_text = merchant ? `【${merchant}】正在举办精彩抽奖活动！快来参与吧！` : '快来一起抽奖，好运等着你！';
        }

        // 推荐文案兜底遵循“标题优先，再正文”的常规
        shareData.recommend_text = sanitizeText(
          shareData.recommend_text || shareData.share_title || shareData.share_text || ''
        );

        this.setData({
          shareContent: shareData,
          loading: false
        });

        console.log('✅ 分享引导页 - 分享内容加载成功，处理后的数据:', shareData);
        
        // 如果是视频，等待可播放事件后再启动，减少首帧卡顿与音画不同步
        if (shareData.media_type === 'video' && shareData.share_media_url) {
          setTimeout(() => {
            if (this.data.videoReady) {
              this.playShareVideo();
            }
          }, 300);
        }
      } else {
        console.log('❌ 分享引导页 - API响应异常 statusCode:', res.statusCode, 'code:', res.data?.code, 'data:', res.data);
        this.showDefaultContent();
      }
    } catch (error) {
      console.log('❌ 分享引导页 - 获取分享内容失败:', error);
      this.showDefaultContent();
    }
  },

  // 视频开始加载
  onVideoLoadStart(e: any) {
    console.log('🎞️ 分享引导页 - 视频开始加载', e);
    this.setData({ buffering: true });
  },

  // 元数据加载完成（可获得时长等信息）
  onVideoLoadedMeta(e: any) {
    console.log('📐 分享引导页 - 视频元数据加载完成', e);
    const duration = e?.detail?.duration;
    if (typeof duration === 'number' && !isNaN(duration)) {
      this.setData({ videoDuration: duration });
    }
  },

  // 视频达到可播放状态
  onVideoCanPlay(e: any) {
    console.log('✅ 分享引导页 - 视频可以播放', e);
    this.setData({ videoReady: true, buffering: false });
    // 若此前已请求自动播放，确保在可播放后启动
    if (!this.data.isVideoPlaying && this.data.shareContent?.media_type === 'video') {
      setTimeout(() => {
        this.playShareVideo();
      }, 100);
    }
  },

  // 播放进度回调，用于检测缓冲结束与时间推进
  onVideoTimeUpdate(e: any) {
    const currentTime = e?.detail?.currentTime;
    const duration = e?.detail?.duration;
    if (typeof currentTime === 'number') {
      const wasBuffering = this.data.buffering;
      // 时间前进代表缓冲结束
      if (wasBuffering && currentTime > this.data.lastVideoTime) {
        this.setData({ buffering: false });
      }
      this.setData({ lastVideoTime: currentTime });
    }
    if (typeof duration === 'number' && !isNaN(duration)) {
      this.setData({ videoDuration: duration });
    }
  },

  // 缓冲事件：展示缓冲状态，避免“声音继续但画面停住”的体验问题
  onVideoWaiting(e: any) {
    console.log('⏳ 分享引导页 - 视频缓冲中', e);
    this.setData({ buffering: true });
  },

  // 进度事件：网络加载进度（可用于调试）
  onVideoProgress(e: any) {
    console.log('📶 分享引导页 - 视频加载进度', e);
  },

  // 显示默认内容
  showDefaultContent() {
    const normalizeUrl = (u: string) => {
      if (!u) return '';
      return u.startsWith('http') ? u : (config.resourceDomain + u);
    };
    const defaultMediaUrl = this.data.isWinning && this.data.prizeImage ? 
      normalizeUrl(this.data.prizeImage) : normalizeUrl('/images/success.svg');
    
    // 智能判断默认媒体类型
    let mediaType = 'image';
    if (defaultMediaUrl) {
      const url = defaultMediaUrl.toLowerCase();
      if (url.includes('.mp4') || url.includes('.mov') || url.includes('.avi') || url.includes('.webm')) {
        mediaType = 'video';
      }
    }
    
    this.setData({
      shareContent: {
        has_share_content: false,
        share_title: this.data.isWinning ? 
          `🎉 我中奖了：${this.data.prizeName}！` : 
          '异企趣玩 - 抽奖活动火热进行中！',
        share_text: this.data.isWinning ? 
          '快来试试你的运气吧！' : 
          '扫码体验智能娃娃机，精彩奖品等你来抽！',
        media_type: mediaType,
        share_media_url: defaultMediaUrl
      },
      loading: false
    });
  },

  // 播放分享视频
  playShareVideo() {
    if (this.data.videoContext && this.data.shareContent?.media_type === 'video') {
      this.data.videoContext.play();
      this.setData({
        isVideoPlaying: true
      });
      console.log('🎬 分享引导页 - 开始播放视频');
    }
  },

  // 停止分享视频
  stopShareVideo() {
    if (this.data.videoContext) {
      this.data.videoContext.pause();
      this.setData({
        isVideoPlaying: false
      });
      console.log('⏸️ 分享引导页 - 停止视频播放');
    }
  },

  // 切换视频静音
  toggleVideoMute() {
    if (this.data.videoContext) {
      // 注意：小程序视频组件静音控制有限，这里主要是示意
      console.log('🔇 分享引导页 - 切换视频静音状态');
    }
  },

  // 视频播放事件
  onVideoPlay() {
    console.log('🎬 分享引导页 - 视频开始播放');
    this.setData({
      isVideoPlaying: true
    });
  },

  // 视频暂停事件
  onVideoPause() {
    console.log('⏸️ 分享引导页 - 视频暂停');
    this.setData({
      isVideoPlaying: false
    });
  },

  // 视频结束事件
  onVideoEnded() {
    console.log('🔚 分享引导页 - 视频播放结束');
    this.setData({
      isVideoPlaying: false
    });
  },

  // 视频出错事件
  onVideoError(e: any) {
    console.log('❌ 分享引导页 - 视频播放出错:', e.detail);
    wx.showToast({
      title: '视频加载失败',
      icon: 'none'
    });
  },

  // 返回上一页
  goBack() {
    wx.navigateBack();
  },
  
  // 分享给朋友
  onShareAppMessage() {
    const shareContent = this.data.shareContent;
    let title = '异企趣玩 - 抽奖活动火热进行中！';
    let desc = '快来一起抽奖，好运等着你！';
    let imageUrl = '';

    if (this.data.isWinning) {
      title = `🎉 我在异企趣玩中了${this.data.prizeName}！`;
      desc = `恭喜我中了${this.data.prizeName}，你也来试试运气吧！`;
      imageUrl = resolveImageUrl(this.data.prizeImage, this.data.orderId || this.data.prizeName);
    } else if (shareContent?.has_share_content) {
      title = shareContent.share_title || shareContent.share_text || title;
      desc = shareContent.share_text || desc;
      if (shareContent.media_type === 'image') {
        imageUrl = resolveImageUrl(shareContent.share_media_url, shareContent.id || shareContent.share_title);
      }
    }

    return {
      title: title,
      desc: desc,
      path: '/pages/index/index', // 分享后进入首页
      imageUrl: imageUrl
    };
  },

  // 分享到朋友圈
  onShareTimeline() {
    const shareContent = this.data.shareContent;
    let title = '异企趣玩 - 抽奖活动火热进行中！';
    let imageUrl = '';

    if (this.data.isWinning) {
      title = `🎉 我在异企趣玩中了${this.data.prizeName}！快来试试你的运气吧！`;
      imageUrl = resolveImageUrl(this.data.prizeImage, this.data.orderId || this.data.prizeName);
    } else if (shareContent?.has_share_content) {
      const shareTitle = shareContent.share_title || '';
      const shareText = shareContent.share_text || '';
      
      // 优先级：分享内容 > 分享标题 > 默认标题
      if (shareText) {
        title = shareText;
      } else if (shareTitle) {
        title = shareTitle;
      }

      if (shareContent.media_type === 'image') {
        imageUrl = resolveImageUrl(shareContent.share_media_url, shareContent.id || shareTitle || 'timeline');
      }
    }

    return {
      title: title,
      imageUrl: imageUrl
      // 朋友圈分享会进入当前页面的单页模式
    };
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
    else if (this.data.isWinning && this.data.prizeName) {
      text = `🎊刚参与了异企趣玩抽奖活动，支付¥${this.data.amount}就有机会获得大奖！我竟然中了${this.data.prizeName}！运气不错的话说不定你也能中大奖呢～有兴趣的朋友可以一起来试试！`;
    } else {
      // 没有API内容和中奖信息时使用通用文案
      text = `🎊刚参与了异企趣玩抽奖活动，支付¥${this.data.amount}就有机会获得大奖！运气不错的话说不定能中大奖呢～有兴趣的朋友可以一起来试试！`;
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

  // 保存分享图片
  saveShareImage() {
    let imageUrl = '';
    
    // 优先使用API返回的分享图片
    if (this.data.shareContent && this.data.shareContent.has_share_content && this.data.shareContent.share_media_url) {
      imageUrl = resolveImageUrl(this.data.shareContent.share_media_url, this.data.shareContent.id || 'share');
    }
    // 其次使用中奖奖品图片
    else if (this.data.isWinning && this.data.prizeImage) {
      imageUrl = resolveImageUrl(this.data.prizeImage, this.data.orderId || this.data.prizeName || 'prize');
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
        } else {
          wx.showToast({
            title: '下载图片失败',
            icon: 'error'
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

  // 去朋友圈
  gotoMoments() {
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

  // 触发分享（响应WXML中的分享按钮）
  triggerShare() {
    wx.showModal({
      title: '分享提示',
      content: '请点击页面右上角的 ••• 按钮，选择"分享到朋友圈"来分享您的内容！',
      showCancel: false,
      confirmText: '我知道了',
      success: () => {
        console.log('用户查看了分享提示');
      }
    });
  },

  // 防止点击关闭（用于阻止事件冒泡）
  preventClose() {
    // 阻止事件冒泡，防止意外关闭
  }
});

export {};
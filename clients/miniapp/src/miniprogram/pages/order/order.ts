// order.ts
// 订单记录页面逻辑

const apiConfig = require('../../config/api.js');
import { resolveImageUrl } from '../../utils/image';
import { DEFAULT_LOTTERY_AMOUNT } from '../../utils/defaults';

interface OrderItem {
  orderId: string;
  internal_id: string;
  amount: number;
  balance: number;
  pay_status: string;
  order_status: number;
  status: 'pending' | 'paid' | 'lottery' | 'completed' | 'refunded' | 'cancelled';
  createTime: string;
  payTime?: string;
  updateTime?: string;
  lotteryResult?: string;
  prize_id?: number;
  prizeName?: string;
  prizeDesc?: string;
  prizeImage?: string | null;
  device_id?: string;
  device_info?: {
    device_id: string;
    location: string;
    merchant: string;
  };
  pay_user?: string;
  wx_transaction_id?: string;
  verification_code?: string;
  verification_status?: number;
  verification_time?: string;
  can_refund?: boolean;
  can_verify?: boolean;
  game_type?: string;
  config_id?: string | number; // 添加配置ID字段
}

Page({
  data: {
    // 订单列表
    orderList: [] as OrderItem[],
    
    // 页面状态
    loading: false,
    isEmpty: false,
    loadingMore: false,
    hasMore: true,
    
    // 登录状态
    isLoggedIn: false,
    needLogin: false,
    
    // 筛选条件
    currentFilter: 'all', // all, pending, paid, cancelled, refunded
    filterOptions: [
      { key: 'all', name: '全部', count: 0 },
      { key: 'pending', name: '待支付', count: 0 },
      { key: 'paid', name: '已支付', count: 0 },
      { key: 'cancelled', name: '已取消', count: 0 },
      { key: 'refunded', name: '已退款', count: 0 }
    ],
    
    // 分页参数
    page: 1,
    pageSize: 10,
    
    // 下拉刷新
    refreshing: false,
    
    // 订单详情弹窗
    showOrderDetail: false,
    currentOrder: null as OrderItem | null,
    
    // 从页面参数传递的目标订单ID
    targetOrderId: '',
    
    // 抽奖动画
    lotteryAnimating: false,
    currentLotteryOrder: null as OrderItem | null,
    
    // 客服弹窗
    showServiceModal: false,
    
    // 奖品信息弹窗
    showPrizeModal: false,
    currentPrizeOrder: null as OrderItem | null,

    // 核销二维码弹窗
    showQrModal: false,
    qrImageUrl: '',
    qrLoading: false
  },

  onLoad(options: any) {
    console.log('订单记录页面加载', options);
    
    // 检查登录状态
    this.checkLoginStatus();
    
    // 如果传递了特定的订单ID，保存起来
    if (options.orderId) {
      this.setData({
        targetOrderId: options.orderId
      });
    }
  },

  onShow() {
    // 页面显示时检查登录状态并刷新数据
    this.checkLoginStatus();
  },

  // 检查登录状态
  checkLoginStatus() {
    const token = wx.getStorageSync('token');
    const userInfo = wx.getStorageSync('userInfo');
    const openid = wx.getStorageSync('openid');
    
    console.log('=== 订单页面登录状态检测 ===');
    console.log('token:', token);
    console.log('userInfo:', userInfo);
    console.log('openid:', openid);
    console.log('token存在:', !!token);
    console.log('userInfo存在:', !!userInfo);
    console.log('openid存在:', !!openid);
    console.log('==============================');
    
    // 订单页面需要检查 token、userInfo 和 openid 三个关键信息
    if (token && userInfo && openid) {
      // 已登录且有完整信息，加载订单数据
      console.log('用户已登录且信息完整，加载订单数据');
      this.setData({
        isLoggedIn: true,
        needLogin: false
      });
      this.loadOrderData();
    } else {
      // 登录信息不完整，显示登录提示
      console.log('用户登录信息不完整，显示登录界面');
      console.log('缺少信息:', {
        token: !token ? '缺少token' : '有token',
        userInfo: !userInfo ? '缺少userInfo' : '有userInfo', 
        openid: !openid ? '缺少openid' : '有openid'
      });
      
      this.setData({
        isLoggedIn: false,
        needLogin: true,
        orderList: [],
        isEmpty: true
      });
      
      // 如果有部分登录信息但不完整，提示重新登录
      if (token || userInfo) {
        wx.showToast({
          title: '登录信息不完整，请重新登录',
          icon: 'none',
          duration: 2000
        });
        
        // 清除不完整的登录信息
        wx.removeStorageSync('token');
        wx.removeStorageSync('userInfo');
        wx.removeStorageSync('openid');
      }
    }
  },

  // 加载订单数据（已登录状态下）
  loadOrderData() {
    // 使用真实API获取订单数据
    this.loadOrderList();
    
    // 如果有传递的订单ID，显示该订单详情
    if (this.data.targetOrderId) {
      setTimeout(() => {
        const order = this.data.orderList.find((o: any) => o.orderId === this.data.targetOrderId);
        if (order) {
          this.showOrderDetail({ currentTarget: { dataset: { index: this.data.orderList.indexOf(order) } } });
        } else {
          wx.showToast({
            title: '订单不存在',
            icon: 'none'
          });
        }
      }, 100);
    }
  },

  // =================== 真实API调用方法 ===================
  
  // 加载订单列表
  loadOrderList() {
    const openid = wx.getStorageSync('openid');
    const token = wx.getStorageSync('token');
    
    if (!openid || !token) {
      console.log('未登录，无法获取订单列表');
      this.setData({
        isLoggedIn: false,
        needLogin: true,
        loading: false
      });
      return;
    }

    this.setData({ loading: true });

    // 根据API文档，直接使用前端筛选状态作为status参数
    const statusParam = this.data.currentFilter === 'all' ? 'all' : this.data.currentFilter;

    console.log('筛选参数:', {
      currentFilter: this.data.currentFilter,
      statusParam: statusParam,
      说明: statusParam === 'cancelled' ? '请求已取消订单' : statusParam === 'paid' ? '请求已支付订单' : statusParam === 'all' ? '请求全部订单' : '其他筛选'
    });

    // 构建请求参数（根据API文档）
    const requestData: any = {
      openid: openid,
      page: this.data.page,
      page_size: this.data.pageSize,
      status: statusParam
    };

    console.log('API请求参数:', requestData);

    // 使用正确的订单列表API地址（根据API文档）
    wx.request({
      url: `${apiConfig.adminUrl}/orders/getList`,
      method: 'GET',
      data: requestData,
      success: (res: any) => {
        console.log('=== 订单API响应 ===');
        console.log('完整响应:', res);
        console.log('状态码:', res.statusCode);
        console.log('响应数据:', res.data);
        console.log('================');
        
        // 处理API响应（根据API文档格式）
        if (res.statusCode === 200 && res.data && res.data.code === 1) {
          const responseData = res.data.data;
          console.log('API响应数据结构:', {
            list_length: responseData.list ? responseData.list.length : 0,
            total: responseData.total,
            page: responseData.page,
            has_more: responseData.has_more,
            filter_counts: responseData.filter_counts
          });
          
          if (responseData && responseData.list && Array.isArray(responseData.list)) {
            // 转换数据格式以匹配前端
            const processedOrderList = responseData.list.map((item: any) => {
              // 统一解析奖品图片URL（含占位图与域名标准化）
              const prizeImageUrl = resolveImageUrl(item.prize_image, item.order_id);
              console.log(`订单 ${item.order_id} 图片解析结果:`, prizeImageUrl);
              
              // 根据准确的order_status定义来判断前端显示状态
              let frontendStatus: 'pending' | 'paid' | 'lottery' | 'completed' | 'refunded' | 'cancelled' = 'pending';
              
              console.log(`订单 ${item.order_id} 状态信息:`, {
                pay_status: item.pay_status,
                order_status: item.order_status,
                prize_id: item.prize_id,
                lottery_result: item.lottery_result,
                verification_status: item.verification_status
              });
              
              // 优先根据 order_status 判断订单最终状态
              if (item.order_status === 3) {
                frontendStatus = 'cancelled'; // 已取消
              } else if (item.order_status === 4) {
                frontendStatus = 'refunded'; // 已退款
              } else if (item.pay_status === 'pending') {
                frontendStatus = 'pending'; // 待支付
              } else if (item.pay_status === 'paid') {
                // 已支付状态，根据订单处理状态和业务逻辑判断
                if (item.order_status === 0) {
                  frontendStatus = 'lottery'; // 待处理（可能是待抽奖）
                } else if (item.order_status === 1) {
                  // 已处理，根据是否有奖品信息判断
                  if (item.prize_id && item.prize_id > 0) {
                    // 有奖品，根据核销状态判断
                    frontendStatus = item.verification_status === 1 ? 'completed' : 'paid';
                  } else {
                    frontendStatus = 'paid'; // 已支付
                  }
                } else if (item.order_status === 2) {
                  frontendStatus = 'completed'; // 已完成
                } else {
                  frontendStatus = 'paid'; // 默认已支付状态
                }
              }
              
              return {
                orderId: item.order_id,
                internal_id: item.internal_id,
                amount: parseFloat(item.amount) || DEFAULT_LOTTERY_AMOUNT,
                balance: parseFloat(item.balance) || 0,
                pay_status: item.pay_status,
                order_status: item.order_status,
                status: frontendStatus,
                createTime: item.create_time,
                payTime: item.pay_time,
                updateTime: item.update_time,
                lotteryResult: item.lottery_result,
                prize_id: item.prize_id,
                prizeName: item.prize_name,
                prizeDesc: item.prize_desc,
                prizeImage: prizeImageUrl,
                device_id: item.device_id,
                device_info: item.device_info,
                pay_user: item.pay_user,
                wx_transaction_id: item.wx_transaction_id,
                verification_code: item.verification_code,
                verification_status: item.verification_status,
                verification_time: item.verification_time,
                can_refund: item.can_refund || false,
                can_verify: item.can_verify || false,
                game_type: item.game_type,
                config_id: item.config_id
              };
            });

            // 更新筛选计数（使用API返回的统计数据）
            const filterCounts = responseData.filter_counts || {};
            console.log('API返回的筛选计数:', filterCounts);
            
            const filterOptions = this.data.filterOptions.map(option => ({
              ...option,
              count: filterCounts[option.key] || 0
            }));

            // 根据页码决定是替换还是追加数据
            const newOrderList = this.data.page === 1 ? processedOrderList : [...this.data.orderList, ...processedOrderList];
            
            console.log('更新订单列表:', {
              currentPage: this.data.page,
              newDataCount: processedOrderList.length,
              totalCount: newOrderList.length,
              isEmpty: newOrderList.length === 0
            });

            this.setData({
              orderList: newOrderList,
              hasMore: responseData.has_more || false,
              isEmpty: newOrderList.length === 0,
              filterOptions,
              loading: false
            });
            
            console.log('订单数据处理完成，共', processedOrderList.length, '条');
            console.log('处理后的订单状态分布:', processedOrderList.reduce((acc: any, order: OrderItem) => {
              acc[order.status] = (acc[order.status] || 0) + 1;
              return acc;
            }, {}));
          } else {
            console.log('API返回的数据格式异常');
            this.setData({
              loading: false,
              isEmpty: true,
              orderList: []
            });
            wx.showToast({ title: '数据格式异常', icon: 'none' });
          }
        } else {
          console.log('API返回失败，错误信息:', (res.data && res.data.msg) || '未知错误');
          this.setData({
            loading: false,
            isEmpty: true,
            orderList: []
          });
          wx.showToast({ title: (res.data && res.data.msg) || '获取订单失败', icon: 'none' });
        }
      },
      fail: (error: any) => {
        console.error('获取订单列表API请求失败:', error);
        this.setData({
          loading: false,
          isEmpty: true,
          orderList: []
        });
        wx.showToast({ title: '网络请求失败', icon: 'none' });
      }
    });
  },

  // 获取订单详情
  getOrderDetail(orderId: string, callback?: (order: OrderItem | null) => void) {
    const openid = wx.getStorageSync('openid');
    const token = wx.getStorageSync('token');
    
    if (!openid || !token) {
      console.log('未登录，无法获取订单详情');
      callback && callback(null);
      return;
    }

    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.getOrderDetail}`,
      method: 'GET',
      data: {
        openid: openid,
        order_id: orderId
      },
      success: (res: any) => {
        if (res.data.code === 1) {
          const item = res.data.data;
          
          // 统一解析奖品图片URL（含占位图与域名标准化）
          const prizeImageUrl = resolveImageUrl(item.prize_image, item.order_id);
          console.log(`获取订单详情 ${item.order_id} 图片解析结果:`, prizeImageUrl);
          
          // 根据order_status映射前端状态
          let frontendStatus: 'pending' | 'paid' | 'lottery' | 'completed' | 'refunded' = 'pending';
          if (item.pay_status === 'paid') {
            if (item.order_status === 1) {
              frontendStatus = item.lottery_result === 'no_prize' ? 'completed' : 'lottery';
            } else if (item.order_status === 2) {
              frontendStatus = 'completed';
            } else {
              frontendStatus = 'paid';
            }
          }
          
          const order = {
            orderId: item.order_id,
            internal_id: item.internal_id,
            amount: parseFloat(item.amount) || 0.01,
            balance: parseFloat(item.balance) || 0,
            pay_status: item.pay_status,
            order_status: item.order_status,
            status: frontendStatus,
            createTime: item.create_time,
            payTime: item.pay_time,
            updateTime: item.update_time,
            lotteryResult: item.lottery_result,
            prize_id: item.prize_id,
            prizeName: item.prize_name,
            prizeDesc: item.prize_desc,
            prizeImage: prizeImageUrl,
            device_id: item.device_id,
            device_info: item.device_info,
            pay_user: item.pay_user,
            wx_transaction_id: item.wx_transaction_id,
            verification_code: item.verification_code,
            verification_status: item.verification_status,
            verification_time: item.verification_time,
            can_refund: item.can_refund,
            can_verify: item.can_verify,
            game_type: item.game_type,
            config_id: item.config_id  // 添加 config_id 字段
          };
          callback && callback(order);
        } else {
          wx.showToast({ title: res.data.msg || '获取订单详情失败', icon: 'none' });
          callback && callback(null);
        }
      },
      fail: (error: any) => {
        console.error('获取订单详情失败:', error);
        wx.showToast({ title: '获取订单详情失败', icon: 'none' });
        callback && callback(null);
      }
    });
  },

  // 订单操作（退款/取消等）
  orderAction(orderId: string, action: string, reason?: string) {
    const openid = wx.getStorageSync('openid');
    const token = wx.getStorageSync('token');
    
    if (!openid || !token) {
      wx.hideLoading();
      wx.showToast({ title: '请先登录', icon: 'none' });
      this.handleLogin();
      return;
    }

    console.log('=== 订单操作API调用 ===');
    console.log('订单ID:', orderId);
    console.log('操作类型:', action);
    console.log('原因:', reason);
    console.log('=====================');

    wx.request({
      url: `${apiConfig.adminUrl}/${apiConfig.api.orderAction}`,
      method: 'POST',
      data: {
        openid: openid,
        order_id: orderId,
        action: action,
        reason: reason
      },
      success: (res: any) => {
        wx.hideLoading();
        console.log('=== 订单操作API响应 ===');
        console.log('完整响应:', res);
        console.log('=====================');
        const ok = res.statusCode === 200 && res.data && res.data.code === 1;
        if (ok) {
          wx.showToast({ title: (res.data.data && res.data.data.message) || res.data.msg || '操作成功', icon: 'success' });
          // 先立即刷新当前订单条目，保证按钮状态及时更新
          const index = this.data.orderList.findIndex(o => o.orderId === orderId);
          this.getOrderDetail(orderId, (updated) => {
            if (updated) {
              const newList = [...this.data.orderList];
              if (index >= 0) {
                newList[index] = updated;
                this.setData({ orderList: newList });
              }
            }
          });
          // 随后拉取最新列表与统计，确保计数与服务端一致
          setTimeout(() => {
            this.setData({ page: 1 });
            this.loadOrderList();
          }, 800);
        } else {
          wx.showToast({ title: (res.data && res.data.msg) || '操作失败', icon: 'none' });
        }
      },
      fail: (error: any) => {
        wx.hideLoading();
        console.error('订单操作API请求失败:', error);
        wx.showToast({ title: '网络请求失败', icon: 'none' });
      }
    });
  },

  // =================== 原有模拟数据方法 ===================

  // 更新筛选条件计数
  updateFilterCounts(orders: OrderItem[]) {
    const counts: Record<string, number> = {
      all: orders.length,
      pending: orders.filter(o => o.status === 'pending').length,
      paid: orders.filter(o => o.status === 'paid').length,
      lottery: orders.filter(o => o.status === 'lottery').length,
      completed: orders.filter(o => o.status === 'completed').length,
      cancelled: orders.filter(o => o.status === 'cancelled').length,
      refunded: orders.filter(o => o.status === 'refunded').length
    };

    return this.data.filterOptions.map(option => ({
      ...option,
      count: counts[option.key] || 0
    }));
  },

  // 切换筛选条件
  switchFilter(e: any) {
    const filter = e.currentTarget.dataset.filter;
    console.log('切换筛选条件:', filter, '当前:', this.data.currentFilter);
    
    if (filter === this.data.currentFilter) return;
    
    this.setData({ 
      currentFilter: filter
    });
    
    // 根据筛选条件过滤订单
    this.filterOrders();
  },

  // 过滤订单
  filterOrders() {
    console.log('开始过滤订单，重置页码为1');
    // 重置页码并重新请求API
    this.setData({ 
      page: 1,
      orderList: [], // 清空现有订单列表
      hasMore: true
    });
    // 使用真实API重新获取数据
    this.loadOrderList();
  },

  // 显示订单详情
  showOrderDetail(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    this.setData({
      currentOrder: order,
      showOrderDetail: true
    });
  },

  // 关闭订单详情
  closeOrderDetail() {
    this.setData({
      showOrderDetail: false,
      currentOrder: null
    });
  },

  // 开始抽奖
  startLottery(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    if (order.status !== 'paid') {
      wx.showToast({ title: '订单状态不正确', icon: 'none' });
      return;
    }
    
    this.setData({
      lotteryAnimating: true,
      currentLotteryOrder: order
    });
    
    // 显示抽奖loading
    wx.showLoading({ title: '抽奖中...', mask: true });
    
    // 模拟抽奖过程 - 生产环境禁用
    /*
    setTimeout(() => {
      this.performLottery(order);
    }, 2000);
    */
    
    // 生产环境：直接调用真实抽奖API
    this.performLottery(order);
  },

  // 执行抽奖逻辑 - 生产环境使用真实API
  performLottery(order: OrderItem) {
    // 读取参数以避免未使用告警，并便于真实API实现
    const orderId = order.orderId;
    console.log('准备发起抽奖，订单ID:', orderId);
    // 生产环境：调用真实抽奖API
    // TODO: 实现真实抽奖API调用
    
    /*
    // 模拟数据 - 生产环境禁用
    // 100%中奖逻辑 - 移除未中奖情况
    const prizes = [
      { name: '🎉 恭喜中奖', desc: '获得精美礼品一份', image: undefined },
      { name: '🎁 幸运奖', desc: '获得特别奖品', image: undefined },
      { name: '🏆 大奖', desc: '获得超值大奖', image: undefined },
      { name: '💎 钻石奖', desc: '获得钻石级奖品', image: undefined },
      { name: '🌟 星级奖', desc: '获得星级奖品', image: undefined }
    ];
    
    // 随机选择一个奖品（100%中奖）
    const randomIndex = Math.floor(Math.random() * prizes.length);
    const selectedPrize = prizes[randomIndex];
    */
    
    // 临时处理：显示需要实现真实API
    wx.hideLoading();
    wx.showToast({
      title: '请实现真实抽奖API',
      icon: 'none'
    });
    
    /*
    // 模拟数据处理 - 生产环境禁用
    const result = {
      status: 'winner',
      prizeName: selectedPrize.name,
      prizeDesc: selectedPrize.desc,
      prizeImage: selectedPrize.image
    };
    
    // 更新订单状态
    const updatedOrder = {
      ...order,
      status: 'lottery' as const,
      lotteryTime: new Date().toLocaleString(),
      lotteryResult: result.status,
      prizeName: result.prizeName,
      prizeDesc: result.prizeDesc,
      prizeImage: result.prizeImage
    };
    
    const orderList = [...this.data.orderList];
    orderList[index] = updatedOrder;
    
    wx.hideLoading();
    
    this.setData({
      orderList,
      lotteryAnimating: false,
      currentLotteryOrder: null
    });
    
    // 显示抽奖结果
    this.showLotteryResult(result);
    
    // 更新筛选计数
    const filterOptions = this.updateFilterCounts(orderList);
    this.setData({ filterOptions });
    */
  },

  // 显示抽奖结果
  showLotteryResult(result: any) {
    // 由于现在100%中奖，所以总是显示中奖弹窗
    wx.showModal({
      title: '🎉 恭喜中奖!',
      content: result.prizeDesc,
      confirmText: '查看奖品',
      cancelText: '确定',
      success: (res) => {
        if (res.confirm) {
          // 跳转到奖品详情或个人中心
          wx.switchTab({ url: '/pages/my/my' });
        }
      }
    });
  },

  // 申请退款
  requestRefund(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    console.log('=== 退款按钮点击 ===');
    console.log('订单信息:', order);
    console.log('支付状态:', order.pay_status);
    console.log('订单状态:', order.order_status);
    console.log('前端状态:', order.status);
    console.log('可退款:', order.can_refund);
    console.log('==================');
    
    // 检查是否可以退款
    if (order.pay_status !== 'paid') {
      wx.showToast({ title: '订单未支付，无法退款', icon: 'none' });
      return;
    }
    
    if (!order.can_refund) {
      wx.showToast({ title: '该订单不支持退款', icon: 'none' });
      return;
    }
    
    wx.showModal({
      title: '申请退款',
      content: `确定要申请退款吗？订单金额：¥${order.amount}`,
      confirmText: '确定退款',
      cancelText: '取消',
      success: (res) => {
        console.log('=== 退款确认弹窗结果 ===');
        console.log('用户点击了:', res.confirm ? '确定退款' : '取消');
        console.log('========================');
        
        if (res.confirm) {
          this.processRefund(order);
        }
      }
    });
  },

  // 处理退款
  processRefund(order: OrderItem) {
    console.log('=== 开始处理退款 ===');
    console.log('订单ID:', order.orderId);
    console.log('订单金额:', order.amount);
    console.log('==================');
    
    wx.showLoading({ title: '处理中...', mask: true });
    
    // 使用真实API处理退款
    this.orderAction(order.orderId, 'refund', '用户申请退款');
    
    // 模拟退款处理（备用方案）
    // setTimeout(() => {
    //   const updatedOrder = {
    //     ...order,
    //     status: 'refunded' as const
    //   };
    //   
    //   const orderList = [...this.data.orderList];
    //   orderList[index] = updatedOrder;
    //   
    //   wx.hideLoading();
    //   
    //   this.setData({ orderList });
    //   
    //   wx.showToast({ title: '退款申请已提交', icon: 'success' });
    // }, 2000);
  },

  // 复制订单号
  copyOrderId(e: any) {
    const orderId = e.currentTarget.dataset.orderid;
    
    wx.setClipboardData({
      data: orderId,
      success: () => {
        wx.showToast({ title: '已复制订单号', icon: 'success' });
      }
    });
  },

  // 复制核销码
  copyVerificationCode(e: any) {
    const code = e.currentTarget.dataset.code;
    
    wx.setClipboardData({
      data: code,
      success: () => {
        wx.showToast({ title: '已复制核销码', icon: 'success' });
      }
    });
  },

  // 显示核销码二维码
  showVerificationQr(e: any) {
    const code = e.currentTarget.dataset.code || '';
    if (!code) {
      wx.showToast({ title: '无核销码', icon: 'none' });
      return;
    }
    this.setData({ qrLoading: true, showQrModal: true, qrImageUrl: '' });
    wx.request({
      url: `${apiConfig.adminUrl}/qrcode/verify`,
      method: 'GET',
      data: { code },
      success: (res: any) => {
        const ok = res.statusCode === 200 && res.data && res.data.code === 1;
        if (ok) {
          const url = (res.data.data && res.data.data.qrcode_url) ? res.data.data.qrcode_url : '';
          this.setData({ qrImageUrl: url, qrLoading: false });
        } else {
          this.setData({ qrLoading: false, showQrModal: false });
          wx.showToast({ title: res.data && res.data.msg ? res.data.msg : '二维码生成失败', icon: 'none' });
        }
      },
      fail: () => {
        this.setData({ qrLoading: false, showQrModal: false });
        wx.showToast({ title: '网络错误', icon: 'none' });
      }
    });
  },

  // 关闭二维码弹窗
  closeQrModal() {
    this.setData({ showQrModal: false, qrImageUrl: '', qrLoading: false });
  },

  // 核销奖品
  verifyPrize(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    if (!order.verification_code) {
      wx.showToast({ title: '核销码不存在', icon: 'none' });
      return;
    }
    
    // 显示奖品信息弹窗
    this.setData({
      showPrizeModal: true,
      currentPrizeOrder: order
    });
  },

  // 扫码并核销
  scanVerify(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];

    const scanner = require('../../utils/scanner.js');
    wx.showLoading({ title: '正在扫码...' });
    scanner.scanCode()
      .then((resp: any) => {
        wx.hideLoading();
        if (resp.code === 1) {
          const data = resp.data || {};
          if (data.can_verify) {
            const orderId = order.orderId || data.order_id;
            const verificationCode = data.verification_code || order.verification_code;
            this.performVerification(orderId, verificationCode);
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

  // 关闭奖品信息弹窗
  closePrizeModal() {
    this.setData({
      showPrizeModal: false,
      currentPrizeOrder: null
    });
  },

  // 执行核销操作
  performVerification(orderId: string, verificationCode: string) {
     const openid = wx.getStorageSync('openid');
     
     wx.request({
       url: `${apiConfig.adminUrl}/${apiConfig.api.verifyOrderCode}`,
      method: 'POST',
      data: {
        openid: openid,
        order_id: orderId,
        verification_code: verificationCode
      },
      success: (res: any) => {
        if (res.data.code === 1) {
          wx.showToast({ title: '核销成功', icon: 'success' });
          // 刷新订单列表
          setTimeout(() => {
            this.loadOrderList();
          }, 1000);
        } else {
          wx.showToast({ title: res.data.msg || '核销失败', icon: 'none' });
        }
      },
      fail: (error: any) => {
        console.error('核销请求失败:', error);
        wx.showToast({ title: '核销失败', icon: 'none' });
      }
    });
  },

  // 联系客服
  contactService() {
    this.setData({
      showServiceModal: true
    });
  },

  // 关闭客服弹窗
  closeServiceModal() {
    this.setData({
      showServiceModal: false
    });
  },

  // 复制微信号
  copyWechatNumber() {
    wx.setClipboardData({
      data: 'service_wechat',
      success: () => {
        wx.showToast({ title: '已复制微信号', icon: 'success' });
        this.closeServiceModal();
      }
    });
  },

  // 前往抽奖页面
  goToLottery(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    console.log('=== 前往抽奖页面 ===');
    console.log('订单信息:', order);
    console.log('订单状态:', order.order_status);
    console.log('抽奖结果:', order.lotteryResult);
    console.log('=================');
    
    if (order.pay_status !== 'paid') {
      wx.showToast({ title: '订单未支付，无法抽奖', icon: 'none' });
      return;
    }
    
    // 修改判断逻辑：只有order_status为2才算已完成，不能再抽奖
    if (order.order_status === 2) {
      wx.showToast({ title: '该订单已完成，无法抽奖', icon: 'none' });
      return;
    }
    
    // 对于必中玩法，不再允许重新抽奖
    if (order.lotteryResult === 'winner') {
      wx.showToast({ title: '该订单已中奖，无需重新抽奖', icon: 'none' });
      return;
    }
    
    // 跳转到抽奖页面
    // 先获取订单详情，确保有最新的config_id
    console.log('=== 开始获取最新订单信息 ===');
    console.log('当前config_id:', order.config_id);
    console.log('============================');
    
    wx.showLoading({ title: '获取订单信息...', mask: true });
    
    this.getOrderDetail(order.orderId, (updatedOrder) => {
      wx.hideLoading();
      
      if (!updatedOrder) {
        wx.showToast({ title: '获取订单信息失败', icon: 'none' });
        return;
      }
      
      console.log('=== 获取到最新订单信息 ===');
      console.log('更新后的config_id:', updatedOrder.config_id);
      console.log('==========================');
      
      // 使用最新的订单信息跳转到抽奖页面
      const configId = updatedOrder.config_id || '';
      
      wx.navigateTo({
        url: `/pages/game-launcher/game-launcher?orderId=${updatedOrder.orderId}&amount=${updatedOrder.amount}&paymentTime=${updatedOrder.payTime ? Math.floor(new Date(updatedOrder.payTime).getTime() / 1000) : Math.floor(Date.now() / 1000)}&deviceId=${updatedOrder.device_id || ''}&configId=${configId}`
      });
    });
  },

  // 继续支付
  continuePay(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    console.log('=== 继续支付按钮点击 ===');
    console.log('订单信息:', order);
    console.log('订单状态:', order.order_status);
    console.log('=====================');
    
    if (order.order_status !== 0) {
      wx.showToast({ title: '订单状态不正确', icon: 'none' });
      return;
    }
    
    wx.showModal({
      title: '继续支付',
      content: `订单金额：¥${order.amount}\n确定要继续支付吗？`,
      confirmText: '立即支付',
      cancelText: '取消',
      success: (res) => {
        if (res.confirm) {
          this.processContinuePay(order);
        }
      }
    });
  },

  // 处理继续支付
  processContinuePay(order: OrderItem) {
    console.log('=== 开始处理继续支付 ===');
    console.log('订单ID:', order.orderId);
    console.log('订单金额:', order.amount);
    console.log('当前config_id:', order.config_id);
    console.log('========================');
    
    const token = wx.getStorageSync('token');
    
    if (!token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    
    // 先获取订单详情，确保有最新的config_id
    wx.showLoading({ title: '获取订单信息...', mask: true });
    
    this.getOrderDetail(order.orderId, (updatedOrder) => {
      if (!updatedOrder) {
        wx.hideLoading();
        wx.showToast({ title: '获取订单信息失败', icon: 'none' });
        return;
      }
      
      console.log('=== 获取到最新订单信息 ===');
      console.log('更新后的config_id:', updatedOrder.config_id);
      console.log('==========================');
      
      wx.showLoading({ title: '正在发起支付...', mask: true });
      
      // 使用专门的重新支付接口
      wx.request({
        url: apiConfig.apiUrl + 'pay/repay',
        method: 'POST',
        header: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        data: {
          order_id: updatedOrder.orderId,
          user_id: wx.getStorageSync('openid'),
          // 强制真实支付模式：后端识别 force_real=1 直接返回真实微信支付参数
          force_real: 1
        },
        success: (res: any) => {
          wx.hideLoading();
          console.log('继续支付API响应:', res);
          
          if (res.statusCode === 200 && res.data && res.data.code === 1) {
            const paymentData = res.data.data;

            // 测试模式：后端已确认支付并返回测试参数，前端不调起微信支付
            if (paymentData && typeof paymentData.package === 'string' && paymentData.package.indexOf('test') !== -1) {
              wx.showToast({ title: '测试模式：已确认支付', icon: 'success' });
              setTimeout(() => {
                wx.navigateTo({
                  url: `/pages/game-launcher/game-launcher?orderId=${updatedOrder.orderId}&amount=${updatedOrder.amount}&paymentTime=${Math.floor(Date.now()/1000)}&deviceId=${updatedOrder.device_id || ''}&configId=${updatedOrder.config_id || ''}`
                });
              }, 1200);
              return;
            }

            // 发起微信支付（真实模式）
            wx.requestPayment({
              timeStamp: paymentData.timeStamp,
              nonceStr: paymentData.nonceStr,
              package: paymentData.package,
              signType: paymentData.signType,
              paySign: paymentData.paySign,
              success: (payRes: any) => {
                console.log('支付成功:', payRes);
                wx.showToast({
                  title: '支付成功！',
                  icon: 'success',
                  success: () => {
                    // 使用最新的订单信息跳转到抽奖页面
                    setTimeout(() => {
                      wx.navigateTo({
                        url: `/pages/game-launcher/game-launcher?orderId=${updatedOrder.orderId}&amount=${updatedOrder.amount}&paymentTime=${Math.floor(Date.now()/1000)}&deviceId=${updatedOrder.device_id || ''}&configId=${updatedOrder.config_id || ''}`
                      });
                    }, 1500);
                  }
                });
              },
              fail: (payErr: any) => {
                console.error('支付失败:', payErr);
                if (payErr.errMsg === 'requestPayment:fail cancel') {
                  wx.showToast({ title: '支付已取消', icon: 'none' });
                } else {
                  wx.showToast({ title: '支付失败，请重试', icon: 'none' });
                }
              }
            });
          } else {
            wx.showToast({ title: res.data.msg || '发起支付失败', icon: 'none' });
          }
        },
        fail: (error: any) => {
          wx.hideLoading();
          console.error('继续支付请求失败:', error);
          wx.showToast({ title: '网络请求失败', icon: 'none' });
        }
      });
    });
  },

  // 取消订单
  cancelOrder(e: any) {
    const index = e.currentTarget.dataset.index;
    const order = this.data.orderList[index];
    
    console.log('=== 取消订单按钮点击 ===');
    console.log('订单信息:', order);
    console.log('订单状态:', order.order_status);
    console.log('=====================');
    
    if (order.order_status !== 0) {
      wx.showToast({ title: '只有待支付订单才能取消', icon: 'none' });
      return;
    }
    
    wx.showModal({
      title: '取消订单',
      content: `确定要取消此订单吗？\n订单号：${order.orderId}\n金额：¥${order.amount}`,
      confirmText: '确定取消',
      cancelText: '保留订单',
      confirmColor: '#ff4444',
      success: (res) => {
        if (res.confirm) {
          this.processCancelOrder(order);
        }
      }
    });
  },

  // 处理取消订单
  processCancelOrder(order: OrderItem) {
    console.log('=== 开始处理取消订单 ===');
    console.log('订单ID:', order.orderId);
    console.log('======================');
    
    wx.showLoading({ title: '处理中...', mask: true });
    
    // 使用真实API处理取消订单
    this.orderAction(order.orderId, 'cancel', '用户主动取消订单');
  },

  // 阻止事件冒泡
  stopPropagation() {
    // 空函数，用于阻止事件冒泡
  },

  // 处理图片加载错误
  handleImageError(e: any) {
    console.error('奖品图片加载失败:', e);
    const fallback = resolveImageUrl('', 'order-image-error');
    const currentPrize = this.data.currentPrizeOrder;
    if (currentPrize) {
      this.setData({ currentPrizeOrder: { ...currentPrize, prizeImage: fallback } });
    }
    wx.showToast({ title: '图片已替换为占位图', icon: 'none' });
  },

  // =================== 登录相关方法 ===================
  
  // 处理登录按钮点击（直接复制首页逻辑）
  handleLogin() {
    this.doDirectLogin();
  },

  // 直接登录（复制首页逻辑）
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

  // 执行登录（复制首页逻辑）
  doLogin(code: string, userInfo: any) {
    wx.showLoading({ title: '登录中...' });
    
    // 调用已有的后端登录接口
    
    wx.request({
      url: apiConfig.apiUrl + apiConfig.api.wechatLogin,
      method: 'POST',
      data: {
        code: code,
        userInfo: userInfo
      },
      success: (res: any) => {
        wx.hideLoading();
        
        console.log('=== 登录API完整响应 ===');
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
            nickName: loginData.nickname || userInfo.nickName,
            avatarUrl: loginData.avatar || userInfo.avatarUrl,
            userId: loginData.user_id,
            openid: loginData.openid
          };
          
          wx.setStorageSync('userInfo', savedUserInfo);
          
          console.log('=== 保存到本地存储的信息 ===');
          console.log('token:', wx.getStorageSync('token'));
          console.log('openid:', wx.getStorageSync('openid'));
          console.log('userInfo:', wx.getStorageSync('userInfo'));
          console.log('==============================');
          
          // 更新页面状态
          this.setData({
            isLoggedIn: true,
            userInfo: savedUserInfo,
            needLogin: false
          });
          
          wx.showToast({
            title: '登录成功',
            icon: 'success',
            duration: 1500,
            success: () => {
              // 登录成功后重新检查状态并加载订单
              setTimeout(() => {
                this.checkLoginStatus();
              }, 500);
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
      fail: (error) => {
        wx.hideLoading();
        console.error('登录请求失败:', error);
        wx.showToast({
          title: '登录失败，请重试',
          icon: 'none'
        });
      }
    });
  },

  // 分享给朋友 - 统一跳转到首页
  onShareAppMessage() {
    // 统一解析分享图片：优先当前奖品图，其次订单列表中首个奖品图，兜底站内占位图
    const current = this.data.currentPrizeOrder || this.data.currentOrder || null;
    let src = (current && current.prizeImage) ? current.prizeImage as string : '';
    if (!src && Array.isArray(this.data.orderList)) {
      const firstWithImage = this.data.orderList.find(o => !!o.prizeImage);
      if (firstWithImage && firstWithImage.prizeImage) src = firstWithImage.prizeImage;
    }
    const imageUrl = resolveImageUrl(src, (current && current.orderId) || 'order-share');

    return {
      title: '异企趣玩 - 抽奖活动火热进行中！',
      desc: '快来一起抽奖，好运等着你！',
      path: '/pages/index/index', // 强制跳转到首页
      imageUrl
    };
  },

  // 分享到朋友圈 - 统一跳转到单页面  
  onShareTimeline() {
    const current = this.data.currentPrizeOrder || this.data.currentOrder || null;
    let src = (current && current.prizeImage) ? current.prizeImage as string : '';
    if (!src && Array.isArray(this.data.orderList)) {
      const firstWithImage = this.data.orderList.find(o => !!o.prizeImage);
      if (firstWithImage && firstWithImage.prizeImage) src = firstWithImage.prizeImage;
    }
    const imageUrl = resolveImageUrl(src, (current && current.orderId) || 'order-timeline');

    return {
      title: '异企趣玩 - 抽奖活动火热进行中！',
      path: '/pages/timeline-share/timeline-share', // 跳转到朋友圈单页面
      imageUrl
    };
  }
});

export {};

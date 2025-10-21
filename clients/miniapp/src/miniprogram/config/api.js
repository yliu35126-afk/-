// 小程序API配置文件
// 动态环境切换：统一从 environment.js 获取并支持一键切换
const env = require('./environment.js');
const cur = env.getCurrentConfig();

// 从 apiBaseUrl 推导基础域名（去掉 /api 或 /api.php）
function deriveBaseUrl(apiBaseUrl) {
  try {
    let url = apiBaseUrl.replace(/\/?api(\.php)?\/?$/i, '/');
    url = url.replace(/\/?index\.php\/?$/i, '/');
    return url.endsWith('/') ? url : (url + '/');
  } catch (_) {
    return apiBaseUrl;
  }
}

const dynamic = {
  baseUrl: deriveBaseUrl(cur.apiBaseUrl),
  apiUrl: cur.apiBaseUrl,
  // 后台入口使用 index.php/api，保持与后端一致
  adminUrl: deriveBaseUrl(cur.apiBaseUrl) + 'index.php/api',
  resourceDomain: deriveBaseUrl(cur.apiBaseUrl).replace(/\/$/, ''),
  wsUrl: cur.websocketUrl
};

const config = {
  // 基础配置（根据环境动态设置）
  baseUrl: dynamic.baseUrl,
  apiUrl: dynamic.apiUrl,
  adminUrl: dynamic.adminUrl,
  // 资源域名配置（用于图片、媒体等静态资源）
  resourceDomain: dynamic.resourceDomain,
  // 可定制占位图（如设置为 '/assets/img/placeholder/prize.png' 或 CDN 完整地址）
  placeholders: {
    // 统一站内占位图：保持与后端当前占位图一致
    // 后端控制器使用 cdnurl('/assets/img/default_prize.png', true)
    // 若后续迁移到 /assets/fallback_prizes/，只需改此处
    prizeImage: '/assets/img/default_prize.png'
  },
  // 统一资源URL规范化（修复 10.0.2.2/localhost/局域网 域名在微信环境不可达的问题）
  normalizeUrl: function(u) {
    if (!u) return '';
    try {
      // 直通微信本地文件
      if (/^wxfile:\/\//i.test(u)) return u;

      const domain = String(dynamic.resourceDomain || '').replace(/\/$/, '');
      const ensureJoin = (p) => domain + (String(p).startsWith('/') ? p : ('/' + p));

      // 相对路径 -> 拼接到资源域名
      if (!/^https?:\/\//i.test(u)) {
        return ensureJoin(u);
      }

      // 带 uploads 的绝对地址，统一切到当前资源域名
      const uploadsIdx = u.indexOf('/uploads/');
      if (uploadsIdx !== -1) {
        return ensureJoin(u.slice(uploadsIdx));
      }

      // 将本地/局域网域名替换为资源域名
      return u.replace(
        /^https?:\/\/((10\.0\.2\.2)|(127\.0\.0\.1)|(localhost)|(192\.168\.[0-9]{1,3}\.[0-9]{1,3}))(\:[0-9]+)?/i,
        domain
      );
    } catch (_) {
      return u;
    }
  },
  
  // 统一提供 wsUrl（已包含 /socket.io/ 路径），供 App/小程序直接引用
  wsUrl: dynamic.wsUrl,
  
  // API接口路径配置（严格按照接口文档）
  api: {
    // 微信登录相关 - 使用API模块
    wechatLogin: 'wechat/login',                    // POST - 微信登录获取Token
    
    // 用户信息相关接口 - 使用API模块
    getUserInfo: 'wechat/getUserInfo',              // GET - 获取用户信息
    updateUserInfo: 'wechat/updateUserInfo',        // POST - 更新用户信息
    
    // 设备相关接口 - 使用API模块
    deviceRegister: 'device/register',              // POST - 设备注册
    getDeviceConfig: 'device/profitconfig',         // GET - 获取设备分润配置

    // 支付相关接口 - 使用API模块
    createPayOrder: 'pay/create',                   // POST - 创建支付订单
    payNotify: 'pay/notify',                        // POST - 支付结果通知
    queryPayOrder: 'pay/query',                     // POST - 查询并同步支付状态
    
    // 订单相关接口 - 使用API模块
    getOrderList: 'orders/list',                    // GET - 获取订单列表
    
    // 奖品相关接口 - 使用API模块
    getPrizeList: 'prizeapi/list',                  // GET - 获取奖品列表
    
    // 抽奖相关接口 - 使用API模块
    smartLottery: 'device/smartLottery',            // POST - 智能抽奖
    getLotteryRecords: 'orders/getLotteryRecords',  // GET - 获取抽奖记录
    getDevicePrizes: 'device/profitprizes',         // GET - 获取指定金额奖品（API接口）
    getDeviceConfig: 'device/profitconfig',         // GET - 获取设备绑定的分润金额列表
    
    // 二维码相关接口 - 使用API模块
    generateQrcode: 'qrcode/generate',              // POST - 生成二维码
    generateMinicode: 'minicode/generate',          // POST - 生成小程序码

    // Token管理接口 - 使用API模块
    checkToken: 'token/check',                      // GET - 检测Token状态
    refreshToken: 'token/refresh',                  // POST - 刷新Token
    
    // 分享相关接口 - 使用API模块
    getShareContent: 'device/shareContent',                      // GET - 获取分享内容
    
    // 核销相关接口
    scanCode: 'lottery/scan',                       // POST - 扫码处理接口
    getLotteryResult: 'lottery/result',             // GET - 获取抽奖结果
    getUserOrders: 'orders/getList',                // GET - 获取用户订单列表
    verifyOrder: 'agent/verifyOrder',               // POST - 核销订单接口
    
    // 代理商相关接口
    getRoleByOpenId: 'agent/getRoleByOpenId',       // GET - 根据openid获取角色信息
    
    // 提现相关接口
    getWithdrawRecords: 'withdraw/records',         // GET - 获取提现记录
    updateWithdrawStatus: 'withdraw/updatestatus',  // POST - 更新提现状态
    
    // 订单详情和操作
    getOrderDetail: 'orders/detail',                // GET - 获取订单详情
    orderAction: 'orders/action',                   // POST - 订单操作（退款、取消等）
    verifyOrderCode: 'orders/verify',               // POST - 验证订单核销码
    
    },
  
  // 响应格式配置
  responseFormat: {
    successCode: 1,        // 成功状态码
    failCode: 0,          // 失败状态码
    paramErrorCode: -1,   // 参数错误状态码
    authErrorCode: -2,    // 权限不足状态码
    tokenExpiredCode: 401 // Token过期状态码
  },
  
  // 请求头配置
  headers: {
    'Content-Type': 'application/json',
    'User-Agent': 'MiniProgram/1.0'
  },
  
  // 超时配置
  timeout: 10000, // 10秒超时
  
  // WebSocket配置（保持与 wsUrl 一致，供可能的旧代码引用）
  websocket: {
    url: dynamic.wsUrl,
    reconnectInterval: 3000,
    maxReconnectAttempts: 5
  }
};

module.exports = config;

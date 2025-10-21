/// <reference path="./types/index.d.ts" />

interface IAppOption {
  globalData: {
    userInfo?: WechatMiniprogram.UserInfo,
    isLoggedIn?: boolean,
    token?: string,
    scanParams?: any,
    socketTask?: WechatMiniprogram.SocketTask,
    currentDeviceCode?: string,
    deviceStatus?: any,
    // 最近一次扫码得到的原始设备码
    scannedDeviceCode?: string,
    // 当前使用的分润/奖品配置ID（可为空表示未设置）
    configId?: number | null,
    // isSimulationMode?: boolean, // 模拟模式标志 - 生产环境禁用
    heartbeatTimer?: any,
    // 连接状态防抖与时间戳（与 app.ts 保持一致）
    isConnecting?: boolean,
    lastConnectAt?: number,
    // 全局登录就绪信号
    loginReady?: Promise<void>,
    loginReadyResolve?: (() => void) | null,
  }
  userInfoReadyCallback?: WechatMiniprogram.GetUserInfoSuccessCallback,
  handleScanEntry?: (options: any) => void,
  parseScene?: (scene: string) => any,
  connectWebSocket?: (deviceCode: string) => void,
  connectRealWebSocket?: (deviceCode: string) => void,
  handleDeviceMessage?: (data: any) => void,
  reconnectWebSocket?: (deviceCode: string, retryCount?: number) => void,
  // connectMockDevice?: (deviceCode: string) => void, // 生产环境禁用
  triggerGlobalEvent?: (eventName: string, data: any) => void,
  parseSceneParams?: (scene: string) => any,
  parsePathParams?: (path: string) => any,
  startHeartbeat?: (socketTask: WechatMiniprogram.SocketTask) => void,
  stopHeartbeat?: () => void,
  // 静默自动登录方法（App.onLaunch 内调用）
  autoLogin?: () => Promise<void>,
  // 强制确保已登录（按钮拦截将调用）
  ensureLogin?: () => Promise<boolean>,
}

// 扩展微信小程序API类型定义
declare namespace WechatMiniprogram {
  interface RequestMerchantTransferOption {
    /** 商户号 */
    mchId: string
    /** 商户AppID */
    appId: string  
    /** package信息 */
    package: string
    /** 接口调用成功的回调函数 */
    success?: (result: any) => void
    /** 接口调用失败的回调函数 */
    fail?: (result: any) => void
    /** 接口调用结束的回调函数（调用成功、失败都会执行） */
    complete?: (result: any) => void
  }

  interface Wx {
    /** 商家转账用户确认收款 */
    requestMerchantTransfer(option: RequestMerchantTransferOption): void
  }
}
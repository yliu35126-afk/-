// utils/scanner.js
const request = require('./request.js');
const config = require('../config/api.js');

class Scanner {
  // 扫码功能
  scanCode() {
    return new Promise((resolve, reject) => {
      wx.scanCode({
        onlyFromCamera: true,
        scanType: ['qrCode'],
        success: (res) => {
          console.log('扫码结果:', res.result);
          
          // 处理扫码结果
          this.handleScanResult(res.result)
            .then(resolve)
            .catch(reject);
        },
        fail: (error) => {
          if (error.errMsg.includes('cancel')) {
            reject(new Error('用户取消扫码'));
          } else {
            reject(new Error('扫码失败'));
          }
        }
      });
    });
  }
  
  // 处理扫码结果
  handleScanResult(code) {
    return request.request({
      url: config.api.scanCode,
      method: 'POST',
      data: { code },
      needAuth: true
    });
  }
  
  // 获取抽奖结果
  getLotteryResult(orderId) {
    return request.request({
      url: config.api.getLotteryResult,
      method: 'GET',
      data: { order_id: orderId },
      needAuth: true
    });
  }
  
  // 获取用户订单
  getUserOrders(page = 1, limit = 20) {
    return request.request({
      url: config.api.getUserOrders,
      method: 'GET',
      data: { page, limit },
      needAuth: true
    });
  }
}

module.exports = new Scanner();

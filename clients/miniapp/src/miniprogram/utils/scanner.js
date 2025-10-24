// utils/scanner.js
const request = require('./request.js');
const config = require('../config/api.js');

function parseQueryParamsFromCode(code) {
  try {
    // 支持纯查询串、完整URL或二维码自定义格式如 record_id=xxx&verify_code=YYY
    const q = String(code || '').trim();
    let search = '';
    if (/^https?:\/\//i.test(q)) {
      const u = new URL(q);
      search = u.search || '';
    } else if (q.includes('?')) {
      search = q.slice(q.indexOf('?'));
    } else if (/=/.test(q)) {
      search = '?' + q;
    }
    const params = new URLSearchParams(search);
    const record_id = params.get('record_id') || params.get('recordId') || params.get('rid') || '';
    const verify_code = params.get('verify_code') || params.get('code') || params.get('vcode') || '';
    return { record_id: record_id ? parseInt(record_id, 10) : 0, verify_code: String(verify_code || '').trim() };
  } catch (_) {
    return { record_id: 0, verify_code: '' };
  }
}

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
  
  // 处理扫码结果（优先走Turntable插件核销）
  handleScanResult(code) {
    const parsed = parseQueryParamsFromCode(code);
    if (parsed.record_id && parsed.verify_code) {
      // 统一到 Turntable 插件核销接口
      return request.request({
        url: config.api.turntableVerify,
        method: 'POST',
        data: { record_id: parsed.record_id, verify_code: parsed.verify_code },
        needAuth: true
      });
    }
    // 兜底：仍按老接口上送原始code（兼容旧码制）
    return request.request({
      url: config.api.scanCode,
      method: 'POST',
      data: { code },
      needAuth: true
    });
  }
  
  // 获取抽奖结果（Turntable：建议改用 record 接口）
  getLotteryResult(orderId, extra = {}) {
    // 若传入的是 record_id，优先走 Turntable 抽奖记录接口
    if (extra && extra.recordId) {
      return request.request({
        url: config.api.turntableRecord,
        method: 'GET',
        data: { page: 1, page_size: 1, device_id: extra.deviceId || '' },
        needAuth: true
      });
    }
    // 兼容旧版：按订单ID查询结果
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

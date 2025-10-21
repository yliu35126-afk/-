import http from "../utils/http"

/**
 * 获取网站信息
 */
export function websiteInfo(params) {
    return http({
        url: "/api/website/info",
        data: params
    })
}


export function diyviewInfo(params) {
    return http({
        url: "/api/website/getWebsiteidByAddress",
        data: params
    })
}

/**
 * 获取版权信息
 */
export function copyRight(params) {
    return http({
        url: "/api/config/copyright",
        data: params
    })
}

/**
 * 获取wap端二维码
 */
export function wapQrcode(params) {
    return http({
        url: "/api/website/wapqrcode",
        data: params
    })
}

export function siteDefaultFiles(params) {
    return http({
        url: "/api/config/defaultimg",
        data: params
    })
}

/**
 * 广告图
 * @param {Object} params 参数
 */
export function adList(params) {
    return http({
        url: "/api/adv/detail",
        data: params
    })
}
/**
 * 获取登陆以及注册页面的验证码显示与否
 */
export function captchaConfig(params) {
    return http({
        url: "/api/config/getCaptchaConfig",
        data: params
    })
}
/**
 * 获取商家服务
 */
export function shopServiceLists(params) {
    return http({
        url: "/api/shopservice/lists",
        data: params
    })
}
// 客服打开方式
export function shopServiceOpen(params) {
    return http({
        url: "/api/config/servicer",
        data: params
    })
}
/**
 * 友情链接
 * @param {Object} params 参数
 */
export function friendlyLink(params) {
    return http({
        url: "/api/pc/friendlyLink",
        data: params
    })
}

/**
 * 导航
 * @param {Object} params 参数
 */
export function navList(params) {
    return http({
        url: "/api/pc/navList",
        data: params
    })
}

/**
 * 获取验证码
 */
export function captcha(params) {
    return http({
        url: "/api/captcha/captcha",
        data: params
    })
}
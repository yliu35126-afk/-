import http from "../utils/http"

/**
 * 发送消息
 * @param {object} params
 */
export function sendMessage(params) {
    return http({
        url: "/servicer/api/chat/say",
        data: params
    })
}
/**
 * 发送图片
 * @param {object} params
 */
export function sendImg(params) {
    return http({
        url: "/api/upload/chatimg",
        data: params
    })
}
/**
 * 标识消息已读
 * @param {object} params
 */
export function readMessage(params) {
    return http({
        url: "/servicer/api/chat/setRead",
        data: params
    })
}

// 绑定客服(客服id,店铺id)client_id，site_id
export function bindServicer(params) {
    return http({
        url: '/servicer/api/chat/bind',
        data: params
    })
}

// 获取聊天记录
export function messageList(params) {
    return http({
        url: '/servicer/api/chat/dialogs',
        data: params
    })
}

// 是否在线
export function hasServicers(params) {
    return http({
        url: '/servicer/api/chat/hasServicers',
        data: params
    })
}

// 获取联系人
export function sessionList(params) {
    return http({
        url: '/servicer/api/chat/chatList',
        data: params
    })
}

// 获取联系人
export function currStore(params) {
    return http({
        url: '/api/shop/info',
        data: params
    })
}

// 获取联系人servicer_site_id
export function groupList(params) {
    return http({
        url: '/servicer/api/servicer/getGroupList',
        data: params
    })
}

// 客服是否在线
export function isHaveServicers(params) {
    return http({
        url: '/servicer/api/chat/hasServicers',
        data: params
    })
}

//关闭客服
export function closeMessage(params) {
    return http({
        url: '/servicer/api/chat/bye',
        data: params
    })
}
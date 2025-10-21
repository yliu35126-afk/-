<?php
// +----------------------------------------------------------------------
// | 店铺手机端权限控制
// +----------------------------------------------------------------------
return [
    'shopapi/index/index' => 'INDEX_ROOT',// 概况

    /************************************************ 相册 **********************************************************/
    'shopapi/album/lists' => 'ALBUM_MANAGE',// 相册分组
    'shopapi/album/piclist' => 'ALBUM_MANAGE',// 图片列表


    /************************************************ 店铺 ********************************************************/

    'shopapi/shop/config' => 'SHOP_CONFIG',// 店铺信息
    'shopapi/shop/contact' => 'SHOP_CONTACT',// 联系方式
    'shopapi/shopwithdraw/apply' => 'SHOP_WITHDRAW_APPLY',// 申请提现
    'shopapi/shopwithdraw/lists' => 'SHOP_WITHDRAW',// 提现记录


    /************************************************ 商品 ********************************************************/

    'shopapi/goods/lists' => 'GOODS_MANAGE',// 商品列表
    'shopapi/goods/addgoods' => 'PHYSICAL_GOODS_ADD',// 添加商品
    'shopapi/goods/editgoods' => 'PHYSICAL_GOODS_EDIT',// 编辑商品
//    'shopapi/goods/editgetgoodsinfo' => 'PHYSICAL_GOODS_EDIT',// 获取商品信息
    'shopapi/goods/deletegoods' => 'GOODS_DELETE',// 删除商品
    'shopapi/goods/recycle' => 'PHYSICAL_GOODS_RECYCLE',// 回收站
    'shopapi/goods/deleterecyclegoods' => 'PHYSICAL_GOODS_RECYCLE_DELETE',// 商品回收站商品删除
    'shopapi/goods/recoveryrecycle' => 'PHYSICAL_GOODS_RECYCLE_RECOVERY',// 商品回收站商品恢复
    'shopapi/goods/offgoods' => 'GOODS_OFF',// 商品下架
    'shopapi/goods/ongoods' => 'GOODS_ON',// 商品上架
    'shopapi/goods/copygoods' => 'GOODS_COPY',//  商品复制
    'shopapi/virtualgoods/addgoods' => 'VIRTUAL_GOODS_ADD',//  添加虚拟商品
    'shopapi/virtualgoods/editgoods' => 'VIRTUAL_GOODS_EDIT',//  编辑虚拟商品


    /************************************************ 订单 ********************************************************/

    'shopapi/order/lists' => 'ORDER_MANAGE',// 订单列表
    'shopapi/order/detail' => 'EXPRESS_ORDER_DETAIL',// 订单详情
    'shopapi/order/close' => 'EXPRESS_ORDER_CLOSE',// 订单关闭
    'shopapi/order/adjustprice' => 'EXPRESS_ORDER_ADJUST_PRICE',// 订单调价
    'shopapi/order/delivery' => 'EXPRESS_ORDER_DELIVERY',// 订单发货
    'shopapi/order/editaddress' => 'EXPRESS_ORDER_EDIT_ADDRESS',// 订单修改收货地址
    'shopapi/orderrefund/lists' => 'ORDER_REFUND_LIST',// 退款维权
    'shopapi/orderrefund/detail' => 'ORDER_REFUND_DETAIL',// 维权详情
    'shopapi/orderrefund/receive' => 'ORDER_REFUND_AGREE',// 维权收货
    'shopapi/orderrefund/refuse' => 'ORDER_REFUND_REFUSE',// 维权拒绝
    'shopapi/orderrefund/complete' => 'ORDER_REFUND_COMPLETE',// 维权通过
    'shopapi/orderrefund/agree' => 'ORDER_REFUND_AGREE',// 维权同意
    'shopapi/localorder/delivery' => 'LOCAL_ORDER_DELIVER',// 外卖订单发货


    /************************************************ 会员 ********************************************************/

    'shopapi/member/list' => 'MEMBER_MANAGE',// 会员列表
    'shopapi/member/detail' => 'MEMBER_DETAIL',// 会员详情
    'shopapi/member/orderlist' => 'MEMBER_DETAIL',// 会员详情


    /************************************************ 用户 ********************************************************/

    'shopapi/user/user' => 'USER_LIST',// 用户列表
    'shopapi/user/info' => 'USER_EDIT',// 用户编辑
    'shopapi/user/adduser' => 'USER_ADD',// 用户添加
    'shopapi/user/edituser' => 'USER_EDIT',// 用户编辑
    'shopapi/user/deleteuser' => 'USER_DELETE',// 删除用户
    'shopapi/user/modifypassword' => 'USER_MODIFY_PASSWORD',// 重置密码
    'shopapi/user/modifyuserstatus' => 'USER_MODIFY_STATUS',// 调整用户状态


    /************************************************ 资产 ********************************************************/

    'shopapi/account/index' => 'ACCOUNT_DASHBOARD_INDEX',// 资产概况
    'shopapi/account/dashboard' => 'ACCOUNT_DASHBOARD_INDEX',// 资产概况
    'shopapi/account/orderstat' => 'ACCOUNT_ORDERLIST',// 账户交易记录
    'shopapi/account/orderlist' => 'ACCOUNT_ORDERLIST',// 账户交易记录
    'shopapi/account/feestat' => 'ACCOUNT_FEE',// 入驻费用统计
    'shopapi/account/fee' => 'ACCOUNT_FEE',// 入驻费用统计
    'shopapi/account/reopenlist' => 'SHOP_REOPEN_LIST',// 续签记录
    'shopapi/settlement/lists' => 'ACCOUNT_SETTLEMENT',// 店铺结算
    'shopapi/settlement/info' => 'ACCOUNT_SETTLEMENT_DETAIL',// 结算详情
    'shopapi/settlement/detail' => 'ACCOUNT_SETTLEMENT_DETAIL',// 结算详情


    /************************************************ 统计 ********************************************************/

    'shopapi/statistics/shop' => 'STAT_SHOP',// 店铺统计
    'shopapi/statistics/getshopstatlist' => 'STAT_SHOP',// 店铺统计
    'shopapi/statistics/goods' => 'STAT_GOODS',// 商品统计
    'shopapi/statistics/getgoodsstatlist' => 'STAT_GOODS',// 商品统计
    'shopapi/statistics/order' => 'STAT_ORDER',// 交易统计
    'shopapi/statistics/getorderstatlist' => 'STAT_ORDER',// 交易统计
    'shopapi/statistics/visit' => 'STAT_VISIT',// 访问统计
    'shopapi/statistics/getvisitstatlist' => 'STAT_VISIT',// 访问统计


    /************************************************ 核销 ********************************************************/

    'shopapi/verify/verifycard' => 'ORDER_VERIFY_CARD',// 核销台
    'shopapi/verify/records' => 'ORDER_VERIFY_RECORDS',// 核销记录
    'shopapi/verify/user' => 'ORDER_VERIFY_USER',// 核销人员
    'shopapi/verify/adduser' => 'ORDER_VERIFY_USER_ADD',// 添加核销人员
    'shopapi/verify/edituser' => 'ORDER_VERIFY_USER_EDIT',// 编辑核销人员
    'shopapi/verify/deleteuser' => 'ORDER_VERIFY_USER_DELETE',// 删除核销人员
    'shopapi/verify/verify' => 'ORDER_VERIFY_CONFIRM',// 核销

];

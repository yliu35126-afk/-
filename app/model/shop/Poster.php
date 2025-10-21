<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace app\model\shop;

use app\model\BaseModel;
use extend\Poster as PosterExtend;
use app\model\web\Config as WebConfig;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 店铺海报
     */
    public function shop($app_type, $page, $qrcode_param)
    {
        try {

            $shop_model = new Shop();
            $shop_info = $shop_model->getShopInfo([['site_id', '=', $qrcode_param[ 'site_id' ]]])[ 'data' ] ?? [];
            if (empty($shop_info)) return $this->error('未获取到店铺信息');

            $qrcode_info = $this->getShopQrcode($app_type, $page, $qrcode_param);
            if ($qrcode_info[ 'code' ] < 0) return $qrcode_info;
            if (!empty($qrcode_param[ 'suid' ])) {
                $member_info = $this->getMemberInfo($qrcode_param[ 'suid' ]);
            }

            $poster_width = 740;
            $poster_height = !empty($member_info) ? 1120 : 1000;

            $web_config_model = new WebConfig();
            $default_img = $web_config_model->getDefaultImg()['data']['value'] ?? [];
            $default_headimg = !empty($default_img['default_headimg']) ? $default_img['default_headimg'] : 'upload/uniapp/default_headimg.png';

            $shop_avatar = $shop_info['avatar'] ?? '';
            //判断图片是否存在,不存在则用默认的商品
            if(!file_exists($shop_avatar)){
                $shop_avatar = 'upload/default/shop/shop_logo.png';
            }
            $shop_logo = $shop_info['logo'] ?? '';
            //判断图片是否存在,不存在则用默认的商品
            if(!file_exists($shop_logo)){
                $shop_logo = 'upload/default/shop/shop_logo.png';
            }

            $poster = new PosterExtend($poster_width, $poster_height);
            $option = [
                [
                    'action' => 'setBackground', // 设背景色
                    'data' => [255, 255, 255]
                ],
                [
                    'action' => 'imageCopy', // 写入店铺logo
                    'data' => [
                        $shop_logo,
                        20,
                        20,
                        700,
                        700
                    ]
                ],
                [
                    'action' => 'imageCopy', // 写入商品二维码
                    'data' => [
                        $qrcode_info[ 'data' ][ 'path' ],
                        510,
                        !empty($member_info) ? 860 : 740,
                        210,
                        210
                    ]
                ],
                [
                    'action' => 'imageText', // 写入二维码提示
                    'data' => [
                        '长按扫码访问',
                        16,
                        [102, 102, 102],
                        555,
                        !empty($member_info) ? 1100 : 980,
                        490,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入店铺名称
                    'data' => [
                        $shop_info[ 'site_name' ],
                        22,
                        [0, 0, 0],
                        140,
                        !empty($member_info) ? 895 : 775,
                        490,
                        2,
                        true
                    ]
                ],

                [
                    'action' => 'imageCircularCopy', // 写入店铺头像
                    'data' => [
                        $shop_avatar,
                        20,
                        740,
                        100,
                        100
                    ]
                ],
                [
                    'action' => 'imageText', // 写入店铺所属分类
                    'data' => [
                        $shop_info[ 'category_name' ] ?? '',
                        18,
                        [102, 102, 102],
                        140,
                        !empty($member_info) ? 965 : 845,
                        490,
                        1
                    ]
                ],
                [
                    'action' => 'imageText', // 写入店铺地址
                    'data' => [
                        $shop_info[ 'full_address' ] . $shop_info[ 'address' ],
                        14,
                        [102, 102, 102],
                        20,
                        !empty($member_info) ? 1065 : 945,
                        490,
                        2,
                        true
                    ]
                ],
            ];
            if (!empty($member_info)) {
                $member_option = [
                    [
                        'action' => 'imageCircularCopy', // 写入用户头像
                        'data' => [
                            !empty($member_info[ 'headimg' ]) ? $member_info[ 'headimg' ] : $default_headimg,
                            20,
                            740,
                            100,
                            100
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入分享人昵称
                        'data' => [
                            $member_info[ 'nickname' ],
                            22,
                            [10, 10, 10],
                            140,
                            790,
                            580,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入分享人昵称
                        'data' => [
                            '分享给你一个店铺',
                            18,
                            [102, 102, 102],
                            140,
                            825,
                            580,
                            1
                        ]
                    ]
                ];
                $option = array_merge($option, $member_option);
            }

            $option_res = $poster->create($option);

            if (is_array($option_res)) return $option_res;
            $res = $option_res->jpeg('upload/poster/shop', 'shop_' . $qrcode_param[ 'site_id' ] . '_' . $qrcode_param[ 'suid' ] . '_' . $app_type);
            return $res;
        } catch ( \Exception $e ) {
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 获取用户信息
     * @param unknown $member_id
     */
    private function getMemberInfo($member_id)
    {
        $info = model('member')->getInfo(['member_id' => $member_id], 'nickname,headimg');
        return $info;
    }


    /**
     * 获取商品二维码
     * @param unknown $app_type 请求类型
     * @param unknown $page uniapp页面路径
     * @param unknown $qrcode_param 二维码携带参数
     * @param string $promotion_type 活动类型 null为无活动
     */
    private function getShopQrcode($app_type, $page, $qrcode_param)
    {
        $res = event('Qrcode', [
            'app_type' => $app_type,
            'type' => 'get',
            'data' => $qrcode_param,
            'page' => $page,
            'qrcode_path' => 'upload/qrcode/shop',
            'qrcode_name' => 'shop_' . $qrcode_param[ 'site_id' ] . '_' . $qrcode_param[ 'suid' ],
        ], true);
        return $res;
    }
}
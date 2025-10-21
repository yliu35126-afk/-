<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\presale\model;

use app\model\BaseModel;
use app\model\upload\Upload;
use extend\Poster as PosterExtend;

/**
 * 海报生成类
 */
class Poster extends BaseModel
{
    /**
     * 商品海报
     */
    public function goods($app_type, $page, $qrcode_param, $promotion_type = 'null')
    {
        try {
            $goods_info = $this->getGoodsInfo($qrcode_param);
            if (empty($goods_info)) return $this->error('未获取到商品信息');

            $qrcode_info = $this->getGoodsQrcode($app_type, $page, ['presale_id' => $goods_info['presale_id'],'suid' => $qrcode_param['suid']], $promotion_type);
            if ($qrcode_info['code'] < 0) return $qrcode_info;

            if (!empty($qrcode_param['suid'])) {
                $member_info = $this->getMemberInfo($qrcode_param['suid']);
            }

            $poster = new PosterExtend(740, 1120);
            $option = [
                [
                    'action' => 'imageCopy', // 背景图
                    'data'   => [
                        'upload/poster/bg/promotion_2.png',
                        0,
                        0,
                        740,
                        1120
                    ]
                ],
                [
                    'action' => 'imageCopy', // 商品图
                    'data'   => [
                        $goods_info['sku_image'],
                        100,
                        134,
                        539,
                        539,
                        5
                    ]
                ],
                [
                    'action' => 'imageCopy', // 条幅
                    'data'   => [
                        'upload/poster/bg/presale.png',
                        100,
                        618,
                        539,
                        55
                    ]
                ],
                [
                    'action' => 'imageCopy', // 二维码
                    'data'   => [
                        $qrcode_info['data']['path'],
                        320,
                        865,
                        100,
                        100,
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品价格
                    'data'   => [
                        '定金¥' . $goods_info['presale_deposit'].'可抵¥'.$goods_info['presale_price'],
                        25,
                        [255, 95, 75],
                        120,
                        733,
                        500,
                        2,
                        true
                    ]
                ],
                [
                    'action' => 'imageText', // 写入商品名称
                    'data'   => [
                        $goods_info['sku_name'],
                        18,
                        [89, 89, 89],
                        120,
                        770,
                        500,
                        1,
                        true
                    ]
                ]
            ];

            if (!empty($member_info)) {
                $member_option = [
                    [
                        'action' => 'imageCircularCopy', // 写入用户头像
                        'data'   => [
                            !empty($member_info['headimg']) ? $member_info['headimg'] : 'upload/uniapp/default_headimg.png',
                            100,
                            35,
                            80,
                            80
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入分享人昵称
                        'data'   => [
                            $member_info['nickname'],
                            22,
                            [255, 255, 255],
                            200,
                            75,
                            440,
                            1
                        ]
                    ],
                    [
                        'action' => 'imageText', // 写入分享语
                        'data'   => [
                            '',
                            18,
                            [255, 255, 255],
                            200,
                            102,
                            440,
                            1
                        ]
                    ]
                ];
                $option        = array_merge($option, $member_option);
            }

            $option_res = $poster->create($option);
            if (is_array($option_res)) return $option_res;

            $res = $option_res->jpeg('upload/poster/goods', 'goods_' . $promotion_type . '_' . $goods_info['presale_id'] . '_' . $qrcode_param['suid'] . '_' . $app_type);
            return $res;
        } catch (\Exception $e) {
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
     * 获取商品信息
     * @param unknown $sku_id
     */
    private function getGoodsInfo($id)
    {
        $join  = [
            ['goods_sku sku', 'pbg.sku_id = sku.sku_id', 'inner'],
        ];
        $field = 'pbg.presale_id,pbg.presale_deposit,pbg.presale_price,sku.sku_name,sku.sku_image';
        $info  = model('promotion_presale_goods')->getInfo(['pbg.presale_id' => $id], $field, 'pbg', $join);
        return $info;
    }

    /**
     * 获取商品二维码
     * @param unknown $app_type 请求类型
     * @param unknown $page uniapp页面路径
     * @param unknown $qrcode_param 二维码携带参数
     * @param string $promotion_type 活动类型 null为无活动
     */
    private function getGoodsQrcode($app_type, $page, $qrcode_param, $promotion_type = 'null')
    {
        $res = event('Qrcode', [
            'app_type'    => $app_type,
            'type'        => 'create',
            'data'        => $qrcode_param,
            'page'        => $page,
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => 'goods_' . $promotion_type . '_' . $qrcode_param['presale_id'] . '_' . $qrcode_param['suid'],
        ], true);
        return $res;
    }
}
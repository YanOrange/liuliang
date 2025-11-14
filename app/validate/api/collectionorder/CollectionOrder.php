<?php
namespace app\validate\api\collectionorder;

use app\validate\BaseValidate;

/**
 * 山之名支付平台
 *
 * Class CollectionOrder
 * @package app\validate\api\collectionorder
 * @date 2022-10-11
 */
class CollectionOrder extends BaseValidate
{
    protected $rule = [
        'order_sn'    => 'require',
        'pay_type' => 'require|in:alipay,wxpay,hsqpay',
    ];

    protected $message = [
        'order_sn.require' => '订单号参数错误',
        'pay_type.require' => '支付方式参数有误',
    ];

    // 验证场景
    protected $scene = [
        'info' => ['order_sn'],
        'payApplySzm' => ['order_sn', 'pay_type'],
    ];
}
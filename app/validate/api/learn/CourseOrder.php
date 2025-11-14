<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class CourseOrder extends BaseValidate
{
    protected $rule = [
        'channel'             => 'require',
        'order_id'            => 'require',
        'content'             => 'require',
        'refund_amount'       => 'require|gt:0',
        'feedback_image'      => 'require',
        'feedback_phone'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'order_id.require' => '订单参数错误',
        'content.require' => '退款原因不能为空',
        'refund_amount.require' => '退款金额不能为空',
        'refund_amount.gt' => '退款金额必须大于0',
        'feedback_image.require' => '凭证图片不能为空',
        'feedback_phone.require' => '反馈人手机不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseOrderList' => ['channel'],
        'getCourseOrderDetail' => ['channel','order_id'],
        'getCourseOrderRefund' => ['channel','order_id','content','refund_amount','feedback_image','feedback_phone'],
    ];
}
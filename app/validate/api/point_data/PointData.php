<?php

namespace app\validate\api\point_data;
use app\validate\BaseValidate;
class PointData extends BaseValidate
{
    //数组顺序就是检测的顺序，比如这里，会先检测code验证码的正确性
    protected $rule = [
        'event_id' => 'require',
        'channel'    => 'require',
        'app_bundle_id' => 'require',
        'page_id' => 'require',
        'for_flow_id' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'event_id.require'    => '事件参数错误',
        'page_id.require'    => '页面参数错误',
        'channel.require'    => '渠道参数错误',
        'app_bundle_id.require'    => '包名参数错误',
        'for_flow_id.require'    => '信息流参数错误',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'ponitReporteData' => ['event_id', 'page_id'],
        'h5PonitReporteData' => ['event_id', 'channel','for_flow_id'],
    ];
}
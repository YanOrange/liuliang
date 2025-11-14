<?php

namespace app\validate\admin\bicost;

use app\validate\BaseValidate;

class BiCost extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'app_class_id' => 'require',
        'channel_id' => 'require',
        'store' => 'require',
        'cost' => 'between:0,99999',
        'expose' => 'number|between:0,99999999',
        'download' => 'number|between:0,99999999',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'app_class_id.require' => '请选择类目',
        'channel_id.require' => '请选择渠道',
        'store.require' => '请选择商店',
        'cost.between' => '花费位于0~99999',
        'expose.number' => '曝光量必须为整数',
        'expose.between' => '曝光量位于0~99999999',
        'download.number' => '下载量必须为整数',
        'download.between' => '下载量位于0~99999999',
    ];

    protected $scene = [
        'add' => ['app_class_id', 'channel_id', 'store', 'cost', 'expose', 'download'],
        'edit' => ['app_class_id', 'channel_id', 'store', 'cost', 'expose', 'download', 'id'],
    ];
}
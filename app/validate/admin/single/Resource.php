<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class Resource extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'title' => 'require',
        'merchant_ids' => 'require',
        'app_ids' => 'require',
        'downland_links' => 'require',
        'down_nums' => 'require|between:0,2147483647',
        'read_nums' => 'require|between:0,2147483647',
        'image' => 'require',
        'content' => 'require',
        'btn_desc' => 'require',
        'confirm_copy_desc' => 'require',
        'flow_desc' => 'require',
        'confirm_btn_desc' => 'require',
        'not_add_qrcode_desc'=> 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require' => '请输入资源标题',
        'merchant_ids.require' => '请选择所属商户',
        'app_ids.require' => '请选择所属应用',
        'downland_links.require' => '请输入资源地址',
        'down_nums.require' => '请输入下载量',
        'down_nums.between' => '下载量超出限制范围',
        'read_nums.require' => '请输入阅读量',
        'read_nums.between' => '阅读量超出限制范围',
        'content.require' => '请输入资源详情',
        'image.require' => '请上传资源封面',
        'btn_desc.require' => '请输入按钮文案',
        'confirm_btn_desc.require' => '请输入确认按钮文案',
        'confirm_copy_desc.require' => '请输入文案',
        'flow_desc.require' => '请输入流程设置',
        'not_add_qrcode_desc.require' => '请输入未添加二维码文案',
    ];

    protected $scene = [
        'add' => ['title', 'merchant_ids', 'app_ids','downland_links','down_nums','read_nums','image','content','btn_desc','confirm_copy_desc','confirm_copy_desc','flow_desc','not_add_qrcode_desc'],
        'edit' => ['title', 'merchant_ids', 'app_ids','downland_links','down_nums','read_nums','image','content','btn_desc','confirm_copy_desc','confirm_copy_desc','flow_desc','not_add_qrcode_desc'],
    ];
}
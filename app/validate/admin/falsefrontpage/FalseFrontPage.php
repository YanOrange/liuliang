<?php

namespace app\validate\admin\falsefrontpage;

use app\model\admin\WxMiniPath;
use app\validate\BaseValidate;

class FalseFrontPage extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'page_title' => 'require|length:1,120',
        'page_image' => 'require|length:1,255',
        'page_path' => 'require|length:1,255'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'page_title.require' => '请输入页面标题',
        'page_title.length' => '页面标题输入长度过长',
        'page_image.require' => '请上传页面截图',
        'page_image.length' => '页面截图名称过长',
        'page_path.require' => '请输入页面路径',
        'page_path.length' => '页面路径长度过长'
    ];

    //标题
    protected function checkWxMiniTitle($wxMiniTitle, $rule, $data)
    {
        $id = WxMiniPath::where('wxmini_title', '=', $wxMiniTitle)->value('id');
        if (!isset($data['id'])) {
            if ($id) {
                return '该标题已存在';
            }
        }

        if ($id && $id != $data['id']) {
            return '标题已存在';
        }
        return true;
    }

    protected $scene = [
        'add' => ['page_title', 'page_image', 'page_path', 'remark'],
        'edit' => ['page_title', 'page_image', 'page_path', 'remark', 'id'],
    ];
}
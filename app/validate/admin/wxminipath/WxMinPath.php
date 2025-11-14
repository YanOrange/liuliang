<?php

namespace app\validate\admin\wxminipath;

use app\model\admin\WxMiniPath;
use app\validate\BaseValidate;

class WxMinPath extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'wxmini_title' => 'require|length:1,120|checkWxMiniTitle',
        'wxmini_image' => 'require|length:1,255',
        'wxmini_path' => 'require|length:1,255'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'wxmini_title.require' => '请输入微信标题',
        'wxmini_title.length' => '微信标题输入长度过长',
        'wxmini_image.require' => '请上传微信截图',
        'wxmini_image.length' => '微信截图名称过长',
        'wxmini_path.require' => '请输入微信路径',
        'wxmini_path.length' => '微信路径长度过长'
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
        'add' => ['wxmini_title', 'wxmini_image', 'wxmini_path', 'remark'],
        'edit' => ['wxmini_title', 'wxmini_image', 'wxmini_path', 'remark', 'id'],
    ];
}
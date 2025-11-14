<?php

namespace app\validate\admin\wxminiprogram;

use app\model\admin\WxMiniProgram;
use think\Validate;
use app\validate\BaseValidate;

class WxMinProgram extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'wxmini_app_id' => 'require|length:1,40|checkWxMiniAppId',
        'wxmini_name' => 'require|length:1,80|checkWxMiniName',
        'wxmini_original_id' => 'require|length:1,80|checkWxMiniId',
        'wxmini_private_key' => 'require|length:1,200',
        'wxmini_path_ids' => 'require|length:1,200',
        'status' => 'require|in:1,2',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'wxmini_app_id.require' => '请输入微信小程序AppId',
        'wxmini_app_id.length' => '微信小程序AppId输入长度过长',
        'wxmini_name.require' => '请输入微信小程序名称',
        'wxmini_name.length' => '微信小程序名称输入长度过长',
        'wxmini_original_id.require' => '请输入微信小程序原始ID',
        'wxmini_original_id.length' => '微信小程序原始输入长度过长',
        'wxmini_private_key.require' => '请输入微信小程序私钥',
        'wxmini_private_key.length' => '微信小程序私钥输入长度过长',
        'wxmini_path_ids.require' => '请选择微信小程序路径',
        'wxmini_path_ids.length' => '微信小程序路径选择过多',
        'status.require' => '请选择微信小程序状态',
    ];

    //微信原始ID唯一
    protected function checkWxMiniId($wxMiniId, $rule, $data)
    {
        $id = WxMiniProgram::where('wxmini_original_id', '=', $wxMiniId)->value('id');
        if (!isset($data['id'])) {
            if ($id) {
                return '该微信小程序原始ID已存在';
            }
        }

        if ($id && $id != $data['id']) {
            return '该微信小程序原始ID已存在';
        }
        return true;
    }

    //微信小程序名称唯一
    protected function checkWxMiniName($wxMiniId, $rule, $data)
    {
        $id = WxMiniProgram::where('wxmini_name', '=', $wxMiniId)->value('id');
        if (!isset($data['id'])) {
            if ($id) {
                return '微信小程序名称已存在';
            }
        }

        if ($id && $id != $data['id']) {
            return '微信小程序名称已存在';
        }
        return true;
    }

    //微信AppId唯一
    protected function checkWxMiniAppId($wxMiniAppId, $rule, $data)
    {
        $id = WxMiniProgram::where('wxmini_app_id', '=', $wxMiniAppId)->value('id');
        if (!isset($data['id'])) {
            if ($id) {
                return '该微信AppId已存在';
            }
        }

        if ($id && $id != $data['id']) {
            return '该微信AppId已存在';
        }
        return true;
    }

    protected $scene = [
        'add' => ['wxmini_original_id', 'wxmini_private_key', 'status', 'wxmini_app_id', 'wxmini_name', 'wxmini_path_ids'],
        'edit' => ['wxmini_original_id', 'wxmini_private_key', 'status', 'wxmini_app_id', 'wxmini_name', 'wxmini_path_ids'],
    ];
}
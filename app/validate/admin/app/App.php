<?php

namespace app\validate\admin\app;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class App extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'app_name'              => 'require|length:2,30|checkAppName:',
        'is_login_show'         => 'require',
        'app_class_id'          => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'app_name.require'             => '应用名不能为空',
        'app_name.length'              => '应用名长度2-30',
        'is_login_show.require'        => '请设置启动app登录状态',
        'app_class_id.require'        => '请选择应用分类',
    ];

    //验证应用名的唯一性
    protected function checkAppName($appName, $rule, $data){
        $appId = AppModel::getFieldByAppName($appName, 'id');
        if (!isset($data['id'])) {
            if ($appId) {
                return '应用名称已存在';
            }
        }
        if ($appId && $appId != $data['id']) {
            return '应用名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['app_name','app_class_id','is_login_show'],
        'edit' => ['app_name','app_class_id','is_login_show'],
    ];
}
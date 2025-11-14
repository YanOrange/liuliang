<?php

namespace app\validate\admin\app_class;

use think\Validate;
use app\model\admin\AppClass as AppClassModel;
use app\validate\BaseValidate;

class AppClass extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'app_class_name'              => 'require|length:2,30|checkAppClassName:',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'app_class_name.require'             => '应用分类名称不能为空',
        'app_class_name.length'              => '应用分类名称长度2-30',
    ];

    //验证应用名的唯一性
    protected function checkAppClassName($appClassName, $rule, $data){
        $appClassId = AppClassModel::getFieldByAppClassName($appClassName, 'id');
        if (!isset($data['id'])) {
            if ($appClassId) {
                return '应用分类名称已存在';
            }
        }
        if ($appClassId && $appClassId != $data['id']) {
            return '应用分类名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['app_class_name'],
        'edit' => ['app_class_name'],
    ];
}
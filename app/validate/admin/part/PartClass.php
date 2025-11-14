<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\part\PartClass as PartClassModel;
class PartClass extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'part_class_name'              => 'require|length:2,8|checkPartClassName:',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'part_class_name.require'             => '分类名称不能为空',
        'part_class_name.length'              => '分类名称长度2-8',
    ];

    //验证分类名的唯一性
    protected function checkPartClassName($partClassName, $rule, $data){
        $partClassId = PartClassModel::where('part_class_name', $partClassName)->value('id');
        if (!isset($data['id'])) {
            if ($partClassId) {
                return '分类名称已存在';
            }
        }
        if ($partClassId && $partClassId != $data['id']) {
            return '分类名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['part_class_name'],
        'edit' => ['part_class_name'],
    ];
}
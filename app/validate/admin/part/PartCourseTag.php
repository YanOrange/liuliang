<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\part\PartCourseTag as PartCourseTagModel;
class PartCourseTag extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'tag_name'              => 'require|length:2,8|checkTagName:',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'tag_name.require'             => '标签名称不能为空',
        'tag_name.length'              => '标签名称长度2-8',
    ];

    //验证标签名的唯一性
    protected function checkTagName($tagName, $rule, $data){
        $tagId = PartCourseTagModel::where('tag_name', $tagName)->value('id');
        if (!isset($data['id'])) {
            if ($tagId) {
                return '标签名称已存在';
            }
        }
        if ($tagId && $tagId != $data['id']) {
            return '标签名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['tag_name'],
        'edit' => ['tag_name'],
    ];
}
<?php

namespace app\validate\admin\learncourse;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnCourse extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_ids'                 => 'require',
        'app_class_id'               => 'require',
        'title'                      => 'require',
        'entry_fee'                  => 'require',
        'desc_image'                 => 'require',
        'btn_desc'                   => 'require',
        'virtual_apply_nums'                  => 'require',
        'teacher_id'                 => 'require',
        'content'                    => 'require',
        'purchase_notes'             => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_ids.require'             => '渠道不能为空',
        'app_class_id.require'           => '类目不能为空',
        'title.require'                  => '课程标题不能为空',
        'entry_fee.require'              => '课程费不能为空',
        'desc_image.require'             => '课程介绍图不能为空',
        'btn_desc.require'               => '按钮文案不能为空',
        'virtual_apply_nums.require'              => '报名人数不能为空',
        'teacher_id.require'             => '主讲老师不能为空',
        'content.require'                => '课程内容不能为空',
        'purchase_notes.require'         => '购课须知不能为空',
    ];

    protected $scene = [
        'add' => ['channel_ids','app_class_id','title','entry_fee','desc_image','btn_desc','virtual_apply_nums','teacher_id','content','purchase_notes'],
        'edit' => ['channel_ids','app_class_id','title','entry_fee','desc_image','btn_desc','virtual_apply_nums','teacher_id','content','purchase_notes'],
    ];
}
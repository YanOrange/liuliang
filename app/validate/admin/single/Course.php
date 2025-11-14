<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class Course extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'course_type' => 'require|in:1,2',
        'status' => 'require|in:1,0',
        'merchant_ids' => 'require',
        'app_ids' => 'require',
        'title' => 'require|length:2,30',
        'course_desc' => 'require|length:2,30',
        'entry_fee' => 'require',
        'original_price' => 'require',
        'video_cover_image' => 'require',
        'virtual_apply_nums' => 'require',
        'content' => 'require',
        'video_resource_ids' => 'require',
        'sort' => 'require',
        'btn_desc' => 'require',
        'confirm_copy_desc' => 'require',
        'flow_desc' => 'require',
        'confirm_btn_desc' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'course_type.require' => '请选择课程分类',
        'status.require' => '请选择课程状态',
        'merchant_ids.require' => '请选择商户',
        'app_ids.require' => '请选择应用',
        'title.require' => '请输入课程标题',
        'title.length' => '课程标题长度2-30字',
        'course_desc.require' => '请输入课程描述',
        'course_desc.length' => '课程描述长度2-30字',
        'entry_fee.require' => '请输入售卖价',
        'original_price.require' => '请输入原价',
        'video_cover_image.require' => '请上传课程封面',
        'virtual_apply_nums.require' => '请输入学习人数',
        'content.require' => '请输入课程详情',
        'video_resource_ids.require' => '请选择资源',
        'sort.require' => '请输入排序',
        'btn_desc.require' => '请输入按钮文案',
        'confirm_copy_desc.require' => '请输入文案',
        'flow_desc.require' => '请输入流程设置',
        'confirm_btn_desc.require' => '请输入确认按钮文案',


    ];

    protected $scene = [
        'add' => ['course_type','status','merchant_ids','app_ids','title','entry_fee','original_price','video_cover_image','virtual_apply_nums','content','video_resource_ids','sort','btn_desc','confirm_copy_desc','flow_desc','confirm_btn_desc'],
        'edit' => ['course_type','status','merchant_ids','app_ids','title','entry_fee','original_price','video_cover_image','virtual_apply_nums','content','video_resource_ids','sort','btn_desc','confirm_copy_desc','flow_desc','confirm_btn_desc'],
    ];
}
<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\part\Course as CourseModel;
class PartCourse extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'merchant_id'   => 'require',
        'title'         => 'require|length:2,30',
        'channel_ids'       => 'require',
        'part_class_ids' => 'require',
        'content'       => 'require',
        'btn_desc'       => 'require',
        'status'        => 'require|in:1,0',
        'virtual_apply_nums' => 'require',
        'compensation' => 'require',
        'compensation_type' => 'require',
        'tag_ids' => 'require',
        'share_desc' => 'require',
        'share_image' => 'require',
        'confirm_copy_desc' => 'require',
        'flow_desc' => 'require',
        'confirm_btn_desc' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'merchant_id.require' =>  '请选择商户',
        'title.require'             => '请输入兼职标题',
        'title.length'              => '兼职标题长度2-30字',
        'channel_ids.require'        => '请选择渠道',
        'part_class_ids.require'        => '请选择分类',
        'content.require'        => '请输入兼职内容',
        'status.require'        => '请选择课程状态',
        'status.in'        => '请选择课程状态',
        'tag_ids.require' => '请选择标签',
        'virtual_apply_nums.require' => '请输入报名人数',
        'compensation.require' => '请输入兼职价格',
        'compensation_type.require' => '请选择兼职价格单位',
        'share_desc.require' => '请输入分享文案',
        'share_image.require' => '请上传分享图片',
        'btn_desc.require'        => '请输入按钮文案',
        'confirm_btn_desc.require'        => '请输入确认按钮文案',
        'confirm_copy_desc.require' => '请输入文案',
        'flow_desc.require' => '请输入流程设置',

    ];
    //一个商户只能绑定一个课程
   /* protected function checkCountCourse($merchant_id, $rule, $data) {

        $courseId = CourseModel::where('merchant_id', $merchant_id)->where('course_type', 1)->value('id');
        if (!isset($data['id'])) {
            if ($courseId) {
                return '该商户已经绑定过其他兼职课程了';
            }
        }
        if ($courseId && $courseId != $data['id']) {
            return '该商户已经绑定过其他兼职课程了';
        }
        return true;
    }*/
    protected $scene = [
        'add' => ['merchant_id','title','channel_ids','part_class_ids','content','status','tag_ids','virtual_apply_nums','compensation','compensation_type','btn_desc','confirm_btn_desc','confirm_copy_desc','flow_desc'],
        'edit' => ['merchant_id','title','channel_ids','part_class_ids','content','status','tag_ids','virtual_apply_nums','compensation','compensation_type','btn_desc','confirm_btn_desc','confirm_copy_desc','flow_desc'],
    ];
}
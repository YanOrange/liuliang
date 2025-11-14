<?php

namespace app\validate\admin\course;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\Course as CourseModel;
class Course extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'title'         => 'require|length:2,30',
        // 'merchant_id'   => 'require|checkCountCourse:',
        'channel_ids'       => 'require',
        'app_ids'       => 'require',
        'content'       => 'require',
        'btn_desc'       => 'require',
        'video_cover_image'       => 'require',
        'video_url'       => 'require',
        'video_burning_time'       => 'require',
        'status'        => 'require|in:1,0',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require'             => '请输入课程标题',
        'title.length'              => '课程标题长度2-30字',
        'merchant_id.require'             => '请选择商户',
        'content.require'        => '请输入招聘内容',
        'status.require'        => '请选择课程状态',
        'btn_desc.require'        => '请输入报名按钮',
        'status.in'        => '请选择课程状态',
        'channel_ids.require'        => '请选择关联渠道',
        'app_ids.require'        => '请选择应用',
        'video_cover_image.require'  => '请上传视频封面图',
        'video_url.require'        => '请上传视频',
        'video_burning_time.require'        => '请输入视频时长',
    ];
    //一个商户只能绑定一个课程
    protected function checkCountCourse($merchant_id, $rule, $data) {

        $courseId = CourseModel::where('merchant_id', $merchant_id)->where('course_type', 0)->value('id');//getFieldByMerchantId($merchant_id, 'id');
        if (!isset($data['id'])) {
            if ($courseId) {
                return '该商户已经绑定过其他课程了';
            }
        }
        if ($courseId && $courseId != $data['id']) {
            return '该商户已经绑定过其他课程了';
        }
        return true;
    }
    protected $scene = [
        'add' => ['title','merchant_id','status','app_ids', 'channel_ids', 'btn_desc','video_cover_image','video_url','video_burning_time'],
        'edit' => ['title','merchant_id','status','app_ids', 'channel_ids', 'btn_desc','video_cover_image','video_url','video_burning_time'],
    ];
}
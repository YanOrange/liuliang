<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\part\Course as CourseModel;
class LiveCourse extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'merchant_id'   => 'require',
        'title'         => 'require|length:2,30',
        'channel_ids'       => 'require',
        'part_class_ids' => 'require',
        'content'       => 'require',
        'status'        => 'require|in:1,0',
        'virtual_apply_nums' => 'require',
        'video_cover_image' => 'require',
        'entry_fee' => 'require',
        'original_price' => 'require',
        'sort' => 'require',
        'live_btn_desc' => 'require',
        'share_desc' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'merchant_id.require' =>  '请选择商户',
        'title.require'             => '请输入课程标题',
        'title.length'              => '课程标题长度2-30字',
        'channel_ids.require'        => '请选择渠道',
        'part_class_ids.require'        => '请选择分类',
        'content.require'        => '请输入兼职内容',
        'status.require'        => '请选择课程状态',
        'status.in'        => '请选择课程状态',
        'virtual_apply_nums.require' => '请输入预约人数',
        'video_cover_image.require' => '请上传课程封面',
        'entry_fee.require' => '请输入售卖价',
        'original_price.require' => '请输入原价',
        'sort.require' => '请输入排序',
        'share_desc.require' => '请输入分享文案',
        'live_btn_desc.require' => '请输入按钮文案',

    ];
    //一个商户只能绑定一个课程
   /* protected function checkCountCourse($merchant_id, $rule, $data) {

        $courseId = CourseModel::where('merchant_id', $merchant_id)->where('course_type', 3)->value('id');
        if (!isset($data['id'])) {
            if ($courseId) {
                return '该商户已经绑定过其他课程了';
            }
        }
        if ($courseId && $courseId != $data['id']) {
            return '该商户已经绑定过其他课程了';
        }
        return true;
    }*/
    protected $scene = [
        'add' => ['merchant_id','title','channel_ids','part_class_ids','content','status','tag_ids','virtual_apply_nums','video_cover_image','entry_fee','original_price','sort','live_btn_desc'],
        'edit' => ['merchant_id','title','channel_ids','part_class_ids','content','status','tag_ids','virtual_apply_nums','video_cover_image','entry_fee','original_price','sort','live_btn_desc'],
    ];
}
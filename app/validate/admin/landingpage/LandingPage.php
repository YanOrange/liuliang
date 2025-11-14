<?php

namespace app\validate\admin\landingpage;

use think\Validate;
use app\validate\BaseValidate;
use app\model\admin\LandingPage as LandingPageModel;
class LandingPage extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'landing_page_type' => 'require',
        'channel_ids'              => 'requireIf:landing_page_type,1',
        'landing_image'         => 'require',
        'course_id'          => 'requireIf:landing_page_type,1',
        'expose_num'          => 'require',
        'is_pay'          => 'requireIf:landing_page_type,2',
        'app_id'          => 'requireIf:landing_page_type,2',
        'weight'          => 'requireIf:landing_page_type,2',
        // 'video_url'          => 'requireIf:landing_page_type,2',
        'btn_desc'          => 'requireIf:landing_page_type,2',
        'lamp_back_image'          => 'requireIf:is_lamp,1',
        'end_image'          => 'requireIf:is_lamp,1',
        'lamp_font_color'          => 'requireIf:is_lamp,1',
        'is_abscheme' => 'require',
        'a_is_lamp'          => 'requireIf:is_abscheme,1',
        'a_landing_images'          => 'requireIf:is_abscheme,1',
        'b_is_lamp'          => 'requireIf:is_abscheme,1',
        'b_landing_images'          => 'requireIf:is_abscheme,1',
        'a_lamp_back_image' => 'checkAlamp:',
        'a_end_image' => 'checkAlamp:',
        'b_lamp_back_image' => 'checkBlamp:',
        'b_end_image' => 'checkBlamp:',
    ];


    //定义内置方法检验失败后返回的字符
    protected $message = [
        'landing_page_type.requireIf'             => '请选择落地页类型',
        'channel_ids.require'             => '请选择关联渠道',
        'landing_image.require'        => '请上传落地页图片',
        'course_id.requireIf'        => '请选择课程',
        // 'video_url.requireIf'        => '请上传视频',
        'is_pay.requireIf'        => '请选择付费类型',
        'app_id.requireIf'        => '请选择关联应用',
        'weight.requireIf'        => '请输入权重',
        'btn_desc.requireIf'        => '请输入按钮名称',
        'lamp_back_image.requireIf'        => '请上传跑马灯背景图',
        'end_image.requireIf'        => '请上传落地页尾图',
        'lamp_font_color.requireIf'        => '请选择跑马灯字体颜色',
        'expose_num.require'        => '请输入曝光次数',
        'is_abscheme.require' => '请选择正确的ab方案',
        'is_abscheme.in' => '请选择正确的ab方案',
        'a_is_lamp.requireIf' => '请选择a方案跑马灯状态',
        'a_landing_images.requireIf' => '请上传a方案落地页主图',
        'b_is_lamp.requireIf' => '请选择b方案跑马灯状态',
        'b_landing_images.requireIf' => '请上传b方案落地页主图',
    ];
    protected $scene = [
        'add' => ['landing_page_type','channel_ids','landing_image','course_id','expose_num','is_pay','app_id','weight','btn_desc','video_url','is_abscheme','a_is_lamp','a_landing_images','b_is_lamp','b_landing_images'],
        'edit' => ['landing_page_type','channel_ids','landing_image','course_id','expose_num','is_pay','app_id','weight','btn_desc','video_url','is_abscheme','a_is_lamp','a_landing_images','b_is_lamp','b_landing_images'],
    ];
}
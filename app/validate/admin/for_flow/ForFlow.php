<?php

namespace app\validate\admin\for_flow;

use think\Validate;
use app\validate\BaseValidate;

class ForFlow extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'for_flow_title'              => 'require|length:2,30',
        'merchant_ids'                => 'require',
        'show_page_title'             => 'require',
        'show_page_image'             => 'require',
        'other_info_set_json'         => 'require|checkOtherInfoSetJson:',
//        'show_page_detail_images'     => 'require',
        'btn_desc'                    => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'for_flow_title.require'             => '投流名称不能为空',
        'for_flow_title.length'              => '投流名称长度2-30',
        'merchant_ids.require'             => '请选择商户',
        'show_page_title.require'             => '请输入专题页名称',
        'show_page_image.require'             => '请上传专题主图',
        'other_info_set_json.require'             => '请设置其他信息',
        'show_page_detail_images.require'             => '请上传专题详情图',
        'btn_desc.require'             => '请输入按钮名称',
    ];

    //验证应用名的唯一性
    protected function checkOtherInfoSetJson($otherInfoSetJson, $rule, $data){
        $otherInfoSetJson = json_decode($otherInfoSetJson, true);
        if ($otherInfoSetJson[0]['is_horse_race_lamp'] == 1) {
            if (empty($otherInfoSetJson[0]['title'])) {
                return '请输入跑马灯标题名称';
            }
            /*if(!preg_match("/^[0-9][0-9]*$/" ,$otherInfoSetJson[0]['line_numbers'])){
                return '请输入跑马灯行数';
            }*/
        }
        /*if ($otherInfoSetJson[1]['is_join_number'] == 1) {
            if(!preg_match("/^[0-9][0-9]*$/" ,$otherInfoSetJson[1]['join_numbers'])){
                return '请输入已报名参加人数';
            }
        }
        if ($otherInfoSetJson[2]['is_residue_number'] == 1) {
            if(!preg_match("/^[0-9][0-9]*$/" ,$otherInfoSetJson[2]['residue_numbers'])){
                return '请输入显示剩余人数';
            }
        }*/
        return true;
    }
    protected $scene = [
        'add' => ['for_flow_title','merchant_ids','show_page_title','show_page_image','other_info_set_json','show_page_detail_images','btn_desc'],
        'edit' => ['for_flow_title','merchant_ids','show_page_title','show_page_image','other_info_set_json','show_page_detail_images','btn_desc'],
    ];
}
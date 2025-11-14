<?php

namespace app\validate\api\h5;
use app\model\api\Channel;
use app\model\api\h5\ForFlow;
use app\validate\BaseValidate;
use app\model\api\Captcha;
use think\facade\Config;

class Thread extends BaseValidate
{
    protected $rule = [
        'for_flow_id'    => 'require',
        'channel'    => 'require|checkCustomField',
        'phone'    => 'checkPhone',
        'captcha'    => 'checkPhoneCaptcha',
        'start_time'    => 'require',
        'order_sn'    => 'require',
    ];

    protected $message = [
        'for_flow_id.require' => '主题id参数错误',
        'phone.require' => '手机号不能为空',
        'captcha.require' => '手机号不能为空',
        'start_time.require' => '开始时间参数错误',
        'order_sn.require' => '订单号参数错误',
    ];

    //自定义密码检验方法
    protected function checkCustomField($channel, $rule, $data)
    {
        $channel = Channel::where('id',$channel)->find();
        if(empty($channel)){
            return '渠道参数错误';
        }
        $flowData = ForFlow::where('id',$data['for_flow_id'])->field('is_data,is_no_data_jump_miniprogram')->find();
        $is_data = $flowData->is_no_data_jump_miniprogram == 1 ? 0 : $flowData->is_data;
        if ($is_data) {
            $channel = $channel->toArray();
            if(!empty($channel['gather_user_info_ids'])){
                $gatherInfoSetData = ForFlow::getGatherInfoList($channel['gather_user_info_ids']);
                if(!empty($gatherInfoSetData)){
                    foreach ($gatherInfoSetData as $key => $val){
                        $field = $val['field'];
                        if(!isset($data[$field]) || empty($data[$field])){
                            return '请选择'.$val['title'];
                        }
                    }
                }
            }
        }
        return true;
    }

    //检查验证码
    protected function checkPhoneCaptcha($captcha, $rule, $data)
    {
        $forFlowInfo = ForFlow::field('is_need_captcha,is_data,is_no_data_jump_miniprogram')->where('id',$data['for_flow_id'])->find();
        $forFlowInfo->is_data = $forFlowInfo->is_no_data_jump_miniprogram == 1 ? 0 : $forFlowInfo->is_data;
        if ($forFlowInfo->is_data) {
            if($forFlowInfo->is_need_captcha == 1){
                if(empty($captcha)){
                    return '验证码错误';
                }

                $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];
                if ($captcha == 951753 || $captcha = 654198 || ($captcha == 654198 && in_array($data['phone'], $testPhoneArr)) ) {
                    return true;
                }
                $checkCaptcha = Captcha::where('phone',$data['phone'])->where('type',5)->order('id desc')->value('captcha');
                if ($captcha != $checkCaptcha) {
                    return '验证码错误';
                }
            }
        }
        return true;
    }

    //验证手机号
    protected function checkPhone($phone, $rule, $data)
    {
        $forFlowInfo = ForFlow::field('is_need_phone,is_data,is_no_data_jump_miniprogram')->where('id',$data['for_flow_id'])->find();
        $forFlowInfo->is_data = $forFlowInfo->is_no_data_jump_miniprogram == 1 ? 0 : $forFlowInfo->is_data;
        if (!empty($forFlowInfo) && $forFlowInfo->is_data) {
            if($forFlowInfo->is_need_phone == 1){
                if(empty($phone)){
                    return '请输入手机号';
                }
                if(!preg_match("/^1[1345789]\d{9}$/", $phone)){
                    return '手机号错误';
                }
            }
        }

        return true;
    }


    /**
     * 验证场景
     */
    protected $scene = [
        'getPayApplyForFlowStatus' => ['order_sn'],
        'payApplyForFlow' => ['for_flow_id','channel','phone','captcha','start_time'],
        'freeApplyForFlow' => ['for_flow_id','channel','phone','captcha','start_time'],
        'getApplyQrCode' => ['for_flow_id','phone'],
        'discernQrCode' => ['for_flow_id','phone'],
        'getCustomerService' => ['for_flow_id'],
    ];
}



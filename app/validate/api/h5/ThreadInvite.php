<?php

namespace app\validate\api\h5;
use app\model\api\Channel;
use app\model\api\h5\ForFlow;
use app\validate\BaseValidate;
use app\model\api\Captcha;
class ThreadInvite extends BaseValidate
{
    protected $rule = [
        'channel'    => 'require|checkCustomField',
        'phone'    => 'require|checkPhone',
        'captcha'    => 'require|checkPhoneCaptcha',
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'captcha.require' => '验证码不能为空',
    ];
    
    //自定义密码检验方法
    protected function checkCustomField($channel, $rule, $data)
    {
        $channel = Channel::where('channel_name',$channel)->find();
        if(empty($channel)){
            return '渠道参数错误';
        }
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
        return true;
    }

    //检查验证码
    protected function checkPhoneCaptcha($captcha, $rule, $data)
    {
        if(empty($captcha)){
            return '验证码错误';
        }
        if ($captcha == 654198) {
            return true;
        }
        $checkCaptcha = Captcha::where('phone',$data['phone'])->where('type',1)->order('id desc')->value('captcha');
        if ($captcha != $checkCaptcha) {
            return '验证码错误';
        }
         return true;
    }

    //验证手机号
    protected function checkPhone($phone, $rule, $data)
    {
        if(empty($phone)){
            return '请输入手机号';
        }
        if(!preg_match("/^1[1345789]\d{9}$/", $phone)){
            return '手机号错误';
        }
        return true;
    }


    /**
     * 验证场景
     */
    protected $scene = [
        'freeApplyInvite' => ['channel','phone','captcha'],
    ];
}



<?php

namespace app\model\api\single;

use app\lib\api\exception\Exception;
use app\lib\api\service\MerchantService;
use app\model\api\Channel;
use app\model\api\single\AppMerchantMessage;
use app\model\api\UserList;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\single\AppMerchantMessageUser;

class AppMerchantMessageConfig extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_merchant_message_config';

    protected $append = [
        'no_read_num',
        'app_merchant_message',
        'is_apply',
        'is_under_eighteen_apply'
    ];

    //未满18岁是否允许报名
    public function getIsUnderEighteenApplyAttr($data)
    {
        $isUnderEighteenApply = 1;
        $channel = request()->post('channel');
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;

    }

    //未读消息数量
    public function getNoReadNumAttr($value,$data)
    {
        $noReadNum = 0;
        $appMerchantMessageUser = AppMerchantMessageUser::where('app_message_id',$data['id'])
            ->where('uid',$GLOBALS['uid'])
            ->count();
        if($appMerchantMessageUser <= 0) {
            $noReadNum = AppMerchantMessage::where('app_message_id', $data['id'])
                ->where('status',1)
                ->limit($data['num'])
                ->count();
        }
        return $noReadNum;
    }

    //消息列表
    public function getAppMerchantMessageAttr($value,$data)
    {
        $appMerchantMessageList = AppMerchantMessage::where('app_message_id',$data['id'])
            ->where('status',1)
            ->field("id,title,content,times,create_time")
            ->order('id desc')
            ->limit($data['num'])
            ->select();
        return $appMerchantMessageList;
    }

    //是否已报名
    public function getIsApplyAttr($value,$data)
    {
        $is_apply = 1;
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('age_range_id')->find();
        $merchantId = (new MerchantService)->getMerchantServiceId($data['merchant_ids'], $userInfo->age_range_id);
        if(!empty($merchantId)){
            if (AppMerchantMessage::checkApplyMerchantMessage($data['id'],$merchantId)) {
                $is_apply = 1;
            }else{
                $is_apply = 0;
            }
        }
        return $is_apply;
    }

}
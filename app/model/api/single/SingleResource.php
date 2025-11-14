<?php

namespace app\model\api\single;

use app\lib\api\service\CustomerService;
use app\lib\api\service\MerchantService;
use app\model\api\Channel;
use app\model\api\Customer;
use app\model\api\single\Merchant;
use app\model\api\UserList;
use laytp\BaseModel;
use think\facade\Request;
use think\model\concern\SoftDelete;

class SingleResource extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'single_resource';

    protected $append = [
        'is_under_eighteen_apply'
    ];

    protected $no_jump_miniprogram_channel = ['quxueps_vivo'];

    //资源列表
    public static function getResourceList($params = [])
    {
        extract($params);
        $data = [];
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantList = Merchant::getMerchantIds($channelInfo);
        $hotResourceList = self::getHotResourceList($merchantList, $channelInfo);
        $recommendResourceList = self::getRecommendResourceList($merchantList, $channelInfo);
        if(!empty($hotResourceList)){
            $data['hot_resource_list'] = $hotResourceList;
        }
        if(!empty($recommendResourceList)){
            $data['recommend_resource_list'] = $recommendResourceList;
        }
        return $data;
    }

    //热门资源
    public static function getHotResourceList($merchantList, $channelInfo)
    {
        $resourceList = [];
        if(!empty($merchantList)){
            foreach($merchantList as $val) {
                $resourceList[] = self::where('status', 1)
                    ->where('resource_type',1)
                    ->whereFindInSet('merchant_ids', $val['id'])
                    ->whereFindInSet('app_ids', $channelInfo['app_id'])
                    ->field('id')
                    ->select();
            }
        }
        $resourceIds = [];
        foreach($resourceList as $key=>$item){
            if(empty($item)) {
                unset($resourceList[$key]);
            }else{
                foreach($item as $val){
                    $resourceIds[] = $val['id'];
                }
            }
        }
        $resourceList = self::where('status',1)
            ->whereIn('id',$resourceIds)
            ->field('id,title,image,down_nums,read_nums')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select();
        return $resourceList;
    }

    //推荐资源
    public static function getRecommendResourceList($merchantList, $channelInfo)
    {
        $resourceList = [];
        if(!empty($merchantList)){
            foreach($merchantList as $val) {
                $resourceList[] = self::where('status', 1)
                    ->where('resource_type',2)
                    ->whereFindInSet('merchant_ids', $val['id'])
                    ->whereFindInSet('app_ids', $channelInfo['app_id'])
                    ->field('id')
                    ->select();
            }
        }
        $resourceIds = [];
        foreach($resourceList as $key=>$item){
            if(empty($item)) {
                unset($resourceList[$key]);
            }else{
                foreach($item as $val){
                    $resourceIds[] = $val['id'];
                }
            }
        }
        $resourceList = self::where('status',1)
            ->whereIn('id',$resourceIds)
            ->field('id,title,image,down_nums,read_nums')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select();
        return $resourceList;
    }

    //资源详情
    public static function getResourceDetail($params = [])
    {
        extract($params);
        $resourceInfo = self::where('id',$resource_id)
            ->field('id,title,content,down_nums,read_nums,resource_ids,downland_links,btn_desc,flow_desc,confirm_copy_desc,not_add_qrcode_desc,confirm_btn_desc')
            ->find();
        if(!empty($resourceInfo)){
            $resourceInfo['is_apply'] = 0;
            $resourceInfo['merchant_id'] = 0;
            $resourceInfo['is_discern_qrcode'] = 0;
            $resourceInfo['is_jump_miniprogram'] = 0;
            $resourceInfo['resource_list'] = [];
            $resourceInfo['evaluate_list'] = [];
            $resourceInfo->flow_desc = !empty($resourceInfo->flow_desc) ? json_decode($resourceInfo->flow_desc,true) : [];
            $resourceInfo->confirm_copy_desc = !empty($resourceInfo->confirm_copy_desc) ? json_decode($resourceInfo->confirm_copy_desc,true) : [];
            $threadInfo = Thread::with(['merchant' => function($query){
                    $query->field('id,is_jump_miniprogram');
                }])
                ->where('uid',$GLOBALS['uid'])
                ->where('resource_id',$resource_id)
                ->field('id,merchant_id,is_discern_qrcode')
                ->order('id desc')
                ->find();
            if(!empty($threadInfo)){
                $resourceInfo['is_apply'] = 1;
                $resourceInfo['merchant_id'] = isset($threadInfo['merchant_id']) ? $threadInfo['merchant_id'] : 0;
                $resourceInfo['is_discern_qrcode'] = isset($threadInfo['is_discern_qrcode']) ? $threadInfo['is_discern_qrcode'] : 0;
                $resourceInfo['is_jump_miniprogram'] = isset($threadInfo['merchant']['is_jump_miniprogram']) ? $threadInfo['merchant']['is_jump_miniprogram'] : 0;
            }
            if(!empty($resourceInfo->resource_ids)){
                $resourceIds = explode(',',$resourceInfo->resource_ids);
                $resourceList = self::whereIn('id',$resourceIds)
                    ->field('id,title,image,content,down_nums,read_nums,downland_links,btn_desc')
                    ->order('id desc')
                    ->select()
                    ->toArray();
                if(!empty($resourceList)){
                    foreach($resourceList as &$val){
                        $val['content'] = getEditText($val['content']);
                    }
                }
                $resourceInfo['resource_list'] = $resourceList;
            }
            $evaluateList = Evaluate::where('be_evaluated_id',$resource_id)
                ->where('status',1)
                ->where('be_evaluated_type',2)
                ->field("nickname,avatar,score,content,create_time")
                ->order('id desc')
                ->limit(20)
                ->select()
                ->toArray();
            $resourceInfo['evaluate_list'] = $evaluateList;
        }
        return $resourceInfo;
    }

    //获取商户客服
    public static function getCustomerQrcode($params = [])
    {
        extract($params);
        $qrcode_image = '';
        $merchant = [];
        $customer = Thread::where('uid', $GLOBALS['uid'])->where('resource_id', $resource_id)->field('customer_id,merchant_id')->find();
        if (!empty($customer['customer_id'])) {
            $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
        }
        if (!empty($customer['merchant_id'])) {
            $merchant = Merchant::where('id', $customer['merchant_id'])->field('customer_qrcode_explain,customer_explain_status')->find();
        }
        $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
        $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
        $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) : '';
        return $data;
    }

    //报名人数
    public function getIsUnderEighteenApplyAttr($data)
    {
        $isUnderEighteenApply = 1;
        $channel = request()->post('channel');
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;

    }
    //报名人数
    public function getVirtualApplyNumsAttr($data)
    {
        $applyNums = Thread::where('resource_id', $data['id'])->count();
        return isset($data['virtual_apply_nums']) ? $data['virtual_apply_nums'] + $applyNums : $applyNums;
    }

    //是否跳转小程序
    public function getIsJumpMiniprogramAttr($data)
    {
        $isJumpMiniprogram = 1;
        $channel = Request::post('channel','');
        if(isset($channel) && !empty($channel) && in_array($channel,$this->no_jump_miniprogram_channel)){
            $isJumpMiniprogram = 0;
            return $isJumpMiniprogram;
        }else{
            return isset($data['is_jump_miniprogram']) ? $data['is_jump_miniprogram'] : $isJumpMiniprogram;
        }
    }

}
<?php
namespace app\lib\service\thread;

use app\lib\api\exception\Exception;
use app\lib\service\common\CurlService;
use app\model\admin\UserList;
use app\model\admin\UserProfile;
use app\model\api\Course;
use app\model\api\Merchant;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Config;
use think\facade\Db;
use app\validate\admin\wechatuserthread\WechatUserThread as WechatUserThreadValidate;
use app\model\admin\Thread;
use app\model\admin\Channel;
use app\model\admin\GatherUserInfo;

class ThreadService
{
    # 来源（B站）
    private $sourceIds = [
        'zy_bzhan_waicai'   => ['id' => 105, 'name' => '城诚-外采-B站', 'thread_price' => 300],
        'zy_bzhan_guangyi'  => ['id' => 115, 'name' => '广颐B站', 'thread_price' => 200],
    ];

    //创建线索用户
    public function addWechatUser($post = [])
    {
        $channelInfo = Channel::with(['app'])->where('id', $post['channel_id'])->find();
        if (! $channelInfo) {
            throw new \think\Exception('投放渠道不存在');
        }
        $appId = $channelInfo['app_id'] ?? '';
        $channelName = $channelInfo['channel_name'] ?? '';
        $sourceArr = $this->sourceIds[$channelName] ?? [];
        if (! $sourceArr && !in_array($appId, [13, 15, 16, 26, 33, 34, 35])) {
            throw new \think\Exception('不属于B站和小红书渠道，请联系技术');
        }
        $sourceId       = $sourceArr['id'] ?? 0;
//        $threadPrice    = $sourceArr['thread_price'] ?? 0;
        $threadPrice    = $channelInfo['cost_price'] ?? 0;

        $is_test = 0;
        if(!empty($post['phone'])) {
            $testPhoneArr = Config::load("extra/test/userphone", "extra") ?? [];
            if (substr($post['phone'],0,2) === '11' || in_array($post['phone'], $testPhoneArr)) {
                $is_test = 1;
            }
        }

        if (! isset($post['isWecomLink']) || ! $post['isWecomLink']) {
            if(!empty($post['phone']) && !empty($post['wechat_number'])) {
                $userInfo = UserList::whereOr(['phone'=>$post['phone'],'wx_number'=>$post['wechat_number']])->where('channel_id', $post['channel_id'])->where('status', 1)->find();
            }else if(!empty($post['phone'])) {
                $userInfo = UserList::where('phone', $post['phone'])->where('status', 1)->where('channel_id', $post['channel_id'])->find();
            }else if($post['wechat_number']){
                $userInfo = UserList::where('wx_number', $post['wechat_number'])->where('status', 1)->where('channel_id', $post['channel_id'])->find();
            }
            if ($userInfo) {
                throw new \think\Exception('用户已存在，请勿重复录入！');
            }
        }

        $customField = [];
        $debt_range = '';
        if ( !empty($post['zhaiwu_leixing']) ){
            $customField[] = '1='.$post['zhaiwu_leixing'];

            $zhaiwuleixingData = GatherUserInfo::where('id',1)->find();
            $zhaiwuleixingData = json_decode($zhaiwuleixingData['gather_info_json'],true);
            $zhaiwuleixingData = array_column($zhaiwuleixingData, 'name', 'id');
            $debt_range        = $zhaiwuleixingData[$post['zhaiwu_leixing']] ?? '';
        }
        $moneyRange = '';
        if ( !empty($post['zhaiwu_money']) ){
            $customField[] = '2='.$post['zhaiwu_money'];

            $zhaiwuData = GatherUserInfo::where('id',2)->find();
            $zhaiwuData = json_decode($zhaiwuData['gather_info_json'],true);
            $zhaiwuData = array_column($zhaiwuData, 'name', 'id');

            $moneyRange = $zhaiwuData[$post['zhaiwu_money']] ?? '';
        }
        $customField = implode(',', $customField);

        try {
            $data = [
                'phone'             => $post['phone'] ?? '11111111111',
                'phone_end_number'  => isset($post['phone']) && !empty($post['phone']) ? substr($post['phone'], -4) : '1111',
                'wx_number'         => $post['wechat_number'],
                'wx_nickname'       => $post['wechat_nickname'],
                'channel'           => $channelInfo['channel_name'] ?? '',
                'channel_id'        => $channelInfo->id ?? 0,
                'app_id'            => $channelInfo->app_id ?? 0,
                'app_class_id'      => $channelInfo->app->app_class_id ?? 0,
                'province'          => $cityInfo['province'] ?? '',
                'city'              => $cityInfo['city'] ?? '',
                'age_range_id'      => $post['age_range_id'] ?? 2,
                'identity_id'       => $post['identify_id'] ?? 0,
                'education_id'      => $post['education_id'] ?? 0,
                'sex'               => $post['sex'] ?? 0,
                'login_time'        => date('Y-m-d H:i:s'),
                'admin_id'          => 0,
                'zhaiwu_leixing'    => $post['zhaiwu_leixing'] ?? 0,
                'zhaiwu_monney'     => $post['zhaiwu_money'] ?? 0,
                'custom_fields'     => $customField,
                'is_wechat'         => 0,
                'is_test'           => $is_test,
                'source_id'         => $post['source_id'] ?? 0,
                'is_origin'         => 3,
            ];
            $user = UserList::create($data);
            $userId = $post['uid'] = $user->id;
            $userProfile = UserProfile::create($post);
            if (!$user || !$userProfile) new Exception('添加用户失败');

            return $this->freeApplyThread([
                'customer_id' => $post['customer_id'] ?? 0,'merchant_id' => $post['merchant_id'] ?? 0,
                'userid' => $userId, 'is_test' => $is_test, 'response_time' => $post['response_time'] ?? date('Y-m-d H:i:s'),
                'money_range' => $moneyRange, 'source_id' => $sourceId, 'thread_price' => $threadPrice,
                'debt_range' => $debt_range
            ], $post);
        } catch (\Exception $e) {
            new Exception($e->getMessage());
        }
    }

    //免费报名
    public function freeApplyThread($data = [], $post = [])
    {
        $userId = $data['userid'];

        //请求地址
        $mmcrm_url = env('MMCRMURL.MMCRM_URL') ?? 'http://szmmcrm.yuluojishu.com';

        $merchantId = $data['merchant_id'];
        $customerId = $data['customer_id'];

        if (!$customerId) {

            $merchantRes = json_decode(curlPost($mmcrm_url . '/admin.api.external_thread_yl/getCustomerInfo',
                [
                    'money_range' => $data['money_range'] ?? '',
                    'is_test' => $data['is_test'] ?? 0,
                    'is_appoint_merchant' => $merchantId ? 1 : 0,
                    'merchant_id' => $merchantId,
                    'is_ecommerce' => 0,
                    'is_appstore' => 0,
                    'is_reduce_thread_num' => 1,
                    'is_bzhanxhs' => 1,     # B站和小红书的线索
                ]), true);

            $merchantId = $merchantRes['data']['merchant_id'] ?? 0;
            $customerId = $merchantRes['data']['id'] ?? 0;
            $customerLink = $merchantRes['data']['customer_link'] ?? '';
        }

        if (!$customerId) {
            throw new \think\Exception('分配销售失败，请稍后再试');
        }

        $courseId = Course::where('merchant_id', $merchantId)->value('id');
        if (empty($courseId)) {
            $courseId = 0;
        }

        $userInfo = UserList::where('id', $userId)->find();
        $channelInfo = \app\model\api\Channel::with(['app' => function ($query) {
            $query->field('id,app_class_id');
        }])->where('id', $userInfo->channel_id)->find();
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];

        $merchantInfo = Merchant::find($merchantId);
        $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo);
        $threadPrice = $data['thread_price'] ?? 0;

        try {
            $ret = Thread::create([
                'uid' => $userInfo->id,
                'course_id' => $courseId,
                'merchant_id' => $merchantId,
                'customer_id' => $customerId,
                'province' => $cityInfo['province_name'] ?? '',
                'city' => $cityInfo['city_name'] ?? '',
                'age' => $ageRange,
                'channel' => $userInfo->channel,
                'channel_id' => $channelInfo['id'] ?? 0,
                'app_id' => $channelInfo['app']['id'] ?? 0,
                'app_class_id' => $channelInfo['app']['app_class_id'] ?? 0,
                'thread_price' => $threadPrice,
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'thread_price_origin' => $threadPrice,
                'thread_type' => isset($merchantInfo->is_jump_miniprogram) ? ($merchantInfo->is_jump_miniprogram > 0 ? 3 : 1) : 0,
                'source' => $channelInfo['source_id'] ?? 0,
                'is_free_try' => $merchantInfo->is_free_try,
                'is_test' => $userInfo->is_test,
                'age_id' => $userInfo->age_range_id,
                'merchant_admin_id' => $merchantInfo->admin_ids,
                'is_origin' => 2,
                'cost_price' => $channelInfo['cost_price'] ?? 0,
                'debt_range' => $data['debt_range'] ?? '',
                'money_range' => $data['money_range'] ?? '',
                'response_time' => !empty($data['response_time']) ? strtotime($data['response_time']) : 0,
                'is_media' => $data['is_media'] ?? 0,
                'media_images' => $data['media_images'] ?? '',
                'source_id' => $data['source_id'] ?? 0,
                'is_super_a' => $data['is_super_a'] ?? 0,
            ]);
            if ($ret) {
                $userInfo = $userInfo->toArray() ?? [];
                $threadInfo = $ret->toArray();
                $threadInfo['is_super_a'] = 0;
                $threadInfo['is_within_three_days'] = 0;
                $threadId = $threadInfo['id'];
                unset($threadInfo['id']);
                unset($userInfo['id']);
                $userInfo['create_time'] = time();
                $userInfo['update_time'] = time();
                $threadInfo['create_time'] = time();
                $threadInfo['update_time'] = time();
                $threadInfo['wm_uid'] = $threadInfo['uid'];
                $threadInfo['init_customer_id'] = $threadInfo['customer_id'];
                $threadInfo['init_merchant_id'] = $threadInfo['merchant_id'];
                $threadInfo['inside_thread_id'] = $threadId;
                $threadInfo['mark'] = 2;
                CurlService::post($mmcrm_url . '/admin.api.external_thread_yl/importExternalThreadYl', ['user_info' => $userInfo, 'thread_info' => $threadInfo]);
                return $threadInfo;
            }
            new Exception('生成线索失败');
        } catch (\Exception $e) {
            new Exception($e->getMessage() . '---' . $e->getLine());
        }
    }
}
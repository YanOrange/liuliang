<?php

namespace app\lib\api\service;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\model\api\Channel;
use app\model\api\Course;
use app\model\api\AppClass;
use think\facade\Db;

class MerchantServiceOverdue
{
    //获取收费和付费的商户的数量
    public static function getOverdueMerchant($channel)
    {
        $channelInfo = Channel::getChannelAppClassOverdue($channel);
        if (!$channelInfo) return [];

        // 应用分类：线索加价梯度(元)
        $appId = $channelInfo['app_id'];
        $appClass = Db::name('app_class')->where('id', $channelInfo['app_class_id'])->field('id, thread_raise_price_grads')->find();
        $gradsPrice = isset($appClass['thread_raise_price_grads']) ? $appClass['thread_raise_price_grads'] : 1;

        $uid = $GLOBALS['uid'];
        $courseModel = new \app\model\api\Course();
        $name = $courseModel->getName();
        $tableName = env('database.prefix') . $name;

        // 站内 | 站外
        $userInfo = UserList::where('id', $uid)->field('id,phone,age_range_id, is_search_plan')->find();
        $userIds = UserList::where('phone',$userInfo->phone)->where('status',1)->column('id');
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = !empty($gatherInfo['name']) ? '"' . $gatherInfo['name'] . '"' : '';

        // 好课推荐列表
        $courseObj = $courseModel->whereExists(function ($query) use ($tableName, $ageRange) {
            $merchantTableName = (new \app\model\api\Merchant())->getName();
            $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' . $tableName . '.merchant_id');
            $query->where('is_switch', 1);
            $query->where("age_range_weight_json->'$." . $ageRange . "'", '>', 0);
            return $query;
        })->where('status', '=', 1)
            ->where('course_type', '=', 0)
            ->whereFindInSet('app_ids', $appId)
            ->field('id, title, virtual_apply_nums, tag_names, merchant_id, apply_succeed_wx_btn,landing_page_btn_image, course_thumbnail_image,btn_desc,entry_fee,video_url, video_cover_image')
            ->visible(['id', 'title', 'virtual_apply_nums', 'tag_names', 'merchant_id', 'apply_succeed_wx_btn','landing_page_btn_image', 'course_thumbnail_image', 'btn_desc', 'entry_fee', 'video_url', 'video_cover_image'])
            ->with(['merchant' => function ($query) {
                $query->field('id, peak_price, is_source, is_jump_miniprogram');    // 高峰线索单价
            }, 'LandingPage' => function ($query) {
                $query->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id');
            }]);
        $courseArr = $courseObj->select()->toArray();

        // 用户是否已报名
        $threadArr = Db::name('thread')
            ->field('course_id, merchant_id')
            ->where('course_id', 'in', array_column($courseArr, 'id'))
            ->whereIn('uid', $userIds)
            ->whereDay('create_time')
            ->select()->toArray();
        $signUpMerchantIds = array_column($threadArr, 'merchant_id');

        // 商户高峰线索单价
        $merchantPeakPriceArr = [];
        foreach ($courseArr as $info) {
            if (isset($info['merchant'])) {
                $merchant = $info['merchant'];
                $merchantPeakPriceArr[] = ['price' => $merchant['peak_price'], 'id' => $merchant['id'], 'is_source' => $merchant['is_source']];
            }
        }
        array_multisort($merchantPeakPriceArr, SORT_DESC);

        $sourceWArr = $sourceNArr = [];
        foreach ($merchantPeakPriceArr as $item) {
            if (!in_array($item['id'], $signUpMerchantIds)) {
                if ($item['is_source'] == 1) {   // 站内
                    $sourceNArr[] = $item;
                } else {
                    $sourceWArr[] = $item;
                }
            }
        }

        $num = 0;
        $merchantPeakPriceArr = !empty($sourceWArr) ? $sourceWArr : $sourceNArr;
        $peakPriceArr = [];                                         // 求周期数
        for ($i = count($merchantPeakPriceArr) - 1; $i >= 0; $i--) {
            $current = $merchantPeakPriceArr[$i];
            if ($i == count($merchantPeakPriceArr) - 1) {           // 价格最低的为1
                $peakPriceArr[] = [
                    'id' => $current['id'],
                    'price' => $current['price'],
                    'weight' => 1
                ];
                $num = 1;
            }

            if (!isset($merchantPeakPriceArr[$i - 1])) continue;    // 防止下标为负数

            $last = $merchantPeakPriceArr[$i - 1];
            $weightNum = intval(($last['price'] - $current['price']) / $gradsPrice);

            $num += $weightNum;
            $peakPriceArr[] = [
                'id' => $last['id'],
                'price' => $last['price'],
                'weight' => $num
            ];
        }

        // 权重随机返回商户ID
        $weight = new WeightService();
        $merchantId = $weight->initData($peakPriceArr);
        $courseId = Course::where('merchant_id',$merchantId)->where('status',1)->value('id');
        return ['course_id' => $courseId,'merchant_id' => $merchantId,'merchant_ids' => $peakPriceArr];
    }

}


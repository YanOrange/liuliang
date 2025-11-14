<?php
/**
 * 首页
 */

namespace app\model\api\touliu;

use app\model\admin\Article;
use app\model\admin\UserList;
use app\model\api\Channel;
use app\model\api\h5\ForFlow;
use app\model\api\LandingPage;
use laytp\BaseModel;

class Show extends BaseModel
{

    /**
     * 首页
     *
     * @param array $params
     * @return void
     */
    public static function homePage($params = [])
    {
        extract($params);
        // 获取渠道信息
        $channelInfo = Channel::where('channel_name', $channel)->find();
        // 获取渠道留资页描述
        $retentionPageDesc = !empty($channelInfo->retention_page_desc) ? json_decode($channelInfo->retention_page_desc, true) : ['大牌律所、正规收账公司', '诉调执收多管齐下，立马收回钱'];
        // 获取落地页信息列表
        $ldyDataList = LandingPage::where('app_id', $channelInfo->app_id)->order('weight desc,id asc')->select()->toArray();
        $videoArr = [];
        foreach ($ldyDataList as $item) {
            if ($item['video_url']) {
                array_push($videoArr, $item['video_url']);
            }
        }
        return [
            'retentionPageDesc' => $retentionPageDesc,
            'ldyDataList' => $ldyDataList,
            'head_picture' => 'http://cdnwm.yuluojishu.com/uploads/20240524/6c1f8567c3c6b020ed376fc14918a9e8.png',
            'video_url' => $videoArr ? $videoArr[array_rand(array_keys($videoArr))] : ''
        ];
    }

    public static function noLoginHomePage($params = [])
    {
        extract($params);
        // 获取渠道信息
        $channelInfo = Channel::where('channel_name', $channel)->find();
        // 获取渠道留资页描述
        $retentionPageDesc = !empty($channelInfo->retention_page_desc) ? json_decode($channelInfo->retention_page_desc, true) : ['大牌律所、正规收账公司', '诉调执收多管齐下，立马收回钱'];
        // 获取落地页信息列表
        $ldyDataList = LandingPage::where('app_id', $channelInfo->app_id)->order('weight desc,id asc')->select()->toArray();
        return [
            'retentionPageDesc' => $retentionPageDesc,
            'ldyDataList' => $ldyDataList,
            'head_picture' => 'http://cdnwm.yuluojishu.com/uploads/20240524/6c1f8567c3c6b020ed376fc14918a9e8.png'
        ];
    }

    /**
     * 留资页获取收集信息
     *
     * @param [type] $token
     * @return void
     */ 
    public static function getGatherInfoData($token,$is_region = 0)
    {
        $uid = checkJwtToken($token);
        $gatherInfoData = [];
        $userInfo = UserList::where('id',$uid)->field('id,phone,channel_id')->find();
        if(!empty($userInfo)){
            $gatherUserInfoIds = Channel::getFieldById($userInfo->channel_id,'gather_user_info_ids');
            if(!empty($gatherUserInfoIds)){
                $gatherInfoData = ForFlow::getGatherInfoList($gatherUserInfoIds);
            }
        }
        if ($is_region) {
            return [
                'data' => $gatherInfoData,
                'is_region' => 1
            ];
        } else {
            return $gatherInfoData;
        }
    }

    /**
     * 获取资讯列表
     *
     * @param array $params
     * @return void
     */
    public static function getInformationList($params = [])
    {
        extract($params);
        // 获取渠道信息
        $channelInfo = Channel::where('channel_name', $channel)->find();
        // 文章列表
        $articleList = Article::whereFindInSet('channel_ids', $channelInfo->id)->select()->toArray();

        $data = [];
        foreach ($articleList as $key => $item) {
            $data[$key]['id'] = $item['id'];
            $data[$key]['title'] = $item['title'];
            $data[$key]['img'] = $item['image'];
            $data[$key]['virtual_read_nums'] = $item['virtual_read_nums'];
            $data[$key]['virtual_like_nums'] = $item['virtual_like_nums'];
        }
        return $data;
    }

    public static function getCustomerCasesList()
    {
        $names = array("王", "李", "张", "刘", "陈", "杨", "赵", "黄", "周", "吴", "徐", "孙", "胡", "朱", "高", "林", "何", "郭", "马", "罗");
        $titles = array("先生", "女士");
        foreach ($titles as $item) {
            // 从名字数组中随机取出一个名字
            $randomName = $names[array_rand($names)];
            // 从称谓数组中随机取出一个称谓
            $randomTitle = $titles[array_rand($titles)];
            // 组合名字与称谓
            $randomPair[] = $randomName . $randomTitle;
        }

//        $type = array("网贷", "信用卡", "信用卡", "网贷", "网贷", "信用卡");
        $type = array("其他债务", "信用卡", "信用卡", "其他债务", "其他债务", "信用卡");
        $services = array("银行", "贷网", "小满", "呗", "享花", "借", "分期", "钱包");
        $amounts = array("3.3万", "10万", "5万", "18万", "98.8万", "44.57万", "135.7万", "55万");
        $repaymentOptions = array(
            "协商延期2年还款，期间停催",
            "每月800元，分30期，减免罚息",
            "一次性结清欠款，减免罚息20000元",
            "分期协商成功，人工全面停催",
            "分期50期，副业介绍成功",
            "只还本金，减免罚息5000元，7天上岸",
            "二次分期成功，避免起诉",
            "停止债上债，延期还款25期"
        );
        $data = [
            [
                'name' => $randomPair[0],
                'age' => strstr($randomPair[0], '先生') !== false ? 1 : 2,
                'type' =>  $type[array_rand($type, 2)[0]],
                'services' => $services[array_rand($services, 2)[0]],
                'amounts' => $amounts[array_rand($amounts, 2)[0]],
                'repaymentOptions' => $repaymentOptions[array_rand($repaymentOptions, 2)[0]]
            ],
            [
                'name' => $randomPair[1],
                'age' => strstr($randomPair[1], '先生') !== false ? 1 : 2,
                'type' =>  $type[array_rand($type, 2)[1]],
                'services' => $services[array_rand($services, 2)[1]],
                'amounts' => $amounts[array_rand($amounts, 2)[1]],
                'repaymentOptions' => $repaymentOptions[array_rand($repaymentOptions, 2)[1]]
            ]
        ];
        return $data;
    }
}
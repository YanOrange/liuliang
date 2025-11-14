<?php
/**
 * 轮播图表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\api\App;
class Banner extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'banner';

    //轮播图列表
    public static function getMerchantBannerList($merchantId = 0, $channelInfo = null)
    {
        $bannerList = self::field('image')->whereFindInSet('merchant_id', $merchantId)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('status', 1)->where('type', 0)->order('sort desc')->limit(5)->select()->toArray();
        if (empty($bannerList)) {
            $bannerList = self::field('image')->whereFindInSet('merchant_id', $merchantId)->where('status', 1)->where('type', 0)->order('sort desc')->limit(5)->select();
        }
        return !empty($bannerList) ? $bannerList : [];
    }
}

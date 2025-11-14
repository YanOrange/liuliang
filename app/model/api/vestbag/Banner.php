<?php
/**
 * 轮播图表模型
 */

namespace app\model\api\vestbag;

use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
class Banner extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'banner';

    //假首页轮播图列表
    public static function getBannerList($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $bannerList = self::whereFindInSet('channel_ids', $channelInfo['channel_id'])
            ->where('status', 1)
            ->where('type', 0)
            ->order('sort desc')
            ->limit(5)
            ->column('image');
        return !empty($bannerList) ? $bannerList : [];
    }
}

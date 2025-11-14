<?php

namespace app\model\api;

use app\lib\api\exception\ExceptionStd;
use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\v2\Banner;

class ArticleNews extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'article_news';

    //文章列表
    public static function getArticleNewsListV2($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $articleNewsData = self::field('id,title,content,image,create_time,arrears_platform,arrears_amount')
           // ->where('app_class_id', $channelInfo['app_class_id'])
            ->whereIn('id', [131,132])
            ->where('status', 1)
            ->limit(6)
            ->select()
            ->toArray();
        foreach ($articleNewsData as &$value) {
            $value['content_text'] = getEditContentText($value['content'], 200);
            $value['create_time'] = date('Y/m/d', strtotime($value['create_time']));
            unset($value['content']);
        }
        return $articleNewsData;
    }
    //逾期文章列表
    public static function getArticleNewsListV3($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $articleNewsData = self::field('id,title,content,image,create_time,arrears_platform,arrears_amount,virtual_read_nums')
           // ->where('app_class_id', $channelInfo['app_class_id'])
            ->whereIn('id', [130,124,81,80])
            ->where('status', 1)
            ->limit(6)
            ->select()
            ->toArray();
        foreach ($articleNewsData as &$value) {
            $value['content_text'] = getEditContentText($value['content'], 200);
            $value['create_time'] = date('Y/m/d', strtotime($value['create_time']));
            $value['virtual_read_nums'] = intval($value['virtual_read_nums']);
            unset($value['content']);
        }
        return $articleNewsData;
    }
    
    //文章详情V2
    public static function getArticleNewsDetailV2($params = [])
    {
        extract($params);
        $data = self::field('id,title,content,virtual_read_nums,create_time')->find($article_id);
        if (empty($data)) {
            new ExceptionStd('文章不存在或已下架');
        }
        return $data;
    }
    //文章列表
    public static function getArticleNewsListV4($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $articleNews = self::field('id,title,image,virtual_read_nums,virtual_like_nums')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->limit(6)
            ->select()
            ->toArray();
        foreach ($articleNews as &$value) {
            $value['virtual_read_number'] = preg_replace('/([\x80-\xff]*)/i','', $value['virtual_read_nums']);
            $value['virtual_like_number'] = preg_replace('/([\x80-\xff]*)/i','', $value['virtual_like_nums']);
        }
        return $articleNews;
    }
    public static function getArticleNewsV3($params)
    {
        extract($params);
        $articleList = self::getArticleNews($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        return [
            'articleList' => $articleList,
            'bannerList' => Banner::getMerchantBannerList(Merchant::where('app_class_id', $channelInfo['app_class_id'])->where('is_source', 1)->value('id'),$channelInfo),
        ];
    }

    //文章列表
    public static function getArticleNews($params)
    {
        extract($params);
        $type = isset($type) && !empty($type) ? $type : 0;
        $where[] = ['type','=',$type];
        $articleNews = [];
        if(isset($channel) && !empty($channel)) {
            
            if ($channel == 'wxmini_dub' || $channel == 'wxmini_original' ) {
                 $articleNews = self::field('id,title,content,image,theme_images,virtual_read_nums,virtual_like_nums,create_time')
                    ->whereIn('id', [13,14,15,53,54])
                //    ->where('status', 1)
                    //->order('id', $channel == 'zwyqyhpt_ios' ? 'desc' : 'asc')
                    ->limit(6)
                    ->select()
                    ->toArray();
            } else if ($channel == 'wxmini_overdue') {
                $articleNews = self::field('id,title,content,image,theme_images,virtual_read_nums,virtual_like_nums,create_time')
                    ->whereIn('id', [82,83,86,76,77])
              //      ->where('status', 1)
                    //->order('id', $channel == 'zwyqyhpt_ios' ? 'desc' : 'asc')
                    ->limit(6)
                    ->select()
                    ->toArray();
            } else if ($channel == 'wxmini_debt') {
                $articleNews = self::field('id,title,content,image,theme_images,virtual_read_nums,virtual_like_nums,create_time')
                    ->whereIn('id', [127,128,129,130])
               //     ->where('status', 1)
                    //->order('id', $channel == 'zwyqyhpt_ios' ? 'desc' : 'asc')
                    ->limit(6)
                    ->select()
                    ->toArray();
            } else {
                $channelInfo = Channel::getChannelAppClass($channel);
                $articleNews = self::field('id,title,content,image,theme_images,virtual_read_nums,virtual_like_nums,create_time')
                ->where('app_class_id', $channelInfo['app_class_id'])
                ->where('status', 1)
                //->order('id', $channel == 'zwyqyhpt_ios' ? 'desc' : 'asc')
                ->limit(6)
                ->select()
                ->toArray();
            }   
        }else{
            $articleNews = self::field('id,title,content,image,theme_images,virtual_read_nums,virtual_like_nums,create_time')
                ->where('status', 1)
                ->where('app_class_id', 1)
                ->where($where)
                ->order('id', 'asc')
                ->limit(6)
                ->select()
                ->toArray();
        }
        /*if (empty($articleNews)) {
            new ExceptionStd('文章数据为空');
        }*/
        foreach($articleNews as &$val) {
            $content = str_replace("&quot;", "", $val['content']);
            $content = html_entity_decode($content);
            $val['content'] = richText($content);
            $themeImages = [];
            if (!empty($val['theme_images'])) {
                $images = explode(', ',$val['theme_images']);
                foreach ($images as $v) {
                    $themeImages[] = preg_replace('/\s+/','',$v);
                }
            }
            $val['create_time'] = date('Y/m/d', strtotime($val['create_time']));
            //$val['create_time'] = date('Y/m/d', $val['create_time']);
            $val['theme_images'] = $themeImages;
            $val['content_desc'] = getEditContentText($val['content']);
        }
        return $articleNews;
    }

    public function getVirtualReadNumsAttr($value, $data)
    {
        return $value . '人已阅读';
    }

    public function getVirtualLikeNumsAttr($value, $data)
    {
        return $value . '人已点赞';
    }

}
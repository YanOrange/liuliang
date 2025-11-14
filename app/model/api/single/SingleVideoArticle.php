<?php

namespace app\model\api\single;

use app\lib\api\service\LandingPageServiceV2;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use app\model\api\Article;
use app\model\api\App;
use app\model\api\single\PartClass;
use app\model\api\LandingPage;

class SingleVideoArticle extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'single_video_article';

    protected $hidden = [
        'tag_id',
        'courseTag'
    ];

    //首页视频文章列表
    public static function getVideoArticleList($params)
    {
        extract($params);
        $classId = isset($class_id) ? $class_id : 1;
        $channelInfo = Channel::getChannelAppClass($channel);
        $where = [];
        if($classId > 1){
            $where[] = ['class_id','=',$classId];
        }
        $limit_num = 50;
        $videoArticleList = [];
        if(!empty($channelInfo)){
            $videoArticleList = self::with(['courseTag'])
                ->where($where)
                ->where('status',1)
                ->whereFindInSet('app_ids',$channelInfo['app_id'])
                ->field('id,title,video_image,play_nums,like_nums,article_id,tag_id,type')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->paginate($limit_num)
                ->toArray();
        }
        $videoArticleList = isset($videoArticleList['data']) && !empty($videoArticleList['data']) ? $videoArticleList['data'] : [];
        return $videoArticleList;
    }

    //首页类目列表
    public static function getVideoClassList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $appInfo = App::where('id', $channelInfo['app_id'])->where('is_landing_page', 1)->find();
        $landingPage = LandingPage::getLandingPageList($params);
        $classOne = ['id' => 1,'title' => '推荐'];
        $classIds = self::where('status',1)
            ->whereFindInSet('app_ids',$channelInfo['app_id'])
            ->group('class_id')
            ->column('class_id');
        $videoClassList = PartClass::where('class_type',3)
            ->whereIn('id',$classIds)
            ->field('id,part_class_name as title')
            ->order('id desc')
            ->select()
            ->toArray();
        array_unshift($videoClassList,$classOne);
        $data['class_data'] = $videoClassList;
        $data['landing_page'] = [
            'is_show' => !empty($landingPage) ? 1 : 0,
            'landing_page_image' => !empty($appInfo) ? $appInfo->landing_page_image : ''
        ];
        return $data;
    }

    public function getTitleAttr($value,$data)
    {
        $title = $data['title'];
        if($data['type'] == 2) {
            $title = Article::where('id',$data['article_id'])->value('title');
        }
        return $title;
    }

    public function getVideoImageAttr($value,$data)
    {
        $videoImage = $data['video_image'];
        if($data['type'] == 2) {
            $videoImage = Article::where('id',$data['article_id'])->value('image');
        }
        return $videoImage;
    }

    public function courseTag()
    {
        return $this->belongsTo('app\model\api\single\PartCourseTag','tag_id','id')
            ->bind(['tag_name','tag_color'])
            ->removeOption('soft_delete');
    }

}
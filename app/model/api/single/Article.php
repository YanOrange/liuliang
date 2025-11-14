<?php

namespace app\model\api\single;

use app\lib\api\exception\ExceptionStd;
use app\model\api\Channel;
use app\model\api\LandingPage;
use app\model\api\Thread;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Article extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'article';

    //推荐阅读列表
    public static function getArticleList($params = [])
    {
        extract($params);
        $sortId = isset($sort_id) && !empty($sort_id) ? $sort_id : 1;
        $order = ['sort' => 'desc', 'id' => 'desc'];
        if($sortId == 2){
            $order = 'id desc';
        }
        if($sortId == 3){
            $order = 'virtual_read_nums desc';
        }
        $data = [];
        $articleList = [];
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantList = Merchant::getMerchantIds($channelInfo);
        if(!empty($merchantList)) {
            foreach ($merchantList as $val) {
                $articleList[] = self::where('status', 1)
                    ->whereFindInSet('merchant_id', $val['id'])
                    ->field('id')
                    ->select();
            }
        }
        $articleIds = [];
        foreach($articleList as $key=>$item){
            if(empty($item)) {
                unset($articleList[$key]);
            }else{
                foreach($item as $val){
                    $articleIds[] = $val['id'];
                }
            }
        }
        $articleList = self::where('status', 1)
            ->whereIn('id',$articleIds)
            ->field('id,title,image,virtual_read_nums,virtual_like_nums')
            ->order($order)
            ->select();
        if(!empty($articleList)){
            $data['sort_list'] = self::sortList();
            $data['article_list'] = $articleList;
        }
        return $data;
    }

    //排序列表
    public static function sortList()
    {
        $data = [
            ['id' => 1,'name' => '综合'],
            ['id' => 2,'name' => '最新'],
            ['id' => 3,'name' => '最热'],
        ];
        return $data;
    }

    //文章详情
    public static function getArticleDetail($params = [])
    {
        extract($params);
        $articleDetail = self::with(['course' => function($query){
            $query->where('status',1);
            $query->field('id,title,merchant_id,video_cover_image,virtual_apply_nums,btn_desc');
        }])
            ->field('id,title,content,image,virtual_read_nums,virtual_like_nums,course_id')
            ->find($article_id);
        if (empty($articleDetail)) {
            new ExceptionStd('文章不存在');
        }
        $articleDetail['langing_page_list'] = [];
        if(!empty($articleDetail['course'])) {
            $articleDetail['course']['is_apply'] = 0;
            $threadInfo = Thread::where('uid', $GLOBALS['uid'])
                ->where('course_id',$articleDetail['course']['id'])
                ->order('id desc')
                ->find();
            $landingPage = LandingPage::withTrashed()
                ->with(['course' => function ($query) {
                    $query->field('id,btn_desc,video_url,merchant_id,entry_fee,virtual_apply_nums,confirm_btn_desc,confirm_copy_desc,flow_desc');
                }])
                ->field('id,landing_image,desc_image,course_id')
                ->where('course_id', $articleDetail['course']['id'])
                ->find();
            if (!empty($landingPage)) {
                $landingPage['course']['confirm_copy_desc'] = !empty($landingPage['course']['confirm_copy_desc']) ? json_decode($landingPage['course']['confirm_copy_desc'],true) : [];
                $landingPage['course']['flow_desc'] = !empty($landingPage['course']['flow_desc']) ? json_decode($landingPage['course']['flow_desc'],true) : [];
                unset($landingPage['course']['apply_nums']);
                $articleDetail['langing_page_list'] = [$landingPage];
            }
            if(!empty($threadInfo)){
                $articleDetail['course']['btn_desc'] = $articleDetail['course']['is_jump_miniprogram'] == 1 ? '加微信' : '已报名';
                $articleDetail['course']['is_apply'] = 1;
            }
        }
        $articleDetail['course'] = $articleDetail['course'] ?? new \stdClass();
        $articleDetail['content'] = richText($articleDetail['content']);
        return $articleDetail;
    }

    public function getVirtualReadNumsAttr($value, $data)
    {
        return $value . '人已阅读';
    }

    public function getVirtualLikeNumsAttr($value, $data)
    {
        return $value . '人已点赞';
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\Course','course_id','id')->removeOption('soft_delete');
    }
}
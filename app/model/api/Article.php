<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\api;

use app\model\api\Channel;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\UserList;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
class Article extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'article';
    protected $append = [
        'study_nums'
    ];
    public static function getArticleList($params)
    {
        self::field('id,title,virtual_read_nums,create_time')->find($article_id);
    }
    //文章详情V2
    public static function getArticleDetailV2($params = [])
    {
        extract($params);
        $data = self::field('id,title,content,virtual_read_nums,create_time')->find($article_id);
        if (empty($data)) {
            new ExceptionStd('文章不存在或已下架');
        }
        return $data;
    }

    //文章详情
    public static function getArticleDetail($params = [])
    {
        extract($params);
        if(isset($channel) && !empty($channel)){
            $channelInfo = channel::getChannelAppClass($channel);
        }
        $userInfo = UserList::getUserInfo();
        $articleDetail = self::with(['course' => function($query){
                $query->where('status',1);
                $query->field('id,title,merchant_id,video_cover_image,virtual_apply_nums,btn_desc');
            }])
            ->field('id,title,content,image,virtual_read_nums,virtual_like_nums,course_id,article_video_url')
            ->find($article_id);
        if (empty($articleDetail)) {
            new ExceptionStd('文章不存在');
        }
        $articleDetail['langing_page_list'] = [];
        //识别图片二维码
        $articleDetail['discern_qrcode_image'] = '';
        if(isset($channelInfo['app_class_id']) && $channelInfo['app_class_id'] == 17){
            if($channelInfo['capital_page_position'] == 1 || ($channelInfo['capital_page_position'] == 2 && $userInfo['is_perfection_info'] == 1)){
                $articleDetail['discern_qrcode_image'] = env('DISCERNQRCODE.DISCERN_QRCODE_IMAGE');
            }
        }
        if(!empty($articleDetail['course'])) {
            $articleDetail['course']['is_apply'] = 0;
            $threadInfo = Thread::where('uid', $GLOBALS['uid'])
                ->where('course_id',$articleDetail['course']['id'])
                ->order('id desc')
                ->find();
            $landingPage = LandingPage::withTrashed()
                ->with(['course' => function ($query) {
                    $query->field('id,btn_desc,video_url,merchant_id,entry_fee,virtual_apply_nums,confirm_btn_desc,confirm_copy_desc,flow_desc,landing_page_btn_image');
                }])
                ->field('id,landing_image,lamp_back_image,end_image,desc_image,lamp_font_color,is_lamp,course_id')
                ->where('course_id', $articleDetail['course']['id'])
                ->find();
            if (!empty($landingPage)) {
                $landingPage['course']['confirm_copy_desc'] = !empty($landingPage['course']['confirm_copy_desc']) ? json_decode($landingPage['course']['confirm_copy_desc'],true) : [];
                $landingPage['course']['flow_desc'] = !empty($landingPage['course']['flow_desc']) ? json_decode($landingPage['course']['flow_desc'],true) : [];
                unset($landingPage['course']['apply_nums']);
                $landingPage['horse_race_lamp'] = self::getHorseRaceLamp($landingPage);
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

    public static function getHorseRaceLamp($data)
    {
        $horseRaceLamp = [];
        if($data['is_lamp'] == 1) {
            $horseRaceLamp = HorseRaceLamp::field('nickname,phone,times')->order('times', 'asc')->select();
            if (!empty($horseRaceLamp)) {
                foreach ($horseRaceLamp as &$val) {
                    $phone_xing = substr($val->phone, 4, 4);  //获取手机号中间四位
                    $val['nickname'] = subNickname($val->nickname);
                    $val['phone'] = str_replace($phone_xing, '****', $val->phone);  //用****进行替换
                    $val['times'] = $val->times . '分钟前';
                }
            }
        }
        return $horseRaceLamp;
    }

    public function getVirtualReadNumsAttr($value, $data)
    {
        return $value . '人已阅读';
    }
    public function getVirtualLikeNumsAttr($value, $data)
    {
        return $value . '人已点赞';
    }
    public function getStudyNumsAttr($value, $data)
    {
        return isset($data['virtual_read_nums']) ? $data['virtual_read_nums'] . '人学习' : rand(2000, 3000) . '人学习';
    }
    //商户推荐阅读列表
    public static function getMerchantArticleList($merchantId = 0, $channelInfo = null)
    {
        $articleList = [];
        if(!empty($channelInfo)){
            $articleList = self::field('id,title,image,content,virtual_read_nums,virtual_like_nums,create_time')->whereFindInSet('merchant_id', $merchantId)->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('status',  1)->order('sort desc')->limit(6)->select()->toArray();
        }
        if (empty($articleList)) {
            $articleList = self::field('id,title,image,content,virtual_read_nums,virtual_like_nums,create_time')->whereFindInSet('merchant_id', $merchantId)->where('status',  1)->order('sort desc')->limit(6)->select()->toArray();
        }
        if(!empty($articleList)){
            foreach($articleList as &$item){
                $item['content'] = getEditContentText($item['content']);
                $item['virtual_like_number'] = preg_replace('/([\x80-\xff]*)/i','', $item['virtual_read_nums']);
                $item['create_time'] = date('Y-m-d', strtotime($item['create_time']));
            }
        }
        return !empty($articleList) ? $articleList : [];
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\Course','course_id','id')->removeOption('soft_delete');
    }
}

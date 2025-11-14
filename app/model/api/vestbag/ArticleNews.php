<?php
/**
 * 文章
 */

namespace app\model\api\vestbag;
use app\lib\api\exception\Exception;
use app\model\api\vestbag\Course as CourseModel;
use laytp\BaseModel;
use app\lib\api\exception\ExceptionStd;
use app\model\api\Channel;

class ArticleNews extends BaseModel
{
    //模型名
    protected $name = 'article_news';
    protected $append = [
        'btn_desc'
    ];
    public function getBtnDescAttr($value, $data)
    {
        return '立即预约';
    }
    public function getCreateTimeAttr($value, $data)
    {
        return isset($data['create_time']) && !empty($data['create_time']) ? date('Y-m-d', $data['create_time']) : '';
    }
    public function getLabelAttr($value, $data)
    {
        return isset($data['label']) && !empty($data['label']) ? json_decode($data['label'], true) : [];
    }
    //智能测算
    public static function getIntelligentMeasurement($params = [])
    {
        extract($params);
        $debtNums = isset($debt_nums) ? $debt_nums : 0;
        $debtAmount = isset($debt_amount) ? $debt_amount : 0;
        return [
            'dissect' => "根据您的当前的困惑,借贷了{$debtNums}平台,欠款{$debtAmount},还款压力高,人工智能法务测算出我们将会于3天内给你解决方案,你也可以主动联系法务。",
            'applyInfo' =>  CourseModel::courseDetail($channel, 0, $app_version)
        ];

    }
    //获取首页banner点击
    public static function getConsultListV2($params = [])
    {
        extract($params);
        $classList = [['id' => 1, 'className' => '企业逾期'],['id' => 2, 'className' => '个人逾期'],['id' => 3, 'className' => '起诉/催收']];
        $channelInfo = Channel::getChannelAppClass($channel);
        $classId = isset($class_id) && !empty($class_id) ? $class_id : 1;
        $articleNews = self::field('id,title,virtual_read_nums,image,label')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->where('tag_id', $classId)
            ->order('id','desc')
            ->limit(6)
            ->select();
        return [
            'bannerList' => ['http://cdnwm.yuluojishu.com/uploads/20230325/d53b75421ab84ff7cfb337becbe607e8.png'],
            'classList' => $classList,
            'articleNewsList' => $articleNews,
        ];
    }

    //获取首页咨询列表
    public static function getConsultList($params = [])
    {
        extract($params);
        $classList = [['id' => 4, 'className' => '推荐'],['id' => 5, 'className' => '逾期咨询'],['id' => 6, 'className' => '法律法规']];
        $channelInfo = Channel::getChannelAppClass($channel);
        $hotArticleTitle = self::field('id,title')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->order('id','desc')
            ->limit(3)
            ->select();
        $classId = isset($class_id) && !empty($class_id) ? $class_id : 4;
        $articleNews = self::field('id,title,virtual_read_nums,image,create_time')
            ->where('app_class_id', $channelInfo['app_class_id'])
            ->where('status', 1)
            ->where('tag_id', $classId)
            ->order('virtual_read_nums','desc')
            ->limit(6)
            ->select();
        return [
            'classList' => $classList,
            'articleNewsList' => $articleNews,
            'hotArticleTitleList' => $hotArticleTitle,
        ];
    }
    //获取文章详情
    public static function getArticleDetail($params = [])
    {
        extract($params);
        $articleInfo = self::field('id,title,content,virtual_read_nums,create_time')->find($article_id);
        if (empty($articleInfo)) {
            new ExceptionStd('文章不存在');
        }
        $articleInfo = $articleInfo->toArray();
        $articleInfo['applyInfo'] = CourseModel::courseDetail($channel, 0, $app_version);
        return $articleInfo;
    }
    //案例详情
    public static function getCaseArticleDetail()
    {
        $articleInfo = ArticleNews::where('app_class_id',32)->field('id,title,content')->find();

        $detail = [];
        if ($articleInfo) {
            $detail = $articleInfo->toArray();
            $content = str_replace("&quot;", "", $detail['content']);
            $content = html_entity_decode($content);
            $detail['content'] = getEditContentText(richText($content));
            $detail['content_desc'] = richText($content);
        }
        return $detail;
    }
}

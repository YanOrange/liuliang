<?php

namespace app\model\api\overdue;

use app\lib\api\exception\Exception;
use app\model\api\Channel;
use laytp\BaseModel;
use think\facade\Config;
use app\model\api\ArticleNews;
use app\lib\api\exception\ExceptionStd;
use app\model\api\Article as ArticleModel;
use app\model\api\vestbag\LawyerCollectorList;
use app\model\api\vestbag\Course as CourseModel;

class OverdueTeam extends BaseModel
{
    protected $name = 'overdue_team';
    protected $append = [
        'btn_desc'
    ];
    public function getBtnDescAttr($value, $data)
    {
        return '了解详情';
    }
    public function getLabelAttr($value, $data)
    {
        return isset($data['label']) && !empty($data['label']) ? json_decode($data['label'], true) : [];
    }

    public function getAuthLabelAttr($value, $data)
    {
        return isset($data['auth_label']) && !empty($data['auth_label']) ? json_decode($data['auth_label'], true) : [];
    }

    //首页更多
    public static function getShowMoreList($params = [])
    {
        extract($params);
        $bannerList = ['http://cdnwm.yuluojishu.com/uploads/20230324/8ded43b8d6c915f586c719a421c811c0.png'];
        $overdueTeamList = self::field('id,nickname,avator,work_year')->order('id','desc')->limit(3)->select();
        $serviceList = [
            ['image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/25aa170f9268ad4c856278f517dc0061.png', 'title' => '债务协商','sub_title' => '解决债务问题'],
            ['image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/9bc413e21b1966d6ce68b3959bef0588.png', 'title' => '延长分期','sub_title' => '解决用户还款分期压力'],
            ['image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/032c1d8808f7ab5fc8638c90ec5694f6.png', 'title' => '停息挂账','sub_title' => '停止罚息'],
            ['image' => 'http://cdnwm.yuluojishu.com/uploads/20230424/73429c1c1de02b31e447d7c4ea892528.png', 'title' => '频繁催收骚扰','sub_title' => '帮助用户抵挡催收电话'],
        ];
        return [
            'bannerList' => $bannerList,
            'overdueTeamList' => $overdueTeamList,
            'serviceList' => $serviceList,
            'applyInfo' => CourseModel::courseDetail($channel, 0, $app_version),

        ];
    }
    //获取律师详情
    public static function getLawyerDetailV2($params = [])
    {
        extract($params);
        $lawyerInfo = self::field('id,nickname,avator,appointment,introduce,work_year,label,star,order_num,auth_label,article_ids')->find($lawyer_id);
        if (empty($lawyerInfo)) {
            new ExceptionStd('记录不存在');
        }
        $lawyerInfo = $lawyerInfo->toArray();
//        $ratioData = [
//            ['specialty_score' => '91%', 'attitude_score' => '86%', 'velocity_score' => '82%'],
//            ['specialty_score' => '86%', 'attitude_score' => '80%', 'velocity_score' => '76%'],
//            ['specialty_score' => '83%', 'attitude_score' => '84%', 'velocity_score' => '73%'],
//            ['specialty_score' => '81%', 'attitude_score' => '89%', 'velocity_score' => '70%'],
//        ];

//        $lawyerInfo['specialty_score'] = $ratioData[$lawyer_id - 2]['specialty_score'];
//        $lawyerInfo['attitude_score'] = $ratioData[$lawyer_id - 2]['attitude_score'];
//        $lawyerInfo['velocity_score'] = $ratioData[$lawyer_id - 2]['velocity_score'];
        $articleId = !empty($lawyerInfo['article_ids']) ?  explode(',',$lawyerInfo['article_ids'])[0] : 0;
        $content = ArticleNews::where('id', $articleId)->value('content');
        $lawyerInfo['service_content'] = !empty($content) ? richText($content) : '';
        $checkSc = LawyerCollectorList::where('uid', $GLOBALS['uid'])->where('lawyer_id', $lawyer_id)->count();
        $lawyerInfo['is_collector'] = $checkSc ? 1 : 0;
        $lawyerInfo['applyInfo'] = CourseModel::courseDetail($channel, 0, $app_version);

        return $lawyerInfo;
    }
    public static function getOverdueTeamListV2($params = [])
    {
        $classList = [['id' => 1, 'className' => '全部'],['id' => 2, 'className' => '停息业务'],['id' => 3, 'className' => '逾期业务'],['id' => 4, 'className' => '催收解决']];
        return [
            'classList' => $classList,
            'OverdueTeamList' => self::getOverdueTeamList(),
        ];
    }
    public static function getOverdueTeamList()
    {
        $data = self::where('status',1)
            ->where('type',1)
            ->field('id,nickname,avator,appointment,introduce,work_year,label,order_num,help_num')
            ->order('id desc')
            ->limit(5)
            ->select();
        return $data;
    }

    public static function getOverdueTeamDetail($params = [])
    {
        extract($params);
        $data = self::where('status',1)
            ->where('id',$team_id)
            ->field('id,nickname,avator,appointment,introduce,article_ids')
            ->find();
        if(!empty($data['article_ids'])){
            $articleIds = explode(',',$data['article_ids']);
            $articleList = ArticleNews::whereIn('id',$articleIds)
                ->field('id,title,content')
                ->select()->toArray();
            foreach ($articleList as &$val) {
                $content = str_replace("&quot;", "", $val['content']);
                $content = html_entity_decode($content);
                $val['content'] = getEditContentText(richText($content));
                $val['content_desc'] = richText($content);
            }
            $data['articleList'] = $articleList;
        }
        return $data;
    }
}
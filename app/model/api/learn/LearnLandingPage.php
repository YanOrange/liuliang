<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnLandingPage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_landing_page';

    protected $hidden = [
        'open_course_json'
    ];

    public static function getGuidePage($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $guidePage = self::where('landing_page_type',3)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('guide_page',1)
            ->field('id,guide_page_video,guide_page_language,guide_page_btn_desc,open_course_json')
            ->order('id desc')
            ->find();
        if(!empty($guidePage)){
            $data = [];
            $guidePage['course_id'] = 0;
            $openCourseArr = !empty($guidePage->open_course_json) ? json_decode($guidePage->open_course_json,true) : [];
            if(!empty($openCourseArr)){
                foreach($openCourseArr as $item){
                    $data[] = [
                        'id' => $item['course_id'],
                        'weight' => $item['expose_num']
                    ];
                }
                $guidePage['course_id'] = (new WeightService())->initData($data);
            }
        }
        return $guidePage;
    }
}

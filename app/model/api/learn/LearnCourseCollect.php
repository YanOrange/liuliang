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
class LearnCourseCollect extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_course_collect';

    public static function collectCourseList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $collectCourseList = self::with(['course' => function($query){
                $query->field('id,title,entry_fee,course_type,desc_image');
            }])
            ->where('uid',$GLOBALS['uid'])
            ->field('id,course_id')
            ->paginate(10)
            ->toArray();
        if(!empty($collectCourseList['data'])){
            foreach($collectCourseList['data'] as &$item){
                $item['credit'] = rand(1,5);
            }
        }
        return $collectCourseList;
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourse','course_id','id')->removeOption('soft_delete');
    }

}

<?php

namespace app\model\api\single;

use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MyCourse extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'thread';

    protected $hidden = [
        'course',
    ];

    public static function getCourseList($params = [])
    {
        extract($params);
        $limit_num = 10;
        $channelInfo = Channel::getChannelAppClass($channel);
        $where[] = ['uid','=',$GLOBALS['uid']];
        $threadList = self::with(['course' => function($query){
                $query->field('id,title,video_cover_image');
            },
            'merchant' => function($query){
                $query->field('id,is_jump_miniprogram');
            }])
            ->where($where)
            ->where('course_id','>',0)
            ->where('app_id',$channelInfo['app_id'])
            ->field('id,course_id,merchant_id')
            ->order('id desc')
            ->paginate($limit_num)
            ->toArray();
        $data = isset($threadList['data']) && !empty($threadList['data']) ? $threadList['data'] : [];
        if(!empty($data)){
            foreach($data as $key=>$val){
                if(empty($val['title']) && empty($val['video_cover_image'])){
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\single\SingleCourse','course_id','id')
            ->bind(['title','video_cover_image'])
            ->removeOption('soft_delete');
    }
    public function merchant()
    {
        return $this->belongsTo('app\model\api\single\Merchant','merchant_id','id')
            ->bind(['is_jump_miniprogram'])
            ->removeOption('soft_delete');
    }
}
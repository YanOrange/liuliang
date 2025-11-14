<?php

namespace app\model\api\fortunecat;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use app\model\api\fortunecat\Course;
use think\facade\Db;
use think\model\concern\SoftDelete;
use app\model\api\fortunecat\PartCourseTag;

class Collect extends BaseModel
{
    protected $name = 'collect';

    public static function getCollectList($params = [])
    {
        extract($params);
        $courseType = isset($course_type) && !empty($course_type) ? $course_type : 1;
        $limit_num = 10;
        $collectList = self::with(['course' => function($query){
                $query->field('id,merchant_id,title,video_cover_image,virtual_apply_nums,entry_fee,compensation,tag_ids,btn_desc,compensation_type');
                $query->with(['merchant' => function($query){
                    $query->field('id,company_name');
                }]);
            }])
            ->where('uid',$GLOBALS['uid'])
            ->where('course_type',$courseType)
            ->order('id desc')
            ->field('id,course_id')
            ->paginate($limit_num)
            ->toArray();
        $collectList = isset($collectList['data']) && !empty($collectList['data']) ? $collectList['data'] : [];
        return $collectList;
    }

    public static function collectCourse($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $isCollect = isset($is_collect) && !empty($is_collect) ? $is_collect : 0;
        $collect = self::where('uid',$GLOBALS['uid'])->where('course_id',$course_id)->find();
        if ($course_id) {
            Db::startTrans();
            try {
                $courseInfo = Course::field('id,collect_nums,course_type')->find($course_id);
                if($isCollect == 1){
                    $courseInfo->collect_nums = -1;
                    $courseInfo->where('collect_nums','>',0)->save();
                    self::where('uid',$GLOBALS['uid'])->where('course_id',$course_id)->delete();
                }else{
                    $courseInfo->collect_nums = +1;
                    $courseInfo->save();
                    if(!$collect){
                        self::create([
                            'uid' => $GLOBALS['uid'],
                            'course_id' => $course_id,
                            'course_type' => $courseInfo->course_type
                        ]);
                    }else{
                        new Exception('已收藏');
                    }
                }
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                new Exception('收藏失败');
            }
        }
        return $isCollect;
    }

    public function getCompensationAttr($value, $data)
    {
        switch($data['compensation_type']){
            case 1;
                $unit = '日';
                break;
            case 2;
                $unit = '次';
                break;
            case 3;
                $unit = '个';
                break;
            case 4;
                $unit = '张';
                break;
            case 5;
                $unit = '小时';
                break;
            case 6;
                $unit = '其他';
                break;
            default:
                $unit = '日';
        }
        return !empty($value) ? '￥'.$value.'元/'.$unit : '';
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\fortunecat\Course','course_id','id')->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\fortunecat\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
}
<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\LearnCourseSection;

class LearnFeedback extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'learn_feedback';

    public function user()
    {
        return $this->belongsTo('app\model\api\UserList','uid','id')->removeOption('soft_delete');
    }

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel', 'channel_id','id')->removeOption('soft_delete');
    }

}

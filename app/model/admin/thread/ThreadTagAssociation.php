<?php
/**
 * 线索标签关联表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadTagAssociation extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_tag_association';

    public function tagExternal()
    {
        return $this->belongsTo('app\model\admin\thread\ThreadTagExternal', 'tag_id', 'id')->removeOption('soft_delete');
    }
}

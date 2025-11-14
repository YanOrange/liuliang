<?php
/**
 * 线索标签类型表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadTagCategoryExternal extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_tag_category_external';


}

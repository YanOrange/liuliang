<?php
/**
 * 试听课程表模型
 */

namespace app\model\admin;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ListeningCourse extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'listening_course';

    protected static function getStatusText($status)
    {
        $statusArr = [
            0 => '禁用',
            1 => '启用',
        ];
        return $statusArr[$status];
    }

    

}

<?php
/**
 * 线索跟进动作选项模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadFollowAction extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'thread_follow_action';

    protected static function getStatusText($status) {
        $statusArr = [
            0 => '禁用',
            1 => '启用'
        ];
        return $statusArr[$status];
    }


}

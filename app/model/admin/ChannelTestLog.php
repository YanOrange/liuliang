<?php
/**
 * 后台渠道表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\facade\Config;
use think\model\concern\SoftDelete;

class ChannelTestLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_test_log';

    public function app()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }

    public function createUser()
    {
        return $this->belongsTo('app\model\admin\User','create_user_id','id')->removeOption('soft_delete');
    }

    public function updateUser()
    {
        return $this->belongsTo('\app\model\admin\User', 'update_user_id')->removeOption('soft_delete');
    }

    public function deleteUser()
    {
        return $this->belongsTo('\app\model\admin\User', 'delete_user_id')->removeOption('soft_delete');
    }

}

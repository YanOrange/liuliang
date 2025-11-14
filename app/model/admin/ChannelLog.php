<?php
/**
 * 后台渠道表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\facade\Config;
use think\model\concern\SoftDelete;

class ChannelLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_log';

    protected $append=['type_str'];

    public $type = [
        1 => '修改APP名称',
        2 => '修改分类',
        3 => '一句话',
        4 => '标签',
        5 => 'LOGO文案',
        6 => 'LOGO图',
        7 => '截图',
        8 => '氛围图文案',
        9 => '氛围图',
        10 => '备注',
        11 => '副标题'
    ];

    public function getTypeStrAttr($value, $data){
        $typeStr = '';
        if (!empty($data['operation_type'])) {
            $typeStr = $this->type[$data['operation_type']];
        }
        return $typeStr;
    }

    public function channel()
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

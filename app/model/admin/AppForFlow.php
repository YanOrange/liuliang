<?php
/**
 * 后台app信息流表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppForFlow extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'app_for_flow';

    protected $append = [
        'app_names',
        'register_nums',
    ];


    public function getRegisterNumsAttr($value, $data)
    {
        $registerNums = 0;
        if (isset($data['id']) && !empty($data['id'])) {
            $registerNums = Thread::where('app_flow_id', $data['id'])->count();
        }
        return $registerNums;
    }
    public function getAppNamesAttr($value, $data)
    {
        if (isset($data['app_ids']) && !empty($data['app_ids'])) {
            $appIdsArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appIdsArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }
    public function user()
    {
        return $this->hasMany('app\model\admin\UserList','app_flow_id','id')->removeOption('soft_delete');
    }

}

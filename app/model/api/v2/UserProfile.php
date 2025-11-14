<?php
/**
 * 用户资料表模型
 */

namespace app\model\api\v2;


use laytp\BaseModel;
use think\facade\Db;

class UserProfile extends BaseModel
{
    //模型名
    protected $name = 'user_profile';

    //获取表字段名
    public static function getColumnList()
    {
        $userColUMNS = Db::query("show COLUMNS FROM lt_user_profile");
        $field = [];
        foreach($userColUMNS as $column){
            if ($column['Field'] !== 'id' && $column['Field'] !== 'uid') {
                $field[] = $column['Field'];
            }
        }
        return $field;
    }
}

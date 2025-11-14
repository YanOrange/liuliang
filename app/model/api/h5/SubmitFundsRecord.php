<?php
/**
 * 用户资料表模型
 */

namespace app\model\api\h5;


use laytp\BaseModel;
use think\facade\Db;

class SubmitFundsRecord extends BaseModel
{
    //模型名
    protected $name = 'submit_funds_record';

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

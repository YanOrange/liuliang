<?php
/**
 * 报名缴费表模型
 */

namespace app\model\admin;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ApplyPayfee extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'apply_payfee';


    protected static function getTypeText($type)
    {
        $typeArr = [
            1 => '全款',
            2 => '定金',
            3 => '尾款',
        ];
        return $typeArr[$type];
    }


}

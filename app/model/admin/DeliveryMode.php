<?php
/**
 * 后台投放模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use laytp\library\Tree;

class DeliveryMode extends BaseModel
{
    use SoftDelete;

    protected $name = 'delivery_mode';

    //获取所有父级id
    public static function getAllParentData($id = 0)
    {
        if ($id > 0 && !empty($id))
        {
            $deliveryModeData = self::select()->toArray();
            $menuTreeObj = Tree::instance();
            $menuTreeObj->init($deliveryModeData);
            $data = $menuTreeObj->getParentIds($id);
            return $data;
        }
    }
}
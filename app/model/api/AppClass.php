<?php
/**
 * 应用分类表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Merchant;
use app\model\api\Thread;

class AppClass extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_class';

    //类目列表
    public static function getAppClassList($params = [])
    {
        extract($params);
        $appClassList = self::field('id,app_class_name')->where('app_class_name', '<>', '综合')->select()->toArray();
        $appClassList = array_column($appClassList, NULL, 'id');
        $redis = get_redis();
        $merchantId = $redis->get(env('redis.user_landing_page_redis_key') . $GLOBALS['uid']);
        if ($merchantId) {
            $appClassId = Merchant::where('id', $merchantId)->value('app_class_id');
            if (isset($appClassList[$appClassId])) {
                $newAppClassId = $appClassList[$appClassId];
                unset($appClassList[$appClassId]);
                array_unshift($appClassList, $newAppClassId);
            }
        }
        $applyAppClassId = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->value('app_class_id');
        if ($applyAppClassId) {
            if (isset($appClassList[$applyAppClassId])) {
                $newApplyAppClassId = $appClassList[$applyAppClassId];
                unset($appClassList[$applyAppClassId]);
                array_unshift($appClassList, $newApplyAppClassId);
            }
        }
        $appClassList = array_values($appClassList);
        return $appClassList;
    }
}

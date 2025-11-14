<?php
/**
 * 账户表模型
 */

namespace app\model\api\vestbag;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;

class VestUserAccount extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vest_user_account';

    //添加账户
    public static  function addAccount($param = [])
    {
        extract($param);
        $ret = self::create([
            'icon_id' => $icon_id,
            'account_name' => $account_name,
            'account_remaining' => $account_remaining,
            'uid' => $GLOBALS['uid'],
        ]);
        if (!$ret) {
            new ExceptionStd('添加账户失败');
        }
    }
    //账户列表
    public static  function getUserAccountList($param = [])
    {
        extract($param);
        return self::field('id,account_name,account_remaining,icon_id')->with(['icon'])->where('uid', $GLOBALS['uid'])->select()->toArray();
    }

    public function icon()
    {
        return $this->belongsTo('app\model\api\vestbag\VestAccountIcon','icon_id','id')->field('id,icon_name,icon_bright_url,icon_dark_url')->removeOption('soft_delete');
    }

}

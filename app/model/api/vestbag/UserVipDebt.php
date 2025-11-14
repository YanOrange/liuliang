<?php


namespace app\model\api\vestbag;
use app\lib\api\exception\Exception;
use app\model\api\UserList;
use app\model\api\vestbag\Course as CourseModel;
use laytp\BaseModel;
use app\lib\api\exception\ExceptionStd;
use app\model\api\Channel;
use think\model\concern\SoftDelete;

class UserVipDebt extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'user_vip_debt';

    //获取用户会员状态
    public static function getUserVipStatus($params = [])
    {
        extract($params);
        $status = self::where('uid', $GLOBALS['uid'])->count();
        $nickname = UserList::where('id', $GLOBALS['uid'])->value('nickname');
        return [
            'status' =>  $status ? 1  : 0,
            'applyInfo' =>  CourseModel::courseDetail($channel, 0, $app_version),
            'nickname' => $nickname
        ];
    }
    //领取会员
    public static function getUserVip($params)
    {
        extract($params);
        $status = self::where('uid', $GLOBALS['uid'])->count();
        if ($status) {
            new ExceptionStd('你已经领取过了');
        }
        $ret = self::create(array_merge($params, [
            'uid' => $GLOBALS['uid'],
        ]));
        $user = UserList::where('id', $GLOBALS['uid'])->find();
        if (!empty($user) && (isset($nickname) && !empty($nickname))) {
            $user->nickname = $nickname;
            $user->save();
        }
        if (!$ret) {
            new ExceptionStd('领取失败');
        }
      return self::getUserVipStatus(['channel' => $channel, 'app_version' => $app_version]);
    }

}

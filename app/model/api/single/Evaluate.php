<?php

namespace app\model\api\single;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\UserList;
class Evaluate  extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'evaluate';

    public static function addEvaluate($params = [])
    {
        extract($params);
        if(!trim($content)){
            new Exception('评价内容无效');
        }
        $userInfo = UserList::where('id',$GLOBALS['uid'])->field('phone,nickname,avatar')->find();
        self::create([
            'be_evaluated_type' => $be_evaluated_type,
            'be_evaluated_id' => $be_evaluated_id,
            'phone' => $userInfo->phone,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
            'score' => $score ?? 0,
            'content' => $content ?? ''
        ]);
        return;
    }

}
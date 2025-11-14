<?php

namespace app\model\admin;

use app\model\api\Course;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AdminActionLog extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'admin_action_log';

    public static function insertLog($params = [])
    {
        self::create([
            'admin_id'       => $params['admin_id'],
            'rule'           => request()->url(),
            'menu'           => $params['menu'],
            'request_body'   => json_encode($params['post'], JSON_UNESCAPED_UNICODE),
            'request_header' => json_encode(request()->header(), JSON_UNESCAPED_UNICODE),
            'ip'             => request()->ip(),
            'status_code'    => 200,
            //'response_body'  => json_encode($params, JSON_UNESCAPED_UNICODE),
            //'create_time'    => date('Y-m-d H:i:s'),
        ]);
    }
}
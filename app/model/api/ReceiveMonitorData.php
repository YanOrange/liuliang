<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class ReceiveMonitorData extends BaseModel
{
    //表名
    protected $name = 'receive_monitor_data';


}
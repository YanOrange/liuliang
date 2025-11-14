<?php


namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class ChannelStatus extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_status';


}

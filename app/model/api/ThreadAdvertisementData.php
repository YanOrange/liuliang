<?php
namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadAdvertisementData extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_advertisement_data';
}

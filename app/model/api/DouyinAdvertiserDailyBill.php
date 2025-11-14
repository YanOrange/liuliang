<?php

namespace app\model\api;

use app\model\api\Course;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class DouyinAdvertiserDailyBill extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'douyin_advertiser_daily_bill';

}
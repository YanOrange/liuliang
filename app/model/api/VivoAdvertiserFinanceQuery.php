<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class VivoAdvertiserFinanceQuery extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vivo_advertiser_finance_query';
}
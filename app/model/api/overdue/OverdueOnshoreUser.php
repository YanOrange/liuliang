<?php

namespace app\model\api\overdue;

use app\lib\api\service\MerchantServiceOverdue;
use app\model\api\Channel;
use app\model\api\UserList;
use laytp\BaseModel;
use think\facade\Config;
use app\model\api\fortunecat\Banner;
use app\model\api\ArticleNews;
use app\model\api\Thread;
class OverdueOnshoreUser extends BaseModel
{
    protected $name = 'overdue_onshore_user';

    public function getDisembarkTimeAttr($value,$data)
    {
        return date('Y-m-d');
    }

    public function getPhoneAttr($value, $data)
    {
        return $data['phone'] ? substr_replace($data['phone'], '****', 3, 4) : '';;
    }

    public function getProcessAmountAttr($value, $data)
    {
        $startNum = rand(1,20);
        return $startNum.'000';
    }

}
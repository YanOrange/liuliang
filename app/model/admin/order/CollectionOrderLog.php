<?php

namespace app\model\admin\order;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class CollectionOrderLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'collection_order_log';

}
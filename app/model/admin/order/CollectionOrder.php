<?php

namespace app\model\admin\order;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class CollectionOrder extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'collection_order';





    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->removeOption('soft_delete');
    }

    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
    }
}
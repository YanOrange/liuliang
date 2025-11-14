<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class TransactionList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'transaction_list';

}
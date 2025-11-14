<?php

namespace app\model\api\caituokun;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Cashbook extends BaseModel
{
//    use SoftDelete;
    //模型表名
    protected $name = 'cashbook';

    protected $field = [
        'note_time',
        'year',
        'month',
        'day',
        'week',
        'date',
        'amount',
        'source_type',
        'type',
        'tab',
        'notes',
        'user_id'
    ];

}
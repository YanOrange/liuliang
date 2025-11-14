<?php

namespace app\model\api\caituokun;

use laytp\BaseModel;

class CheckInRecord  extends BaseModel
{
    protected $name = 'check_in_record';

    protected $field = [
        'date', 'num', 'user_id', 'sgin_time', 'score'
    ];
}
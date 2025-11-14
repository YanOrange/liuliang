<?php

namespace app\model\api\caituokun;

use laytp\BaseModel;

class CashbookSignIn extends BaseModel
{
    protected $name = 'cashbook_sign_in';

    protected $field = [
        'date', 'num', 'user_id', 'sgin_time', 'score'
    ];
}
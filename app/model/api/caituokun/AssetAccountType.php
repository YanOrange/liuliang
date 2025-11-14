<?php

namespace app\model\api\caituokun;

use laytp\BaseModel;

class AssetAccountType  extends BaseModel
{
    protected $name = 'asset_account_type';

    protected $field = [
        'type', 'name', 'img'
    ];
}
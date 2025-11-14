<?php

namespace app\model\api\caituokun;

use laytp\BaseModel;

class AssetAnalysis extends BaseModel
{
    protected $name = 'asset_analysis';

    protected $field = [
        'type', 'category', 'name','amount','notes','card_no','virtual_account_type','investment_account_type','user_id'
    ];
}
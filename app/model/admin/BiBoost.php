<?php
/**
 * BI商户补量记录登记
 * @date 2022-11-04
 * @author chenlele
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class BiBoost extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'bi_merchant_boost';

}
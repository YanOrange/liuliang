<?php
/**
 * 后台课程表模型
 */

namespace app\model\admin\single;

use app\model\admin\App;
use app\model\admin\Merchant;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppMerchantMessage extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'app_merchant_message';


}

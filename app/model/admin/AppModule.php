<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppModule extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_module';
}
<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class SourceChannel extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'source_channel';
}
<?php
/**
 * 轮播图表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class GdtMarketingClue extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'gdt_marketing_clue';

}

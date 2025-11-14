<?php
/**
 * 轮播图表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class DouyinMarketingClue extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'douyin_marketing_clue';

}

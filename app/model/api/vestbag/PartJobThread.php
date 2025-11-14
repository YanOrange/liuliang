<?php
/**
 * 兼职报名线索
 */

namespace app\model\api\vestbag;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class PartJobThread extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'part_job_thread';


}

<?php
/**
 * 后台线索标签类别模型
 */

namespace app\model\admin\thread;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ThreadTagCategory extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'thread_tag_category';

}

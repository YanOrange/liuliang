<?php
/**
 * 后台落地页表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class LandingPage extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'landing_page';
    protected $append = [
        'a_landing_images_t',
        'b_landing_images_t',
    ];

    public function course()
    {
        return $this->belongsTo('app\model\admin\Course','course_id','id')->removeOption('soft_delete');
    }
    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->removeOption('soft_delete');
    }

    public function getALandingImagesTAttr($value, $data)
    {
        return isset($data['a_landing_images']) && !empty($data['a_landing_images']) ? implode(', ', explode(',', $data['a_landing_images'])) : '';
    }
    public function getBLandingImagesTAttr($value, $data)
    {
        return isset($data['b_landing_images']) && !empty($data['b_landing_images']) ? implode(', ', explode(',', $data['b_landing_images'])) : '';
    }
}

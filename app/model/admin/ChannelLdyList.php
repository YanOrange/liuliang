<?php
/**
 * 后台渠道落地页表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ChannelLdyList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel_ldy_list';

    public function channel()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->field('id,channel_name')->removeOption('soft_delete');
    }
    public function getLdylzBtnColorAttr($value, $data)
    {
        return isset($data['ldylz_btn_color']) ? (strpos($data['ldylz_btn_color'], '#') === false ? '#' . $data['ldylz_btn_color'] : $data['ldylz_btn_color'] ) : '';
    }
    public function getLdylzOptionColorAttr($value, $data)
    {
        return isset($data['ldylz_option_color']) ? (strpos($data['ldylz_option_color'], '#') === false ? '#' . $data['ldylz_option_color'] : $data['ldylz_option_color'] ) : '';
    }

}

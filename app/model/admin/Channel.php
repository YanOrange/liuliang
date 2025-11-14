<?php
/**
 * 后台渠道表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Channel extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'channel';

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name,app_class_id')->removeOption('soft_delete');
    }

    public function adminUser()
    {
        return $this->belongsTo('\app\model\admin\User', 'admin_user_id')->removeOption('soft_delete');
    }

    public function method()
    {
        return $this->belongsTo('\app\model\admin\PromotionMethod', 'promotion_id')->field('id,name')->removeOption('soft_delete');
    }

    public function platform()
    {
        return $this->belongsTo('\app\model\admin\PromotionPlatform', 'platform_id')->field('id,name')->removeOption('soft_delete');
    }
    
    public function channelStatus()
    {
        return $this->belongsTo('app\model\admin\ChannelStatus','channel_id','id')->field('id,is_delivery,is_putaway,is_open,is_put')->removeOption('soft_delete');
    }

    // 投放方式
    public function channelPromotion()
    {
        return $this->belongsTo('app\model\admin\PromotionMethod','promotion_id','id')->field('id,name')->removeOption('soft_delete');
    }

    // 投放平台
    public function channelPlatform()
    {
        return $this->belongsTo('app\model\admin\PromotionPlatform','platform_id','id')->field('id,name')->removeOption('soft_delete');
    }

    // 投放模式
    public function channelDeliveryMode()
    {
        return $this->belongsTo('app\model\admin\DeliveryMode','delivery_mode_id','id')->field('id,name,pid')->removeOption('soft_delete');
    }
}

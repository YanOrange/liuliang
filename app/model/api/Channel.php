<?php
/**
 * 渠道表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class Channel extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'channel';

    //留资页位置
    const CAPITAL_PAGE_POSITION_LOGIN = 1; //用户登陆后
    const CAPITAL_PAGE_POSITION_APPLY = 2; //用户报名

    public static function getChannelAppClass($channel = null, $isShow = 1)
    {
        if (!empty($channel)) {
            $channel = self::with(['app'])->where('channel_name', $channel)->find();
        }
        if($isShow == 0)
        {
            $freeLandingPageAffirm = isset($channel->free_landing_page_affirm) ? $channel->free_landing_page_affirm : 1;
            $payLandingPageAffirm = isset($channel->pay_landing_page_affirm) ? $channel->pay_landing_page_affirm : 1;
        } else {
            $freeLandingPageAffirm = isset($channel->course_free_landing_page_affirm) ? $channel->course_free_landing_page_affirm : 1;
            $payLandingPageAffirm = isset($channel->course_pay_landing_page_affirm) ? $channel->course_pay_landing_page_affirm : 1;
        }
        return [
            'channel_name' => isset($channel->channel_name) ? $channel->channel_name : '',
            'channel_id' => isset($channel->id) ? $channel->id : 0,
            'mc_h5_url' => isset($channel->mc_h5_url) ? $channel->mc_h5_url : '',
            'app_id' => isset($channel->app->id) ? $channel->app->id : 0,
            'app_class_id' => isset($channel->app->app_class_id) ? $channel->app->app_class_id : 0,
            'is_many_organization' => isset($channel->app->is_many_organization) ? $channel->app->is_many_organization : 1,
            'cost_price' => isset($channel->cost_price) && $channel->cost_price > 0 ? $channel->cost_price : 0,
            'source_id' => isset($channel->source_id) && $channel->source_id > 0 ? $channel->source_id : 1,
            'store' => isset($channel->store) && $channel->store > 0 ? $channel->store : '',
            'capital_page_position' => isset($channel->capital_page_position) ? $channel->capital_page_position : self::CAPITAL_PAGE_POSITION_LOGIN,
            'free_landing_page_affirm' => $freeLandingPageAffirm,
            'pay_landing_page_affirm' => $payLandingPageAffirm,
            'is_landing_page' =>  isset($channel->is_landing_page) ? $channel->is_landing_page : 1,
            'is_speed_feed' => isset($channel->is_speed_feed) ? $channel->is_speed_feed : 0,
            'is_slow' => isset($channel->is_slow) ? $channel->is_slow : 0,
            'cost_price' => isset($channel->cost_price) && $channel->cost_price > 0 ? $channel->cost_price : 0,
            'is_under_eighteen_apply' => isset($channel->is_under_eighteen_apply) ? $channel->is_under_eighteen_apply : 1,
            'wx_btn_desc' => isset($channel->wx_btn_desc) ? $channel->wx_btn_desc : 1,
            'is_more_apply' => isset($channel->is_more_apply) ? $channel->is_more_apply : 0,
            //'pay_landing_page_affirm' => isset($channel->pay_landing_page_affirm) ? $channel->pay_landing_page_affirm : 1,
            //'pay_landing_page_affirm' => isset($channel->pay_landing_page_affirm) ? $channel->pay_landing_page_affirm : 1,
        ];
    }
    public function app()
    {
        return $this->belongsTo('app\model\api\App','app_id','id')->removeOption('soft_delete');
    }

    public function frontPage()
    {
        return $this->belongsTo('app\model\admin\FalseFrontPage','front_page_id','id')->removeOption('soft_delete');
    }

    /**
     * 供 逾期类目
     * @param null $channel
     * @return array
     * @date 2022-09-21
     * @author chenlele
     */
    public static function getChannelAppClassOverdue($channel = null)
    {
        if (!$channel) return [];

        $channel = self::with(['app'])->where('channel_name', $channel)->find();
        return [
            'channel_id' => isset($channel->id) ? $channel->id : 0,
            'app_id' => isset($channel->app->id) ? $channel->app->id : 0,
            'app_class_id' => isset($channel->app->app_class_id) ? $channel->app->app_class_id : 0,
            'is_many_organization' => isset($channel->app->is_many_organization) ? $channel->app->is_many_organization : 1,
            'cost_price' => isset($channel->cost_price) && $channel->cost_price > 0 ? $channel->cost_price : 0,
            'source_id' => isset($channel->source_id) && $channel->source_id > 0 ? $channel->source_id : 1,
            'overdue_version_home_desc' => isset($channel->overdue_version_home_desc) ? $channel->overdue_version_home_desc : '',
            'overdue_version_home_image' => isset($channel->overdue_version_home_image) ? $channel->overdue_version_home_image : '',
        ];
    }
}

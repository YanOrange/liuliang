<?php
/**
 * 后台应用表模型
 */

namespace app\model\api\v2;

use app\lib\api\city\IpCity;
use app\model\admin\FalseFrontPage;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
use app\model\api\Channel;
use app\model\api\WxminiPath;
use app\lib\api\other\UserCity;
use app\model\api\AppClass;
use app\model\api\Merchant;


class App extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'app';

    //获取初始化配置文件
    public static function getInitConfig($params = [])
    {
        extract($params);
        $initConfig = self::where('android_bundle_id|ios_bundle_id', '=', $app_bundle_id)->field('alipay_app_id,wxpay_app_id,wxmini_original_id,yd_business_id,app_class_id')->find();
        //$wxminiOriginalIds = WxminiProgram::where('status',1)->column('wxmini_original_id');
        $channelInfo = Channel::where('channel_name',$channel)->field('id,startup_page_image,app_home_desc,is_login_show,is_show_nickname,show_course_title,show_article_title,front_page_id,is_jump_online_service,is_part_job_menu_show,is_cultivate_menu_show,app_login_btn_desc,app_login_btn_color,part_version,wx_btn_desc,retention_page_desc,user_material_btn_desc,is_show_personal_statement')->find();
        if (empty($initConfig) || empty($channelInfo)) {
            new ExceptionStd('初始化配置文件失败');
        }
        $wxminiPathInfo = WxminiPath::getWxminiPath($channel);
        $frontPagePath = FalseFrontPage::where('id',$channelInfo['front_page_id'])->value('page_path');
        $initConfig = $initConfig->toArray();
        $wxminiPath = '';
        if($channel == 'xchmh_oppo') $wxminiPath = '/pages/Game/Game';
        if($channel == 'quxuepr_vivo') $wxminiPath = 'pages/TikTok/TikTok';
        $wxAuthDesc = AppClass::where('id', $initConfig['app_class_id'])->value('wx_auth_desc');
        $appClassId = $initConfig['app_class_id'];
        $serviceLink = $appClassId == 9 ? env('SERVICE.H5_LINK_OVERDUE') : env('SERVICE.H5_LINK');
        $merchantStatus = Merchant::where('id', 237)->value('is_switch');
        $overdue_share_data = [
            'share_title' => '快速处理信用卡逾期问题',
            'shart_link' => 'http://yqshare.yuluojishu.com/#/',
        ];
        /* if (in_array($channel, ['qkjfcl_oppo','nnzwyh_oppo','yqzwgj_oppo','gdqkjf_oppo','zwczsa_oppo', 'xykyqsa_oppo'])) {
             $serviceLink = 'http://betachat.yuluojishu.com/php/app.php?widget-mobile&';
         }
         if ($channel == 'yqzwgj_oppo') {
             $serviceLink = 'http://betachatweb.yuluojishu.com//kefu/5c6cbcb7d55ca/651313989efe6';
         }*/
        $initConfig = array_merge(
            $initConfig,
            [
                'startup_page_image' => $channelInfo->startup_page_image,
                'app_home_desc' => !empty($channelInfo->app_home_desc) ? json_decode($channelInfo->app_home_desc,true) : [],
                'wxmini_original_id' => $wxminiPathInfo['wxmini_original_id'],
                'yd_business_id' => $initConfig['yd_business_id'],
                'sdk_app_id' => env('tenim.sdk_app_id', ''),
                'is_login_show' => $channelInfo->is_login_show,
                'is_show_nickname' => $channelInfo->is_show_nickname,
                'show_course_title' => $channelInfo->show_course_title,
                'show_article_title' => $channelInfo->show_article_title,
                'wxmini_path' => $wxminiPathInfo['wxmini_path'] == '/pages/Montage/Montage' ? ($merchantStatus == 1 ? '/pages/Montage/Montage' : '/pages/PR2/PR2') : $wxminiPathInfo['wxmini_path'],
                'front_page_path' => $frontPagePath ?? '',
                'is_part_job_menu_show' => isset($app_version) && !empty($app_version) && $app_version == $channelInfo->part_version && $channelInfo->is_part_job_menu_show ? 1 : $channelInfo->is_part_job_menu_show,
                'is_cultivate_menu_show' => isset($app_version) && !empty($app_version) && $app_version == $channelInfo->part_version && $channelInfo->is_cultivate_menu_show ? 1 : $channelInfo->is_cultivate_menu_show,
                'service_link' => $serviceLink,
                'is_jump_online_service' => self::getIsJumpOnlineService($channelInfo, isset($app_version) ? $app_version : '', $channel, $appClassId),
                'is_apply_popout' => 0,
                'is_ios_vestbag' => env('config.isiosvestbag'),
                'app_login_btn_desc' => $channelInfo->app_login_btn_desc ?? '立即登录',
                'app_login_btn_color' => $channelInfo->app_login_btn_color ?? '',
                'retention_page_desc' => !empty($channelInfo->retention_page_desc) ? json_decode($channelInfo->retention_page_desc, true) : ['花七秒钟让我们了解你', '以便帮你推荐合适的教学老师'],
                'user_material_btn_desc' => !empty($channelInfo->user_material_btn_desc) ? $channelInfo->user_material_btn_desc : '开启学习之旅',
                'is_hidden_show' => $channel == 'xsjzdq_huawei' || $channel == 'mhch_vivo' || $channel == 'qxxfy_oppo' ? 1 : 0,
                'wx_btn_desc' => !empty($channelInfo->wx_btn_desc) ? $channelInfo->wx_btn_desc : '授权微信',
                'wx_auth_desc' => !empty($wxAuthDesc) ? $wxAuthDesc : '点我授权,边学边赚钱!',
                'is_show_personal_statement' => $channelInfo->is_show_personal_statement,
                'sdk_url' => input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME') . '/api.user/getSdkAgreementContent?channel='.$channel,
                'is_yq_customer_link_affirm' => $channel == 'xchmh_vivo' ? 0 : 1,
                'overdue_share_data' => $overdue_share_data,
                'refund_instruction' => '退费详细说明（app内付费课程均为0.01元）
1、如支付费用0.01元后，已经观看该课程，费用不予退款。
2、支付费用0.01后，时间小于1天，全额退款，退款流程：我的-客服咨询-留言。留言内容为：支付宝/微信，交易单号，以及联系方式，退款时间：工作日48小时内原路退回。
3、支付费用0.01后，时间超过24小时，原则上不予以退款，详情联系客服（我的-客服咨询），工作日48小时内予以回复。',
            ]
        );
        return ['data' => Aes::aesEncrypt(json_encode($initConfig), env('sign.aeskey'))];
    }

    public static function getInitAppConfig($params = [])
    {
        extract($params);
        $initConfig = self::where('android_bundle_id|ios_bundle_id', '=', $app_bundle_id)->field('alipay_app_id,wxpay_app_id,wxmini_original_id,yd_business_id,app_class_id')->find();
        //$wxminiOriginalIds = WxminiProgram::where('status',1)->column('wxmini_original_id');
        $channelInfo = Channel::where('channel_name', $channel)->field('id,startup_page_image,app_home_desc,is_login_show,is_show_nickname,show_course_title,show_article_title,front_page_id,is_jump_online_service,is_part_job_menu_show,is_cultivate_menu_show,app_login_btn_desc,app_login_btn_color,part_version,wx_btn_desc,retention_page_desc,user_material_btn_desc,is_show_personal_statement')->find();
        if (empty($initConfig) || empty($channelInfo)) {
            new ExceptionStd('初始化配置文件失败');
        }
        $wxminiPathInfo = WxminiPath::getWxminiPath($channel);
        return [
            'startup_page_image' => $channelInfo->startup_page_image,
            'app_home_desc' => !empty($channelInfo->app_home_desc) ? json_decode($channelInfo->app_home_desc,true) : [],
            'wxmini_original_id' => $wxminiPathInfo['wxmini_original_id'],
            'yd_business_id' => $initConfig['yd_business_id'],
            'sdk_app_id' => env('tenim.sdk_app_id', ''),
            'is_login_show' => $channelInfo->is_login_show,
            'is_show_nickname' => $channelInfo->is_show_nickname,
            'show_course_title' => $channelInfo->show_course_title,
            'show_article_title' => $channelInfo->show_article_title,
            'wxmini_path' => $wxminiPathInfo['wxmini_path'] == '/pages/Montage/Montage' ? ($merchantStatus == 1 ? '/pages/Montage/Montage' : '/pages/PR2/PR2') : $wxminiPathInfo['wxmini_path'],
            'front_page_path' => $frontPagePath ?? '',
            'wxmini_app_id'   => $wxminiPathInfo['wxmini_app_id'],
        ];
    }

    public static function getIsJumpOnlineService($channelInfo = null, $app_version = null, $channel = null, $appClassId)
    {
        if ($appClassId == 9) {
            return 1;
        }
        if (UserCity::checkCity($channel)) {
            return 1;
        }
        return  isset($app_version) && !empty($app_version) && $app_version == $channelInfo->part_version && $channelInfo->is_jump_online_service ? 1 : $channelInfo->is_jump_online_service;
    }
    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
}

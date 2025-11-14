<?php
/**
 * 微信小程序页面
 * @date 2022-10-08
 * @author chenlele
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use app\model\api\WxminiProgram;
use app\model\api\AppClass;

class WxminiPath extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'wxmini_path';

    //获取渠道小程序路径
    public static function getWxminiPath($channel = null)
    {
        $wxminiPathId = Channel::where('channel_name', $channel)->value('wxmini_path_ids');
        $channelInfo = Channel::getChannelAppClass($channel);
        $wxminiProgramIds = AppClass::where('id', $channelInfo['app_class_id'])->value('wxmini_program_ids');
        if (empty($wxminiPathId) || empty($wxminiProgramIds)) {
            return self::getChannelEmptywxminiPathId($wxminiProgramIds, $wxminiPathId);
        }
        $wxminiProgramInfo = WxminiProgram::where('status', 1)->where('wxmini_status', 1)->whereIn('id', $wxminiProgramIds)->whereFindInSet('wxmini_path_ids', $wxminiPathId)->order('id desc')->find();
        if (empty($wxminiProgramInfo)) {
            $wxminiProgramInfo = WxminiProgram::where('status', 1)->where('wxmini_status', 1)->whereFindInSet('wxmini_path_ids', $wxminiPathId)->order('id desc')->find();
        }
        return [
            'wxmini_original_id' => isset($wxminiProgramInfo->wxmini_original_id) ? $wxminiProgramInfo->wxmini_original_id : '',
            'wxmini_app_id' => isset($wxminiProgramInfo->wxmini_app_id) ? $wxminiProgramInfo->wxmini_app_id : '',
            'wxmini_private_key' => isset($wxminiProgramInfo->wxmini_private_key) ? $wxminiProgramInfo->wxmini_private_key : '',
            'wxmini_path' => $wxminiPathId ? self::where('id', $wxminiPathId)->value('wxmini_path') : '',
        ];
    }

    //渠道绑定的微信小程序为空或类目绑定的微信小程序为空调用此方法
    public static function getChannelEmptywxminiPathId($wxminiProgramIds = null, $wxminiPathId = null)
    {
        if (empty($wxminiPathId)) {
            if (!empty($wxminiProgramIds)) {
                $wxminiProgramInfo = WxminiProgram::where('status', 1)->where('wxmini_status', 1)->whereIn('id', $wxminiProgramIds)->order('id desc')->find();
            }
            if (!isset($wxminiProgramInfo) || empty($wxminiProgramInfo)) {
                $wxminiProgramInfo = WxminiProgram::where('status', 1)->where('wxmini_status', 1)->order('id desc')->find();
            }
        }
        if (empty($wxminiProgramIds)) {
            $wxminiProgramInfo = WxminiProgram::where('status', 1)->where('wxmini_status', 1)->whereFindInSet('wxmini_path_ids', $wxminiPathId)->order('id desc')->find();
        }
        return [
            'wxmini_original_id' => isset($wxminiProgramInfo->wxmini_original_id) ? $wxminiProgramInfo->wxmini_original_id : '',
            'wxmini_app_id' => isset($wxminiProgramInfo->wxmini_app_id) ? $wxminiProgramInfo->wxmini_app_id : '',
            'wxmini_private_key' => isset($wxminiProgramInfo->wxmini_private_key) ? $wxminiProgramInfo->wxmini_private_key : '',
            'wxmini_path' => $wxminiPathId ? self::where('id', $wxminiPathId)->value('wxmini_path') : '',
        ];
    }
}
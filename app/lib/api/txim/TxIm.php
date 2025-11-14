<?php

namespace app\lib\api\txim;
use Hedeqiang\TenIM\IM;
use Tencent\TLSSigAPIv2;
//腾讯IM
class TxIm
{
    //注册腾讯IM用户
    public static function regTxImUser($uid = 0, $userType = null, $data = [])
    {
        if (empty($uid) || empty($userType) || empty($data)) {
            return false;
        }
        try {
            $tximId = md5(uniqid() . env('TENGXUNIM.LABEL') . $userType.'_' . $uid);
            $data['UserID'] = $tximId;
            $result  = (new IM())->send('im_open_login_svc','account_import', $data);
            if (isset($result['ActionStatus']) && $result['ActionStatus'] == 'OK') {
                return $tximId;
            }
            if (isset($result['ErrorCode']) && $result['ErrorCode'] == 40005) {
                $data['Nick'] = getRandomStr();
                $result  = (new IM())->send('im_open_login_svc','account_import', $data);
                if (isset($result['ActionStatus']) && $result['ActionStatus'] == 'OK') {
                    return $tximId;
                }
            }
            return false;
        } catch (\Exception $e) {
           return false;
        }
    }
    //修改腾讯IM用户资料
    public static function updateTxImUser($tximId = 0, $data = [])
    {
        try {
            if (!empty($tximId) || !empty($data)) {
                $txUserInfo = [];
                if (isset($data['Nick'])) $txUserInfo[] = ['Tag' => 'Tag_Profile_IM_Nick', 'Value' => $data['Nick']];
                if (isset($data['FaceUrl'])) $txUserInfo[] = ['Tag' => 'Tag_Profile_IM_Image', 'Value' => $data['FaceUrl']];
                $result = (new IM())->send('profile', 'portrait_set', ['From_Account' => $tximId, 'ProfileItem' => $txUserInfo]);
                if (isset($result['ActionStatus']) && $result['ActionStatus'] == 'OK') {
                    return $tximId;
                }
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    //获取用户签名
    public static function getTxImUserSign($tximId = null)
    {
        if (!empty($tximId)) {
            $userSign  = (new TLSSigAPIv2(env('TENGXUNIM.APPID'), env('TENGXUNIM.SECRETKEY')))->genUserSig($tximId);
            return $userSign ?? '';
        }
        return '';
    }
}
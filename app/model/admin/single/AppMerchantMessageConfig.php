<?php
/**
 * 后台课程表模型
 */

namespace app\model\admin\single;

use app\model\admin\App;
use app\model\admin\Merchant;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AppMerchantMessageConfig extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'app_merchant_message_config';

    // 追加属性
    protected $append = [
        'app_names',
        'merchant_names'
    ];

    public function getAppNamesAttr($value, $data) {
        if (!empty($data['app_ids'])) {
            $appArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }

    public function getMerchantNamesAttr($value, $data) {
        if (isset($data['merchant_ids']) && !empty($data['merchant_ids'])) {
            $appArray = explode(',', $data['merchant_ids']);
            $merchantNames = Merchant::field('merchant_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($merchantNames)) {
                $merchantNamesList = array_column($merchantNames, 'merchant_name');
                return implode('、', $merchantNamesList);
            }
        }
        return '-';
    }
}

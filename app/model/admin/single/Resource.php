<?php
/**
 * 资源表模型
 */

namespace app\model\admin\single;

use app\model\admin\App;
use app\model\admin\Merchant;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Resource extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'single_resource';

    // 追加属性
    protected $append = [
        'merchant_names',
        'app_names',
    ];

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

    public function getAppNamesAttr($value, $data) {
        if (isset($data['app_ids']) && !empty($data['app_ids'])) {
            $appArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }


}

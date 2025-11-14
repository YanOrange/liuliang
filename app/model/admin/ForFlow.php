<?php
/**
 * 后台投流表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class ForFlow extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'for_flow';

    protected $append = [
        'merchant_names',
        'channel_nums',
        'show_page_detail_images_blank',
       // 'pv_nums',
       // 'uv_nums',
       // 'register_nums',
        'app_names',
       // 'apply_nums',
        'pv_nums_count',
        'uv_nums_count'
    ];



    //报名总数
    public function applyNums()
    {
        return $this->hasMany('app\model\admin\Thread','flow_id','id')->removeOption('soft_delete');
    }


    //pv数
    public function pvNums()
    {
        return $this->hasMany('app\model\admin\FlowPvUv','for_flow_id','id')->where('type', 1)->removeOption('soft_delete');
    }

    //uv数
    public function uvNums()
    {
        return $this->hasMany('app\model\admin\FlowPvUv','for_flow_id','id')->where('type', 2)->removeOption('soft_delete');
    }

    //注册数
    public function registerNums()
    {
        return $this->hasMany('app\model\admin\UserList','flow_id','id')->where('type', 2)->removeOption('soft_delete');
    }
   public function getAppNamesAttr($value, $data)
    {
        if (isset($data['app_ids']) && !empty($data['app_ids'])) {
            $appIdsArray = explode(',', $data['app_ids']);
            $appNames = App::field('app_name')->whereIn('id', $appIdsArray)->select()->toArray();
            if (!empty($appNames)) {
                $appNamesList = array_column($appNames, 'app_name');
                return implode('、', $appNamesList);
            }
        }
        return '-';
    }
    /*public function getApplyNumsAttr($value, $data)
   {
        return Thread::where('flow_id', $data['id'])->count();
    }
    public function getPvNumsAttr($value, $data)
    {
        $pvNums = 0;
        if (isset($data['type']) && $data['type'] == 1) {
            if (isset($data['id']) && !empty($data['id'])) {
                $pvNums = FlowPvUv::where('for_flow_id', $data['id'])->where('type', 1)->count();
            }
        }

        return $pvNums;
    }
    public function getUvNumsAttr($value, $data)
    {
        $uvNums = 0;
        if (isset($data['type']) && $data['type'] == 1) {
            if (isset($data['id']) && !empty($data['id'])) {
                $uvNums = FlowPvUv::where('for_flow_id', $data['id'])->where('type', 2)->count();
            }
        }
        return $uvNums;
    }
    public function getRegisterNumsAttr($value, $data)
    {
        $registerNums = 0;
        if (isset($data['type']) && $data['type'] == 1) {
            if (isset($data['id']) && !empty($data['id'])) {
                $registerNums = UserList::where('flow_id', $data['id'])->count();
            }
        } else {
            $registerNums = Thread::where('flow_id', $data['id'])->count();
        }
        return $registerNums;
    }*/
    public function getShowPageDetailImagesBlankAttr($value, $data)
    {
        $imagesStr = '';
        if (isset($data['show_page_detail_images']) && !empty($data['show_page_detail_images'])) {
            $imagesArr = explode(',', $data['show_page_detail_images']);
            $imagesStr = implode(', ', $imagesArr);
        }
        return $imagesStr;
    }
    public function getMerchantNamesAttr($value, $data)
    {
        if (isset($data['merchant_ids']) && !empty($data['merchant_ids'])) {
            $merchantArray = explode(',', $data['merchant_ids']);
            $merchantNames = Merchant::field('merchant_name')->whereIn('id', $merchantArray)->select()->toArray();
            if (!empty($merchantNames)) {
                $merchantNamesList = array_column($merchantNames, 'merchant_name');
                return implode('、', $merchantNamesList);
            }
        }
        return '-';
    }
    //渠道数量
    public function getChannelNumsAttr($value, $data)
    {
        $channelNums = 0;
        if (isset($data['h5_link_json']) && !empty($data['h5_link_json'])) {
            $h5LinkArr = json_decode($data['h5_link_json'], true);
            $channelNums = count($h5LinkArr);
        }
        return $channelNums;
    }

    public function getPvNumsCountAttr()
    {
        return 0;
    }

    public function getUvNumsCountAttr()
    {
        return 0;
    }
}

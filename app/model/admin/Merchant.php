<?php
/**
 * 后台商户表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\Thread;
class Merchant extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant';

    protected $append = [
       // 'consume_nums',
     //   'available_thread_nums',
    ];

    public function appClass()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
    //总量
    public function totalThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->removeOption('soft_delete');
    }
    //付费流量
    public function appThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('is_assign', 0)->where('assign_mode', 0)->where('is_register', 0)->removeOption('soft_delete');
    }
    //无效线索
    public function validThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_valid', 0)->where('is_register', 0)->removeOption('soft_delete');
    }
    //注册量
    public function registerThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('is_register', 1)->removeOption('soft_delete');
    }
    //客服分配量
    public function customerAssignThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('assign_mode', '>', 0)->where('assign_mode', '<', 5)->removeOption('soft_delete');
    }
    //纯表单量
    public function formThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('is_form',1)->removeOption('soft_delete');
    }
    
    //当天客服手动补量
    public function customerThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('assign_mode', '=', 5)->where('is_form',0)->removeOption('soft_delete');
    }
    //当天客服纯表单补量
    public function customerFormThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('assign_mode', '=', 5)->where('is_form',1)->removeOption('soft_delete');
    }
    //当天自然补量
    public function natureThreadNum()
    {
        return $this->hasMany('app\model\admin\Thread','merchant_id','id')->whereDay('create_time')->where('is_test', 0)->where('assign_mode', '=', 6)->removeOption('soft_delete');
    }
    /*public function getConsumeNumsAttr($value, $data)
    {
        return Thread::where('merchant_id', $data['id'])->group('uid')->count();
    }
    public function getAvailableThreadNumsAttr($value, $data)
    {
        $availableThreadNums = $data['total_thread_nums_limit'] - $this->consume_nums;
        return $availableThreadNums >= 0 ? $availableThreadNums : 0;
    }*/
}

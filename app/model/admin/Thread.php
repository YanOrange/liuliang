<?php
/**
 * 后台线索模型
 */

namespace app\model\admin;

use app\model\admin\thread\ThreadTag;
use app\model\admin\thread\ThreadTagExternal;
use app\model\admin\thread\ThreadUserStatus;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Thread extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread';

    // 追加属性
    protected $append = [
        'tag_names',
    ];

    public function getTagNamesAttr($value, $data) {
        if (isset($data['thread_tag_ids']) && !empty($data['thread_tag_ids'])) {
            $appArray = explode(',', $data['thread_tag_ids']);
            $tagNames = ThreadTagExternal::field('title')->whereIn('id', $appArray)->select()->toArray();
            if (!empty($tagNames)) {
                $tagNamesList = array_column($tagNames, 'title');
                return implode('、', $tagNamesList);
            }
        }
        return '-';
    }

    public function getIsDiscernQrcodeAttr($value, $data)
    {
        $isDiscernQrcode = $data['is_discern_qrcode'];
        if($data['is_discern_qrcode'] == 1 && $data['is_real_qrcode'] == 1) $isDiscernQrcode = 1;
        if($data['is_discern_qrcode'] == 1 && $data['is_real_qrcode'] == 0) $isDiscernQrcode = 2;
        return $isDiscernQrcode;
    }

    public function userStatus()
    {
        return $this->hasMany('app\model\admin\thread\ThreadUserStatus','thread_id','id')->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
    public function course()
    {
        return $this->belongsTo('app\model\admin\Course','course_id','id')->removeOption('soft_delete');
    }
    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')->removeOption('soft_delete');
    }
    public function user()
    {
        //return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
        return $this->belongsTo('app\model\admin\UserList','uid','id');
    }
    public function channelpro()
    {
        return $this->belongsTo('app\model\admin\Channel','channel_id','id')->removeOption('soft_delete');
    }
    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')->field('id,app_name')->removeOption('soft_delete');
    }
    public function class()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')->removeOption('soft_delete');
    }
    public function flow()
    {
        return $this->belongsTo('app\model\admin\ForFlow','flow_id','id')->removeOption('soft_delete');
    }
    public function singleCourse()
    {
        return $this->belongsTo('app\model\admin\single\Course','course_id','id')->removeOption('soft_delete');
    }
    public function resource()
    {
        return $this->belongsTo('app\model\admin\single\Resource','resource_id','id')->removeOption('soft_delete');
    }
    public function appMessage()
    {
        return $this->belongsTo('app\model\admin\single\AppMerchantMessageConfig','app_message_id','id')->removeOption('soft_delete');
    }
    public function admin()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')->removeOption('soft_delete');
    }
    public function threadExternal()
    {
        return $this->belongsTo('app\model\admin\ThreadExternal','id','inside_thread_id')->removeOption('soft_delete');
    }

    public function threadSource()
    {
        return $this->belongsTo('app\model\admin\thread\ThreadSource','source_id','id')->removeOption('soft_delete');
    }
}

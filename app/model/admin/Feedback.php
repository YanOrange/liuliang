<?php
/**
 * 后台意见反馈表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\Customer;
use app\model\admin\ThreadExternal;
use app\model\admin\LegalAffairs;
use app\model\admin\Thread;

class Feedback extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'feedback';
    protected $append = [
        'customer_name',
        'law_name',
    ];
    public function getCustomerNameAttr($value, $data)
    {
        $customerName = '-';
        if (isset($data['uid']) && !empty($data['uid'])) {
            $id = Thread::where('uid', $data['uid'])->order('id desc')->value('id');
            if (!empty($id)) {
                $customerId = ThreadExternal::where('inside_thread_id', $id)->order('id desc')->value('customer_id');
                if (!empty($customerId)) {
                    $customerName = Customer::where('id', $customerId)->value('nickname');
                }
            }
        }
        return $customerName;
    }
    public function getLawNameAttr($value, $data)
    {
        $lawName = '-';
        if (isset($data['uid']) && !empty($data['uid'])) {
            $id = Thread::where('uid', $data['uid'])->order('id desc')->value('id');
            if (!empty($id)) {
                $legalAffairsId = ThreadExternal::where('inside_thread_id', $id)->order('id desc')->value('legal_affairs_id');
                if (!empty($legalAffairsId)) {
                    $lawName = LegalAffairs::where('id', $legalAffairsId)->value('nickname');
                }
            }    
        }
        return $lawName;
    }
    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')->removeOption('soft_delete');
    }
    
    /**
     * 反馈提交
     *
     * @param array $params
     * @return void
     */
    public static function FeedbackAdd($params = [])
    {
        $uid = $GLOBALS['uid'];
        $data = [
            'uid' => $uid,
            'content' => $params['content'],
            'contact' => $params['contact']
        ];
        self::create($data);
        return true;
    }
}

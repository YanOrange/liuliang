<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class ClueFeedback extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'clue_feedback';

    public function getProblemAttachmentAttr($value,$data)
    {
        $data['problem_attachment'] = !empty($data['problem_attachment']) ? json_decode($data['problem_attachment']) : [];
        return $data['problem_attachment'];
    }

    public function getAttruditorOneTimeAttr($value,$data)
    {
        $data['auditor_one_time'] = !empty($data['auditor_one_time']) ? date('Y-m-d H:i:s',$data['auditor_one_time']) : '';
        return $data['auditor_one_time'];
    }

    public function getAttruditorTwoTimeAttr($value,$data)
    {
        $data['auditor_two_time'] = !empty($data['auditor_two_time']) ? date('Y-m-d H:i:s',$data['auditor_two_time']) : '';
        return $data['auditor_two_time'];
    }

    public function problem()
    {
        return $this->belongsTo('app\model\admin\ClueProblem','clue_problem_id','id')->removeOption('soft_delete');
    }

    public function threadExternal()
    {
        return $this->belongsTo('app\model\admin\ThreadExternal','thread_external_id','id')->removeOption('soft_delete');
    }

    public function userExternal()
    {
        return $this->belongsTo('app\model\admin\UserListExternal','thread_external_uid','id')->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function subMerchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','submitter','id')->removeOption('soft_delete');
    }

    public function subCustomer()
    {
        return $this->belongsTo('app\model\admin\Customer','submitter','id')->removeOption('soft_delete');
    }

    public function auditorOne()
    {
        return $this->belongsTo('app\model\admin\User','auditor_one','id')->removeOption('soft_delete');
    }

    public function auditorTwo()
    {
        return $this->belongsTo('app\model\admin\User','auditor_two','id')->removeOption('soft_delete');
    }

}

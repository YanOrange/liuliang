<?php

namespace app\model\api\customer;

use app\lib\api\exception\Exception;
use app\lib\api\service\CustomerService;
use app\lib\api\service\WeightService;
use app\model\admin\GatherUserInfo;
use app\model\admin\User;
use app\model\api\Customer;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use laytp\library\Token;
use app\model\api\Merchant;
use app\model\api\App;
use app\model\api\Course;
use app\model\api\Channel;
use app\model\api\AppClass;
use app\model\admin\thread\ThreadTag;
use think\Request;

class Thread extends BaseModel
{
    use SoftDelete;
    //模型表名
    protected $name = 'thread';

    protected $hidden = [
        'merchant_id',
        'app_id',
        'app_class_id',
        'admin_id',
        'merchant',
        'app',
        'appClass',
        'course',
        'customer',
        'flow'
    ];

    public static function getThreadList($params = [])
    {
        extract($params);
        $token = request()->header('laytp-admin-token');
        $adminLoginInfo = Token::get($token);
        if(!$adminLoginInfo){
            new Exception('登录已失效');
        }
        $adminLoginId =  $adminLoginInfo['user_id'];
        $loginUserInfo = User::where('id',$adminLoginId)->find();
        $data = self::buildSearch($params)
            ->with(['user1','merchant','app','appClass','admin' => function($query){
                $query->field('id,nickname');
            }])
            ->order('id desc')
            ->paginate(10)
            ->toArray();
        foreach($data['data'] as $key => &$val){
            if($loginUserInfo->is_show_phone == 0) {
                $val['phone'] = $val['phone'] ? substr_replace($val['phone'], '****', 3, 4) : '';
                $val['user1']['phone'] = isset($val['user1']['phone']) && !empty($val['user1']['phone']) ? substr_replace($val['user1']['phone'], '****', 3, 4) : '';
            }
        }
        $total = self::buildSearch($params)->count();
        return ['total' => $total,'data' => $data['data']];
    }

    // 构建查询条件
    public static function buildSearch($params = [])
    {
        file_put_contents('customer_thread_search.txt',json_encode($params,JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND);
        extract($params);
        $token = request()->header('laytp-admin-token');
        $adminLoginInfo = Token::get($token);
        if(!$adminLoginInfo){
            new Exception('登录已失效');
        }
        $adminLoginId =  $adminLoginInfo['user_id'];
        $loginUserInfo = User::where('id',$adminLoginId)->field('id,merchant_ids,is_under_eighteen_thread')->find();
        $whereCon = [];
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $threadModel = new self();
        $name = $threadModel->getName();
        $tableName = env('database.prefix') . $name;
        if($loginUserInfo['id'] != 1){
            $whereCon[] = [$tableName.'.merchant_id','in',$merchantIds];
        }
        $threadModel = $threadModel->field('id,uid,merchant_id,app_id,app_class_id,admin_id,channel,create_time,is_sms');
        $threadModel = $threadModel->withJoin(['user1','merchant'],'inner');
        if (isset($is_assign) && !empty($is_assign)) {
            if($is_assign == 1){
                $threadModel = $threadModel->where($tableName.'.is_assign', 3)->where($tableName.'.admin_id',$adminLoginId);
            }
            if($is_assign == 2){
                $threadModel = $threadModel->where($tableName.'.is_assign', 0)->where($whereCon);
            }
            if($is_assign == 3){
                $threadModel = $threadModel->where($tableName.'.is_assign', 1)->where($whereCon);
            }
        }else{
            $threadModel = $threadModel->where($tableName.'.is_assign', 0)->where($whereCon);
        }
        if (isset($thread_tag_ids) && !empty($thread_tag_ids)) {
            $threadModel = $threadModel->whereFindInSet($tableName.'.thread_tag_ids', $thread_tag_ids);
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $merchant_id = explode(',',$merchant_id);
            $threadModel = $threadModel->where($tableName.'.merchant_id', 'in', $merchant_id);
        }
        if (isset($course_id) && !empty($course_id)) {
            $threadModel = $threadModel->where($tableName.'.course_id', '=', $course_id);
        }
        if (isset($customer_id) && !empty($customer_id)) {
            $threadModel = $threadModel->where($tableName.'.customer_id', '=', $customer_id);
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $threadModel = $threadModel->where($tableName.'.channel_id', '=', $channel_id);
        }
        if (isset($app_id) && !empty($app_id)) {
            $threadModel = $threadModel->where($tableName.'.app_id', '=', $app_id);
        }
        if (isset($app_class_id) && !empty($app_class_id)) {
            $threadModel = $threadModel->where($tableName.'.app_class_id', '=', $app_class_id);
        }
        if (isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) {
            $threadModel = $threadModel->where($tableName.'.is_discern_qrcode', '=', $is_discern_qrcode);
        }
        if (isset($thread_type) && is_numeric($thread_type)) {
            $threadModel = $threadModel->where($tableName.'.thread_type', '=', $thread_type);
        }
        if (isset($thread_tag_id) && is_numeric($thread_tag_id)) {
            $threadModel = $threadModel->whereFindInSet($tableName.'.thread_tag_ids', $thread_tag_id);
        }
        if($loginUserInfo['is_under_eighteen_thread'] == 1) {
            $threadModel = $threadModel->where($tableName.'.age', '=', '未满18岁');
        }else{
            $threadModel = $threadModel->where($tableName.'.age', '<>', '未满18岁');
        }
        if (isset($age_range) && !empty($age_range)) {
            $threadModel = $threadModel->where($tableName.'.age', '=', $age_range);
        }

        $threadModel = $threadModel->where('user1.is_test', '=', 0);
        if (isset($phone) && !empty($phone)) {
            $threadModel = $threadModel->where('user1.phone', '=', $phone);
        }
        if (isset($phone_end_number) && !empty($phone_end_number)) {
            $threadModel = $threadModel->where('user1.phone_end_number', '=', $phone_end_number);
        }
        if (isset($nickname) && !empty($nickname)) {
            $threadModel = $threadModel->where('user1.nickname', '=', $nickname);
        }
        if (isset($wx_nickname) && !empty($wx_nickname)) {
            $threadModel = $threadModel->where('user1.wx_nickname', '=', $wx_nickname);
        }
        if (isset($identity_id) && !empty($identity_id)) {
            $threadModel = $threadModel->where('user1.identity_id', '=', $identity_id);
        }
        if (isset($education_id) && !empty($education_id)) {
            $threadModel = $threadModel->where('user1.education_id', '=', $education_id);
        }
        if (isset($is_has_computer_id) && !empty($is_has_computer_id)) {
            $threadModel = $threadModel->where('user1.is_has_computer_id', '=', $is_has_computer_id);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName.'.create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        return $threadModel;
    }

    public static function detail($params = [])
    {
        extract($params);
        $data = self::field('id,merchant_id,course_id,customer_id,uid,channel,app_id,app_class_id,flow_id,is_discern_qrcode,source,thread_type,thread_tag_ids,province,city,source,create_time')
            ->with(['merchant','course','customer','user' => function($query){
                $query->field('id,nickname,avatar,phone,wx_nickname,age_range_id,identity_id,education_id,is_has_computer_id,zw_mold,zw_money,need_id,zhaiwu_leixing,zhaiwu_monney,cuishou_zhuangtai');
            },'app','appClass','flow'])
            ->where('id',$thread_id)
            ->find()
            ->toArray();
        $data['source'] = self::source($data['source']);
        $data['thread_type'] = self::threadType($data['thread_type']);
        $threadTagIds = !empty($data['thread_tag_ids']) ? explode(',',$data['thread_tag_ids']) : [];
        $threadTagTitle = ThreadTag::whereIn('id',$threadTagIds)->column('title');
        $data['thread_tag_name'] = implode(',',$threadTagTitle);
        return $data;
    }

    public static function source($source)
    {
        $sourceName = '';
        if($source == 1) $sourceName = 'app';
        if($source == 2) $sourceName = 'h5投流';
        if($source == 3) $sourceName = 'app投流';
        if($source == 4) $sourceName = '线索追踪';
        return $sourceName;
    }

    public static function threadType($type)
    {
        $typeName = '';
        if($type == 1) $typeName = '0元纯表单';
        if($type == 2) $typeName = '1分纯表单';
        if($type == 3) $typeName = '0元加微信';
        if($type == 4) $typeName = '1分加微信';
        return $typeName;
    }

    public static function merchantList()
    {
        $token = request()->header('laytp-admin-token');
        $adminLoginInfo = Token::get($token);
        if(!$adminLoginInfo){
            new Exception('登录已失效');
        }
        $adminLoginId =  $adminLoginInfo['user_id'];
        $loginUserInfo = User::where('id',$adminLoginId)->field('id,merchant_ids')->find();
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $data = Merchant::whereIn('id',$merchantIds)
            ->field('id,merchant_name as name')
            ->select()
            ->toArray();
        return $data;
    }

    public static function customerList()
    {
        $token = request()->header('laytp-admin-token');
        $adminLoginInfo = Token::get($token);
        if(!$adminLoginInfo){
            new Exception('登录已失效');
        }
        $adminLoginId =  $adminLoginInfo['user_id'];
        $loginUserInfo = User::where('id',$adminLoginId)->field('id,merchant_ids')->find();
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $data = Customer::where('status',1)
            ->whereIn('merchant_id',$merchantIds)
            ->field('id,account_name as name')
            ->select()
            ->toArray();
        return $data;
    }

    public static function courseList()
    {
        $token = request()->header('laytp-admin-token');
        $adminLoginInfo = Token::get($token);
        if(!$adminLoginInfo){
            new Exception('登录已失效');
        }
        $adminLoginId =  $adminLoginInfo['user_id'];
        $loginUserInfo = User::where('id',$adminLoginId)->field('id,merchant_ids')->find();
        $merchantIds = explode(',',$loginUserInfo['merchant_ids']);
        $data = Course::where('status',1)
            ->whereIn('merchant_id',$merchantIds)
            ->field('id,title as name')
            ->select()
            ->toArray();
        return $data;
    }

    public static function appList()
    {
        $data = App::field('id,app_name as name')
            ->select()
            ->toArray();
        return $data;
    }

    public static function channelList()
    {
        $data = Channel::field('id,channel_name as name')
            ->select()
            ->toArray();
        return $data;
    }

    public static function appClassList()
    {
        $data = AppClass::field('id,app_class_name as name')
            ->select()
            ->toArray();
        return $data;
    }
    //年龄
    public static function ageRangeList()
    {
        $gatherInfoJson = GatherUserInfo::where('id',1)->value('gather_info_json');
        $ageRangeList = json_decode($gatherInfoJson, true);
        return $ageRangeList;
    }

    //身份
    public static function identifyList()
    {
        $gatherInfoJson = GatherUserInfo::where('id',2)->value('gather_info_json');
        $identityList = json_decode($gatherInfoJson, true);
        return $identityList;
    }

    //学历
    public static function educationList()
    {
        $gatherInfoJson = GatherUserInfo::where('id',3)->value('gather_info_json');
        $educationList = json_decode($gatherInfoJson, true);
        return $educationList;
    }

    //产品类型
    public static function threadTypeList()
    {
        $data = [
            ['id' => 1,'name' => '0元纯表单'],
            ['id' => 2,'name' => '1分纯表单'],
            ['id' => 3,'name' => '0元加微信'],
            ['id' => 4,'name' => '1分加微信'],
        ];
        return $data;
    }

    public static function tagList()
    {
        $data = ThreadTag::field('id,title as name')->select()->toArray();
        return $data;
    }

    public function user1()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')
            ->bind(['nickname','avatar','phone','age_range','identity','education'])
            ->removeOption('soft_delete');
    }

    public function user()
    {
        return $this->belongsTo('app\model\admin\UserList','uid','id')
            ->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')
            ->bind(['merchant_name'])
            ->removeOption('soft_delete');
    }

    public function app()
    {
        return $this->belongsTo('app\model\admin\App','app_id','id')
            ->bind(['app_name'])
            ->removeOption('soft_delete');
    }

    public function appClass()
    {
        return $this->belongsTo('app\model\admin\AppClass','app_class_id','id')
            ->bind(['app_class_name'])
            ->removeOption('soft_delete');
    }

    public function admin()
    {
        return $this->belongsTo('app\model\admin\User','admin_id','id')
            ->removeOption('soft_delete');
    }

    public function course()
    {
        return $this->belongsTo('app\model\admin\Course','course_id','id')
            ->bind(['title'])
            ->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer','customer_id','id')
            ->bind(['account_name','wechat_number'])
            ->removeOption('soft_delete');
    }

    public function flow()
    {
        return $this->belongsTo('app\model\admin\ForFlow','flow_id','id')
            ->bind(['for_flow_title'])
            ->removeOption('soft_delete');
    }


}
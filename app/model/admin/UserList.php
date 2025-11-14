<?php
/**
 * 后台用户表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class UserList extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'user_list';
    protected $append = [
        'apply_nums',
        'app_start_total',
        'app_use_time',
        'age_range',
        'identity',
        'education',
        'is_has_computer',
        'need',
        'cuishou'
    ];


    //报名数量
    public function applyNums()
    {
        return $this->hasMany('app\model\admin\Thread','uid','id');
    }

    public function getAgeRangeAttr($value, $data)
    {
        $ageRange = '';
        $gatherInfoJson = GatherUserInfo::where('id',1)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $ageRangeList = json_decode($gatherInfoJson, true);
            $ageRangeList = array_column($ageRangeList, 'name', 'id');
            $ageRange = isset($ageRangeList[$data['age_range_id']]) ? $ageRangeList[$data['age_range_id']] : '';
        }
        return $ageRange;
    }

    public function getIdentityAttr($value, $data)
    {
        $identity = '';
        $gatherInfoJson = GatherUserInfo::where('id',2)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $identityList = json_decode($gatherInfoJson, true);
            $identityList = array_column($identityList, 'name', 'id');
            $identity = isset($identityList[$data['identity_id']]) ? $identityList[$data['identity_id']] : '';
        }
        return $identity;
    }

    public function getEducationAttr($value, $data)
    {
        $education = '';
        $gatherInfoJson = GatherUserInfo::where('id',3)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $educationList = json_decode($gatherInfoJson, true);
            $educationList = array_column($educationList, 'name', 'id');
            $education = isset($educationList[$data['education_id']]) ? $educationList[$data['education_id']] : '';
        }
        return $education;
    }

    public function getIsLikeGamesAttr($value, $data)
    {
        $isLikeGames = '';
        $gatherInfoJson = GatherUserInfo::where('id',7)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $isLikeGameList = json_decode($gatherInfoJson, true);
            $isLikeGameList = array_column($isLikeGameList, 'name', 'id');
            $isLikeGames = isset($isLikeGameList[$data['is_like_games']]) ? $isLikeGameList[$data['is_like_games']] : '';
        }
        return $isLikeGames;
    }

    public function getIsHasComputerAttr($value, $data)
    {
        $isHasComputer = '';
        $gatherInfoJson = GatherUserInfo::where('id',6)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $isHasComputerList = json_decode($gatherInfoJson, true);
            $isHasComputerList = array_column($isHasComputerList, 'name', 'id');
            $isHasComputer = isset($isHasComputerList[$data['is_has_computer_id']]) ? $isHasComputerList[$data['is_has_computer_id']] : '';
        }
        return $isHasComputer;
    }

    public function getZwMoldAttr($value, $data)
    {
        $zwMold = '';
        if(!empty($data['zw_mold'])){
            $gatherInfoJson = GatherUserInfo::where('id',12)->value('gather_info_json');
            if(!empty($gatherInfoJson)) {
                $zwMoldList = json_decode($gatherInfoJson, true);
                $zwMoldList = array_column($zwMoldList, 'name', 'id');
                $zwMold = isset($zwMoldList[$data['zw_mold']]) ? $zwMoldList[$data['zw_mold']] : '';
            }
        }else {
            $gatherInfoJson = GatherUserInfo::where('id', 18)->value('gather_info_json');
            if (!empty($gatherInfoJson)) {
                $zwMoldList = json_decode($gatherInfoJson, true);
                $zwMoldList = array_column($zwMoldList, 'name', 'id');
                $zwMold = isset($zwMoldList[$data['zhaiwu_leixing']]) ? $zwMoldList[$data['zhaiwu_leixing']] : '';
            }
        }
        return $zwMold;
    }

    public function getZwMoneyAttr($value, $data)
    {
        $zwMoney = '';
        if(!empty($data['zw_money'])){
            $gatherInfoJson = GatherUserInfo::where('id', 13)->value('gather_info_json');
            if (!empty($gatherInfoJson)) {
                $zwMoneyList = json_decode($gatherInfoJson, true);
                $zwMoneyList = array_column($zwMoneyList, 'name', 'id');
                $zwMoney = isset($zwMoneyList[$data['zw_money']]) ? $zwMoneyList[$data['zw_money']] : '';
            }
        }else {
            $gatherInfoJson = GatherUserInfo::where('id', 19)->value('gather_info_json');
            if (!empty($gatherInfoJson)) {
                $zwMoneyList = json_decode($gatherInfoJson, true);
                $zwMoneyList = array_column($zwMoneyList, 'name', 'id');
                $zwMoney = isset($zwMoneyList[$data['zhaiwu_monney']]) ? $zwMoneyList[$data['zhaiwu_monney']] : '';
            }
        }
        return $zwMoney;
    }

    public function getNeedAttr($value, $data)
    {
        $need = '';
        $gatherInfoJson = GatherUserInfo::where('id',15)->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $needList = json_decode($gatherInfoJson, true);
            $needList = array_column($needList, 'name', 'id');
            $need = isset($needList[$data['need_id']]) ? $needList[$data['need_id']] : '';
        }
        return $need;
    }

    public function getCuishouAttr($value, $data)
    {
        $cuishou = '';
        if(!empty($data['cuishou_id'])){
            $gatherInfoJson = GatherUserInfo::where('id',17)->value('gather_info_json');
            if(!empty($gatherInfoJson)) {
                $cuishouList = json_decode($gatherInfoJson, true);
                $cuishouList = array_column($cuishouList, 'name', 'id');
                $cuishou = isset($cuishouList[$data['cuishou_id']]) ? $cuishouList[$data['cuishou_id']] : '';
            }
        }else{
            $gatherInfoJson = GatherUserInfo::where('id',21)->value('gather_info_json');
            if(!empty($gatherInfoJson)) {
                $cuishouList = json_decode($gatherInfoJson, true);
                $cuishouList = array_column($cuishouList, 'name', 'id');
                $cuishou = isset($cuishouList[$data['cuishou_zhuangtai']]) ? $cuishouList[$data['cuishou_zhuangtai']] : '';
            }
        }
        return $cuishou;
    }

    public function getApplyNumsAttr($value, $data)
    {
        $applyNums = 0;
        if (isset($data['id']) && !empty($data['id'])) {
            return Thread::where('uid', $data['id'])->count();
        }
        return $applyNums;
    }

    public function getAppStartTotalAttr($value, $data)
    {
        $appStartTotal = 0;
        if(isset($data['appUserStart'])){
            $appStartTotal = count($data['appUserStart']);
        }
        return $appStartTotal;
    }

    public function getAppUseTimeAttr($value, $data)
    {
        $appUseTime = 0;
        if(isset($data['appUserStart'])){
            $appStartTotal = count($data['appUserStart']);
            $useTimeTotal = array_sum(array_column($data['appUserStart'],'use_time'))/$appStartTotal;
            $appUseTime = $appStartTotal > 0 && $useTimeTotal > 0 ? round($useTimeTotal/$appStartTotal,2) : 0;
        }
        return $appUseTime;
    }

    public function getLatelyStartAppTimeAttr($value, $data)
    {
        $latelyStartAppTime = !empty($data['lately_start_app_time']) ? date('Y-m-d H:i:s',$data['lately_start_app_time']) : '';
        return $latelyStartAppTime;
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

    public function appUserStart()
    {
        return $this->belongsTo('app\model\admin\AppUserStartRecord','flow_id','id')->removeOption('soft_delete');
    }

    public function thread()
    {
        return $this->belongsTo('app\model\admin\Thread','id','uid')->removeOption('soft_delete');
    }
}

<?php
/**
 * 逾期案件
 */

namespace app\model\api\vestbag;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use app\lib\api\exception\ExceptionStd;
use app\model\api\overdue\OverdueTeam as OverdueTeamModel;
class LawyerCollectorList extends BaseModel
{
    //模型名
    protected $name = 'lawyer_collector_list';

    //收藏或取消收藏
    public static function likeLawyer($params = [])
    {
        extract($params);
        $lawyerInfo = OverdueTeamModel::find($lawyer_id);
        if (empty($lawyerInfo)) {
            new Exception('律师不存在');
        }
        $collectorInfo = self::where('uid', $GLOBALS['uid'])->where('lawyer_id', $lawyer_id)->find();
        if (!empty($collectorInfo)) {
            $collectorInfo->delete();
            return;
        }
        self::create([
            'uid' => $GLOBALS['uid'],
            'lawyer_id' => $lawyer_id
        ]);
        return;
    }
    //获取我的收藏列表
    public static function getLawyerCollectorList($params = [])
    {
        extract($params);
        $classList = [['id' => 1, 'className' => '全部'],['id' => 2, 'className' => '停息业务'],['id' => 3, 'className' => '逾期业务'],['id' => 4, 'className' => '催收解决']];
        $lawyerList = self::field('uid,lawyer_id')->with(['lawyerInfo'])->where('uid', $GLOBALS['uid'])->select();
        return [
            'classList' => $classList,
            'lawyerList' => $lawyerList,
        ];
    }

    public function lawyerInfo()
    {
        return $this->belongsTo('app\model\api\overdue\OverdueTeam','lawyer_id','id')->field('id,nickname,avator,work_year,label')->removeOption('soft_delete');
    }

}

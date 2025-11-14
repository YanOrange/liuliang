<?php
/**
 * 应用表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use app\lib\api\exception\Exception;
use think\Facade\Db;
class Wm extends BaseModel
{
    protected $connection = 'mongodb';
    protected $table = 'wm';

    public function  collection(){
        return Db::connect($this->connection)->table($this->table);
    }

    public  function mongo($pipeline = [],$bool = false){
        $data = $this->collection()->cmd([
            'aggregate'=>$this->table,
            'pipeline'=>$pipeline,
            'explain'=>false,
        ]);
        return $bool ?  (!empty($data[0]) ?$data[0]: 0) : $data;
    }

}

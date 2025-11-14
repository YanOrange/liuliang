<?php

namespace app\model\api\h5;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class HorseRaceLamp extends BaseModel
{
    use SoftDelete;

    protected $name = 'horse_race_lamp';
}
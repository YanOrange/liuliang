<?php

namespace app\lib\api\service;

class WeightService
{
    protected $container;
    protected $weight = 0;

    public function initData($arr)
    {
        if (empty($arr)) {
            return 0;
        } else {
            foreach ($arr as $item) {
                $weight = $item['weight'];//扩大权重 提高单次的精确度
                $this->weight += $weight;
                $array['id'] = $item['id'];
                $array['weight'] = $weight;
                $container[] = $array;
            }
            $this->container = $container;
        }
        return $this->getUid();
    }

    public function getUid()
    {
        $random = $this->random();
        //初始化区间参数
        $left = 0;//左闭区间
        $right = 0;//右开区间
        foreach ($this->container as $item) {
            //区间宽度
            $size = $item['weight'];
            //右区间 + 区间宽度
            $right += $size;
            if ($random >= $left && $random < $right) {
                return $item['id'];
            } else {
                //准备下一轮的循环 左区间 + 区间宽度
                $left += $size;
            }
        }
        return 0;
    }
    protected function random()
    {
        //右边是开区间  这个生成的是闭区间
        return mt_rand(0, $this->weight - 1);
    }

}
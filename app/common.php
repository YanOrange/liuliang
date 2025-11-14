<?php

/**
 * 公共函数
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use laytp\library\Http;
use app\model\api\Channel;
use app\model\api\App;
use think\facade\Env;
use think\facade\Db;
//二位数组去重
if (!function_exists('arrayDuplicate ')) {
    //生成JWT TOKEN
    function arrayDuplicate($arr=[],$key=null)
    {
        //建立一个目标数组
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if(isset($res[$value[$key]])){
                unset($value[$key]);
            }else{
                $res[$value[$key]] = $value;
            }

        }
        return $res;
    }
}


if (!function_exists('getJwtToken')) {
    //生成JWT TOKEN
    function getJwtToken($uid = 0)
    {
        $nowTime = time();
        $jwtConfig = config('jwt');
        $token = array(
            "iss" => $jwtConfig['key'],
            "aud" => '',
            "iat" => $nowTime,
            "nbf" => $nowTime,
            "exp" => $nowTime + $jwtConfig['exp'],
            "data" => [
                'uid' => $uid,
            ]
        );
        return JWT::encode($token, $jwtConfig['key'], $jwtConfig['sign_type']);
    }
}

if (!function_exists('checkJwtToken')) {
    //验证JWT TOKEN
    function checkJwtToken($token = null)
    {
        $jwtConfig = config('jwt');
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token, new Key($jwtConfig['key'], $jwtConfig['sign_type'])); //HS256方式，这里要和签发的时候对应
            $ret = (array)$decoded;
            $user = $ret['data'];
            return $user->uid;
        } catch(\Firebase\JWT\SignatureInvalidException $e) { //签名不正确
            return '签名不正确';
        } catch(\Firebase\JWT\BeforeValidException $e) { // 签名在某个时间点之后才能用
            return 'token失效';
        } catch(\Firebase\JWT\ExpiredException $e) { // token过期
            return 'token失效';
        } catch(Exception $e) { //其他错误
            return  '未知错误';
        }
    }
}

//根据生日计算年龄
if (!function_exists('getAge')) {

    function getAge($birthday)
    {
        $age = strtotime($birthday);
        if (empty($birthday) || $age === false) {
            return 0;
        }
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
        $now = strtotime("now");
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $age = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1))
            $age -= 1;
        return $age;
    }
}


if (!function_exists('richText')) {
    /**
     * 给富文本编辑器图片视频宽度设为100%
     */
    function richText($content)
    {
        if (!empty($content)) {
            //hmtl符号反转义
            $content = str_replace("&quot;", "", $content);
            $content = html_entity_decode($content);
            //替换图片 视频的宽度
            $contentStr = preg_replace_callback('#(<img.*?)#i', function($matches) {return $matches[1].' width="100%"';},$content);
            $contentStr = preg_replace_callback('#(<viedo.*?)#i', function($matches){return $matches[1].' width="100%"';}, !empty($contentStr) ? $contentStr : $content);
            $content = !empty($contentStr) ? $contentStr : $content;
            return $content;
        }
    }
}

if (!function_exists('getGeneralEditTextUrl')) {
    /**
     * 给普通字段图片视频地址加上绝对路径拼接域名
     */
    function getGeneralEditTextUrl($content, $isMore = false)
    {
        if (!empty($content)) {
            $imageLists = explode(',', $content);
            foreach ($imageLists as $val) {
                $urlInfo = parse_url($val);
                if (isset($urlInfo['host']) && !empty($urlInfo['host'])) {
                    $images[] = $val;
                } else {
                    $images[] = env('ALIOSS.DOMAIN') .'/'. $val;
                }
            }
            return $isMore ? $images : implode(',', $images);
        }
    }
}

if (!function_exists('create_order_sn')) {
    /**
     * 生成订单号
     */
    function create_order_sn()
    {
        $order_id_main = date('YmdHis') . rand(10000000,99999999);
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i=0; $i<$order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main,$i,1));
        }
        return $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
    }
}

if (!function_exists('floatToInt')) {
    /**
     * 金额元转成分
     */
    function floatToInt($var)
    {
        if ($var) {
            $var = floatval($var);
            $var = $var * 100;
            return (int)$var;
        }
        return 0;
    }
}

//时间范围匹配
if (!function_exists('checkThreadRangeTime')) {
    function checkThreadRangeTime()
    {
        //list($startTime, $endTime) = explode('-', env('thread.peaktime', '11:00:00-15:00:00'));
        list($startTime, $endTime) = explode('-', env('thread.peaktime', '08:00:00-11:00:00'));
        $day = date('Y-m-d ',time());
        $startTime = strtotime($day . $startTime);
        $endTime = strtotime($day . $endTime);
        if (time() >= $startTime && time() <= $endTime) {
            return true;
        }
        return false;
    }
}

//根据渠道名获取渠道id 应用id 应用分类id等信息
if (!function_exists('getChannelAppClass')) {
    function getChannelAppClass($channel = null)
    {
        if (!empty($channel)) {
            $channel = Channel::with(['app'])->where('channel_name', $channel)->find();
        }
    }
}

if (!function_exists('get_redis')) {
    /**
     * 连接redis
     *
     */
    function get_redis()
    {

        $redis = new \Redis();
        $redis->connect(env('redis.host'), env('redis.port'));
        $redis->auth(env('redis.password'));
        return $redis;
    }
}

if (!function_exists('getDefaultWeek')) {
    function getDefaultWeek($key)
    {
        $data = [["week" => "周一", "time" => ""],["week" => "周二", "time" => ""],["week" => "周三", "time" => ""],["week" => "周四", "time" => ""],["week" => "周五", "time" => ""],["week" => "周六", "time" => ""],["week" => "周日", "time" => ""]];
        return $data[$key];
    }
}

/**
 * 获取毫秒时间戳
 */
if (!function_exists('getMillisecond')) {
    function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
}

/**
 * 生成唯一随机字符串
 */
if (!function_exists('createUniqueRandomStr')) {
    function createUniqueRandomStr()
    {
        return date('YmdHis') . rand(100000, 999999);
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', Env::get('domain.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit;
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                exit;
            }
        }
    }
}

if (!function_exists('changeKeys')) {
    /**
     * 替换键名
     */
    function changeKeys($array, $oldKeyArr, $newKeyArr)
    {
        if (!is_array($array)) return $array;
        $newArray = array();
        foreach ($array as $key => $value) {
            // 处理数组的键，如color改成颜色
            $key = array_search($key, $oldKeyArr, true) === false ? $key : $newKeyArr[array_search($key, $oldKeyArr)];
            if (is_array($value)) {
                $value = changeKeys($value, $oldKeyArr, $newKeyArr);
            }
            $newArray[$key] = $value;
        }
        return $newArray;
    }
}

if (!function_exists('subNickname')) {
    /**
     * 用户昵称加*
     */
    function subNickname($user_name)
    {
        $strlen = mb_strlen($user_name, 'utf-8'); //获取字符长度
        $firstStr = mb_substr($user_name, 0, 1, 'utf-8');  //查找字符第一个
        $str = $firstStr . str_repeat('*', $strlen - 1);  //拼接第一个+把字符串 "* " 重复 $strlen - 1 次：
        return $str;
    }
}

if (!function_exists('getTimes')) {
    /**
     * 时间解析
     */
    function getTimes($thrTime)
    {
        $timeStr = '';
        $curTime = time();
        $day = floor(($curTime - $thrTime) / 86400);//日
        $hour = floor(($curTime - $thrTime) % 86400 / 3600);//时
        $second = floor(($curTime - $thrTime) % 86400 / 60);//分
        $minute = floor(($curTime - $thrTime) % 86400 % 60);//秒
        if ($second > 0) {
            $timeStr = $second . '秒前';
        }
        if ($minute > 0) {
            $timeStr = $minute . '分钟前';
        }
        if ($hour > 0) {
            $timeStr = $hour . '小时前';
        }
        if ($day > 0) {
            $timeStr = $day . '天前';
        }
        return $timeStr;
    }
}

if (!function_exists('getEditText')) {
    /**
     * 提取富文本编辑器纯文字内容
     */
    function getEditText($content)
    {
        $content = strip_tags($content);
        if (!empty($content)) {
            $content = mb_substr(str_replace(array("\r","\n","\s","\t"),'',$content), 0, 10);
            $content = str_replace("&nbsp;","[space_code]",$content);
            $content = html_entity_decode($content);
            return  str_replace("[space_code]","",$content);
        }
    }
}

if (!function_exists('getEditContentText')) {
    /**
     * 提取富文本编辑器纯文字内容
     */
    function getEditContentText($content, $length = 50)
    {
        $content = strip_tags($content);
        if (!empty($content)) {
            $content = mb_substr(str_replace(array("\r","\n","\s","\t"),'',$content), 0, $length);
            $content = str_replace("&nbsp;","[space_code]",$content);
            $content = html_entity_decode($content);
            return  str_replace("[space_code]","",$content);
        }
    }
}

if (!function_exists('getNickname')) {
    /**
     * 随机生成昵称
     */
    function getNickname()
    {
        $id = rand(0, 9);
        $nickname = '';
        $arr = array('富', '强', '民', '主', '文', '明', '和', '谐', '自');
        $randStr = explode('.', '由.平.等.公.正.法.治.爱.国.敬.业.诚.信.友.善')[rand(0, 14)];
        $nickname .= $arr[$id] . $randStr;
        return $nickname;
    }
}


if (!function_exists('getAgeToAgeRange')) {
    /**
     * 把整形年龄转化成年龄段
     */
    function getAgeToAgeRange($age = 0)
    {
        $gatherInfoJson = \app\model\api\v2\GatherUserInfo::where('field','age_range_id')->value('gather_info_json');
        $ageRangeId = 0;
        $ageText = '';
        if (!empty($gatherInfoJson)) {
            $gatherInfoArr = json_decode($gatherInfoJson, true);
            $gatherInfoArr = array_slice($gatherInfoArr,0,6);
            if (!empty($gatherInfoArr)) {
                foreach ($gatherInfoArr as $k => &$val) {
                    $val['age_name'] = preg_replace("/[\x{4e00}-\x{9fa5}]+/u",'', $val['name']);
                    if ($val['name'] == '未满18岁') {
                        unset($gatherInfoArr[$k]);
                    }
                }
                $gatherInfoArr = array_values($gatherInfoArr);
                foreach ($gatherInfoArr as $key => $v) {
                    if ($age < 18) {
                        $ageRangeId = 1;
                        $ageText = '未满18岁';
                        break;
                    } else {
                        $ageRangeArr = explode( "-", $v['age_name']);
                        $ageRangeArrCount = count($ageRangeArr);
                        if ($ageRangeArrCount > 1) {
                            if ($age >= $ageRangeArr[0]  &&  $age <= $ageRangeArr[1]) {
                                $ageRangeId = $gatherInfoArr[$key]['id'];
                                $ageText = $gatherInfoArr[$key]['name'];
                                break;
                            }
                        } else {
                            if ($age >= $ageRangeArr[0]) {
                                $ageRangeId = $gatherInfoArr[$key]['id'];
                                $ageText = $gatherInfoArr[$key]['name'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        return compact('ageRangeId','ageText');
    }
}

/**
 * 出生日期转年龄
 */
if (!function_exists('getBirthdayToAge')) {
    function getBirthdayToAge($birthday)
    {
        if (empty($birthday)) {
            return 0;
        }
        list($year, $month, $day) = explode("-", $birthday);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff = date("d") - $day;
        if ($day_diff < 0 || $month_diff < 0)
            $year_diff--;
        return $year_diff;
    }
}


if (!function_exists('checkNoJumpWechatPhone')) {
    function checkNoJumpWechatPhone($uid = 0)
    {
        $isJump = 1;
        if (!empty($uid)) {
            $user = Db::name('user_list')->field('phone,wx_nickname')->where('id', $uid)->find();
            if (!empty($user)) {
                $countPhone = 0;
                $countWxNickname = 0;
                if (!empty($user['phone'])) {
                    $countPhone = Db::name('no_jump_wechat_phone')->where('phone', $user['phone'])->count();
                }
                if (!empty($user['wx_nickname'])) {
                    $countWxNickname = Db::name('no_jump_wechat_phone')->where('wx_nickname', $user['wx_nickname'])->count();
                }
                $isJump = $countPhone || $countWxNickname ? 0 : 1;
            }
        }
        return $isJump;
    }
}
if (!function_exists('convert')) {
    function convert($second)
    {
        $newtime = '';
        $d = floor($second / (3600 * 24));
        $h = floor(($second % (3600 * 24)) / 3600);
        $m = floor((($second % (3600 * 24)) % 3600) / 60);
        if ($d > '0') {
            if ($h == '0' && $m == '0') {
                $newtime = $d . '天';
            } else {
                $newtime = $d . '天' . $h . '小时' . $m . '分钟';
            }
        } else {
            if ($h != '0') {
                if ($m == '0') {
                    $newtime = $h . ':00:00';
                } else {
                    $newtime = $h . ':' . $m.':00';
                }
            } else {
                if ($m == '0') {
                    $newtime = '00:00:'.$second;
                } else {
                    $second_n = $second - $m * 60;
                    if($second_n > 0 && $second_n < 10){
                        $second_n = '0'.$second_n;
                    }
                    $newtime = '00:' . $m.':'.$second_n;
                }
                //$newtime = '00:'.$m.':00';
            }
        }
        return $newtime;
    }
}

if (!function_exists('getWeekDay')) {
    function getWeekDay()
    {
        $timeStr = time();
        $nowDay = date('w', $timeStr);

        $monday_str = $timeStr - ($nowDay - 1) * 60 * 60 * 24;
        $monday = date('Y-m-d', $monday_str);

        $sunday_str = $timeStr + (7 - $nowDay) * 60 * 60 * 24;
        $sunday = date('Y-m-d', $sunday_str);

        for ($i = 0; $i < 7; $i++) {
            $arr[$i] = date('Y-m-d', strtotime($monday . '+' . $i . 'day'));
        }
        return $arr;
    }
}

if (!function_exists('curlPost')) {
    function curlPost($url, $body, $headers = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);//设置请求体1
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $data = curl_exec($curl);
        if(is_array($data)){
            trace($url.'-'.json_encode($body).'-'.json_encode($data), 'info');
        }else{
            trace($url.'-'.json_encode($body).'-'.$data, 'info');
        }

        if ($data === false) {
            return false;
        } else {
            return $data;
        }
    }
}

if (!function_exists('getPhoneSystem')) {
    function getPhoneSystem()
    {
        $phoneSystem = '';
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) {
            $phoneSystem = 'ios';
        }
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
            $phoneSystem = 'android';
        }
        return $phoneSystem;
    }
}

if (!function_exists('phoneEncryption')) {
    function phoneEncryption($phone)
    {
        return !empty($phone) ? substr_replace($phone, '****', 3, 4) : '';
    }
}

if (!function_exists('wxNumberEncryption')) {
    function wxNumberEncryption($wxNumber)
    {
        if (mb_strlen($wxNumber) == 0) return '';
        if (mb_strlen($wxNumber) >= 3) return mb_substr($wxNumber, 0, 3) . '****';
        else return mb_substr($wxNumber, 0, 1) . '****';
    }
}

if (!function_exists('getRandomStr')) {
    function getRandomStr($length)
    {
        $str = 'abcdefjhighlmnopqrstuvwxyzABCDEFJHIGKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str) - 1;
        $randStr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randStr .= $str[$num];
        }
        return $randStr;
    }
}

if (!function_exists('getIpArea')) {
    function getIpArea($ip = null)
    {
        $host = "https://c2ba.api.huachen.cn";
        $path = "/ip";
        $method = "GET";
        $appcode = "475642fe0dc3426aaf54ed2db7a0b43c";
        $headers = array();
        $ip = !empty($ip) ? $ip : request()->ip();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "ip=" . $ip;
        $bodys = "";
        $url = $host . $path . "?" . $querys;
    
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        return json_decode(curl_exec($curl), true);
    }
}

/**
 * 生成订单号
 */
if (!function_exists('create_order_sn')) {
    function create_order_sn() {
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        return $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
    }
}







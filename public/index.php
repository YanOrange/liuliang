<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// header("Access-Control-Allow-Origin: $origin");
// // 设置跨域头（必须在任何输出之前）
// // header("Access-Control-Allow-Origin: *"); // 允许所有域名，生产环境建议替换为具体域名
// header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // 允许的请求方法
// header("Access-Control-Allow-Headers: *"); // 允许的请求头

// 如果是OPTIONS请求（预检请求），直接返回204
// if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//     http_response_code(204);
//     exit;
// }


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");

// 如果是 OPTIONS 请求，直接返回 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);
define('LT_VERSION', '2.0.0');

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);

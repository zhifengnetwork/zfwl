<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
define('HTTP_HOST', $_SERVER['HTTP_HOST']);

// http://zfwl.zhifengwangluo.c3w.cc/
// if (preg_match("/(.*)\.(.*)\.c3w\.cc/i", HTTP_HOST, $matches)) {
//     $partner = $matches[1];
//     $key     = $matches[2];
//     $modules = [
//         'zhifengwangluo'   => 'admin',
//         'home'             => 'home',
//         'pay'              => 'pay',
//         'api'              => 'api',
//         'sapi'             => 'sapi',
//         'agent'            => 'agent',
//         'kf'               => 'kf',
//     ];
//     $module = isset($modules[$key]) ? $modules[$key] : 'home';
//     define('BIND_MODULE', $module);
// } else {
    $terrace = [
        // 'zfwl.zhifengwangluo.c3w.cc' => 'admin',
        '127.0.0.1:10059' => 'agent',
        '127.0.0.1:10058' => 'home',
        '127.0.0.1:10057' => 'sapi',
        '127.0.0.1:10056' => 'api',
        '127.0.0.1:12588' => 'admin',
        // '127.0.0.1:12580' => 'admin',
    ];
    if (!empty($terrace[HTTP_HOST])) {
        $module = $terrace[HTTP_HOST];
        define('BIND_MODULE', $module);
    }
// }

$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
define('SITE_URL',$http.'://'.$_SERVER['HTTP_HOST']); // 网站域名

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

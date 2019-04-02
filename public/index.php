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
if (preg_match("/(.*)\.(.*)\.fangxiname\.com/i", HTTP_HOST, $matches)) {
    $partner = $matches[1];
    $key     = $matches[2];
    $modules = [
        'goc'   => 'admin',
        'home'  => 'home',
        'pay'   => 'pay',
        'api'   => 'api',
        'sapi'  => 'sapi',
        'agent' => 'agent',
        'kf'    => 'kf',
    ];
    $module = isset($modules[$key]) ? $modules[$key] : 'home';
    define('MG_PARTNER', $partner);
    define('BIND_MODULE', $module);
    echo 111;
    exit;
} else {
    $terrace = [
        '127.0.0.1:10060' => 'kf',
        '127.0.0.1:10059' => 'agent',
        '127.0.0.1:10058' => 'home',
        '127.0.0.1:10057' => 'sapi',
        '127.0.0.1:10056' => 'api',
        '127.0.0.1:12588' => 'admin',
    ];
    define('MG_PARTNER', 'dev');
    if (!empty($terrace[HTTP_HOST])) {
        $module = $terrace[HTTP_HOST];
        define('BIND_MODULE', $module);
    }
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

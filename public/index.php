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

//http://zfwl.zhifengwangluo.c3w.cc/
if (preg_match("/(.*)\.(.*)\.c3w\.cc/i", HTTP_HOST, $matches)) {
    $partner = $matches[1];
    $key     = $matches[2];
    $modules = [
        'zfwl'              => 'admin',
        'dist'              => 'zf_shop',
        'api'               => 'api',
    ];
    $module = isset($modules[$partner]) ? $modules[$partner] : 'home';
    define('BIND_MODULE', $module);
} else {
    $terrace = [
        'agent.zfwl.local' => 'agent',
        'www.zfwl.local' => 'home',
        'sapi.zfwl.local' => 'sapi',
        'api.zfwl.local' => 'api',
        'admin.zfwl.local' => 'admin',
    ];
    if (!empty($terrace[HTTP_HOST])) {
        $module = $terrace[HTTP_HOST];
        define('BIND_MODULE', $module);
    }
 }

$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
define('SITE_URL',$http.'://'.$_SERVER['HTTP_HOST']); // 网站域名

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

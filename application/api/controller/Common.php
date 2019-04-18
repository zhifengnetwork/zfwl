<?php
namespace app\api\controller;

use app\common\model\Config;
use think\Controller;

/**
 * 有大量的配置都需要继承这个公共的
 */
class Common extends Controller
{
    protected function _initialize()
    {
        config((new Config)->getConfig());
    }
}

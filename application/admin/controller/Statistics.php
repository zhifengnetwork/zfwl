<?php
namespace app\admin\controller;

use think\Db;

/**
 * 首页
 */
class Statistics extends Common
{
    public function index()
    { 
        $this->assign('meta_title', '首页');
        return $this->fetch();
    }

}

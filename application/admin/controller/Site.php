<?php
namespace app\admin\controller;

use think\Db;

class Site extends Common
{
    public function index()
    {
        
     
        $this->assign('meta_title', '首页');
        return $this->fetch();
    }

}

<?php
namespace app\admin\controller;

use think\Db;

class Site extends Common
{
    public function index()
    {
        
     
        return $this->fetch('',[
            'meta_title'    =>  '网站设置',
        ]);
    }

}

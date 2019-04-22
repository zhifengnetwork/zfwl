<?php
namespace app\admin\controller;

use think\Db;

/**
 * 首页
 */
class Total extends Common
{
    /***
     * 财务数据
     */
    public function finance()
    {
        $this->assign('meta_title', '财务数据');
        return $this->fetch();
    }
    /***
     * 业务数据
     */
    public function business()
    {
        $this->assign('meta_title', '业务数据');
        return $this->fetch();
    }

}
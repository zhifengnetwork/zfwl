<?php
namespace app\admin\controller;

use think\Db;

/**
 * 财务管理
 */
class Finance extends Common
{
    /**
     * 余额记录
     */
    public function balance_log()
    { 
        $this->assign('meta_title', '余额记录');
        return $this->fetch();
    }

    /**
     * 积分记录
     */
    public function  integral_log(){
        $this->assign('meta_title', '积分记录');
        return $this->fetch();
    }
    /***
     * 余额提现
     */

    public function balance_withdrawal(){
        $this->assign('meta_title', '余额提现');
        return $this->fetch();

    }

    /***
     * 余额提现设置
     */
    public function balance_set(){
        $this->assign('meta_title', '余额提现设置');
        return $this->fetch();
    }

    





 

}

<?php
namespace app\api\controller;

use app\api\model\Login as LoginModel;
use think\Db;

/**
 * 登入控制器不需登录
 */
class Login extends Common
{
    /**
     * 游客登入
     */
    public function visitor_login()
    {
        write_log('visitor_login.txt');
        $data = LoginModel::visitor_login();
        $this->result(...$data);
    }

    /**
     * 手机登入
     */
    public function tel_login()
    {
        $data = LoginModel::tel_login();
        $this->result(...$data);
    }

    /**
     * 查看代理记录
     */
    public function agent_record()
    {
        $uid = input('agentid', '');
        Db::table('agent_data')->insert(['value' => $uid]);
        $this->result('', 0, 'success');
    }
}

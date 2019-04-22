<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/22
 * Time: 17:53
 */

namespace app\api\controller;

use think\Db;
use think\Loader;
use think\Request;
use think\Session;
use think\captcha\Captcha;

class Login extends \think\Controller
{
    public function index () {
        redirect('login/login')->send();
        exit;
    }

    public function login () {
        if (Request::instance()->isPost()) {
            $username = input('post.username');
            $password = input('post.password');

            // 实例化验证器
            $validate = Loader::validate('Login');
            // 验证数据
            $data = ['username' => $username, 'password' => $password, 'captcha' => request()->input('captcha')];
            // 验证
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            $where['username'] = $username;
            $where['status']   = 1;
            $user_info = Db::table('mg_user')->where($where)->find();
            if ($user_info && $user_info['password'] === minishop_md5($password, $user_info['salt'])) {
                $session['uid']     = $user_info['mgid'];
                $session['user_name'] = $user_info['username'];
                // 记录用户登录信息
                Session::set('admin_user_auth', $session);
                return json(['code'=>1,'msg'=>'登录成功']);
            }
            return json(['code'=>0,'msg'=>'密码错误！']);
        }
    }

    /*
     *  获取验证码
      */
    public function loginCaptcha () {
        $captcha = new Captcha();
        return $captcha->entry();
    }

    /*
     * 退出登录
     */
    public function login_out()
    {
        session('admin_user_auth', null);
        session('ALL_MENU_LIST', null);
        return json(['code'=>1,'msg'=>'请登录','data'=>'']);
    }
}
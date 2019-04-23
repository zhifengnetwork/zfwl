<?php
namespace app\admin\controller;
use app\common\model\Config;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use think\View;

/*
 * 公共控制器
 */
class Common extends Controller
{
    /*
     * 初始化
     */
    public function _initialize()
    {
        $session['mgid']     = 26;
        $session['username'] = 'xiaozhi';
        Session::set('admin_user_auth',$session);
        config((new Config)->getConfig());
        if (session('admin_user_auth')) {
            if (!defined('UID')) {
                define('UID', session('admin_user_auth.mgid'));
            }
        } else {
            define('UID', null);
        }
        if (!UID) {
            // $this->redirect('login/index');
            redirect('login/index')->send();
            exit;
        }
        if (session('admin_user_auth.mgid') == 1) {
            define('IS_ROOT', 1);
        } else {
            define('IS_ROOT', null);
        }
        //权限判断
        $this->auth();
        static $url;
        !$url && $url = request()->path();
        $url = str_replace('admin/', '', $url);
        
        $this->view->mginfo     = $this->mginfo    = session('admin_user_auth');

        $array = self::get_leftmenu();
        foreach( $array as $v){
            if($url == $v['url']){ 
                $array2 = $v;
                break;
            }
        }
        $this->view->left_menu  = self::get_leftmenu();
       
        $this->view->lefts_menu = $array2;
        View::share('meta_title', 'GAME');
    }

    /*
     * 跳转登录页面
     */
    protected function login()
    {
        $this->redirect(url('login/index'));
    }

    protected function admin_log($type,$content_type,$content)
    {
        (new UserAgentLog())->_save_admin($type,$content_type,$content);
    }
    /**
     * 左侧菜单
     */
    protected function get_leftmenu()
    {
        //获取所有可见菜单
        $all_menu       = '';
        $admin_userinfo = Session::get('admin_user_auth');
        if (!$all_menu) {
            $where['status'] = 1;
            $where['hide']   = 1;
            $all_menu        = Db::table('menu')->where($where)->order('sort ASC')->field("id,title,pid,url,tip,group,sort,icon")->select();
        }
       
        //权限判断
        $auth_rules = get_menu_auth();
        $list       = [];
        foreach ($all_menu as $val) {
            if (check_menu_auth($val['id'], $auth_rules)) {
                $list[] = $val;
            }
        }
      
        $menu_tree = list_to_tree($list);
       
       
       
        Session::set('ALL_MENU_LIST', $menu_tree);
        $left_menu = self::menu($menu_tree);
        return $left_menu;
    }

    /**
     * 左侧菜单
     */
    private function menu($left_menu)
    {
      
        static $url;
       
        //!$url && $url = strtolower(request()->controller() . '/' . request()->action());
       !$url && $url = request()->path();
        $url = str_replace('admin/', '', $url);

        foreach ($left_menu as $key => &$val) {
            if (!empty($val['_child'])) {
                $val['_child'] = self::menu($val['_child']);
                if ($url == $val['url']) {
                    $val['class'] = 'select';
                } else {
                    $val['class'] = empty(array_filter(array_column($val['_child'], 'class'))) ? '' : 'select';
                }
            } else {
                $val['class'] = $url == $val['url'] ? 'select' : '';
            }
        }
      
        return $left_menu;
    }

    /*
     * 权限判断
     */
    protected function auth()
    {
        $request = Request::instance();
        //当前url

        $url = $request->path();
        if ($url == '/') {
            $url = strtolower($request->controller() . "/" . $request->action());
        }
        //超级管理员，直接返回
        if (UID === IS_ROOT) {
            return true;
        }

        //获取当前菜单的id
        $rule_id = Db::table('menu')->where('url', $url)->value('id');

        //获取当前登录用户所在的用户组(可以是多组)
        $groups = Db::table('auth_group_access')->where('mgid', UID)->column('group_id');
        if (!$groups) {
            return $this->error("没有权限");
        }

        //所有权限数组
        $rules_array = [];
        $arr         = [];
        foreach ($groups as $v) {
            $rules = Db::table('auth_group')->where('id', $v)->where('status', 1)->value('rules');
            if ($rules) {
                $arr = explode(',', $rules);
            }

            $rules_array = array_merge($rules_array, $arr);
        }
        //去除重复值
        $rules_array = array_unique($rules_array);
        // //权限判断
        // if (!in_array($rule_id, $rules_array)) {
        //     return $this->error("没有权限");
        // }

    }

}

<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/19
 * Time: 15:57
 */

namespace app\admin\controller;


class Shop extends Common
{
    public function index () {
        dump(session('admin_user_auth'));die;
    }

    public function getKeysList () {
        $list = model('DiyKeys')->where('status',1)->select();
        return json($list);
    }

    public function editShop () {

    }
}
<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/19
 * Time: 11:49
 */

namespace app\api\controller;


class Shop extends Common
{
    public function index () {
        dump(session('admin_user_auth'));die;
    }
}
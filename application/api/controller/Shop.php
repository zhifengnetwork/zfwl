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
//        dump(123);die;
    }

    public function getKeysList () {
        $list = model('DiyKeys')->where('status',1)->select();
        return json($list);
    }
}
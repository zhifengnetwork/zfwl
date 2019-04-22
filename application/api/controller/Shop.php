<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/19
 * Time: 11:49
 */

namespace app\api\controller;


class Shop extends ApiBase
{
    public function index () {
        dump(session('admin_user_auth'));die;
    }

    public function getShopData () {

        $res = model('DiyEweiShop')->getShopData();
        if (!empty($res)){
            return json(['code'=>1,'msg'=>'','data'=>$res]);
        }else{
            return json(['code'=>0,'msg'=>'没有数据，请添加','data'=>'']);
        }
    }

    public function gooodsList () {
        $keyword = request()->param('keyword','');
        $cat_id = request()->param('cat_id',0,'intval');
        $list = model('Goods')->getGoodsList($keyword,$cat_id);
        if (!empty($list)){
            return json(['code'=>1,'msg'=>'','data'=>$list]);
        }else{
            return json(['code'=>0,'msg'=>'没有数据哦','data'=>$list]);
        }
    }
}
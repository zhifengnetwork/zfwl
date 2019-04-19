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
    public function _initialize () {
        parent::_initialize();
        $info = session('admin_user_auth');
        $this->admin_id = $info['mgid'];
    }
    public function index () {

    }

    public function getKeysList () {
        $list = model('DiyKeys')->where('status',1)->select();
        return json($list);
    }

    public function editShop () {
        if (request()->isPost()){
            $data = request()->param('data');
            if (!empty($data)){
                $res = model('DiyEweiShop')->edit($data,$this->admin_id);
                if ($res){
                    return json(['code'=>1,'msg'=>'保存成功']);
                }else{
                    return json(['code'=>0,'msg'=>'保存失败']);
                }

            }else{
                return json(['code'=>0,'msg'=>'首页不能为空，请您添加组件']);
            }
        }
    }

    public function getShopData () {

        $res = model('DiyEweiShop')->getShopData($this->admin_id);
        if (!empty($res)){
            return json(['code'=>1,'msg'=>'','data'=>$res]);
        }else{
            return json(['code'=>0,'msg'=>'没有数据，请添加','data'=>'']);
        }
    }
}
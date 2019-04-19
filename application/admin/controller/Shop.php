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
        $where = [];
        $where = ['status'=>1,'admin_id'=>$this->admin_id];
        $getData = model('DiyEweiShop')->where($where)->find();
        if (!empty($getData)){
            if (!empty($getData['data'])){
                $data = json_decode($getData['data']);
                $res = towArraySort($data,'key_num');
                dump($res);
            }
        }else{
            return json(['code'=>0,'msg'=>'没有数据，请添加']);
        }
    }
}
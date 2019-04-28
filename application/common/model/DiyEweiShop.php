<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/19
 * Time: 14:58
 */

namespace app\common\model;

use think\helper\Time;
use think\Model;

class DiyEweiShop extends Model
{
    protected $table = 'diy_ewei_shop';

    public function edit ($data,$admin_id,$page_name,$id = 0) {

        if (!empty($id)){
            $where = [];
            $where['id'] = $id;
            $where['status'] >= 0;
            $find = $this->where($where)->find();
        }
        try{
            if (!empty($find)){
                //修改
                $res_data['page_name'] = $page_name;
                $res_data['update_time'] = time();
                $res_data['data'] = json_encode($data);
                $res_data['last_data'] = $find['data'];
                $res = $this->where('id',$find['id'])->update($res_data);
            }else{
                //添加
                $res_data['admin_id'] = $admin_id;
                $res_data['page_name'] = $page_name;
                $res_data['page_type'] = 1;
                $res_data['status'] = 0;
                $res_data['add_time'] = time();
                $res_data['data'] = json_encode($data);
                $res = $this->insert($res_data);
            }
            if ($res){
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e){
            return false;
        }
    }

    public function getShopData ($id = 0) {
        $where = [];
        if (!empty($id)){
            $where['id'] = $id;
            $where['status'] >= 0;
        }else{
            $where['status'] = 1;
        }
        $getData = $this->where($where)->find();
        if (!empty($getData)){
            if (!empty($getData['data'])){
                $data = json_decode($getData['data']);
                $res = towArraySort($data,'key_num');
                if ($res){
                    return $res;
                }else {
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
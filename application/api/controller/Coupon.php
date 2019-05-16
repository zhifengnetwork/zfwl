<?php
namespace app\api\controller;
use think\Db;

class Coupon extends ApiBase
{   

    public function get_coupon(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $coupon_id = input('coupon_id');
        if(!$coupon_id) $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！']);

        $time = time();
        
        $where['coupon_id'] = $coupon_id;
        $where['start_time'] = ['<', $time];
        $where['end_time'] = ['>', $time];

        $coupon = Db::table('coupon')->where($where)->find();
        if(!$coupon) $this->ajaxReturn(['status' => -2 , 'msg'=>'该优惠券已过期！']);

        $where = [];
        $where['user_id'] = $user_id;
        $where['coupon_id'] = $coupon_id;

        $res = Db::table('coupon_get')->where($where)->find();
        if($res) $this->ajaxReturn(['status' => -2 , 'msg'=>'您已领取过，请勿重复领取！']);

        $where['add_time'] = $time;
        $res = Db::table('coupon_get')->insert($where);

        if($res) $this->ajaxReturn(['status' => 1 , 'msg'=>'领取成功！' ,'data'=>'']);
    }

    public function my_coupon(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $type = input('type');
        if($type != 1 && $type != 2 && $type != 3 ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
        }

        $where['user_id'] = $user_id;
        if( $type == 1 ){
            $where['start_time'] = ['<', time()];
            $where['end_time'] = ['>', time()];
            $where['is_use'] = 0;
        }else if( $type == 2 ){
            $where['is_use'] = 1;
        }else if( $type == 3 ){
            $where['end_time'] = ['<', time()];
            $where['is_use'] = 0;
        }

        $data = Db::table('coupon_get')->where($where)->select();
        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>$data]);
    }


}

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
        $where['start_time'] = ['<', strtotime($time)];
        $where['end_time'] = ['>', strtotime($time)];

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
}

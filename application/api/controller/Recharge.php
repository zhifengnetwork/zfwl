<?php

/***
 * 充值api
 */
namespace app\api\controller;
use app\api\controller\TestNotify;
use Payment\Common\PayException;
use Payment\Notify\PayNotifyInterface;
use Payment\Notify\AliNotify;

use \think\Model;
use \think\Config;
use \think\Db;
use \think\Env;
use \think\Request;

class Recharge extends ApiBase
{
    /**
     * 充值商品
     */

    public function good(){

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2, 'msg'=>'用户不存在','data'=>'']);
        }
        $good_list  =  Db::name('recharge_good')->where(['state' => 1])->select();

        $this->ajaxReturn(['status' => -2 , 'msg'=>'充值商品','data'=>$good_list]);


        


    }


    /***
     * 充值接口
     */
    public function pay(){
        $user_id = $this->get_user_id();//用户ID
        if(!$user_id){
            $this->ajaxReturn(['status' => -2, 'msg'=>'用户不存在','data'=>'']);
        }
        $recharge_type  = input('recharge_type',3); //2微信 3支付宝
        $good_id        = input('id',0);//商品ID
      
        if($good_id > 0){
            // 判断商品组是否存在
             $good = Db::name(['id' => $id, 'state' => 1])->find();
            !$good && $this->ajaxReturn(['status' => -2, 'msg'=>'商品不存在或者已失效','data'=>'']);
            $amount = $good['total_amount'];
        }else{
            $amount         = input('amount', 0);//自选金额
            //判断金额是否存在
            $data           = [];
            $data['amount'] = $amount;
    
            $rule = [
                'amount|金额' => '>:0|<:1000000',
            ];
            $validate = new Validate($rule);
            $result   = $validate->check($data);
            if (!$result) {
                $this->ajaxReturn(['status' => -2, 'msg'=>$validate->getError(),'data'=>'']);
            }
        }
        $order_sn = date('YmdHis',time()) . mt_rand(10000000,99999999);

        $data = [
            'order_sn' => $order_sn,
            'user_id'  => $user_id,
            'source'   => $pay_type,
            'status'   => 0,
            'create_time' => time(),
        ];
        // 启动事务
        Db::startTrans();

        $res = Db::name('recharge_order')->insert($data);

        if (!$res) {
             Db::rollback();
            $this->ajaxReturn(['status' => -2, 'msg'=>'充值失败','data'=>'']);
        }

        // 提交事务
        Db::commit();


        

        

        // if(){

        // }
        

        
       
        
           
    }

}
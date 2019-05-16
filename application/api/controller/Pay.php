<?php

/***
 * 支付api
 */
namespace app\api\controller;
use app\api\controller\TestNotify;

use Payment\Common\PayException;
use Payment\Notify\PayNotifyInterface;
use Payment\Notify\AliNotify;
use Payment\Client\Charge;
use Payment\Client\Notify;
use Payment\Config as PayConfig;
use app\common\model\Member as MemberModel;
use app\common\model\Order;

use \think\Model;
use \think\Config;
use \think\Db;
use \think\Env;
use \think\Request;

class Pay extends ApiBase
{
     /**
     * 析构流函数
     */
    public function  __construct() {           
        require_once ROOT_PATH.'vendor/riverslei/payment/autoload.php';
    }    

    /***
     * 支付
     */
    public function payment(){
        $order_id     = input('order_id',1413);
        $pay_type     = input('pay_type',3);//支付方式
        $user_id      = 51;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $order_info   = Db::name('order')->where(['order_id' => $order_id])->find();//订单信息
        $member       = MemberModel::get($user_id);
        //验证是否本人的
        if(!$order_info){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>'']);
        }
        if($order_info['user_id'] != $user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>'']);
        }

    	if($order_info['pay_status'] == 1){
			$this->ajaxReturn(['status' => -4 , 'msg'=>'此订单，已完成支付!','data'=>'']);
    	}
        // $sysset       = Db::name('sysset')->find();
        // $config       = unserialize($sysset['sets']);
        $amount       = $order_info['order_amount'];
        $client_ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $payData['order_no']        = $order_info['order_sn'];
        $payData['body']            = getPayBody($order_id);
        $payData['timeout_express'] = time() + 600;
        $payData['amount']          = $order_info['order_amount'];
        if($pay_type == 3){
            $payData['subject']      = '支付宝支付';
            $payData['goods_type']   = 1;
            $payData['return_param'] = '';
            $payData['store_id']     = '';
            $payData['quit_url']     = '';
        }elseif($pay_type == 2){
            $payData['subject']      = '微信支付';
            $payData['openid']       = $order_info['openid'];
            $payData['product_id']   = '';
            $payData['sub_appid']    = '';
            $payData['sub_mch_id']   = '';
        }elseif($pay_type == 1){
            $balance_info  = get_balance($user_id,0);
            if($balance_info['balance'] < $order_info['order_amount']){
                $this->ajaxReturn(['status' => 0 , 'msg'=>'余额不足','data'=>'']);
            }
            //扣除用户余额
            $balance = [
                'balance'            =>  Db::raw('balance-'.$amount.''),
            ];
            Db::table('member_balance')->where(['user_id' => $user_id,'balance_type' => 0])->update($balance);
            
            //修改订单状态
            $update = [
                'order_status' => 1,
                'pay_status'   => 1,
                'pay_type'     => $pay_type,
                'pay_time'     => time(),
            ];
            $reult = Order::where(['order_id' => $order_id])->update($update);
            if($reult){
                $this->ajaxReturn(['status' => 1 , 'msg'=>'余额支付成功!','data'=>'']);
            }else{
                $this->ajaxReturn(['status' => 0 , 'msg'=>'余额支付失败','data'=>'']);
            }
        }
              $pay_config = Config::get('pay_config');
            try {
                $url = Charge::run(PayConfig::ALI_CHANNEL_WAP, $pay_config, $payData);
                // $this->ajaxReturn(['status' => 1 , 'msg'=>'请求路径','data'=> $url]);
            } catch (PayException $e) {
               $this->ajaxReturn(['status' => 0 , 'msg'=>$e->errorMessage(),'data'=>'']);
               exit;
            }
            header('Location:' . $url);
           

    } 
    public function alipay_notify(){
                $callback   = new TestNotify();
                $type       = 'ali_charge';
                $pay_config = Config::get('pay_config');

                $retData = Notify::getNotifyData($type, $pay_config);// 获取第三方的原始数据，未进行签名检查
                file_put_contents('log123456.php', var_export($retData , true)); 
        
                $ret = Notify::run($type, $pay_config, $callback);// 处理回调，内部进行了签名检查
                file_put_contents('log12345679.php', var_export(13123, true)); 
                file_put_contents('log12345678.php', var_export($ret, true)); 
    }





    /**
     * 微信支付完成回调
     */
    public function notify()
    {
        $weixin_pay_arr = Config::get('th_wx_config');
        $app_id         = $weixin_pay_arr['appid'];
        $app_key        = $weixin_pay_arr['appsecret'];
        $mch_id         = $weixin_pay_arr['mch_id'];
        $mch_key        = $weixin_pay_arr['mch_key'];

        $notify = new Notify($app_id, $app_key, $mch_id, $mch_key);

        // 支付校验失败
        $transaction = $notify->verify();
        if (!$transaction) {
            echo $notify->reply('FAIL', 'verify transaction error');
            return;
        }
        $wechat_result = $transaction->toArray();
        $order_no      = $wechat_result['out_trade_no'];
     
        //使用统一的日志函数
        pft_log('wxpay_notify', json_encode([$wechat_result]), 'month');
        // 判断是否已经处理过(微信可能通知多次)并获取agent_id用于通知
        $has_order = Db::table('user_order')->alias('uo')
        ->join('machine m', 'uo.machine_id = m.machine_id', 'LEFT')
        ->join('place p', 'm.place_id = p.place_id', 'LEFT')
        ->field('uo.order_id, uo.amount, uo.service_name,uo.good_id, uo.good_name, uo.good_price, uo.paid, uo.end_price, uo.order_time, uo.order_amount, uo.subject, uo.body, uo.uid, uo.wx_openid, uo.cst_id, m.mac, m.gw_did, m.machine_id, m.rm_id, p.area, p.channel_id, p.agent_id, p.place_id, p.pf_div, p.channel_div, p.agent_div, p.place_div,m.factory_id,p.place_name,m.platform_id,uo.create_time,m.app_protocol_type,m.machine_width,m.machine_type')
        ->where('uo.order_no', $order_no)
        ->find();
        if (!$has_order) {
            echo   $notify->reply('FAIL', 'no this transaction');
            return;
        } else if ($has_order['paid']) {
            echo   $notify->reply();
            return;
        }
      
        $end_price = $has_order['order_amount']*$has_order['place_div'];
        $order = [
            'area_id'        => $has_order['area'], //支付完成时设置更精准
            'channel_id'     => $has_order['channel_id'], //支付完成时设置更精准
            'agent_id'       => $has_order['agent_id'], //支付完成时设置更精准
            'place_id'       => $has_order['place_id'], //支付完成时设置更精准
            'mac'            => $has_order['mac'], //支付完成时设置更精准
            'pf_div'         => $has_order['pf_div'],
            'channel_div'    => $has_order['channel_div'],
            'agent_div'      => $has_order['agent_div'],
            'place_div'      => $has_order['place_div'],
            'paid'           => 1,
            'time_paid'      => time(),
            'transaction_id' => $order_no,
            'end_price'      => $end_price,//主账户最终收益
        ];
        
        $ress  = Db::table('user_order')->where(['order_no' => $order_no])->update($order);
        $censuso = [
            'agent_id'     => $has_order['agent_id'],
            'order_id'     => $has_order['order_id'],
            'place_id'     => $has_order['place_id'],
            'order_amount' => $has_order['order_amount'],
            'amount'       => $end_price,
            'division'     => $has_order['place_div'],
            'create_time'  => $has_order['create_time'],
            'order_type'   => 1,
       ];
       Db::table('agent_census')->insert($censuso);
        //记录收益统计
        $total_order = [
            'agent_id'       => $has_order['agent_id'], //商户ID
            'order_no'       => $order_no, //订单号
            'place_id'       => $has_order['place_id'], 
            'machine_id'     => $has_order['machine_id'], 
            'order_id'       => $has_order['order_id'],
            'place_name'     => $has_order['place_name'],
            'order_time'     => $has_order['order_time'],
            'order_amount'   => $has_order['order_amount'],
            'good_name'      => $has_order['good_name'],
            'good_price'     => $has_order['good_price'],
            'order_type'     => 1,
            'pay_type'       => 0,
            'service_name'   => $has_order['service_name'],
            'end_price'      => $end_price,//主账户最终收益
            'create_time'    => time(),
        ];
        Db::table('total_order')->insert($total_order);
        //商户总收益和余额
        $rema = [
            'order_num'         =>  ['exp', 'order_num+1'],
            'remainder'         =>  ['exp', 'remainder+'.$end_price.''],
            'profit'            =>  ['exp', 'profit+'.$end_price.''],
        ];
         Db::table('agent')->where(['agent_id'=>$has_order['agent_id']])->update($rema);

        //商户今日收益
        
         $todaywhere['agent_id']    =    $has_order['agent_id'];
         $todaywhere['create_time'] =    strtotime(date("Y-m-d"));
         $today_info = Db::table('today_profit')->where($todaywhere)->find();
         if($today_info){
            $todayprofit = [
                'profit'            =>  ['exp', 'profit+'.$end_price.''],
            ];
            // Db::table('agent')->where(['agent_id' => $has_order['agent_id']])->update($todayprofit);
            Db::table('today_profit')->where($todaywhere)->update($todayprofit);
         }else{
            $todaywhere['profit'] = $end_price;
            Db::table('today_profit')->insert($todaywhere);
         }

         //子账号今日收益
         $accountwhere['account_id'] = $has_order['agent_id'];
         $accountlist=Db::table('agent')->field('agent_id,division,profit,remainder')->where($accountwhere)->select();
        if(count($accountlist)>0){
            foreach($accountlist as $v){
                $perplace    = Db::table('user_permission')->where('agent_id',$v['agent_id'])->value('place_id');
                $perplacearr = explode(',',$perplace);
                    if(in_array($has_order['place_id'],$perplacearr)){
                        
                        $profit = $has_order['order_amount']   *    $v['division'];
                        $todayaccount['agent_id']              =    $v['agent_id'];
                        $todayaccount['create_time']           =    strtotime(date("Y-m-d"));
                        $remass = [
                            'order_num'                        =>  ['exp', 'order_num+1'],
                            'remainder'                        =>  ['exp', 'remainder+'.$profit.''],
                            'profit'                           =>  ['exp', 'profit+'.$profit.''],
                        ];
                        Db::table('agent')->where(['agent_id' => $v['agent_id']])->update($remass);
                        $census = [
                            'amount'       => $profit,
                            'agent_id'     => $v['agent_id'],
                            'order_id'     => $has_order['order_id'],
                            'place_id'     => $has_order['place_id'],
                            'order_amount' => $has_order['order_amount'],
                            'division'     => $v['division'],
                            'create_time'  => $has_order['create_time'],
                            'order_type'   => 1,
                       ];
                        Db::table('agent_census')->insert($census);
                        $account_res = Db::table('today_profit')->where($todayaccount)->find();
                        if($account_res){
                            $todayprofitacc = [
                                'profit'          =>  ['exp', 'profit+'.$profit.''],
                            ];
                            Db::table('today_profit')->where($todayaccount)->update($todayprofitacc);
                         }else{
                            $todayaccount['profit'] = $profit;
                            Db::table('today_profit')->insert($todayaccount);
                         }
                        $remainder=[
                            'account_id'  => $v['agent_id'],
                            'agent_id'    => $has_order['agent_id'],
                            'price'       => $profit,
                            'order_id'    => $has_order['order_id'],
                            'platform_id' => $has_order['platform_id'],
                            'create_time' => time()
                        ];
                        Db::table('account_remainder')->insert($remainder);
                    }
             
            }
        }

        Db::table('account_remainder')->insert(['agent_id'=>$has_order['agent_id'],'account_id'=>$has_order['agent_id'],'order_id'=> $has_order['order_id'], 'create_time' => time(),'platform_id' => $has_order['platform_id'],'price'=> $end_price,]);
        //设备启动       
        Start::start_machine($has_order['machine_id'],$has_order['order_time'],0,false);
  
  
        $timeend =$time_min*60+time();
        $machineup = [
            'work_time'         =>  ['exp', 'work_time+'.$has_order['order_time'].''],
            'work_count'        =>  ['exp', 'work_count+1'],
            'profit'            =>  ['exp',  'profit+'.$has_order['order_amount'].''],
            'state'             =>  3,
            'work_endtime'      =>  $timeend,
        ];
        Db::table('machine')->where(['machine_id'=>$has_order['machine_id']])->update($machineup);
      
        $res = true;
        //记录控制的结果
        $controlData = [
            'machine_id'  => $has_order['machine_id'],
            'mac'         => $has_order['mac'],
            'order_id'    => $has_order['order_id'],
            'uid'         => $has_order['uid'],
            'wx_openid'   => $has_order['wx_openid'],
            'is_auto'     => 1,
            'event'       => 1, //控制启动/叠加
            'retcode'     => $res ? 0 : 1101,
            'create_time' => time(),
        ];
        Db::table('machine_control_log')->insert($controlData);
        $orderData = [
            'use_time' => time(),
            'cmd_sent_count' => 1,
            'cmd_sent_time'  => time(),
        ];
        //默认控制成功
        if($has_order['machine_type'] == 2 || $has_order['app_protocol_type'] == 1){

        }else{
            $order['is_used']   = 1;
        }
       
        Db::table('user_order')->where(['order_no' => $order_no])->update($orderData);
    

        //报表统计
        StatisService::addStatisAsynchTask($has_order['order_id']);
        //咪小二分润
        if ($has_order['cst_id']) {
            $couponModel = new CouponModel();
            $couponInfo  = $couponModel->getCouponReceiveInfo($has_order['cst_id'], 'suid');

            if (isset($couponInfo['suid']) && $couponInfo['suid'] > 0 ) {
                $taskExecuteModel = new TaskExecuteModel();
                $params = json_encode([$order_no, $has_order['order_amount'] * 100, $has_order['cst_id']]);
                $taskExecuteModel->addTask('100011', $params);
            }
        }

        // 获取需要通知的人并发送通知
        $openid_list = Db::table('agent_notice_config')
        //->where('agent_id', ['=',0], ['=',$has_order['agent_id']], 'OR')
            ->where('(all_agent=1 OR agent_id=' . $has_order['agent_id'] . ' OR place_id=' . $has_order['place_id'] . ')')
            ->where('event', 1)
            ->column('wx_openid');
        if ($openid_list) {
            $title  = '您有一位客户付款成功！ (' . str_replace(' - ' . $has_order['subject'], '', $has_order['body']) . ')';
            $name   = $has_order['subject'];
            $amount = $has_order['order_amount'];
            $time   = $order['time_paid'];
            $remark = '点击进入平台查看更多信息';
            $i      = 0;
            foreach ($openid_list as $openid) {
                if (++$i > 1) {
                    usleep(50000);
                }

                $this->_notice_agent($openid, $title, $name, $amount, $time, $remark);
            }
        }

        // 应答微信
        echo $notify->reply();
    }



    private function _notice_agent($openid, $title = '您有一位客户付款成功！', $name = '零钱微SPA', $amount, $time, $remark = '')
    {
        // 发送客户付款成功模板消息
        try {
            $appid     = Config::get('biz_wx_config.appid');
                        $appsecret = Config::get('biz_wx_config.appsecret');
            $notice    = new Notice($appid, $appsecret);

            $template = Config::get('biz_wx_tmplmsg.pay_success');
            $url      = url('admin/order/index', '', true, true);
            $color    = '#FF0000';
            $data     = array(
                'first'    => [$title . "\n", '#078610'],
                'keyword1' => [$name, '#014D79'], //名称
                'keyword2' => ['￥' . $amount, '#014D79'], //金额
                'keyword3' => [date('Y-m-d H:i:s', $time), '#014D79'],
                'remark'   => [$remark ? "\n" . $remark : '', '#014D79'],
            );

            $messageId = $notice->to($openid)->template($template)->data($data)->url($url)->color($color)->send();
        } catch (Exception $e) {
        } catch (\Exception $e) {
        }
    }

}

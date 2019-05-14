<?php

/***
 * 支付api
 */
namespace app\api\controller;
use Payment\Common\PayException;
use Payment\Client\Charge;

use \think\Config;
use \think\Db;
use \think\Env;
use \think\Request;

class Pay extends Common
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
        $order_id     = input('order_id','');
        $pay_type     = input('pay_type','ali');//支付方式
        $order_info   = Db::name('order')->where(['order_id' => $order_id])->find();//订单信息
        $sysset       = Db::name('sysset')->find();
        $config       = unserialize($sysset['sets']);
        $body         = getPayBody($order_id);
        $payData      = [
            'body'            => $body,
            'subject'         => '支付宝支付',
            'order_no'        => $order_info['order_sn'],
            'timeout_express' => time() + 60,// 表示必须 600s 内付款
            'amount'          => $order_info['order_amount'],// 单位为元 ,最小为0.01
            'return_param'    => '',// 一定不要传入汉字，只能是 字母 数字组合
            // 'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// 客户地址
            'goods_type'      => '1',// 0—虚拟类商品，1—实物类商品
            'store_id'        => '',
            'quit_url'        => 'http://helei112g.github.io', // 收银台的返回按钮（用户打断支付操作时返回的地址,4.0.3版本新增）
        ];

        $orderNo  = time() . rand(1000, 9999);
        $refundNo = time() . rand(1000, 9999);
        if($type == 'ali'){
            // $payData = [
            //     'body'           => 'ali wap pay',
            //     'subject'        => '测试支付宝手机网站支付',
            //     'order_no'        => $orderNo,
            //     'timeout_express' => time() + 600,// 表示必须 600s 内付款
            //     'amount'          => '0.5',// 单位为元 ,最小为0.01
            //     'return_param'   => 'tata',// 一定不要传入汉字，只能是 字母 数字组合
            //     // 'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// 客户地址
            //     'goods_type'     => '1',// 0—虚拟类商品，1—实物类商品
            //     'store_id'      => '',
            //     'quit_url'     => 'http://helei112g.github.io', // 收银台的返回按钮（用户打断支付操作时返回的地址,4.0.3版本新增）
            // ];

            $payData = [
                'out_trade_no' => '15578313955571',
                'trade_no'     => '',// 支付宝交易号， 与 out_trade_no 必须二选一
                'refund_fee'   => '0.5',
                'reason'       => '我要退款',
                'refund_no'    => $refundNo,
            ];
            
           
            
            //write_log('order.txt',array('order_id' => $refundNo));
            //  write_log('refund.txt',array('order_id' => $refundNo));


            
            // $payData = [
            //     'trans_no' => time(),
            //     'payee_type' => 'ALIPAY_LOGONID',
            //     'payee_account' => '13226785330',// ALIPAY_USERID: 2088102169940354      ALIPAY_LOGONID：aaqlmq0729@sandbox.com
            //     'amount' => '0.1',
            //     'remark' => '转账拉，有钱了',
            //     'payer_show_name' => '一个未来的富豪',
            // ];

            $aliConfig =  [
                'use_sandbox'               => false,// 是否使用沙盒模式
            
                'app_id'                    => '2019050264367537',
                'sign_type'                 => 'RSA2',// RSA  RSA2
            
                // ！！！注意：如果是文件方式，文件中只保留字符串，不要留下 -----BEGIN PUBLIC KEY----- 这种标记
                // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
                'ali_public_key'            => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjaw+zIeq9IU07mw62+q1xVHGxrUpyGPWchp6oJQIoKx+odn8mAvi8yZvA/idj9cjVJ9Uzv+0isaOSJoI7p19ER9wbDvmvtXDo+bWfPGNRnZTyxzrfRD9PVNvxAyVw+rnCfbG9VhV3mYll0edlCRXJJYJNhf/9jQnTBxmMpZRa0SdH2IxdcDgkf7eFJUzTZudR9oW1zvFcZjV+GVQ8vAenYTNHzWsv21I9o1ErvP0OOb2UGx+DpEW+MEjbYFXHoyqaoUnbGo2HpCkx9LliAehSgrrPKSsHukpQj9A4VRfES5sNQM5nD2ygF4hOFWsxG8E7EXNzCerxTRHjgCsRfZ43QIDAQAB',
            
                // ！！！注意：如果是文件方式，文件中只保留字符串，不要留下 -----BEGIN RSA PRIVATE KEY----- 这种标记
                // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
                'rsa_private_key'           => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCNrD7Mh6r0hTTubDrb6rXFUcbGtSnIY9ZyGnqglAigrH6h2fyYC+LzJm8D+J2P1yNUn1TO/7SKxo5ImgjunX0RH3BsO+a+1cOj5tZ88Y1GdlPLHOt9EP09U2/EDJXD6ucJ9sb1WFXeZiWXR52UJFcklgk2F//2NCdMHGYyllFrRJ0fYjF1wOCR/t4UlTNNm51H2hbXO8VxmNX4ZVDy8B6dhM0fNay/bUj2jUSu8/Q45vZQbH4OkRb4wSNtgVcejKpqhSdsajYekKTH0uWIB6FKCus8pKwe6SlCP0DhVF8RLmw1AzmcPbKAXiE4VazEbwTsRc3MJ6vFNEeOAKxF9njdAgMBAAECggEAVhak0oReTdfkIj2CRsCJVC4tK/JKQYrpdMzCV3GdDIXFLXTZGUufzUE9lJwuoomI3pMzZdXcT7f4HgX8B4OLzCvelOaRgMVE7QQIskPWJUsh//rC3mzEdc+NywQavcKwQk3C+LOE+m/3x8Ws66hpi8HgNw6+a02l04ouT+8n6pYM1f+4Vy7Fb4LdDEkzKDkvtxj+czXDp/1gnRExvqpMkK9iwFfmvO4rO8Ubw+URjIX6vuIZytbnZqT1jpJNfmvigWPplOdRiyVk5YVtBKNAQYSlk8y6Tiftq9VuJEmRuSs3ohoxS9h128Fbze2/oYDtwLGZKXh2D6GivE2Xz9CGAQKBgQDezHVMJdNjIdO0iUh8oW0li9NOYkl1Bsk4ICKPiKWTEx5OT/qbvO/VZskUaHauWgwhMN12wkM6UI0tBonO/ZOON7Fh/5bQCC5QKHtN//EHz932sKyO/qHgCiWzib30jSZ5XtBx8ClPiKfDIu+UaZXlgCLgoi0fxcfyvWQXL7mDmQKBgQCiyOtdF1Alt0oKSE1v5qARmZ4ldb4FWWwaVQJgocEP11wwIQhq3y4ZgHBrDJP35ec7Hyrlr1i/+hjVd3nEtEzt1F4Jj0aNXHwlch+fsfKR1eN5EcP6zmS6idj/239w+lTGN0eIylvBW/J811E7VF1lWgnesYcmOXxCG+XJdJpp5QKBgDj1xqtAJGn8tPY7/tc2IgRuWgh5IlST9o+tz4gopEQUqDPXSLfWNu61B4V7K5Rpmx5FMulwwuU+wMkZGdRcigPbAzONt43Z+ZUutE99tq6LmzC9fHBWcyYnEfpzpafHCmYPMnVetAEMa+98mAm2cMcq2j/Z1nWACB1sBBHVdrVJAoGBAIkNUiPRQfhPJfYcQ54n9LJ8vIpbZD3KuNo+oj7LUOlOb15SIW0hNAXifkOSlm3LUXAUYKB6jeUr4oavDYVQK8i82OOBjmvr5tX8DKX+QvUHuHmxPGhIJsRq1Jktq1FqYb90wTRo8vGLwU/cVJb4A54WPWMR4nCLS5O5OzDujCcFAoGAARLKIuYqRdyemWv2LaHCvVd+r9T8uyzRJK/udX8hb6mfScCyQeLsMj6oavlWioTGVDZb1zHjd8Uo64buGyXsBqzmgQXPtt3w3Vgs+WvfpUCzSCsPalhSsCaPKRvxYtoxZ/HepwegX77aT3sT6sERIEksl9wyClV5Q3mqP5JYI8k=',
            
                'limit_pay'                 => [
                    //'balance',// 余额
                    //'moneyFund',// 余额宝
                    //'debitCardExpress',// 	借记卡快捷
                    //'creditCard',//信用卡
                    //'creditCardExpress',// 信用卡快捷
                    //'creditCardCartoon',//信用卡卡通
                    //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
                ],// 用户不可用指定渠道支付当有多个渠道时用“,”分隔
            
                // 与业务相关参数
                'notify_url'                => 'https://helei112g.github.io/v1/notify/ali',
                'return_url'                => 'https://helei112g.github.io/',
            
                'return_raw'                => false,// 在处理回调时，是否直接返回原始数据，默认为 true
            ];

        }else{



        }
       

      
       
        // try {
        //     $url = Transfer::run(Config::ALI_TRANSFER, $aliConfig, $payData);
        // } catch (PayException $e) {
        //     echo $e->errorMessage();
        //     exit;
        // }

        try {
            $ret = Refund::run(Config::ALI_REFUND, $aliConfig, $payData);
        } catch (PayException $e) {
            echo $e->errorMessage();
            exit;
        }

        // try {
        //     $url = Charge::run(Config::ALI_CHANNEL_WAP, $aliConfig, $payData);
        // } catch (PayException $e) {
        //     echo $e->errorMessage();
        //     exit;
        // }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);

        // header('Location:' . $url);
       
        // $str = Charge::run();
        // die;
    } 











    /**
     * 微信支付原生charge
     */
    public function charge()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $order_id = input('order_id/d',0);
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>'']);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>'']);
        }

        $pay_code    = input('pay_code/s','weixinH5');
        
    	if($order['pay_status'] == 1){
			$this->ajaxReturn(['status' => -4 , 'msg'=>'此订单，已完成支付!','data'=>'']);
    	}
        
        //获取微信支付配置
        $weixin_pay_arr = Config::get('th_wx_config');
        $app_id     = $weixin_pay_arr['appid'];
        $app_key    = $weixin_pay_arr['appsecret'];
        $mch_id     = $weixin_pay_arr['mch_id'];
        $mch_key    = $weixin_pay_arr['mch_key'];
        $notify_url = $weixin_pay_arr['notify_url'];
        $trade_type = $weixin_pay_arr['trade_type'];
        //第 1 步：定义商户
        $business = new Business($app_id, $app_key, $mch_id, $mch_key);
        //第 2 步：定义订单
        $order               = new Order();
        $order->body         = $goods['desc'];
        $order->out_trade_no = $out_trade_no;

        //配送单结算要点-付款需要扣除退款金额
        $order->total_fee  = $service['price'] * 100;
        $order->openid     = $openid;
        $order->notify_url = $notify_url;
        $order->trade_type = $trade_type;
        try {
            //第 3 步：统一下单
            $unifiedOrder = new UnifiedOrder($business, $order);
            //第 4 步：生成支付配置文件
            $payment = new Payment($unifiedOrder);
            $payment->getConfig();
        } catch (Exception $e) {
            $this->ajaxReturn(['status' => 0, 'info' => '支付请求失败:' . $e->getMessage()]);
        } catch (\Exception $e) {
            $this->ajaxReturn(['status' => 0, 'info' => '支付请求失败:' . $e->getMessage()]);
        }
        $this->ajaxReturn(['status' => 1, 'paytype' => $paytype , 'order_no' => $out_trade_no, 'info' => $payment->getConfig()]);
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

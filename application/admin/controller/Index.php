<?php
namespace app\admin\controller;
use Payment\Common\PayException;
use Payment\Client\Charge;
use Payment\Client\Transfer;
use Payment\Client\Refund;

use Payment\Config;
use think\Loader;
use think\Request;
use think\Db;

/**
 * 首页
 */
class Index extends Common
{
    public function index()
    { 
        require_once ROOT_PATH.'vendor/riverslei/payment/autoload.php';
        $type     = input('type','ali');
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
                'trade_no' => '',// 支付宝交易号， 与 out_trade_no 必须二选一
                'refund_fee' => '0.5',
                'reason' => '我要退款',
                'refund_no' => $refundNo,
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
        // $where= [];
        // $where['status'] = ['>=',0];
        // $list       = Db::table('diy_ewei_shop')
        //             ->where($where)
        //             ->order('id')
        //             ->paginate(10, false, ['page' => request()->param('page')]);
        // $this->assign('list', $list);
        // $this->assign('meta_title', '店铺装修');
        // return $this->fetch();
    }


    public function page_edit(){
        $id  = request()->param('id',0,'intval');
        $this->assign('id',$id);
        $this->assign('meta_title', '页面编辑');
        return $this->fetch();
    }


    public function page_add(){
        $this->assign('meta_title', '页面新增');
        return $this->fetch();
    }

    public function status(){
        
    }

    public function page_enable () {
        $id = request()->param('id',0,'intval');
        $status = request()->param('status',0,'intval');
        if (!empty($id)){
            $getPage = model('DiyEweiShop')->where(['id'=>$id])->find();
            if (!empty($getPage)){
                if ($getPage['status'] == $status){
                    if ($status == 0){
                        return json(['code'=>0, 'msg'=>'该页面已经是禁用了','data'=>[]]);
                    }else{
                        return json(['code'=>0, 'msg'=>'该页面已经是启用了','data'=>[]]);
                    }
                }else{
                    if ($status == 1){
                        $getThisEnablePage = model('DiyEweiShop')->where(['status'=>1])->find();
                        if (!empty($getThisEnablePage)){
                            model('DiyEweiShop')->where(['id'=>$getThisEnablePage['id']])->update(['status'=>0]);
                        }
                    }
                    $updateThisPage = model('DiyEweiShop')->where(['id'=>$id])->update(['status'=>$status]);
                    if ($updateThisPage){
                        return json(['code'=>1, 'msg'=>'操作成功','data'=>[]]);
                    }else{
                        return json(['code'=>0, 'msg'=>'操作失败','data'=>[]]);
                    }
                }
            }else{
                return json(['code'=>0, 'msg'=>'页面不存在！','data'=>[]]);
            }
        }else{
            return json(['code'=>0, 'msg'=>'id不存在','data'=>[]]);
        }
    }

    public function page_delete () {
        $id = request()->param('id',0,'intval');
        if (!empty($id)) {
            $getPage = model('DiyEweiShop')->where(['id' => $id])->find();
            if (!empty($getPage)){
                $delete = model('DiyEweiShop')->where(['id'=>$id])->update(['status'=>-1]);
                if ($delete){
                    return json(['code'=>1, 'msg'=>'操作成功','data'=>[]]);
                }else{
                    return json(['code'=>0, 'msg'=>'操作失败','data'=>[]]);
                }
            }else{
                return json(['code'=>0, 'msg'=>'页面不存在！','data'=>[]]);
            }
        }else{
            return json(['code'=>0, 'msg'=>'id不存在','data'=>[]]);
        }
    }

    /***
     * 支付方式
     */
    public function pay_set(){
        if( Request::instance()->isPost() ){
            $data = input('post.');
            $path = ROOT_PATH . '/data/cert';
            if (!file_exists($path)){ 
                mkdir($path,0777,true);
            }
            $sec = '';
            if (!empty($_FILES['weixin_cert_file']['name'])){
                $sec['cert'] = $this->upload_cert('weixin_cert_file');
            }
            if (!empty($_FILES['weixin_key_file']['name'])){
                $sec['key'] = $this->upload_cert('weixin_key_file');
            }
            if (!empty($_FILES['weixin_root_file']['name'])){
                $sec['root'] = $this->upload_cert('weixin_root_file');
            }
            $update['sets']    =   serialize($data);
            if(!empty($sec)){
                $update['sec'] =   serialize($sec);
            }
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($update);
            if($res){
                $this->success('编辑成功', url('index/pay_set'));
            }
            $this->error('编辑失败');
        }
        $sysset = Db::table('sysset')->field('*')->find();
      
        $set    = unserialize($sysset['sets']);
        $sec    = unserialize($sysset['sec']);
      
        $this->assign('sec', $sec);
        $this->assign('set', $set);
        $this->assign('meta_title', '支付方式');
        return $this->fetch();
    }

    /***
     * 支付参数
     */
    public function pay_content(){
        $sysset     = Db::table('sysset')->field('*')->find();
        $set        = unserialize($sysset['sets']);
        $payment    = unserialize($sysset['payment']);
       
        $set    = unserialize($sysset['sets']);
        if(Request::instance()->isPost()){
            $patdata = input('post.');
            if($patdata['pay']['alipay'] == 1){
                if(empty($patdata['alipay']['account'])){
                     $this->error('支付宝账号不能为空');
                }
                if(empty($patdata['alipay']['partner'])){
                    $this->error('合作者身份不能为空');
                }
                if(empty($patdata['alipay']['secret'])){
                    $this->error('支付宝校验密钥不能为空');
                }
            }
            if($patdata['pay']['weixin'] == 1){
                if(empty($patdata['wechat']['appid'])){
                    $this->error('微信appid不能为空');
                }
                if(empty($patdata['wechat']['secret'])){
                    $this->error('微信secret不能为空');
                }
                if(empty($patdata['wechat']['key'])){
                    $this->error('商户密钥不能为空');
                }
                if(empty($patdata['wechat']['account_name'])){
                    $this->error('微信账户名称不能为空');
                }
                if(empty($patdata['wechat']['mchid'])){
                    $this->error('微信支付商户号不能为空');
                }
                if(empty($patdata['wechat']['apikey'])){
                    $this->error('商户支付密钥不能为空');
                }
            }
            $set['pay']['weixin'] = $patdata['pay']['weixin'];
            $set['pay']['alipay'] = $patdata['pay']['alipay'];
            $update['sets']    = serialize($set);
            unset($patdata['pay']);
            $update['payment']  = serialize($patdata);
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($update);
            if($res !== false ){
                $this->success('编辑成功', url('index/pay_content'));
            }
            $this->error('编辑失败');
        }
        $this->assign('set', $set);
        $this->assign('payment', $payment);
        $this->assign('meta_title', '支付参数');
        return $this->fetch();
    }
    /**
     * 支付交易设置
     */
    public function pay_py(){
            $sysset     = Db::table('sysset')->field('*')->find();
            $set        = unserialize($sysset['sets']);
        if(Request::instance()->isPost()){
            $trade = input('post.');
            
            $set['trade']   = $trade['trade'];
            
            $sysset['sets'] = serialize($set);
            
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($sysset);
            if($res !== false ){
                $this->success('编辑成功', url('index/pay_py'));
            }
            $this->error('编辑失败');
        }
    
        $this->assign('set', $set);
        $this->assign('meta_title', '支付交易设置');
        return $this->fetch();

    }
    /**
     * 商城提醒
     */
    public function notice(){
        $sysset     = Db::table('sysset')->field('*')->find();
        $set        = unserialize($sysset['sets']);
      
        if(Request::instance()->isPost()){
            $notice          = input('post.');

            $set['notice']   = $notice['notice'];
            
            $sysset['sets'] = serialize($set);
            
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($sysset);
            if($res !== false ){
                $this->success('编辑成功', url('index/notice'));
            }
            $this->error('编辑失败');
        }
        $this->assign('newtype', $set['notice']['newtype']);
        $this->assign('set', $set);
        $this->assign('meta_title', '支付交易设置');
        return $this->fetch();
    }




    public function upload_cert($file_name){
        $dephp_2 = $file_name . '_1.pem';
        $dephp_4 = $_FILES[$file_name]['name'];
        $dephp_5 = $_FILES[$file_name]['tmp_name'];
        if (!empty($dephp_4) && !empty($dephp_5)){
            $dephp_6 = strtolower(substr($dephp_4, strrpos($dephp_4, '.')));
            if ($dephp_6 != '.pem'){
                $dephp_7 = "";
                if ($file_name == 'weixin_cert_file'){
                    $dephp_7 = 'CERT文件格式错误';
                }else if ($file_name == 'weixin_key_file'){
                    $dephp_7 = 'KEY文件格式错误';
                }else if ($file_name == 'weixin_root_file'){
                    $dephp_7 = 'ROOT文件格式错误';
                }
                $this->error($dephp_7);
            }
            return file_get_contents($dephp_5);
        }
        return "";
    }





}

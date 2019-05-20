<?php
namespace app\api\controller;
use Payment\Notify\PayNotifyInterface;
use Payment\Config;
use think\Loader;
use think\Db;

/**
 * @author: helei
 * @createTime: 2016-07-20 18:31
 * @description:
 */

/**
 * 客户端需要继承该接口，并实现这个方法，在其中实现对应的业务逻辑
 * Class TestNotify
 * anthor helei
 */
class TestNotify implements PayNotifyInterface
{
    public function notifyProcess(array $data)
    {
        $channel = $data['channel'];
        if ($channel === Config::ALI_CHARGE){// 支付宝支付
            // array (
            //     'notify_time' => '2019-05-16 19:40:33',
            //     'notify_type' => 'trade_status_sync',
            //     'notify_id' => '2019051600222194032091281017974707',
            //     'app_id' => '2019050264367537',
            //     'transaction_id' => '2019051622001491281034488845',
            //     'order_no' => '20190515213016563588',
            //     'out_biz_no' => '',
            //     'buyer_id' => '2088022531091287',
            //     'buyer_account' => '151****2455',
            //     'seller_id' => '2088531154918656',
            //     'seller_email' => 'gzyx5558@163.com',
            //     'trade_state' => 'success',
            //     'amount' => '0.01',
            //     'receipt_amount' => '0.01',
            //     'invoice_amount' => '0.01',
            //     'pay_amount' => '0.01',
            //     'point_amount' => '0.00',
            //     'refund_fee' => '',
            //     'subject' => '支付宝支付',
            //     'body' => 'ADS大声地说',
            //     'trade_create_time' => '2019-05-16 19:40:31',
            //     'pay_time' => '2019-05-16 19:40:32',
            //     'trade_refund_time' => '',
            //     'trade_close_time' => '',
            //     'channel' => 'ali_charge',
            //     'fund_bill_list' => 
            //     array (
            //       0 => 
            //       array (
            //         'amount' => '0.01',
            //         'fundChannel' => 'ALIPAYACCOUNT',
            //       ),
            //     ),
            //   )
            //修改订单状态
            $update = [
                'seller_id'      => $data['seller_id'],
                'transaction_id' => $data['transaction_id'],
                'order_status'   => 1,
                'pay_status'     => 1,
                'pay_time'       => strtotime($data['pay_time']),
            ];

            Db::startTrans();

            Db::name('order')->where(['order_sn' => $data['order_no']])->update($update);

            $order = Db::table('order')->where(['order_sn' => $data['order_no']])->field('order_id,user_id')->find();

            $goods_res = Db::table('order_goods')->field('goods_id,goods_name,goods_num,spec_key_name,goods_price,sku_id')->where('order_id',$order['order_id'])->select();
            $jifen = 0;
            foreach($goods_res as $key=>$value){
                $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points')->find();
                //付款减库存
                if($goods['less_stock_type']==2){
                    Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('inventory',$value['goods_num']);
                }
                $baifenbi = strpos($goods['gift_points'] ,'%');
                if($baifenbi){
                    $jg = sprintf("%.2f",$value['goods_price'] * $value['goods_num']);
                    $jifen = sprintf("%.2f",$jifen + ($jg * $goods['gift_points']));
                }else{
                    $jifen = sprintf("%.2f",$jifen + ($value['goods_num'] * $goods['gift_points']));
                }
            }

            $res = Db::table('member')->update(['id'=>$order['user_id'],'gouwujifen'=>$jifen]);
            
            if($order['order_id']){
                Db::commit();
                return true;
            }else{
                Db::rollback();
                return false;
            }


            
        } elseif ($channel === Config::WX_CHARGE) {// 微信支付
        } elseif ($channel === Config::CMB_CHARGE) {// 招商支付
        } elseif ($channel === Config::CMB_BIND) {// 招商签约
        } else {
            // 其它类型的通知
        }
        // 执行业务逻辑，成功后返回true
        return true;
    }
}
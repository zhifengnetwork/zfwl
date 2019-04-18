<?php
namespace app\api\controller;

use app\common\model\ChangeGoldLog;
use app\common\model\Channel;
use app\common\model\ChannelGroup;
use app\common\model\Goods;
use app\common\model\NotifyService;
use app\common\model\RechargeOrder;
use app\common\model\UserPlayer;
use org\MoPay;
use think\Db;
use think\Queue;

/**
 * 支付
 */
class Pay extends Common
{
    /**
     * 验证ios支付
     */
    public function iosPayment()
    {
        write_log('apiPay.txt');
        //获取 App 发送过来的数据,设置时候是沙盒状态
        $receipt   = input('data');
        $uid       = input('uid');
        $isSandbox = true;
        //开始执行验证
        try
        {
            $data = $this->getReceiptData($receipt, $isSandbox);
            if (!is_array($data)) {
                $data = $this->getReceiptData($receipt);
                if (!is_array($data)) {
                    $this->result('', 1, $data);
                }
            }
            if (rechargeOrder::where(['channel_order_no' => $data['transaction_id']])->master()->find()) {
                $this->result('', 1, '已存在');
            } else {

                $goods  = Goods::where('ios_product_id', $data['product_id'])->field('value')->find();
                $amount = $goods['value'];
                $order  = [
                    'uid'              => $uid,
                    'amount'           => $amount, //
                    'gold'             => $amount, // 暂时使用100倍分数
                    'channel'          => 5, // ios
                    'status'           => 1,
                    'channel_order_no' => $data['transaction_id'],
                ];

                $rechargeOrder = new RechargeOrder($order);
                if (!$rechargeOrder->save()) {
                    $this->result('', 1, '充值失败');
                }

                // 已支付待领取的金币
                $where = [
                    'uid'        => $uid,
                    'is_receive' => 0,
                    'is_add'     => 1,
                    'status'     => ['in', [1, 2]],
                ];

                // 已支付待领取的金币
                $list = RechargeOrder::where($where)->field('gold,id,source')->master()->select();

                if ($list) {
                    $info = UserPlayer::where('uid', $uid)->field('gold,type,bankgold')->master()->find();

                    $gold          = 0;
                    $orderIdArr    = [];
                    $changeGoldLog = []; // 金币变动记录
                    foreach ($list as $val) {
                        $gold += $val['gold'];
                        $orderIdArr[]    = $val['id'];
                        $changeGoldLog[] = [
                            'type'        => $val->getdata('source') == RechargeOrder::KF ? ChangeGoldLog::KF_CZ : ChangeGoldLog::APP_CZ,
                            'uid'         => $uid,
                            'change_gold' => $val['gold'],
                            'end_gold'    => $info['gold'] + $gold,
                            'user_type'   => $info['type'],
                            'rel_id'      => $val['id'],
                        ];
                    }

                    // 启动事务
                    Db::startTrans();

                    $goldLog = new ChangeGoldLog;
                    if (!$goldLog->saveAll($changeGoldLog)) {
                        Db::rollback();
                        return ['', -1];
                    }
                    RechargeOrder::where('id', 'in', $orderIdArr)->update(['is_receive' => 1]);
                    UserPlayer::where('uid', $uid)->update(['gold' => Db::raw('gold+' . $gold)]);

                    // 传给游服加上银行金币
                    $endGold = $info['gold'] + $gold;
                    $msgArr  = [
                        $uid => $endGold + $info['bankgold'],
                    ];

                    $res = NotifyService::upUserCard(1102, $msgArr);
                    if (!$res) {
                        Db::rollback();
                        $this->result('', 1, '领取失败');
                    }
                    // 提交事务
                    Db::commit();
                }
                $this->result(['transaction_id' => $data['transaction_id']], 0, '支付成功');
            }
        }

        //捕获异常
         catch (Exception $e) {
            $this->result('', 1, $e->getMessage());
        }
    }

    private function getReceiptData($receipt, $isSandbox = false)
    {
        if ($isSandbox) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        } else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }
        $postData = json_encode(
            array('receipt-data' => $receipt)
        );
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);
        //判断时候出错，抛出异常
        if ($errno != 0) {
            return $errmsg;
        }

        $data = json_decode($response);
        //判断返回的数据是否是对象
        if (!is_object($data)) {
            return 'Invalid response data';
        }

        //判断购买时候成功
        if (!isset($data->status) || $data->status != 0) {
            return 'Invalid receipt';
        }

        //返回产品的信息
        return array(
            'quantity'       => $data->receipt->quantity,
            'product_id'     => $data->receipt->product_id,
            'transaction_id' => $data->receipt->transaction_id,
            'purchase_date'  => $data->receipt->purchase_date,
            'bid'            => $data->receipt->bid,
            'bvrs'           => $data->receipt->bvrs,
        );
    }

    /**
     * 商品列表
     */
    public function goodsList()
    {
        //  $uid = think_decrypt(input('token/s', ''));
        // !$uid && $this->result('', 1, 'token已失效');
        // mainVer => 1, subVer => 13, pkgName => h5_client, platform => Windows,

        $subVer      = input('subVer/d', '');
        $mainVer     = input('mainVer/d', ''); // 主版本号（容器版本）
        $packageName = input('pkgName/s', ''); // 包名 客户端定的字段
        $platform    = input('platform/s', 'Android'); // platform = iOS / Android/ Windows /  OS X 包名 (暂时没有使用)

        $list = ChannelGroup::where('status', 1)->order('sort desc')->column('group_name,type,icon,picture,is_quota,remark,id', 'id');

        if ($list) {
            $host = str_replace('api', 'home', $_SERVER['HTTP_HOST']);
            foreach ($list as &$val) {
                $val['goods'] = [];
                // 图片url拼接
                $val['icon']    = 'http://' . $host . $val['icon'];
                $val['picture'] = 'http://' . $host . $val['picture'];
            }
            unset($val);
            $ids   = array_column($list, 'id');
            $goods = Goods::where('group_id', 'in', $ids)->field('title,text,value,ios_product_id,group_id')->select();
            if ($goods) {

                $goods = collection($goods)->toArray();
                foreach ($goods as &$val) {
                    $group_id = $val['group_id'];
                    unset($val['group_id']);
                    $list[$group_id]['goods'][] = $val;

                }
                unset($val);
            }
        }

        $this->result($list, 0);
    }

    /**
     * 墨支付回调地址
     */
    public function moPayNotifyUrl()
    {
        write_log('moPayNotifyUrl.txt');
        $data = input();
        // 这里要求传一个渠道ID过来
        $info = Channel::get($data['param1']);

        $json = [
            'md5key'    => $info['md5key'],
            'url'       => $info['url'], // 支付地址
            'mchid'     => $info['mchid'], // 商户ID
            'productid' => $info['productid'], // 产品ID
            'passageid' => $info['passageid'], // 通道ID
            'appid'     => $info['appid'], // 用户私钥
        ];

        // 签名验证
        if (!(new MoPay($json['md5key'], $json['url']))->checkSign($data)) {
            write_log('moPayNotifyUrlresult.txt', '签名失败');
            echo '签名失败';
        }

        if ($data['status'] == 2) {
            // 状态为2的是订单成功的
            $orderId        = $data['mchOrderNo']; // 订单id
            $channelOrderNo = $data['channelOrderNo']; // 支付宝支付订单号
            $payOrderId     = $data['payOrderId']; // 墨支付订单号

            $where = ['out_trade_no' => $orderId];
            $data  = RechargeOrder::where($where)->master()->field('uid,status,amount,gold,channel_id,id')->find();
            if (!$data || $data['status'] == 1) {
                echo 'success';
                return;
            }

            $where['status'] = 0;
            $update          = [
                'status'           => 1,
                'channel_order_no' => $channelOrderNo,
                'org_order_id'     => $payOrderId,
            ];
            $res = RechargeOrder::where($where)->update($update);

            // 推送支付成功数据
            if ($res) {

                $uid = $data['uid'];
                // 已支付待领取的金币
                $gold = RechargeOrder::where(['uid' => $uid, 'is_receive' => 0, 'status' => ['in', [1, 2]], 'is_add' => 1])->sum('gold');
                NotifyService::appCollectionGold($uid, $gold);

                $pushData = [
                    'uid'        => $data['uid'],
                    'gold'       => $data['gold'],
                    'amount'     => $data['amount'],
                    'channel_id' => $data['channel_id'], //渠道ID
                    'rate'       => $info['rate'], //费率
                    'order_id'   => $data['id'], //费率
                ];
                $jobHandlerClassName = 'app\api\job\ReportStatisticsJob@payStatistics';
                $isPushed            = Queue::push($jobHandlerClassName, $pushData, config('queue_job.reportStatistics'));
            }

        }
        echo 'success';
    }

    /**
     * 众宝支付回调地址
     */
    public function zonBaoNotifyUrl()
    {

        write_log('zonBaoPayNotifyUrl.txt');
        $data = input();

        $merKey   = 'd544210b1166434ba05c415857651160'; //密钥
        $sign_str = "orderid=" . $data['orderid'] . "&result=" . $data['result'] . "&amount=" . $data['amount'] . "&systemorderid=" . $data['systemorderid'] . "&completetime=" . $data['completetime'] . "&key=" . $merKey;

        if (md5($sign_str) != $data['sign']) {
            write_log('moPayNotifyUrlresult.txt', '签名失败');
            echo '签名失败';
        }

        if ($data['result'] == 1) {
            // 状态为0的是订单成功的
            $orderId        = $data['orderid']; // 订单id
            $channelOrderNo = $data['systemorderid']; // 支付宝支付订单号
            $payOrderId     = $data['systemorderid']; // 此次订单过程中众宝接口系统内的订单Id

            $where = ['out_trade_no' => $orderId];
            $data  = RechargeOrder::where($where)->master()->field('uid,status,amount,gold,channel_id,id,percent')->find();
            if (!$data || $data['status'] == 1) {
                echo 'success';
                return;
            }

            $where['status'] = 0;
            $update          = [
                'status'           => 1,
                'channel_order_no' => $channelOrderNo,
                'org_order_id'     => $payOrderId,
            ];
            $res = RechargeOrder::where($where)->update($update);
            // 这里要求传一个渠道ID过来
            $info = Channel::where('id', $data['channel_id'])->find();
            // 推送支付成功数据
            if ($res) {

                $uid = $data['uid'];
                // 已支付待领取的金币
                $gold = RechargeOrder::where(['uid' => $uid, 'is_receive' => 0, 'status' => ['in', [1, 2]], 'is_add' => 1])->sum('gold');
                NotifyService::appCollectionGold($uid, $gold);

                $pushData = [
                    'uid'        => $data['uid'],
                    'gold'       => $data['gold'],
                    'amount'     => $data['amount'],
                    'channel_id' => $data['channel_id'], //渠道ID
                    'rate'       => $data['percent'], //费率
                    'order_id'   => $data['id'],
                ];
                $jobHandlerClassName = 'app\api\job\ReportStatisticsJob@payStatistics';
                $isPushed            = Queue::push($jobHandlerClassName, $pushData, config('queue_job.reportStatistics'));
            }
        }
        echo 'success';
    }

}

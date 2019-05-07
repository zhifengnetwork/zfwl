<?php
/**
 * 众筹相关服务层
 *
 *  @author dwer.cn
 *  @date 2017-08-26
 */
namespace mgcore\service;

use mgcore\model\Crowd;
use mgcore\model\Place;
use mgcore\model\UserOrder;
use mgcore\service\NoticeService;
use Overtrue\Wechat\LuckMoney;
use Overtrue\Wechat\Payment\Business;
use \think\Config;

class CrowdService
{
    //每日最大收益 - 单位分
    private $_maxIncome = 20000;

    //每日保底收益 - 单位分
    private $_minIncome = 1500;

    private $_getLuckMoneyLib = null;

    public function __construct()
    {
        //收益配置
        $crowdInfo = Config::get('crowd');
        if ($crowdInfo) {
            $this->_maxIncome = $crowdInfo['max_income'];
            $this->_minIncome = $crowdInfo['min_income'];
        }
    }

    /**
     * 定时计算众筹产品的每日平均收益
     * @author dwer
     * @date   2017-09-15
     *
     * @return
     */
    public function calcProductIncome($calcDay)
    {
        pft_log('crowd/product_income', json_encode(['start', $calcDay]));

        //先获取总条目数
        $crowdModel = new Crowd();
        $orderModel = new UserOrder();
        $total      = $crowdModel->getIncomeList($calcDay, $getTotal = true);

        //统一计算前一天的收益
        $tmpTime  = strtotime($calcDay) - 24 * 3600;
        $yestoday = date('Y-m-d', $tmpTime);

        $size = 500;
        $page = ceil($total / $size);
        for ($i = 1; $i <= $page; $i++) {
            $list = $crowdModel->getIncomeList($calcDay, false, $i, $size);

            foreach ($list as $item) {
                $cpId        = $item['cp_id'];
                $realProduct = $item['real_product'];
                $type        = $item['type'];

                if ($type == 1) {
                    //计算小咪按摩椅场地昨日的平均收益
                    $avgIncome  = $orderModel->getPlaceDayIncome($yestoday, $realProduct);
                    $calcIncome = $avgIncome;
                } else {
                    $avgIncome  = 15;
                    $calcIncome = 15;
                }

                $tmpDay = date('Ymd', strtotime($yestoday));
                $res    = $crowdModel->addProductIncome($cpId, $type, $tmpDay, $avgIncome, $calcIncome, $realProduct);

                pft_log('crowd/product_income', json_encode([$cpId, $realProduct, $avgIncome, $res]));
            }

            //休息两秒后继续
            //sleep(0.5);
        }

        //结束
        pft_log('crowd/product_income', json_encode(['end', $calcDay]));
    }

    /**
     * 定时计算众筹用户的收益和退款
     * @author dwer
     * @date   2017-09-15
     *
     * @return
     */
    public function calcCrowdIncome($calcDay)
    {
        pft_log('crowd/crowd_income', json_encode(['start', $calcDay]));

        //先获取总条目数
        $crowdModel = new Crowd();
        $total      = $crowdModel->getCalcList($calcDay, $getTotal = true);

        $size = 500;
        $page = ceil($total / $size);

        //统一计算前一天的收益
        $tmpTime  = strtotime($calcDay) - 24 * 3600;
        $yestoday = date('Y-m-d', $tmpTime);

        for ($i = 1; $i <= $page; $i++) {
            $list = $crowdModel->getCalcList($calcDay, false, $i, $size);

            foreach ($list as $item) {
                $orderId = $item['order_id'];
                $res     = $crowdModel->genDayIncome($orderId, $yestoday, $this->_minIncome, $this->_maxIncome);

                //TODO - 这边可以添加微信错误提醒
                if ($res[0] == 0) {
                    $errMsg = $res[1];

                }

                pft_log('crowd/crowd_income', json_encode([$orderId, $calcDay, $res]));
            }

            //休息两秒后继续
            //sleep(0.5);
        }

        //结束
        pft_log('crowd/crowd_income', json_encode(['end', $calcDay]));
    }

    /**
     * 定时给众筹用户发红包
     * @author dwer
     * @date   2017-09-15
     *
     * @return
     */
    public function multiSendRed($calcDay)
    {
        pft_log('crowd/send_red', json_encode(['start', $calcDay]));

        // 加载wechat微信处理类库
        require VENDOR_PATH . 'wechat-2/autoload.php';

        //红包显示日期
        $showDay = date('Y年m月d日', strtotime($calcDay));
        $showMsg = "小咪{$showDay}还款";

        //统一计算前一天的收益
        $tmpTime  = strtotime($calcDay) - 24 * 3600;
        $yestoday = date('Y-m-d', $tmpTime);

        //先获取总条目数
        $crowdModel = new Crowd();
        $total      = $crowdModel->getRedList($yestoday, $getTotal = true);

        $size = 500;
        $page = ceil($total / $size);

        //准备接口
        $payInfo    = Config::get('wx_config');
        $app_id     = $payInfo['appid'];
        $app_key    = $payInfo['appsecret'];
        $mch_id     = $payInfo['mch_id'];
        $mch_key    = $payInfo['mch_key'];
        $clientCert = $payInfo['client_cert'];
        $clientKey  = $payInfo['client_key'];

        $business = new Business($app_id, $app_key, $mch_id, $mch_key);
        $business->setClientCert($clientCert);
        $business->setClientKey($clientKey);

        $luckMoney = new LuckMoney($business);

        for ($i = 1; $i <= $page; $i++) {
            $list = $crowdModel->getRedList($yestoday, false, $i, $size);
            foreach ($list as $item) {
                $uid       = $item['uid'];
                $openId    = $item['open_id'];
                $day       = $item['day'];
                $mchBillno = $item['mch_billno'];
                $dayRefund = $item['day_refund']; //单位分
                $cpName    = $item['cp_name'];
                $mobile    = $item['mobile'];
                $orderId   = $item['order_id'];

                //祝福语的长度是128，这边做下限制
                if (mb_strlen($cpName) >= 120) {
                    $cpName = mb_substr($cpName, 0, 120) . '...';
                }
                $tmpMsg = $showMsg . "【{$cpName}】";

                //调用微信的红包接口
                $redData = [
                    'mch_billno'   => $mchBillno,
                    'send_name'    => "小咪{$showDay}",
                    're_openid'    => $openId,
                    'total_amount' => $dayRefund,
                    'wishing'      => $tmpMsg,
                    'act_name'     => '小咪每日还款',
                    'total_num'    => 1,
                    'remark'       => $tmpMsg,
                ];

                //结果
                $isSuccess  = false;
                $sendListid = 0;
                $errMsg     = '';

                try {
                    $res        = $luckMoney->send($redData);
                    $returnCode = $res['return_code'];

                    pft_log('crowd/send_red_raw', json_encode([$redData, $res]));

                    if ($returnCode == 'FAIL') {
                        //打红包失败
                        $isSuccess = false;
                        $errMsg    = $res['return_msg'];
                    } else {
                        $resultCode = $res['result_code'];
                        if ($resultCode == 'FAIL') {
                            //打红包失败
                            $isSuccess = false;
                            $errMsg    = $res['err_code_des'] . "[{$res['err_code']}]";
                        } else {
                            $sendListid = $res['send_listid'];
                            $isSuccess  = true;
                        }
                    }

                } catch (\Exception $e) {
                    //打红包失败
                    $isSuccess = false;
                    $errMsg    = $e->getMessage();
                }

                if ($isSuccess) {
                    //打款成功
                    $res = $crowdModel->sendRedSucc($mchBillno, $sendListid, $payTime = false);
                    pft_log('crowd/send_red', json_encode([$mchBillno, $sendListid, $res]));

                } else {
                    //TODO - 这边可以添加微信错误提醒
                    $time = date('Y-m-d H:i:s');
                    $msg  = "【众筹红包故障】，电话：{$mobile}，订单ID：{$orderId}, 原因：{$errMsg}";
                    NoticeService::warningMsg($time, $msg);

                    pft_log('crowd/send_red_error', json_encode([$redData, $errMsg]));
                }
            }

            //休息两秒后继续
            sleep(1);
        }

        //结束
        pft_log('crowd/send_red', json_encode(['end', $calcDay]));
    }

    /**
     * 重新发送红包
     * @author dwer
     * @date   2017-10-09
     *
     * @param  int $incomeId
     * @return array
     */
    public function resendRed($incomeId)
    {
        if (!$incomeId) {
            return [203, '参数错误'];
        }

        //获取红包信息
        $crowdModel = new Crowd();
        $incomeInfo = $crowdModel->getRedInfo($incomeId);
        if (!$incomeInfo) {
            return [203, '参数错误'];
        }

        //判断当前状态
        if ($incomeInfo['receive_status'] != 2) {
            return [204, '当前状态不能重新发放红包'];
        }

        //添加修改日志
        pft_log('crowd/red_again', json_encode([$incomeId, $incomeInfo]));

        //修改平台流水号
        $oldMchBillno = $incomeInfo['mch_billno'];
        if (!$oldMchBillno) {
            return [205, 'mch_billno不存在'];
        }

        $tmp = explode('Z', $oldMchBillno);
        if (isset($tmp[1]) && $tmp[1]) {
            $tmpOrder = intval($tmp[1]) + 1;
        } else {
            $tmpOrder = 1;
        }
        $newMchBillno = $tmp[0] . 'Z' . $tmpOrder;

        $res = $crowdModel->udpateMchBillno($incomeId, $newMchBillno);
        if (!$res) {
            return [500, 'mch_billno更新出错'];
        }

        //获取红包类库
        $luckMoney = $this->_getLuckMoneyLib();

        $openId    = $incomeInfo['open_id'];
        $calcDay   = $incomeInfo['day'];
        $dayRefund = $incomeInfo['day_refund']; //单位分
        $cpName    = $incomeInfo['cp_name'];
        $mobile    = $incomeInfo['mobile'];
        $orderId   = $incomeInfo['order_id'];

        //祝福语的长度是128，这边做下限制
        if (mb_strlen($cpName) >= 120) {
            $cpName = mb_substr($cpName, 0, 120) . '...';
        }

        //红包显示日期
        $showDay = date('Y年m月d日', strtotime($calcDay) + 24 * 3600);
        $showMsg = "小咪{$showDay}还款";
        $tmpMsg  = $showMsg . "【{$cpName}】";

        //调用微信的红包接口
        $redData = [
            'mch_billno'   => $newMchBillno,
            'send_name'    => "小咪{$showDay}重发",
            're_openid'    => $openId,
            'total_amount' => $dayRefund,
            'wishing'      => $tmpMsg,
            'act_name'     => '小咪每日还款',
            'total_num'    => 1,
            'remark'       => $tmpMsg,
        ];

        //结果
        $isSuccess  = false;
        $sendListid = 0;
        $errMsg     = '';

        try {
            $res        = $luckMoney->send($redData);
            $returnCode = $res['return_code'];

            pft_log('crowd/red_again', json_encode([$redData, $res]));

            if ($returnCode == 'FAIL') {
                //打红包失败
                $isSuccess = false;
                $errMsg    = $res['return_msg'];
            } else {
                $resultCode = $res['result_code'];
                if ($resultCode == 'FAIL') {
                    //打红包失败
                    $isSuccess = false;
                    $errMsg    = $res['err_code_des'] . "[{$res['err_code']}]";
                } else {
                    $sendListid = $res['send_listid'];
                    $isSuccess  = true;
                }
            }

        } catch (\Exception $e) {
            //打红包失败
            $isSuccess = false;
            $errMsg    = $e->getMessage();
        }

        if ($isSuccess) {
            //打款成功
            $res = $crowdModel->sendRedSucc($newMchBillno, $sendListid, $payTime = false);
            pft_log('crowd/red_again', json_encode([$newMchBillno, $sendListid, $res]));

            return [200, '红包重发成功'];
        } else {
            //TODO - 这边可以添加微信错误提醒
            $time = date('Y-m-d H:i:s');
            $msg  = "【众筹红包故障】，电话：{$mobile}，订单ID：{$orderId}, 原因：{$errMsg}";
            NoticeService::warningMsg($time, $msg);

            pft_log('crowd/red_again', json_encode([$redData, $errMsg]));

            return [500, $errMsg];
        }
    }

    /**
     * 查询红包的领取状态
     * @author dwer
     * @date   2017-09-25
     *
     * @param  date $calcDay
     * @return array
     */
    public function queryRedStatus($calcDay)
    {
        pft_log('crowd/query_red', json_encode(['start', $calcDay]));
        // 加载wechat微信处理类库
        require VENDOR_PATH . 'wechat-2/autoload.php';

        //准备接口
        $payInfo    = Config::get('wx_config');
        $app_id     = $payInfo['appid'];
        $app_key    = $payInfo['appsecret'];
        $mch_id     = $payInfo['mch_id'];
        $mch_key    = $payInfo['mch_key'];
        $clientCert = $payInfo['client_cert'];
        $clientKey  = $payInfo['client_key'];

        $business = new Business($app_id, $app_key, $mch_id, $mch_key);
        $business->setClientCert($clientCert);
        $business->setClientKey($clientKey);

        $luckMoney = new LuckMoney($business);

        $crowdModel = new Crowd();
        $list       = $crowdModel->getRedQueryList($calcDay);

        foreach ($list as $item) {
            $mchBillno = $item['mch_billno'];

            //红包领取状态
            $status = '';

            try {
                $res        = $luckMoney->query($mchBillno);
                $returnCode = $res['return_code'];

                pft_log('crowd/query_red_raw', json_encode([$mchBillno, $res]));

                if ($returnCode == 'FAIL') {
                    //查询红包失败
                    $isSuccess = false;
                    $errMsg    = $res['return_msg'];
                } else {
                    $resultCode = $res['result_code'];
                    if ($resultCode == 'FAIL') {
                        //查询红包失败
                        $isSuccess = false;
                        $errMsg    = $res['err_code_des'] . "[{$res['err_code']}]";
                    } else {
                        // 红包状态
                        // SENDING:发放中  SENT:已发放待领取 FAILED：发放失败 RECEIVED:已领取 RFUND_ING:退款中 REFUND:已退款
                        $status    = $res['status'];
                        $isSuccess = true;
                    }
                }

            } catch (\Exception $e) {
                //打红包失败
                $isSuccess = false;
                $errMsg    = $e->getMessage();
            }

            if ($isSuccess) {
                //打款成功
                $res = -1;
                if (in_array($status, ['RECEIVED', 'REFUND'])) {
                    $res = $crowdModel->updateRedStatus($mchBillno, $status);
                }

                pft_log('crowd/query_red', json_encode([$mchBillno, $status, $res]));
            } else {
                //TODO - 这边可以添加微信错误提醒
                $time = date('Y-m-d H:i:s');
                $msg  = "【众筹红包查询故障】，红包ID：{$mchBillno}, 原因：{$errMsg}";
                NoticeService::warningMsg($time, $msg);

                pft_log('crowd/query_red_error', json_encode([$mchBillno, $errMsg]));
            }
        }

        //结束
        pft_log('crowd/query_red', json_encode(['end', $calcDay]));
    }

    /**
     * 重发退回的红包
     * @author dwer
     * @date   2017-09-15
     *
     * @return
     */
    public function tryRedPackage()
    {
        pft_log('crowd/try_red', json_encode(['start']));

        $crowdModel = new Crowd();
        $list       = $crowdModel->getRedRefundList();

        foreach ($list as $item) {
            $incomeId = $item['id'];
            $this->resendRed($incomeId);
        }

        pft_log('crowd/try_red', json_encode(['end',$list]));
    }

    /**
     * 处理定时任务
     * @author dwer
     * @date   2017-09-15
     *
     * @return
     */
    public function handleTasks($nowTime = null)
    {
        $nowTime = $nowTime ? $nowTime : time();
        pft_log('crowd/handle_tasks', json_encode(['start', $nowTime]));

        //先获取总条目数
        $crowdModel = new Crowd();
        $list       = $crowdModel->getTask($nowTime);
        if (!$list) {
            pft_log('crowd/handle_tasks', '没有任务需要处理');
        }

        foreach ($list as $item) {
            $cpId            = $item['cp_id'];
            $autoOnlineTime  = $item['auto_online_time'];
            $autoOfflineTime = $item['auto_offline_time'];
            $autoRatioTime   = $item['auto_ratio_time'];
            $nextRatio       = $item['next_ratio'];

            if ($autoOnlineTime) {
                //定时上架
                $res = $crowdModel->onlineProduct($cpId);
                pft_log('crowd/handle_tasks', json_encode(['online', $res, $cpId, $nowTime]));
            }

            if ($autoOfflineTime) {
                //定时下架
                $res = $crowdModel->offlineProduct($cpId);
                pft_log('crowd/handle_tasks', json_encode(['offline', $res, $cpId, $nowTime]));
            }

            if ($autoRatioTime) {
                //定时切换年华利率
                $res = $crowdModel->adjustRatio($cpId, $nextRatio);
                pft_log('crowd/handle_tasks', json_encode(['ratio', $res, $cpId, $nowTime]));
            }
        }

        pft_log('crowd/handle_tasks', json_encode(['end']));
    }

    /**
     * 导入众筹商品
     * @author dwer
     * @date   2017-09-18
     *
     * @return
     */
    public function importCrowdProduct()
    {
        $placeModel = new Place();
        $crowdModel = new Crowd();

        pft_log('crowd/import_product', json_encode(['start']));

        //产品默认的价格 - 单位分
        $price = 500000;

        //产品默认的年化收益率 - 单位分
        $ratio = 12;

        $placeList = $placeModel->getList($agentId = false, $channelId = false, $field = 'place_id, place_name, place_address, machine_num');
        foreach ($placeList as $item) {
            $productName = $item['place_name'];
            $stock       = intval($item['machine_num']);
            $desc        = $item['place_address'];
            $realProduct = $item['place_id'];

            $res = $crowdModel->addProduct($productName, $price, $stock, $ratio, $desc, $realProduct, $isOnline = true);

            pft_log('crowd/import_product', json_encode([$res, $productName, $price, $stock, $ratio, $desc, $realProduct]));
        }

        pft_log('crowd/import_product', json_encode(['end']));
    }

    //获取红包类库
    private function _getLuckMoneyLib()
    {
        if (!$this->_getLuckMoneyLib) {
            // 加载wechat微信处理类库
            require VENDOR_PATH . 'wechat-2/autoload.php';

            //准备接口
            $payInfo    = Config::get('wx_config');
            $app_id     = $payInfo['appid'];
            $app_key    = $payInfo['appsecret'];
            $mch_id     = $payInfo['mch_id'];
            $mch_key    = $payInfo['mch_key'];
            $clientCert = $payInfo['client_cert'];
            $clientKey  = $payInfo['client_key'];

            $business = new Business($app_id, $app_key, $mch_id, $mch_key);
            $business->setClientCert($clientCert);
            $business->setClientKey($clientKey);

            $this->_getLuckMoneyLib = new LuckMoney($business);
        }

        return $this->_getLuckMoneyLib;
    }
}

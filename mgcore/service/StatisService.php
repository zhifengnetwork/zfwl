<?php
namespace mgcore\service;

use \mgcore\model\Statis as statisModel;
use \mgcore\model\Order  as orderModel;
use think\Config;
use \think\Db;
use think\Exception;
use think\Cache;
use \mgcore\model\TaskExecute as AsynchTaskModel;

class StatisService
{
    // 统计模型
    private $_statisModel;
    //订单模型
    private static $_orderModel;

    //消费操作
    const RESUME_ACTION = 1;
    //退款操作
    const REFUND_ACTION = 2;

    //统计类型  1-场所日报表 2-机器日报表
    const STATIS_TYPE_PLACE = 1;
    const STATIS_TYPE_MACHINE = 2;


    /**
     * 实例化统计模型
     * @author cyw
     * @date   2017-08-08
     * @return mixed
     *
     */
    private function _statisModel()
    {
        if (!$this->_statisModel) {
            $this->_statisModel = new statisModel();
        }
        return $this->_statisModel;
    }

    private static function _orderModel()
    {
        if (!self::$_orderModel) {
            self::$_orderModel = new orderModel();
        }
        return self::$_orderModel;
    }

    /**
     * 统计时间段内场所的实际收入
     * @author cyw
     * @date   2017-08-08
     * @param  string $beginTime
     * @param  string $endTime
     * @return array
     * 
     */
    public function placeDivMoneyLog($beginTime, $endTime)
    {
        // 实例化统计模型
        $statisModel = $this->_statisModel();
        // 计算订单下多少出单的场所
        $placeIdList = $statisModel->getPartnerByTime('1', $beginTime, $endTime);
        $data = [];

        if ($placeIdList) {
            foreach ($placeIdList as $placeValue) {
                $countRes = $statisModel->placeTotalStatic([$placeValue['id']], date('Y-m-d', $beginTime), date('Y-m-d', $endTime));
                isset($countRes[0]) && $data[$placeValue['id']] = $countRes[0];
            }
        }
        return $data;
    }

    /**
     * 统计时间段内代理的实际收入
     * @author cyw
     * @date   2017-08-08
     * @param  string $beginTime
     * @param  string $endTime
     * @return array
     * 
     */
    public function agentDivMoneyLog($beginTime, $endTime)
    {
        // 实例化统计模型
        $statisModel = $this->_statisModel();
        // 计算订单下多少出单的代理
        $agentIdList = $statisModel->getPartnerByTime('2', $beginTime, $endTime);
        $data = [];

        if ($agentIdList) {
            foreach ($agentIdList as $agentValue) {
                $countRes = $statisModel->getAgentDiv([$agentValue['id']], date('Y-m-d', $beginTime), date('Y-m-d', $endTime));
               isset($countRes[0]) && $data[$agentValue['id']] = $countRes[0];
            }
        }
        return $data;
    }

    /**
     * 统计时间段内渠道商的实际收入
     * @author cyw
     * @date   2017-08-08
     * @param  string $beginTime
     * @param  string $endTime
     * @return array
     * 
     */
    public function channelDivMoneyLog($beginTime, $endTime)
    {
        // 实例化统计模型
        $statisModel = $this->_statisModel();
        // 计算订单下多少出单的代理
        $channelIdList = $statisModel->getPartnerByTime('3', $beginTime, $endTime);
        $data = [];

        if ($channelIdList) {
            foreach ($channelIdList as $channelValue) {
                $countRes = $statisModel->getChannelDiv([$channelValue['id']],  date('Y-m-d', $beginTime), date('Y-m-d', $endTime));
                isset($countRes[0]) && $data[$channelValue['id']] = $countRes[0];
            }
        }
        return $data;
    }


    /**
     * 增加报表统计异步任务
     * @author xiexy
     * @date   2017-11-23
     * @param  string $orderNo     订单号
     * @param  string $actionType  操作类型 1-消费 2-退款
     * @return array
     *
     */
    public static function addStatisAsynchTask($orderId, $actionType = self::RESUME_ACTION)
    {
        if (in_array($actionType, [self::RESUME_ACTION, self::REFUND_ACTION])) {
            $asynchTaskModel = new AsynchTaskModel();
            //场所报表统计参数
            $pStatisParams   = json_encode([$orderId, $actionType, self::STATIS_TYPE_PLACE]);
            //机器报表统计场所
            $mStatisParams   = json_encode([$orderId, $actionType, self::STATIS_TYPE_MACHINE]);

            $sequence = $actionType == self::RESUME_ACTION ? 200 : 100;

            $pRes = $asynchTaskModel->addTask('100013', $pStatisParams, '', $sequence);
            $mRes = $asynchTaskModel->addTask('100013', $mStatisParams, '', $sequence);

            if (!$pRes || !$mRes) {
                pft_log('Asynchtask/add_statis_task', json_encode([$orderId, $actionType]));
            }
        }
    }


    /**
     * 更新场所日报表
     * @author xiexy
     * @date            2017-11-23
     * @param  string   $orderNo     订单编号
     * @param  integer  $actionType  操作类型 1-消费 2-退款
     * @param  integer  $statisType  统计类型 1-场所日报表 2-机器日报表
     * @return array
     *
     */
    public static function updatePlaceStatis($orderId, $actionType = self::RESUME_ACTION, $statisType = self::STATIS_TYPE_PLACE)
    {
        if (!$orderId || !in_array($actionType, [self::RESUME_ACTION, self::REFUND_ACTION])) {
            throw new Exception('参数错误 - order_id:' . $orderId . '  actionType:' . $actionType);
        }

        if (!$statisType || !in_array($statisType, [self::STATIS_TYPE_PLACE, self::STATIS_TYPE_MACHINE])) {
            throw new Exception('statis_type参数错误 - statis_type:' . $statisType);
        }

        //更新结果
        $result      = false;
        $statisModel = new statisModel();

        $orderFields = 'order_no, machine_id, amount, channel_id, agent_id, place_id, area_id, time_paid, sale_user_profit, channel_div, agent_div, place_div, pf_div';

        $orderInfo   = self::_orderModel()->getOrderInfo($orderId, $orderFields);

        if (!$orderInfo || !$orderInfo['time_paid']) {
            throw new Exception('异常订单 - order_id:' . $orderId);
        }

        //机器编号
        $machineId      = $orderInfo['machine_id'];
        //代理id
        $agentId        = $orderInfo['agent_id'];
        //订单消费所在场所
        $placeId        = $orderInfo['place_id'];
        //订单金额
        $orderAmount    = $orderInfo['amount'];
        //小二分润
        $saleUserProfit = $orderInfo['sale_user_profit'];
        //纯收益:最后分润金额
        $orderPorfit    = round($orderInfo['amount'] * 0.994) - $orderInfo['sale_user_profit'];
        //场所收益
        $placeProfit    = round($orderPorfit * $orderInfo['place_div']);
        //代理收益
        $agentProfit    = round($orderPorfit * $orderInfo['agent_div']);
        //渠道收益
        $channelProfit  = round($orderPorfit * $orderInfo['channel_div']);
        //平台收益
        $platformProfit = round($orderPorfit * $orderInfo['pf_div']);
        //统计日期
        $statisDate     = date('Y-m-d', $orderInfo['time_paid']);

        $orderPorfit    = round($orderPorfit);

        $statisInfo     = $statisType == self::STATIS_TYPE_PLACE ?
            $statisModel -> getDateStatis($statisDate, $placeId,'id, order_num','place', '', $agentId) :
            $statisModel -> getDateStatis($statisDate, $placeId,'id, order_num','machine', $machineId, $agentId);

        //消费:当日报表记录是否存在-存在则更新 不存在则生成
        if ($actionType == self::RESUME_ACTION) {

            if (!$statisInfo) {
                //添加
                 if ($statisType == self::STATIS_TYPE_PLACE) {
                     // 场所收益报表
                     $result =$statisModel->addPlaceStatis($orderInfo['channel_id'], $placeId, $orderInfo['agent_id'], $orderInfo['area_id'], $statisDate,
                         $orderAmount, $orderPorfit, $saleUserProfit, $placeProfit, $agentProfit, $channelProfit, $platformProfit);
                 } else {
                    // 机器收益报表
                     $result =$statisModel->addPlaceStatis($orderInfo['channel_id'], $placeId, $orderInfo['agent_id'], $orderInfo['area_id'], $statisDate,
                         $orderAmount, $orderPorfit, $saleUserProfit, $placeProfit, $agentProfit, $channelProfit, $platformProfit, 'machine', $machineId);
                 }
            } else {
                //更新
                if ($statisType == self::STATIS_TYPE_PLACE) {
                    // 场所收益报表
                    $result = $statisModel->updatePlaceStatis($statisDate, $placeId, $agentId, $orderAmount, $orderPorfit, $saleUserProfit,
                        $placeProfit, $agentProfit, $channelProfit, $platformProfit, $statisInfo['order_num']);
                } else {
                    // 机器收益报表
                    $result = $statisModel->updatePlaceStatis($statisDate, $placeId, $agentId, $orderAmount, $orderPorfit, $saleUserProfit,
                        $placeProfit, $agentProfit, $channelProfit, $platformProfit, $statisInfo['order_num'], 'machine', $machineId);
                }
            }
        }

        //退款:需要从订单付款时间当日统计数据中扣除该笔退款订单产生的的营业额、纯收益和小二、场所、代理、渠道、平台的分润
        if ($actionType == self::REFUND_ACTION) {

            if (!$statisInfo) {
                throw new Exception('退款异常 - 当日数据记录不存在 order_id:' . $orderId);
            }

            //退款记录查询字段
            $refundLogFields  = 'status, refund_fee, update_time';
            //退款日志记录
            $refundLogInfo    = self::_orderModel()->getRefundLogInfo($orderInfo['order_no'], $refundLogFields);

            if (!$refundLogInfo || $refundLogInfo['status'] != 1) {
                throw new Exception('异常退款记录 - order_id:' . $orderId);
            }

            //退款金额
            $refundAmount = $refundLogInfo['refund_fee'];
            //更新
            if ($statisType == self::STATIS_TYPE_PLACE) {
                // 场所收益报表
                $result = $statisModel->updateStatisByRefund($statisDate, $placeId, $agentId, $refundAmount, $orderAmount, $orderPorfit, $saleUserProfit,
                    $placeProfit, $agentProfit, $channelProfit, $platformProfit, $statisInfo['order_num']);
            } else {
                // 机器收益报表
                $result = $statisModel->updateStatisByRefund($statisDate, $placeId, $agentId, $refundAmount, $orderAmount, $orderPorfit, $saleUserProfit,
                    $placeProfit, $agentProfit, $channelProfit, $platformProfit, $statisInfo['order_num'], 'machine', $machineId);

            }
        }
        return $result;
    }


    public static function updateData($startDate, $endDate, $placeId, $statisType = 'place')
    {
        $startTime = strtotime($startDate);
        $endTime   = strtotime($endDate);
        $nowTime   = time();
        $table     = '';

        if (!$startTime || !$endTime) {
            throw new Exception('时间参数错误');
        }

        if ($statisType == 'place') {

            $table = 'statistics_report_place';
            $group = 'place_id';

        } elseif ($statisType == 'machine') {

            $table = 'statistics_report_machine';
            $group = 'place_id, machine_id';

        }

        //天数
        $days = ceil(($endTime - $startTime) / (3600 * 24))+1;

        for ($i = 1; $i <= $days; $i++) {

            $dayStartTime = $startTime    + ( 3600 * 24 * ($i - 1) );
            $dayEndTime   = $dayStartTime + ( 3600 * 24 - 1 );

            //场所收益报表
            $placeStatic  = self::_orderModel()->getDayStatic($dayStartTime, $dayEndTime, $group, $placeId);
            //场所退款
            $placeRefund  = self::_orderModel()->getRefundStatic($dayStartTime, $dayEndTime, $group, $placeId);

            if ($placeStatic) {

                foreach ($placeStatic as &$static) {

                    if ($statisType == 'place') {
                        unset($static['machine_id']);
                    }

                    $static['create_time'] = $nowTime;
                    $static['date']        = date('Y-m-d', $dayStartTime);

                    //退款数据
                    foreach ($placeRefund as $key => $refund) {

                        if ($statisType == 'place' && $static['place_id'] == $refund['place_id']) {
                            $static['refund_amount'] = $refund['refund_amount'];
                            unset($placeRefund[$key]);
                        }

                        if ($statisType == 'machine' && $static['machine_id'] == $refund['machine_id']) {
                            $static['refund_amount'] = $refund['refund_amount'];
                        }
                    }
                }

                $res =  Db::table($table)->insertAll($placeStatic);
                if (!$res) {
                    throw new Exception('导入数据错误');
                }
            }
        }
        echo 'success';
    }
}

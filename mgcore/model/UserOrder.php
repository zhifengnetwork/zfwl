<?php
/**
 * 订单相关的模型封装
 * @author dwer
 * @date   2017-09-03
 *
 */

namespace mgcore\model;

use mgcore\model\Machine;
use think\Config;
use think\Model;

class UserOrder extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_orderTable = 'user_order';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }


    /**
     * 获取场所日平均收益
     * @author dwer
     * @date   2017-09-15
     *
     * @param  date $calcDay 计算日期 - 2017-09-15
     * @param  int $placeId 场所ID
     * @return int 收益 - 单位分
     */
    public function getPlaceDayIncome($calcDay, $placeId)
    {
        if (!$calcDay || !$placeId) {
            return false;
        }

        $startTime = strtotime($calcDay . ' 00:00:00');
        $endTime   = strtotime($calcDay . ' 23:59:59');

        $field = 'sum(amount) as total_amount';
        $where = [
            'place_id'  => $placeId,
            'time_paid' => ['between', [$startTime, $endTime]],
            'paid'      => 1,
        ];

        $info        = $this->table($this->_orderTable)->where($where)->field($field)->find();
        $totalAmount = $info ? $info['total_amount'] : 0;

        //获取场所设备数量
        $machineNum = (new Machine)->getNumByPlace($placeId);
        $machineNum = max(1, $machineNum);
        $avgIncome  = num_fmt(($totalAmount / $machineNum), 2, true);

        return $avgIncome;
    }


    /**
     * 更新订单咪小二分润金额
     * @author xiexy
     * @date   2017-10-24
     *
     * @param  string  $orderNo  订单编号
     * @param  string  $amount   金额 单位分
     * @return boolean
     */
    public function updateSaleUserProfit($orderNo, $amount)
    {
        $res = $this->table($this->_orderTable)->where(['order_no' => $orderNo, 'paid' => 1])->update(['sale_user_profit' => (int) $amount]);
        return $res;
    }


    public function getOrderInfo($orderNo, $field = '*')
    {
        $orderInfo = $this->table($this->_orderTable)->field($field)->where(['order_no' => $orderNo])->find();
        return $orderInfo ? $orderInfo : [];
    }
}

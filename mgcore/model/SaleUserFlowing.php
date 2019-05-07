<?php
/**
 * 交易流水模型
 *
 */

namespace mgcore\model;

use think\Model;

class SaleUserFlowing extends Model
{

    //默认数据库配置
    protected $connection = 'database';

    private $_userFlowingTable = 'user_flowing';

    //交易类型 1- 消费 2-提现 3-订单退款
    const CONSUME_TYPE = 1;
    const CASH_TYPE    = 2;
    const REFUND_TYPE  = 3;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 增加流水记录
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId        咪小二id
     * @param    $transAmount       交易金额
     * @param    $orderNo           订单编号
     * @param    $amount            账户余额
     * @param    $transactionId     第三方交易流水号
     * @param    $type              类型
     * @param    $remark            备注
     * @param    $productionId      产品id
     * @param    $uid               用户id
     * @return   boolean
     */
    public function addFlowingRecord($uid, $suid, $transAmount, $orderNo, $amount, $transactionId = 0, $type = 1, $remark ='', $productionId = 0)
    {
        $record = [
            'uid'            => $uid,
            'suid'           => $suid,
            'type'           => $type,
            'trans_amount'   => $transAmount,
            'amount'         => $amount,
            'order_no'       => $orderNo,
            'product_id'     => $productionId,
            'transaction_id' => $transactionId,
            'remarks'        => $remark,
            'add_time'       => time(),
        ];
        $res = $this->table($this->_userFlowingTable)->insert($record);
        return $res;
    }

    /**
     * 获取用户收益统计
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @return   array
     */
    public function getStatistics($uid, $startTime = 0, $endTime = 0)
    {
        $res = $this->table($this->_userFlowingTable)->field('abs(sum(trans_amount)) as total_profit, type')->group('type')->where(['uid' => $uid])->select();
        return $res;
    }

    /**
     * 获取用户收益明细
     * @author   xiexy
     * @date     2017-09-25
     * @param    int    $saleUserId   咪小二id
     *  @param   int    $startTime    开始时间
     * @param    int    $endTime      终止时间
     * @return   array
     */
    public function getProfitDetail($uid, $suid, $startTime = 0, $endTime = 0, $limit = 15, $query = [], $order = 'uf.id DESC')
    {
        $condition                           = [];
        $uid && $condition['uf.uid']         = $uid;
        $suid && $condition['uf.suid']       = $suid;
        $startTime && $condition['add_time'] = ['egt', $startTime];
        $endTime && $condition['add_time']   = ['elt', $endTime];
        $field                               = 'uf.type, uf.trans_amount, uf.amount as balance, from_unixtime(uf.add_time) as add_time, uo.amount, uo.wx_openid, u.wx_nickname';

        $profitDetail = $this->table($this->_userFlowingTable)
            ->alias('uf')
            ->join('user_order uo', 'uf.order_no = uo.order_no')
            ->join('user u', 'u.wx_openid = uo.wx_openid', 'left')
            ->field($field)
            ->where($condition)
            ->order($order)
            ->paginate($limit, false, ['query' => $query]);

        return $profitDetail;
    }

    /**
     *  获取渠道\代理\场所\设备 下的咪小二分润总额
     *  @author xiexy
     *  @date   2017-10-24
     *  @param  int  $channelId   渠道id
     *  @param  int  $placeId     场所id
     *  @param  int  $agentId     代理id
     *  @param  int  $machineId   设备id
     *  @param  int  $startTime   开始时间
     *  @param  int  $endTime     终止时间
     *  @return integer
     */
    public function getTotalProfit($channelId = 0, $agentId = 0, $placeId = 0, $machineId = 0, $startTime = 0, $endTime = 0)
    {
        $where['uf.type']     = self::CONSUME_TYPE;
        $where['uf.add_time'] = ['between', [$startTime, $endTime]];

        $channelId && $where['uo.channel_id'] = (int) $channelId;
        $agentId && $where['uo.agent_id']     = (int) $agentId;
        $placeId && $where['uo.place_id']     = (int) $placeId;
        $machineId && $where['uo.machine_id'] = (int) $machineId;

        $totalProfit = $this->table($this->_userFlowingTable)
            ->alias('uf')
            ->field('sum(trans_amount) as profit')
            ->where($where)
            ->join('user_order uo', 'uo.order_no = uf.order_no')
            ->find();
        return $totalProfit ? $totalProfit['profit'] : 0;
    }

    /**
     *  更新第三方订单流水号
     *  @author xiexy
     *  @date   2017-10-24
     *  @param  int  $channelId   渠道id
     *  @param  int  $placeId     场所i
     *  @return boolean
     */
    public function updateTransactionId($newMchBillno, $oldMchBillno, $transactionId)
    {
        $res = $this->table($this->_userFlowingTable)->where(['order_no' => $oldMchBillno])->update(['order_no' => $newMchBillno, 'transaction_id' => $transactionId]);
        return $res ? true : false;
    }


    /**
     *  获取账户余额
     *  @author xiexy
     *  @date   2017-10-24
     *  @param  int  $uid   用户id
     *  @return int
     */
    public function getUserBalance($uid)
    {
        $balance = $this->table($this->_userFlowingTable)
            ->where(['uid' => $uid])
            ->field('amount')
            ->order('add_time DESC')
            ->limit(0, 1)
            ->find();

        return isset($balance['amount']) ? $balance['amount'] : 0;
    }


    /**
     * 批量增加流水记录
     * @author   xiexy
     * @return   boolean
     */
    public function addFlowingList($list = [])
    {
        $res = $this->table($this->_userFlowingTable)->insertAll($list);
        return $res;
    }


    /**
     * 查询流水记录
     * @author   xiexy
     * @return   boolean
     */
    public function getFlowingInfo($orderNo, $uid, $field = '*')
    {
        if (!$orderNo || !$uid) {
            return [];
        }

        $condition = ['order_no' => $orderNo, 'uid' => (int) $uid];
        $res = $this->table($this->_userFlowingTable)->where($condition)->field($field)->find();
        return $res;
    }
}

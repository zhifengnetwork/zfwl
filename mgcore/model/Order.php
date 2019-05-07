<?php
/**
 * 订单相关模型
 * @author cyw
 * @date   2017-07-27
 *
 */

namespace mgcore\model;

use think\Db;
use think\Model;
use think\Config;

class Order extends Model
{
    private $_orderTable     = 'user_order';
    private $_refundLogTable = 'order_refund_log';

    //已付款状态
    const PAY_STATUS    = 1;
    //已退款状态
    const REFUND_STATUS = 1;
    //已使用状态
    const USED_STATUS   = 1;
    //未退款状态
    const NOT_REFUND_STATUS = 0;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 获取订单的当前支付状态
     * @author cyw
     * @date   2017-07-27
     *
     * @param int $orderNum
     * @return int
     */
    public function getPayStatus($orderNum)
    {
        // 判断传入值是否是字符串
        if (!is_string($orderNum)) {
            return false;
        }

        $where = [
            'order_id' => $orderNum,
        ];

        $result = $this->table($this->_orderTable)->field('paid')->where($where)->find();
        return $result;
    }

    /**
     * 获取订单的当前退款状态
     * @author cyw
     * @date   2017-07-27
     *
     * @param int $orderNum
     * @return int
     */
    public function getRefundStatus($orderNum)
    {
        // 判断传入值是否是字符串
        if (!is_string($orderNum)) {
            return false;
        }

        $where = [
            'order_id' => $orderNum,
        ];

        $result =$this->table($this->_orderTable)->field('refunded')->where($where)->find();
        return $result;
    }

    /**
     * 获取订单是否是已经使用
     * @author cyw
     * @date   2017-07-27
     *
     * @param int $orderNum
     * @return int
     */
    public function getIsUsedStatus($orderNum)
    {
        // 判断传入值是否是字符串
        if (!is_string($orderNum)) {
            return false;
        }

        $where = [
            'order_id' => $orderNum,
        ];

        $result = $this->table($this->_orderTable)->field('is_used')->where($where)->find();
        return $result;
    }

    /**
     * 获取订单的详情
     * @author cyw
     * @date   2017-07-27
     *
     * @param int $orderNum
     * @return int
     */
    public function getOrderInfo($orderId, $field = '*')
    {
        // 判断传入值是否是字符串
        if (!$orderId) {
            return false;
        }

        $where  = ['order_id' => $orderId];
        $result = $this->table($this->_orderTable)->field($field)->where($where)->find();
        return $result;
    }

    /**
     * 添加退款记录
     * @author dwer
     *
     * @param string $orderNo
     * @param int $status
     * @param string $errMsg
     * @param int $loginId
     * @param int $refundFee
     * @param int $totalFee
     * @param string $outRefundNo
     * @param string $inRefundNo
     */
    public function addRefundLog($orderNo, $status, $errMsg, $loginId, $refundFee, $totalFee, $inRefundNo, $outRefundNo = '')
    {
        if (!$orderNo || !in_array($status, [0, 1, 2])) {
            return false;
        }

        $refundInfo = $this->getRefundLogInfo($orderNo, 'id');
        if ($refundInfo) {
            //更新状态
            $where = ['order_no' => $orderNo];
            $data  = [
                'status'      => $status,
                'err_msg'     => $errMsg,
                'update_time' => time(),
                'login_id'    => $loginId,
            ];
            $tmp = $this->table($this->_refundLogTable)->where($where)->update($data);
            $res = $tmp ? true : false;
        } else {
            //新增记录
            $data = array(
                'order_no'      => $orderNo,
                'create_time'   => time(),
                'update_time'   => time(),
                'status'        => $status,
                'err_msg'       => $errMsg,
                'refund_fee'    => $refundFee,
                'total_fee'     => $totalFee,
                'out_refund_no' => $outRefundNo,
                'in_refund_no'  => $inRefundNo,
                'login_id'      => $loginId,
            );
            $tmp = $this->table($this->_refundLogTable)->insert($data);
            $res    = $tmp ? true : false;
        }

        return $res;
    }

    /**
     * 获取退款日志的详情
     * @author cyw
     * @date   2017-07-31
     *
     * @param int $orderNum
     * @return int
     */
    public function getRefundLogInfo($orderNum, $field = '*')
    {
        // 判断传入值是否是字符串
        if (!is_string($orderNum)) {
            return false;
        }

        $where = [
            'order_no' => $orderNum,
        ];

        $result = $this->table($this->_refundLogTable)->field($field)->where($where)->find();
        return $result;
    }

    /**
     * 获取退款日志的详情
     * @author cyw
     * @date   2017-07-31
     *
     * @param int $orderNum   退款单号
     * @param int $refundFee 退款金额
     * @return int
     */
    public function getRefundTimeOutLog($orderNum, $refundFee, $field = '*')
    {
        // 判断传入值是否是字符串
        if (!is_string($orderNum) || !is_numeric($refundFee)) {
            return false;
        }

        $where = [
            'order_no'   => $orderNum,
            'status'     => '2',
            'refund_fee' => $refundFee,
        ];

        $result = $this->table($this->_refundLogTable)->field($field)->where($where)->find();
        return $result;
    }

    /**
     * 修改订单退款状态和退款金额
     * @param int $orderNum 订单号
     * @param string $refundFee 退款金额 - 单位分
     * @return bool
     */
    public function updateRefundStatus($orderNo, $refundFee)
    {
        $where = ['order_no' => $orderNo];
        $data  = [
            'refunded'         => 1,
            'amount_refunded'  => $refundFee,
            'sale_user_profit' => 0,
        ];
        $result = $this->table($this->_orderTable)->where($where)->update($data);
        return $result;
    }



    /**
     * 获取各场所每日报表统计
     * @param int $startTime 开始时间戳
     * @param int $endTime   终止时间戳
     * @return array
     */
    public function getDayStatic($startTime, $endTime, $group = 'place_id', $placeId = '')
    {
        if ($endTime - $startTime > 3600 * 24) {
            return false;
        }

        $where = [
            'paid'      => self::PAY_STATUS,
            'refunded'  => self::NOT_REFUND_STATUS,
            'time_paid' => ['between', [$startTime, $endTime]]
        ];
        $placeId && $where['place_id'] = $placeId;

        $field = 'machine_id, channel_id, agent_id, place_id, area_id, count(1) order_num,
                  sum(amount) as total_amount, sum(amount_refunded) as refund_amount, sum(sale_user_profit) as sale_user_profit,
                  sum(round(amount * 0.994) - sale_user_profit)  as profit_amount,
                  sum((round(amount * 0.994) - sale_user_profit) * place_div) as place_profit,
                  sum((round(amount * 0.994) - sale_user_profit) * agent_div) as agent_profit,
                  sum((round(amount * 0.994) - sale_user_profit) * channel_div) as channel_profit,
                  sum((round(amount * 0.994) - sale_user_profit) * pf_div) as platform_profit';

        $data = $this->table($this->_orderTable)->where($where)->field($field)->group($group)->select();
        return $data;
    }


    /**
     * 获取各场所每日退款金额统计
     * @param int $startTime 开始时间戳
     * @param int $endTime   终止时间戳
     * @return array
     */
    public function getRefundStatic($startTime, $endTime, $group = 'place_id', $placeId = '')
    {
        if ($endTime - $startTime > 3600 * 24) {
            return false;
        }

        $where = [
            'paid'      => self::PAY_STATUS,
            'time_paid' => ['between', [$startTime, $endTime]]
        ];
        $placeId && $where['place_id'] = $placeId;

        $data = $this->table($this->_orderTable)->where($where)->field('machine_id, place_id, sum(amount_refunded) refund_amount')->group($group)->select();
        return $data;
    }


    /**
     * 获取订单列表
     * @param int $startTime 开始时间戳
     * @param int $endTime   终止时间戳
     * @param arr $placeId   场所id
     * @return array
     */
    public function getOrderList($startTime, $endTime, $placeId = [], $limit = 15, $query = [], $orderBy = 'uo.create_time DESC')
    {

        $where = [
            'uo.paid'           => self::PAY_STATUS,
            'uo.create_time' => ['BETWEEN', [$startTime, $endTime]]
        ];

        $placeId && $where['uo.place_id'] = ['IN', $placeId];
        $field = 'FROM_UNIXTIME(uo.time_paid, "%Y-%m-%d %H:%i") as time_paid, uo.amount, u.wx_nickname, m.name, uo.refunded';

        $list  = $this->table($this->_orderTable .' uo')
                ->field($field)
                ->join('machine m', 'm.machine_id = uo.machine_id')
                ->join('user u', 'u.wx_openid = uo.wx_openid')
                ->where($where)
                ->order($orderBy)
                ->paginate($limit, false, ['query' => $query]);

        return $list;
    }
    /***
     * 更新订单状态
     */
    public function update_order_info($has_order,$end_price){
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
            'transaction_id' => $has_order['order_no'],
            'end_price'      => $end_price,//主账户最终收益
        ];
        Db::table('user_order')->where(['order_no' => $has_order['order_no']])->update($order);
    }
    /***
     * 收益统计
     */
    public function total($has_order,$end_price,$pay_type){
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
                'order_no'       => $has_order['order_no'], //订单号
                'place_id'       => $has_order['place_id'], 
                'machine_id'     => $has_order['machine_id'], 
                'order_id'       => $has_order['order_id'],
                'place_name'     => $has_order['place_name'],
                'order_time'     => $has_order['order_time'],
                'order_amount'   => $has_order['order_amount'],
                'good_name'      => $has_order['good_name'],
                'good_price'     => $has_order['good_price'],
                'order_type'     => 1,
                'pay_type'       => $pay_type,
                'service_name'   => $has_order['service_name'],
                'end_price'      => $end_price,//主账户最终收益
                'create_time'    => time(),
            ];
            Db::table('total_order')->insert($total_order);
              //商户总收益和余额
          Db::table('account_remainder')->insert(['agent_id'=>$has_order['agent_id'],'account_id'=>$has_order['agent_id'],'order_id'=> $has_order['order_id'], 'create_time' => time(),'platform_id' => $has_order['platform_id'],'price'=> $end_price,]);
    }
    /***
     * 商户总收益和余额||今日收益
     */
    public function agent_total($agent_id,$end_price){
        //商户总收益和余额
        $rema = [
            'order_num'         =>  ['exp', 'order_num+1'],
            'remainder'         =>  ['exp', 'remainder+'.$end_price.''],
            'profit'            =>  ['exp', 'profit+'.$end_price.''],
        ];
        Db::table('agent')->where(['agent_id'=>$agent_id])->update($rema);
        //商户今日收益
        $todaywhere['agent_id']    =    $agent_id;
        $todaywhere['create_time'] =    strtotime(date("Y-m-d"));
        $today_info = Db::table('today_profit')->where($todaywhere)->find();
        if($today_info){
            $todayprofit = [
                'profit'            =>  ['exp', 'profit+'.$end_price.''],
            ];
            Db::table('today_profit')->where($todaywhere)->update($todayprofit);
        }else{
            $todaywhere['profit'] = $end_price;
            Db::table('today_profit')->insert($todaywhere);
        }
    }


    /***
     * 子账号收益
     */
    public function account_total($has_order){
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
       
   }

}

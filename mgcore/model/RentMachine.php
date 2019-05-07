<?php
/**
 * 按摩椅租赁模型
 *
 */

namespace mgcore\model;
use think\Model;

class RentMachine extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    //表名
    private $_orderTable = 'rent_order';

    //是否已支付 0-未支付 1-已支付
    const IS_PAID = 1;
    const NO_PAID = 0;

    //订单状态
    const ALL_STATUS          = -1;//全部
    const INIT_STATUS         = 0; //无状态
    const WAIT_DELIVER_STATUS = 1; //待发货
    const DELIVERED_STATUS    = 2; //已发货
    const CONFIRM_STATUS      = 3; //确认收货
    const CANCLE_STATUS       = 4; //已取消
    const RETURNING_STATUS    = 5; //退货中
    const RETURNED_STATUS     = 6; //已退货
    const EXPIRE_STATUS       = 7; //已到期

    //颜色分类
    const RED_COLOR  = 2;
    const GOLD_COLOR = 1;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }


    /**
     * 获取订单详情
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   array
     */
    public function getOrderInfo($orderNo, $field = '*', $join = false)
    {
        if ($join) {
            $field = 'ro.*, u.mobile, u.wx_openid';
            $orderInfo = $this->table($this->_orderTable)->alias('ro')->field($field)->join('user u', 'u.uid = ro.uid')->where(['ro.order_no' => $orderNo])->find();
        } else {
            $orderInfo = $this->table($this->_orderTable)->field($field)->where(['order_no' => $orderNo])->find();
        }

        return $orderInfo ? $orderInfo : [];
    }


    /**
     * 支付完成更新订单状态
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function completePayment($orderNo, $transactionId, $totalMoney)
    {
        $data = [
            'paid'           => self::IS_PAID,
            'status'         => self::WAIT_DELIVER_STATUS,
            'transaction_id' => $transactionId,
            'amount'         => $totalMoney,
            'paid_time'      => time()
        ];

        $res  = $this->table($this->_orderTable)->where(['order_no' => $orderNo])->update($data);
        return $res;
    }


    /**
     * 添加订单
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function genOrder($orderNo, $openId, $uid, $amount, $num, $name, $mobile, $address, $orderDesc, $remark, $color)
    {
        if (!($amount = (int) $amount) || !($num = (int) $num) || !$openId) {
            return false;
        }

        $orderInfo = [
            'order_no'      => $orderNo,
            'openid'        => $openId,
            'uid'           => $uid,
            'amount'        => $amount,
            'num'           => $num,
            'machine_color' => $color,
            'receiver_name' => $name,
            'mobile'        => $mobile,
            'address'       => $address,
            'desc'          => $orderDesc,
            'remark'        => $remark,
            'create_time'   => time()
        ];

        $res  = $this->table($this->_orderTable)->insert($orderInfo);
        return $res;
    }


    /**
     * 修改订单状态
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function updateOrderStatus($orderNo, $status, $version)
    {
        $data = ['status' => $status, 'version' => $version + 1];

        if ($status == self::RETURNING_STATUS) {

            $data['apply_refund_time'] = time();
        } elseif ($status == self::CONFIRM_STATUS) {

            $data['confirm_time'] = time();
        } elseif ($status == self::DELIVERED_STATUS) {

            $data['express_time'] = time();
        } else {

            $data['update_time'] = time();
        }

        $res = $this->table($this->_orderTable)->where(['order_no' => $orderNo, 'version' => $version])->update($data);
        return $res;
    }


    /**
     * 获取不同状态的订单
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function getOrderByStatus($status = '', $field = '*', $limit, $query, $order = 'create_time DESC')
    {
        $condition = [];
        $status !== '' && $condition = ['status' => (int) $status];
        $orderList = $this->table($this->_orderTable)->where($condition)->field($field)->order($order)->paginate($limit, false, ['query' => $query]);;
        return $orderList ? $orderList : [];
    }


    /**
     * 获取不同状态的订单
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function updateExpressNo($orderNo, $expressNo, $version)
    {
        $condition = ['order_no' => $orderNo, 'version' => $version ];
        $orderData = ['express_no' => $expressNo, 'status' => self::DELIVERED_STATUS, 'express_time' => time()];
        $updateRes = $this->table($this->_orderTable)->where($condition)->update($orderData);
        return $updateRes;
    }


    /**
     * 取消订单
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function cancelOrder($orderNo, $version)
    {
        $condition = ['order_no' => $orderNo, 'version' => $version ];
        $orderData = ['status' => self::CANCLE_STATUS, 'update_time' => time()];
        $updateRes = $this->table($this->_orderTable)->where($condition)->update($orderData);
        return $updateRes;
    }


    /**
     * 获取个人订单列表
     * @author   xiexy
     * @date     2017-09-25
     * @param    $orderNo      订单编号
     * @return   integer
     */
    public function getOrderListByStatus ($status = self::ALL_STATUS, $uid = 0, $field = '*', $offset, $limit = 15)
    {
        $condition = [];
        if ($uid) {
            $condition['uid'] = $uid;

        }

        if ($status != self::ALL_STATUS) {
            $condition['status'] = (int) $status;

        }

        $orderList =  $this->table($this->_orderTable)
            ->where($condition)
            ->field($field)
            ->order('status ASC, create_time DESC')
            ->limit($offset, $limit)
            ->select();

        return $orderList;
    }
}
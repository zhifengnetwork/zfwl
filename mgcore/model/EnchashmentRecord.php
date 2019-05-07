<?php
/**
 * 咪小二提現模型
 *
 */
namespace mgcore\model;

use think\Model;

class EnchashmentRecord extends Model
{

    //默认数据库配置
    protected $connection = 'database';

    private $_table = 'enchashment_record';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    //申请提现状态 1-申请中 2-提现成功
    const STATUS_APPLYING = 1;
    const STATUS_CASHED   = 2;

    //红包状态 0-未知 1-已领取 2-已退款
    const RECEIVE_STATUS_UNKNOWN  = 0;
    const RECEIVE_STATUS_RECEIVED = 1;
    const RECEIVE_STATUS_REFUND   = 2;

    /**
     * 添加申请提现记录
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId   咪小二id
     * @param    $amount       提现金额
     * @param    $status       提现状态
     * @return   array
     */
    public function addRecord($saleUserId, $uid, $amount, $status = self::STATUS_APPLYING)
    {
        $record = [
            'suid'        => $saleUserId,
            'amount'      => $amount,
            'uid'         => $uid,
            'status'      => $status,
            'mch_billno'  => 'xmfh' . date('YmdHis', time()) . $saleUserId .  $amount,
            'create_time' => time(),
        ];

        $res = $this->table($this->_table)->insert($record, false, true);
        return $res;
    }

    /**
     * 获取提现记录
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId   咪小二id
     * @param    $amount       提现金额
     * @param    $status       提现状态
     * @return   array
     */
    public function getRecord($startTime = 0, $endTime = 0, $status = 0, $saleUserId = 0, $offset = 0, $limit = 15, $orderBy = 'id desc')
    {
        $query = [];
        $where = [];

        if ($startTime && $endTime) {
            $where['er.create_time'] = ['between', [$startTime, $endTime]];
            $query['start']          = date('Y-m-d', $startTime);
            $query['end']            = date('Y-m-d', $endTime);
        }

        if ($status) {
            $where['er.status'] = $status;
            $query['status']    = $status;
        }

        if ($saleUserId) {
            $where['er.suid'] = $saleUserId;
            $query['suid']    = $saleUserId;
        }

        $field  = 'su.id, su.name, u.mobile, u.wx_openid, from_unixtime(er.create_time) as create_time, er.status, er.amount ,er.id as eid';
        $record = $this->table($this->_table)
            ->alias('er')
            ->field($field)
            ->join('sale_user su', 'su.id = er.suid', 'LEFT')
            ->join('user u', 'u.uid = er.uid', 'LEFT')
            ->where($where)
            ->limit($offset, $limit)
            ->order($orderBy)
            ->paginate($limit, false, ['query' => $query]);

        return $record;
    }

    /**
     * 根据id获取提现记录
     * @author   xiexy
     * @date     2017-09-25
     * @param    array    $idArr       申请提现表id
     * @param    string   $field       返回字段
     * @param    integer  $status      提现状态
     * @return   array
     */
    public function getRecordListById($idArr = [], $status = self::STATUS_APPLYING, $orderBy = 'er.id DESC')
    {
        $where                                                = [];
        is_array($idArr) && !empty($idArr) && $where['er.id'] = ['IN', $idArr];
        $status && $where['er.status']                        = (int) $status;
        $field                                                = 'er.suid, er.amount, er.mch_billno, u.wx_openid';
        $res                                                  = $this->table($this->_table)
            ->alias('er')
            ->field($field)
            ->join('user u', 'er.uid = u.uid', 'LEFT')
            ->where($where)
            ->order($orderBy)
            ->select();
        return $res;
    }

    /**
     * 提现打款成功
     * @author   xiexy
     * @date     2017-09-25
     * @param    array    $idArr          申请记录表id
     * @param    string   $send_listid    红包订单的微信单号
     * @param    integer  $pay_time       打款时间
     * @return   array
     */
    public function doCashSucc($mchBillNo, $send_listid, $pay_time)
    {
        $data = [
            'status'         => self::STATUS_CASHED,
            'send_listid'    => $send_listid,
            'receive_status' => self::RECEIVE_STATUS_UNKNOWN,
            'update_time'    => $pay_time,
        ];
        $res = $this->table($this->_table)->where(['mch_billno' => $mchBillNo])->update($data);
        return $res;
    }

    /**
     * 根据id获取提现记录
     * @author   xiexy
     * @date     2017-09-25
     * @param    array    $idArr       申请提现表id
     * @param    string   $field       返回字段
     * @param    integer  $status      提现状态
     * @return   array
     */
    public function getRecordById($id, $status = self::STATUS_APPLYING)
    {
        $field = 'u.wx_openid, er.*';
        $res   = $this->table($this->_table)
            ->alias('er')
            ->field($field)
            ->join('user u', 'u.uid = er.uid')
            ->where(['id' => $id, 'status' => $status])
            ->find();
        return $res;
    }

    /**
     * 获取需要查询红包状态的列表
     * @author xiexy
     * @date   2017-09-25
     *
     * @param  date $calcDay
     * @return array
     */
    public function getRedQueryList($calcDay)
    {
        if (!$calcDay || !strtotime($calcDay)) {
            return [];
        }

        $field   = 'mch_billno, id';
        $endTime = strtotime($calcDay . ' 00:00:00');
        $where   = [
            'create_time'    => ['ELT', $endTime],
            'status'         => self::STATUS_CASHED,
            'receive_status' => self::RECEIVE_STATUS_UNKNOWN,
        ];
        $orderStr = "id desc";
        $list     = $this->table($this->_table)->where($where)->field($field)->order($orderStr)->page('1, 500')->select();
        return $list ? $list : [];
    }

    /**
     * 更新红包领取的状态
     * @author xiexy
     * @date   2017-09-07
     *
     * @param  string $mchBillno 本系统的红包交易流水号
     * @param  string $receiveStatus RECEIVED=已经领取 REFUND=已经退款
     * @return bool
     */
    public function updateRedStatus($mchBillno, $receiveStatus = 'RECEIVED')
    {
        $statusArr = ['RECEIVED' => 1, 'REFUND' => 2];

        if (!$mchBillno || !array_key_exists($receiveStatus, $statusArr)) {
            return false;
        }

        $where = ['mch_billno' => $mchBillno];
        $data  = ['receive_status' => $statusArr[$receiveStatus]];

        $res = $this->table($this->_table)->where($where)->update($data);
        return $res ? true : fasle;
    }

    /**
     * 更新支付订单号
     * @author xiexy
     * @date   2017-10-24
     *
     * @param  string  $mchBillno  本系统的红包交易流水号
     * @param  integer $id         id
     * @return bool
     */
    public function udpateMchBillno($id, $mchBillno)
    {
        $res = $this->table($this->_table)->where(['id' => $id])->update(['mch_billno' => $mchBillno]);
        return $res ? true : false;
    }



    /**
     * 更新支付订单号
     * @author xiexy
     * @date   2017-10-24
     *
     * @param  string  $mchBillno  本系统的红包交易流水号
     * @param  integer $id         id
     * @return bool
     */
    public function getRecordByStatus($status = self::STATUS_CASHED, $receiveStatus = self::RECEIVE_STATUS_REFUND)
    {
        $condition = ['status' => $status, 'receive_status' => $receiveStatus];
        $res =  $this->table($this->_table)->where($condition)->select();
        return $res;
    }

}


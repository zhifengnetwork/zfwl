<?php
namespace mgcore\model;

use think\Db;
use think\Model;
use \mgcore\model\Stat;

class Settlereport extends Model
{
    // 定义
    private $_settleTabel = 'settle_report';

    /**
     * 判断是否存在该日期的数据
     * @author cyw
     * @date 17-08-09
     * @param date|int $date 201707
     * @param int      $receiveId
     * @param string   $type
     * @return $this
     */
    public function isExist($date, $receiveId, $type = 'place')
    {
        if (!in_array($type, ['place', 'agent', 'channel'])) {
            return false;
        }
        switch ($type) {
            case 'place':
                $typeId = 1;
                break;
            case 'agent':
                $typeId = 2;
                break;
            case 'channel':
                $typeId = 3;
                break;
        }

        $where = [
            'date'       => $date,
            'receive_id' => $receiveId,
            'type'       => $typeId,
        ];

        $res = Db::table($this->_settleTabel)
            ->where($where)
            ->field('id')
            ->find();
        return $res ? $res : '';
    }

    /**
     * 判断是否存在该日期的数据
     * @author cyw
     * @date 17-08-09
     * @param $date
     * @return array|''
     */
    public function isExistMonth($date)
    {
        if (!$date) {
            return false;
        }

        $where = [
            'date' => $date,
        ];

        $res = Db::table($this->_settleTabel)
            ->where($where)
            ->field('id')
            ->find();
        return $res ? $res : '';
    }

    /**
     * 判断是否存在该日期的数据
     * @author cyw
     * @date 17-08-09
     * @param $date
     * @return int
     */
    public function delMonth($date)
    {
        if (!$date) {
            return false;
        }

        $where = [
            'date' => $date,
        ];

        $res = Db::table($this->_settleTabel)
            ->where($where)
            ->delete();
        return $res ? $res : 0;
    }

    /**
     * 插入数据
     * @author cyw
     * @date 17-08-09
     * @param string  date          日期
     * @param int     $receiveId    伙伴id
     * @param string  $actualAmount 实收金额
     * @param string  $cardNo       卡号
     * @param string  $cardOpenbank 开户行
     * @param string  $cardUname    持卡人
     * @param string  $subbranchNo  支行行号
     * @param int     $type         1：场地 2：代理 3：合作伙伴
     *
     */
    public function insert($date, $receiveId, $actualAmount, $cardNo, $cardOpenbank, $cardUname, $subbranchNo = '', $type = 1)
    {
        $data = [
            'date'          => $date,
            'receive_id'    => $receiveId,
            'actual_amount' => $actualAmount,
            'card_no'       => $cardNo,
            'card_openbank' => $cardOpenbank,
            'card_uname'    => $cardUname,
            'subbranch_no'  => $subbranchNo,
            'type'          => $type,
        ];

        $res = Db::table($this->_settleTabel)->insert($data);
        return $res ? $res : [];
    }

    /**
     * 修改付款状态
     * @param int  $id     列表id
     * @param int  $status 0:未付款 1: 已付款
     * @param int  $loginId 操作人id
     *
     */
    public function editStatus($id, $status, $loginId)
    {
        if (!$id) {
            return false;
        }

        $data = [
            'pay_status' => $status,
            'pay_time'   => time(),
            'login_id'   => $loginId,
        ];

        return Db::table($this->_settleTabel)->where(['id' => $id])->update($data);
    }

    /**
     * 获取列表数据
     * @author cyw
     * @date 17-08-09
     * @param  string $date 日期
     * @param  int    $type 1:场地 2：代理 3：合作伙伴
     * @param  array  $params 用户输入数组
     * @param  int    $payStatus 0：未付款 1：已付款
     * @param  int    $receiveId 合作伙伴的id
     * @return array
     *
     */
    public function getList($date, $params, $type = 1, $payStatus = '', $receiveId = '')
    {
        if (!$date || !$type) {
            return false;
        }

        $where = [
            'type' => $type,
            'date' => $date,
        ];

        if ($receiveId !== '') {
            $where['receive_id'] = $receiveId;
        }

        if ($payStatus !== '') {
            $where['pay_status'] = $payStatus;
        }

        $field = 'id, receive_id, date, actual_amount, card_no, card_openbank,
                    card_uname, subbranch_no, pay_status, type, pay_time';

        $res = Db::table($this->_settleTabel)->field($field)->where($where)->paginate(30, false, ['query' => $params]);

        return $res ? $res : [];
    }

    /**
     * 取得记录最后一条数据
     * @author cyw
     * @date 17-08-09
     * @return array
     *
     */
    public function getLastRecord()
    {
        $field = 'date';
        $res   = Db::table($this->_settleTabel)->field($field)
            ->order('id', 'DESC')
            ->limit('1')
            ->find();
        return $res ? $res : [];
    }


    /**
     * 打款成功更新状态
     * @author xeixy
     * @date 17-11-13
     * @param  $transTime    打款日期
     * @param  $cardNo       对方账号
     * @param  $cardUname    账号名
     * @return array
     *
     */
    public function completePay($transTime, $cardNo, $cardUname)
    {
        if (!$transTime || !$cardNo || !$cardUname) {
            return false;
        }

        $transMonth = date('Ym', strtotime($transTime));
        $condition  = " date = {$transMonth } - 1 AND card_uname = '{$cardUname}' AND TRIM(replace(card_no, ' ', '')) = {$cardNo} ";

        $res  = Db::table($this->_settleTabel)->where($condition)->update(['pay_status' => 1, 'pay_time' => strtotime($transTime)]);
        return $res;
    }


    /**
     * 获取渠道/代理/场所打款记录
     * @author xiexy
     * @date 17-11-22
     * @return array
     *
     */
    public function getRecord($receiveId, $type)
    {
        if (!$receiveId || !$type) {
            return false;
        }

        $where  = ['receive_id' => (int) $receiveId, 'type' => (int) $type];
        $field  = 'date, pay_time, pay_status';

        $record = Db::table($this->_settleTabel)->where($where)->field($field)->select();
        return $record;
    }

}

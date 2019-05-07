<?php
namespace mgcore\model;

use think\Db;
use think\Model;
use \mgcore\model\Machine;
use \mgcore\model\Stat;

class Statis extends Model
{
    private $_orderTable    = 'user_order';
    private $_mStatisTable  = 'statistics_report_machine';
    private $_pStatisTable  = 'statistics_report_place';
    private $_dateTmpTable  = 'date_tmp';


    //统计类型 1-场所 2-代理 3-渠道 4-平台
    const STATIS_TYPE_PLACE     = 1;
    const STATIS_TYPE_AGENT     = 2;
    const STATIS_TYPE_CHANNEL   = 3;
    const STATIS_TYPE_PLATFORM  = 4;


    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    public static function orderstatis($timetype = 0, $begin_time = null, $end_time = null, $agent_id = 0, $machine = '', $area_id = 0, $place = '')
    {
        $where = ['paid' => 1];

        if ($agent_id) {
            $where['agent_id'] = $agent_id;
        }

        if (!empty($machine)) {
            $where['machine_id'] = ['IN', $machine];
        }
        if (!empty($place)) {
            $where['place_id'] = $place;
        }

        //快捷时间处理
        if (!($begin_time || $end_time) && $timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        }
        $begin_time && ($where['time_paid'][] = ['>=', $begin_time]);
        $end_time && ($where['time_paid'][] = ['<', $end_time]);

        return Db::table('user_order')
            ->where($where)
            ->field('SUM(order_amount) AS order_amount, COUNT(order_id) AS order_num');
    }

    public static function orderamount($timetype = 0, $begin_time = null, $end_time = null, $agent_id = 0, $machine = '', $place = '')
    {
        //把已经退款的数据排除
        $where = [
            'paid'     => 1,
            'refunded' => 0,
        ];

        if ($agent_id) {
            $where['agent_id'] = $agent_id;
        }

        if (!empty($machine)) {
            $where['machine_id'] = ['IN', $machine];
        }
        if (!empty($place)) {
            $where['place_id'] = is_numeric($place) ? $place : ['IN', $place];
        }

        //快捷时间处理
        if ($timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        }
        if ($begin_time && $end_time) {
            $where['time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['time_paid'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['time_paid'] = ['<', $end_time];
        }

        $amount = Db::table('user_order')->where($where)->sum('order_amount');
        return $amount;
    }

    /**
     * 统计退款的金额
     * @param int $timetype
     * @param null $begin_time
     * @param null $end_time
     * @param int $agent_id
     * @param string $machine
     * @param string $place
     * @return float|int
     *
     */
    public static function refundFeeSum($timetype = 0, $begin_time = null, $end_time = null, $agent_id = 0, $machine = '', $place = '')
    {
        $where = ['paid' => 1, 'refunded' => 1]; // 选择已支付的 已退款的

        if ($agent_id) {
            $where['agent_id'] = $agent_id;
        }

        if (!empty($machine)) {
            $where['machine_id'] = ['IN', $machine];
        }
        if (!empty($place)) {
            $where['place_id'] = is_numeric($place) ? $place : ['IN', $place];
        }

        //快捷时间处理
        if ($timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        }
        if ($begin_time && $end_time) {
            $where['time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['time_paid'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['time_paid'] = ['<', $end_time];
        }

        $amount = Db::table('user_order')->where($where)->sum('amount_refunded');
        $amount = sprintf("%1.2f", $amount / 100);
        return $amount;
    }

    /**
     * 设备数量统计
     * @param  integer $timetype   0 不限， 1-今日，2-本周，3-本月，4-本年
     */
    public static function machinecount($timetype = 0, $agent_id = 0, $place_id = 0, $area_id = 0, $begin_time = null, $end_time = null)
    {
        $where = [];
        if ($agent_id) {
            $where['agent_id'] = $agent_id;
        }
        if ($place_id) {
            $where['place_id'] = is_numeric($place_id) ? $place_id : ['IN', $place_id];
        }
        //快捷时间处理
        if ($timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        }

        if ($begin_time && $end_time) {
            $where['create_time'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['create_time'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['create_time'] = ['<', $end_time];
        }
        $where['product_state'] = 4;
        $machinecount           = Db::table('machine')->where($where)->count();

        return $machinecount;
    }

    /**
     * 获取平均日收益 todo 结构待改进 目前仅作参考
     * @param  integer $agent_id   [description]
     * @param  integer $place_id   [description]
     * @param  [type]  $begin_time [description]
     * @param  [type]  $end_time   [description]
     * @return [type]              [description]
     */
    public static function machinedayearnings($agent_id = 0, $place_id = 0, $begin_time = null, $end_time = null)
    {
        $machines = Machine::getmachine(0, $agent_id, $place_id);

        if (!empty($machines)) {
            $machine_ids  = array_column($machines, 'machine_id');
            $order_amount = Stat::order_group($machine_ids, 'machine_id');
            $average      = 0;
            foreach ($machines as $key => &$val) {
                $days           = (time() - $val['create_time']) / 86400 - 1;
                $days           = $days < 1 ? 1 : ceil($days);
                $val['average'] = empty($order_amount[$val['machine_id']]) ? 0 : $order_amount[$val['machine_id']]['order_amount'] / $days;
                $average += $val['average'];
            }
            return $average / count($machines);
        } else {
            return 0;
        }
    }

    public static function machine($agent_id, $kw = '', $machine = '')
    {
        $where['agent_id'] = $agent_id;
        if (!empty($kw)) {
            $where['location'] = $kw;
        }

        if (!empty($machine)) {
            $where['machine_id'] = ['IN', $machine];
        }

        return Db::table('machine')->where($where)->select();
    }

    /**
     * 区域场地数分组统计
     */
    public static function areaplacegroupcount($ids = [], $key = 'agent_id')
    {
        $where[$key]              = ['IN', $ids];
        $where['m.product_state'] = 4;
        $list                     = Db::table('place')->alias('p')
            ->field("COUNT(p.place_id) AS place_num, COUNT(m.machine_id) AS machine_num, p.$key, p.start_time, p.end_time")
            ->join('machine m', 'p.place_id = m.place_id', 'LEFT')
            ->where($where)
            ->group($key)
            ->select();

        if ($list) {
            $list = array_column($list, null, $key);
        }
        return $list;
    }

    /**
     * 场地未核对数分组统计
     */
    public static function notplacegroupcount($ids = [], $key = 'agent_id')
    {
        $where[$key]    = ['IN', $ids];
        $where['state'] = 0;
        $list           = Db::table('dispatch_place')->field("count(id) AS n_place_num, $key")
            ->where($where)
            ->group($key)
            ->select();
        if ($list) {
            $list = array_column($list, null, $key);
        }
        return $list;
    }

    /**
     * 场地已核对数分组统计
     */
    public static function yesplacegroupcount($ids = [], $key = 'agent_id')
    {
        $where[$key]    = ['IN', $ids];
        $where['state'] = 1;
        $list           = Db::table('dispatch_place')->field("count(id) AS y_place_num, $key")
            ->where($where)
            ->group($key)
            ->select();
        if ($list) {
            $list = array_column($list, null, $key);
        }
        return $list;
    }

    /**
     * 根据时间段统计渠道商分成收入
     * @author cyw
     * @date   2017-08-08
     * @param string $beginTime
     * @param string $endTime
     * @return array|bool|false|\PDOStatement|string|Model
     *
     */
    public function countChannelDivByTime($channelId, $beginTime = '', $endTime = '')
    {
        if (!$channelId) {
            return false;
        }
        $where = [
            'paid'       => 1,
            'channel_id' => $channelId,
        ];

        if ($beginTime && $endTime) {
            $where['time_paid'] = [['>=', $beginTime], ['<', $endTime]];
        } elseif ($beginTime) {
            $where['time_paid'] = ['>=', $beginTime];
        } elseif ($endTime) {
            $where['time_paid'] = ['<', $endTime];
        }

        $field  = 'channel_id, SUM(((amount - amount_refunded)*0.994 - sale_user_profit)*channel_div) AS actual_amount';
        $result = Db::table('user_order')->field($field)
            ->where($where)
            ->find();
        return $result;
    }

    /**
     * 根据时间段统计代理的分成收入
     * @author cyw
     * @date   2017-08-08
     * @param string $beginTime
     * @param string $endTime
     * @return array|bool|false|\PDOStatement|string|Model
     *
     */
    public function countAgentDivByTime($agentId, $beginTime = '', $endTime = '')
    {
        if (!$agentId) {
            return false;
        }
        $where = [
            'paid'     => 1,
            'agent_id' => $agentId,
        ];

        if ($beginTime && $endTime) {
            $where['time_paid'] = [['>=', $beginTime], ['<', $endTime]];
        } elseif ($beginTime) {
            $where['time_paid'] = ['>=', $beginTime];
        } elseif ($endTime) {
            $where['time_paid'] = ['<', $endTime];
        }

        $field  = 'channel_id, agent_id, SUM(((amount - amount_refunded) * 0.994 - sale_user_profit)*agent_div) AS actual_amount';
        $result = Db::table('user_order')->field($field)
            ->where($where)
            ->find();
        return $result;
    }

    /**
     * 根据时间段统计相关的分成收入
     * @author cyw
     * @date   2017-08-08
     * @param string $beginTime
     * @param string $endTime
     * @return array|bool|false|\PDOStatement|string|Model
     *
     */
    public function countPlaceDivByTime($placeId, $beginTime = '', $endTime = '')
    {
        if (!$placeId) {
            return false;
        }
        $where = [
            'paid'     => 1,
            'place_id' => $placeId,
        ];

        if ($beginTime && $endTime) {
            $where['time_paid'] = [['>=', $beginTime], ['<', $endTime]];
        } elseif ($beginTime) {
            $where['time_paid'] = ['>=', $beginTime];
        } elseif ($endTime) {
            $where['time_paid'] = ['<', $endTime];
        }

        $field  = 'channel_id, agent_id, place_id, SUM(((amount  - amount_refunded) * 0.994 - sale_user_profit)*place_div) AS actual_amount';
        $result = Db::table('user_order')->field($field)
            ->where($where)
            ->find();
        return $result;
    }

    /**
     * 统计时间段内的出单场所/代理/渠道商数量
     * @author cyw
     * @date   2017-08-08
     * @param int    $type   1:场地  2：代理商  3：合作伙伴
     * @param string $beginTime
     * @param string $endTime
     * @return array|false|\PDOStatement|string|Model
     *
     */
    public function getPartnerByTime($type = 1, $beginTime = '', $endTime = '')
    {
        if ($beginTime && $endTime) {
            $where['time_paid'] = [['>=', $beginTime], ['<', $endTime]];
        } elseif ($beginTime) {
            $where['time_paid'] = ['>=', $beginTime];
        } elseif ($endTime) {
            $where['time_paid'] = ['<', $endTime];
        } else {
            $where = 1;
        }

        switch ($type) {
            case '1':
                $field = 'DISTINCT(place_id) as id';
                break;
            case '2':
                $field = 'DISTINCT(agent_id) as id';
                break;
            case '3':
                $field = 'DISTINCT(channel_id) as id';
                break;
        }

        $result = Db::table('user_order')->field($field)
            ->where($where)
            ->select();
        return $result;
    }



    /**
     * 统计时间段内的场所每日报表数据
     * @author xiexy
     * @date            2017-11-21
     * @param int       $placeId  场所ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function placeDayStatic($placeId, $agentId, $startDate, $endDate, $profitField)
    {

        $field         = "dt.date, IFNULL(ps.{$profitField},0) profit_amount";
        $sqlCondition  = ['dt.date'  => ['between', [$startDate, $endDate]]];
        $agentCon      = '';

        if ($agentId) {
            $agentStr      = trim(implode(',', $agentId), ',');
            $agentCon   = "AND ps.agent_id IN ({$agentStr})";
        }
        $joinCondition = "ps.place_id = {$placeId} AND dt.date = ps.date ". $agentCon;

        $data = $this->table($this->_pStatisTable . ' ps')
                ->join($this->_dateTmpTable . ' dt', $joinCondition, 'right')
                ->where($sqlCondition)
                ->field($field)
                ->order('dt.date DESC')
                ->select();

        return $data;
    }

    /**
     * 统计时间段内的场所报表数据
     * @author xiexy
     * @date            2017-11-21
     * @param int       $placeId  场所ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function placeTotalStatic($placeId = [], $startDate, $endDate, $profitField = 'place_profit', $agentId = [])
    {
        $where = ['ps.place_id' => ['IN', $placeId]];

        if(!empty($agentId)) {
            $where['ps.agent_id'] =  ['IN', $agentId];
        }

        if ($startDate && $endDate) {
            $where['ps.date' ] =  ['between', [$startDate, $endDate]];
        }

        $field = "p.place_id type_id, p.place_name name, SUM(ps.total_amount) total_amount, SUM(ps.refund_amount) refund_amount,  SUM(ps.sale_user_profit) sale_user_profit, SUM(ps.{$profitField}) profit_amount";
        $data  = $this->table($this->_pStatisTable . ' ps')->where($where)->field($field)->group('ps.place_id')->join('place p', 'p.place_id = ps.place_id')->select();
        pft_log('statis/place_static', json_encode([$data, $placeId, $startDate, $endDate]));
        return $data;
    }



    /**
     * 统计时间段内的代理收益详情
     * @author xiexy
     * @date            2017-11-21
     * @param int       $agentId  代理ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function agentProfitDetail($agentId = [], $startDate = '', $endDate = '', $placeId = [])
    {
        if ($agentId) {
            $where        = ['ps.agent_id' =>  ['IN', $agentId]];
            $profitField  = 'ps.agent_profit';
        }

        if ($placeId) {
            $where['ps.place_id'] =  ['IN', $placeId];
            $profitField          = 'ps.place_profit';
        }

        if ($startDate && $endDate) {
            $where['ps.date' ] =  ['between', [$startDate, $endDate]];
        }

        $field = "p.place_id type_id, p.place_name name, SUM({$profitField}) profit_amount";
        $data  = $this->table($this->_pStatisTable . ' ps ')->where($where)->field($field)->group('type_id')->join('place p', 'p.place_id = ps.place_id')->select();
        return $data;
    }



    /**
     * 统计时间段内的渠道收益详情
     * @author xiexy
     * @date            2017-11-21
     * @param int       $channelId  渠道ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function channelProfitDetail($channelId = [], $startDate = '', $endDate = '', $agentId = [])
    {
        $where = [];
        if ($channelId) {
            $where        = ['ps.channel_id' => ['IN', $channelId]];
            $profitField  = 'ps.channel_profit';
        }

        if ($agentId) {
            $where        = ['ps.agent_id' => ['IN', $agentId]];
            $profitField  = 'ps.agent_profit';
        }

        if ($startDate && $endDate) {
            $where['ps.date' ] =  ['between', [$startDate, $endDate]];
        }

        $field = "a.agent_id type_id, a.name, SUM({$profitField}) profit_amount";
        $data  = $this->table($this->_pStatisTable . ' ps')->where($where)->field($field)->group('ps.agent_id')->join('agent a', 'a.agent_id = ps.agent_id')->select();
        return $data;
    }


    /**
     * 统计时间段内的平台收益详情
     * @author xiexy
     * @date            2017-11-21
     * @param int       $channelId  渠道ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function platformProfitDetail($startDate, $endDate, $isAdmin = true, $channelId = [])
    {

        $profitField = $isAdmin ? 'platform_profit' : 'channel_profit';
        $field       = "c.name, c.channel_id type_id, IFNULL(SUM(ps.{$profitField}), 0) profit_amount";
        $where       = [];

        if ($channelId) {
            $where['ps.channel_id'] = ['IN', $channelId];
        }

        if ($startDate && $endDate) {
            $where['ps.date'] =  ['between', [$startDate, $endDate]];
        }

        $data  = $this->table($this->_pStatisTable . ' ps')->where($where)->field($field)->group('c.channel_id')->join('channel c', 'c.channel_id = ps.channel_id ', 'right')->select();
        return $data;
    }


    /**
     * 统计平台/渠道/代理/场所每月收益
     * @author xiexy
     * @date            2017-11-21
     * @param integer    $typeId  type对应的id
     * @param integer    $type    1-场所 2-代理 3-渠道
     * @return array
     *
     */
    public function perMonthProfit($type = '', $typeId = [])
    {
        $where = [];

        switch ($type) {

            case self::STATIS_TYPE_PLACE :

                $where['place_id']   = ['IN', $typeId];
                $profitFiled         = 'place_profit';
                break;

            case self::STATIS_TYPE_AGENT :
                $where['agent_id']   =  ['IN', $typeId];;
                $profitFiled         = 'agent_profit';
                break;

            case self::STATIS_TYPE_CHANNEL :
                $where['channel_id'] =  ['IN', $typeId];;
                $profitFiled         = 'channel_profit';
                break;

            case self::STATIS_TYPE_PLATFORM :
                $profitFiled         = 'platform_profit';
                break;

            default :
                return false;
        }

        $field = " DATE_FORMAT(date, '%Y-%m') month, sum({$profitFiled}) profit_amount";
        $data  = $this->table($this->_pStatisTable)->where($where)->field($field)->group('month')->order('date desc')->select();
        return $data;
    }


    /**
     * 统计每月各场所，代理，渠道的收益
     * @author xiexy
     * @date   2017-11-22
     * @param  string   $type        统计类型,包括场所，代理，渠道
     * @param  string   $typeId      当统计类型为场所时值为场所id, 代理-代理id  渠道-渠道id
     * @param  string   $month       月份 格式:2017-01
     * @return array
     *
     */
    public function getProfitDetailByMonth($month, $type, $typeId = [], $profitField, $agentId = '')
    {
        $where       = ["DATE_FORMAT(ps.date, '%Y-%m')" => $month];
        $data        = [];

        switch ((int) $type) {

            case self::STATIS_TYPE_PLACE :

                $data = $this->placeTotalStatic($typeId, $month.'-01', $month . '-31', $profitField, $agentId);
                return $data;
                break;

            case self::STATIS_TYPE_AGENT :

                $where['ps.agent_id']   = ['IN', $typeId];
                $field       = " p.place_name name, ps.place_id type_id, sum(ps.{$profitField}) profit_amount";
                $groupField  = ' ps.place_id ';
                $joinTable   = ' place p ';
                $joinCondi   = ' p.place_id = ps.place_id ';
                break;

            case self::STATIS_TYPE_CHANNEL :
                $where['ps.channel_id'] = ['IN', $typeId];
                $field       = " a.name name, ps.agent_id type_id, sum(ps.{$profitField}) profit_amount ";
                $groupField  = ' ps.agent_id';
                $joinTable   = ' agent a';
                $joinCondi   = ' a.agent_id = ps.agent_id';
                break;

            case self::STATIS_TYPE_PLATFORM :
                $typeId  &&  $where['ps.channel_id'] = ['IN', $typeId];
                $field       = " c.name name, ps.channel_id type_id, sum(ps.{$profitField}) profit_amount ";
                $groupField  = ' ps.channel_id';
                $joinTable   = ' channel c';
                $joinCondi   = ' c.channel_id = ps.channel_id';
                break;

            default :
                return $data;
        }

        $data  = $this->table($this->_pStatisTable . ' ps')->where($where)->field($field)->join($joinTable, $joinCondi)->group($groupField)->select();
       // dump($data);exit;
        return $data;
    }


    /**
     * 更新场所报表日数据
     * @author xiexy
     * @date   2017-11-23
     * @param  string   $channelId        渠道id
     * @param  string   $placeId          场所id
     * @param  string   $agentId          代理id
     * @param  string   $areaId           区域id
     * @param  string   $date             日期 格式：2016-01
     * @param  string   $orderAmount      订单金额
     * @param  string   $orderPorfit      纯收益
     * @param  string   $saleUserProfit   咪小二分润金额
     * @param  string   $placeProfit      场所分润金额
     * @param  string   $agentProfit      代理分润金额
     * @param  string   $channelProfit    渠道分润金额
     * @param  string   $platformProfit   平台分润金额
     * @param  string   $statisType       更新类型 place:更新statistics_report_place表   machine:更新statistics_report_machine表
     * @param  string   $machineId        机器id 当$statisType = machine时必填
     * @return boolean
     *
     */
    public function updatePlaceStatis($date, $placeId, $agentId, $orderAmount, $orderPorfit, $saleUserProfit, $placeProfit, $agentProfit, $channelProfit, $platformProfit, $lockNum, $statisType = 'place', $machineId = '')
    {
        $table       = '';
        $orderAmount = (int) $orderAmount;
        $orderPorfit = (int) $orderPorfit;

        $statisType  == 'place'   && $table = $this->_pStatisTable;
        $statisType  == 'machine' && $table = $this->_mStatisTable;


        if (!$table || !$placeId || !$orderAmount || !$orderPorfit || !is_date_time($date)) {
            return false;
        }

        if ($statisType  == 'machine' && !$machineId) {
            return false;
        }

        $where = [
            'date'      => $date,
            'place_id'  => $placeId,
            'order_num' => $lockNum,
            'agent_id'  => $agentId
        ];

        $statisType  == 'machine' && $where['machine_id'] = $machineId;

        $data = [
            'total_amount'      => ['exp', 'total_amount + ' . (int) $orderAmount],
            'profit_amount'     => ['exp', 'profit_amount + ' . (int) $orderPorfit],
            'sale_user_profit'  => ['exp', 'sale_user_profit + ' . (int) $saleUserProfit],
            'place_profit'      => ['exp', 'place_profit + ' . (int) $placeProfit],
            'agent_profit'      => ['exp', 'agent_profit + ' . (int)$agentProfit],
            'channel_profit'    => ['exp', 'channel_profit + ' . (int)$channelProfit],
            'platform_profit'   => ['exp', 'platform_profit + ' . (int)$platformProfit],
            'order_num'         => ['exp', 'order_num + 1'],
            'update_time'       => time()

        ];
        $res = $this->table($table)->where($where)->update($data);
        return $res;
    }


    /**
     * 创建报表日数据
     * @author xiexy
     * @date   2017-11-23
     * @param  string   $channelId        渠道id
     * @param  string   $placeId          场所id
     * @param  string   $agentId          代理id
     * @param  string   $areaId           区域id
     * @param  string   $date             日期 格式：2016-01
     * @param  string   $orderAmount      订单金额
     * @param  string   $orderPorfit      纯收益
     * @param  string   $saleUserProfit   咪小二分润金额
     * @param  string   $placeProfit      场所分润金额
     * @param  string   $agentProfit      代理分润金额
     * @param  string   $channelProfit    渠道分润金额
     * @param  string   $platformProfit   平台分润金额
     * @param  string   $statisType       更新类型 place:更新statistics_report_place表   machine:更新statistics_report_machine表
     * @param  string   $machineId        机器id 当$statisType = machine时必填
     * @return boolean
     *
     */
    function addPlaceStatis($channelId, $placeId, $agentId, $areaId, $date, $orderAmount, $orderPorfit, $saleUserProfit, $placeProfit, $agentProfit, $channelProfit, $platformProfit, $statisType = 'place' , $machineId = '')
    {

        $channelId   = (int) $channelId;
        $placeId     = (int) $placeId;
        $agentId     = (int) $agentId;
        $orderAmount = (int) $orderAmount;
        $orderPorfit = (int) $orderPorfit;
        $table       = '';

        $statisType  == 'place'   && $table = $this->_pStatisTable;
        $statisType  == 'machine' && $table = $this->_mStatisTable;

        if (!$table || !$areaId || !$channelId || !$placeId || !$agentId || !$orderAmount || !$orderPorfit || !is_date_time($date)) {
            return false;
        }

        $data = [
            'channel_id'        => $channelId,
            'agent_id'          => $agentId,
            'place_id'          => $placeId,
            'area_id'           => $areaId,
            'date'              => $date,
            'total_amount'      => $orderAmount,
            'profit_amount'     => (int) $orderPorfit,
            'sale_user_profit'  => (int) $saleUserProfit,
            'place_profit'      => (int) $placeProfit,
            'agent_profit'      => (int) $agentProfit,
            'channel_profit'    => (int) $channelProfit,
            'platform_profit'   => (int) $platformProfit,
            'order_num'         => 1,
            'create_time'       => time(),
        ];

        if ($table == $this->_mStatisTable) {
            $data['machine_id'] = $machineId;
        }

        $res = $this->table($table)->insert($data);
        return $res;
    }


    /**
     * 查询场所日报表数据
     * @author xiexy
     * @date   2017-11-23
     * @param  string   $date        日期
     * @param  string   $placeId     场所id
     * @param  string   $field       查询字段
     * @param  string   $statisType  查询类型 place:更新statistics_report_place表   machine:更新statistics_report_machine表
     * @param  string   $machineId   机器id 当$statisType = machine时必填
     * @return array
     *
     */
    public function getDateStatis($date, $placeId, $field = "*", $statisType = 'place', $machineId = '', $agentId = '')
    {
        $table      = '';
        $statisType == 'place'   && $table = $this->_pStatisTable;
        $statisType == 'machine' && $table = $this->_mStatisTable;

        if ($statisType  == 'machine' && !$machineId) {
            return false;
        }

        $where      = ['date' => $date, 'place_id' => $placeId];
        $agentId  &&  $where['agent_id'] = $agentId;
        $statisType == 'machine' && $where['machine_id'] = $machineId;

        $info = $this->table($table)->where($where)->field($field)->find();
        return $info;
    }


    /**
     * 退款更新报表数据
     * @author xiexy
     * @date   2017-11-24
     * @param  string   $date             日期
     * @param  string   $placeId          场所id
     * @param  string   $refundAmount     退款金额
     * @param  string   $orderAmount      订单金额
     * @param  string   $orderPorfit      订单纯收益
     * @param  string   $saleUserProfit   咪小二分润金额
     * @param  string   $placeProfit      场所分润金额
     * @param  string   $agentProfit      代理分润金额
     * @param  string   $channelProfit    渠道分润金额
     * @param  string   $platformProfit   平台分润金额
     * @param  string   $statisType       更新类型 place:更新statistics_report_place表   machine:更新statistics_report_machine表
     * @param  string   $machineId        机器id 当$statisType = machine时必填
     * @return array
     *
     */
    public function updateStatisByRefund($date, $placeId, $agentId, $refundAmount, $orderAmount, $orderPorfit, $saleUserProfit, $placeProfit, $agentProfit, $channelProfit, $platformProfit, $orderNum, $statisType = 'place' , $machineId = '')
    {
        $table        = '';
        $placeId      = (int) $placeId;
        $orderAmount  = (int) $orderAmount;
        $orderPorfit  = (int) $orderPorfit;
        $refundAmount = (int) $refundAmount;

        $statisType   == 'place'   && $table = $this->_pStatisTable;
        $statisType   == 'machine' && $table = $this->_mStatisTable;

        if (!$table && !is_date_time($date) || !$placeId || !$orderAmount || !$orderPorfit || !$refundAmount) {
            return false;
        }

        if ($statisType  == 'machine' && !$machineId) {
            return false;
        }

        $where       = ['date' => $date, 'place_id' => $placeId, 'order_num' => $orderNum, 'agent_id' => $agentId];
        $statisType  == 'machine' && $where['machine_id'] = $machineId;

        $data  = [
            'refund_amount'     => ['exp', 'refund_amount + ' . $refundAmount],
          //  'total_amount'      => ['exp', 'total_amount - ' . $orderAmount],
            'profit_amount'     => ['exp', 'profit_amount - ' .  $orderPorfit],
            'sale_user_profit'  => ['exp', 'sale_user_profit - ' . (int) $saleUserProfit],
            'place_profit'      => ['exp', 'place_profit - ' . (int) $placeProfit],
            'agent_profit'      => ['exp', 'agent_profit - ' . (int) $agentProfit],
            'channel_profit'    => ['exp', 'channel_profit - ' . (int) $channelProfit],
            'platform_profit'   => ['exp', 'platform_profit - ' . (int) $platformProfit],
           // 'order_num'         => ['exp', 'order_num - 1'],
            'update_time'       => time()
        ];

        $res = $this->table($table)->where($where)->update($data);
        return $res;
    }



    /**
     * 统计时间段内的代理总收益
     * @author xiexy
     * @date            2017-11-21
     * @param int       $agentId  代理ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function getAgentDiv($agentId = [], $startDate, $endDate)
    {
        $field = "SUM(agent_profit) profit_amount";
        $where['date' ] =  ['between', [$startDate, $endDate]];
        $where['agent_id' ] =  ['IN', $agentId];
        $data  = $this->table($this->_pStatisTable)->where($where)->field($field)->group('agent_id')->select();

        return $data;
    }


    /**
     * 统计时间段内的渠道总收益
     * @author xiexy
     * @date            2017-11-21
     * @param int       $agentId  代理ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function getChannelDiv($channelId = [], $startDate, $endDate)
    {
        $field = "SUM(channel_profit) profit_amount";
        $where['date' ] =  ['between', [$startDate, $endDate]];
        $where['channel_id' ] =  ['IN', $channelId];
        $data  = $this->table($this->_pStatisTable)->where($where)->field($field)->group('channel_id')->select();
        return $data;
    }


    /**
     * 统计时间段内的营业数据
     * @author xiexy
     * @date            2017-11-21
     * @param int       $agentId  代理ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function getAdminStatis($startDate, $endDate)
    {
        $where = ['date' => ['BETWEEN', [$startDate, $endDate]]];
        $field = 'SUM(order_num) order_num, SUM(total_amount) amount, SUM(refund_amount) reunfd_amount';
        $data  = $this->table($this->_pStatisTable)->where($where)->field($field)->select();
        return $data;
    }


    /**
     * 获取场所收益列表
     * @author xiexy
     * @date            2017-11-21
     * @param int       $agentId  代理ID
     * @param string    $startDate
     * @param string    $endDate
     * @return array
     *
     */
    public function getPlaceStatisList($statDate, $endDate, $placeId = [], $placeName = '', $placeState = '-1', $agentId = [], $channelId = [], $areaId = [], $limit = 200, $query = [], $type = 'list')
    {
        $condition = [];

        $condition['s.date'] = ['BETWEEN', [$statDate, $endDate]];
        $placeName && $condition['p.place_name'] = ['LIKE', "%{$placeName}%"];
        $placeId   && $condition['s.place_id']   = ['IN', $placeId];
        $agentId   && $condition['s.agent_id']   = ['IN', $agentId];
        $channelId && $condition['s.channel_id'] = ['IN', $channelId];
        $areaId    && $condition['s.area_id']    = ['IN', $areaId];

        $placeState > -1 && $condition['p.state'] = (int) $placeState;

        if ($type == 'list') {

            $field = 'p.place_name, p.machine_num, SUM(s.order_num) order_num, SUM(s.total_amount) order_amount, 
            SUM(s.refund_amount) refund_amount, SUM(s.sale_user_profit) sale_user_profit, SUM(s.place_profit) place_amount,
            SUM(s.agent_profit) agent_amount, SUM(s.channel_profit) channel_amount, SUM(s.platform_profit) pf_amount, ar.area_name, c.name channel_name, a.name agent_name';

            $result =  $this->table($this->_pStatisTable . ' s')
                ->join('place p', 'p.place_id = s.place_id')
                ->join('area ar',  'p.area = ar.area_id')
                ->join('agent a', 'a.agent_id = p.agent_id')
                ->join('channel c', 'c.channel_id = p.channel_id')
                ->field($field)
                ->where($condition)
                ->order('s.place_id ASC')
                ->group('s.place_id')
                ->paginate($limit, true, ['query' => $query]);

        } elseif ($type == 'export') {

            $field = 'ar.area_pid, p.place_id, p.area, p.start_time, p.end_time, p.place_name, p.place_address, c.name AS channel_name,
             a.name AS agent_name, p.pf_div, p.channel_div, p.agent_div, p.place_div, p.card_no, p.subbranch_no, p.card_openbank, 
             p.card_uname, SUM(s.order_num) order_num, SUM(s.total_amount) order_amount, SUM(s.place_profit) place_amount,
             SUM(s.agent_profit) agent_amount, SUM(s.channel_profit) channel_amount, SUM(s.platform_profit) pf_amount';

            $result = $this->table($this->_pStatisTable . ' s')
                ->join('place p', 'p.place_id = s.place_id')
                ->join('agent a', 'p.agent_id = a.agent_id')
                ->join('channel c', 'p.channel_id = c.channel_id')
                ->join('area ar', 'ar.area_id = p.area')
                ->field($field)
                ->where($condition)
                ->group('p.place_id')
                ->order('p.place_id ASC')
                ->select();

        } else {

            $field = ' SUM(s.order_num) order_num, SUM(s.total_amount) order_amount ';
            $result =  $this->table($this->_pStatisTable . ' s')
                ->field($field)
                ->join('place p', 'p.place_id = s.place_id')
                ->join('area ar',  'p.area = ar.area_id')
                ->join('agent a', 'a.agent_id = p.agent_id')
                ->join('channel c', 'c.channel_id = p.channel_id')
                ->where($condition)
                ->group('s.place_id')
                ->select();
        }
        return $result;
    }
}

<?php
namespace mgcore\model;

use think\Db;
use think\Model;

class Stat extends Model
{
    public static function amount($agent_id, $begin_time = null, $end_time = null, $machine = '', $kw = '')
    {
        $where = [
            'uo.agent_id' => $agent_id,
            'uo.paid'     => 1,
        ];

        if ($begin_time && $end_time) {
            $where['uo.time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } else {
            $begin_time && ($where['uo.time_paid'] = ['>=', $begin_time]);
            $end_time && ($where['uo.time_paid'] = ['<', $end_time]);
        }

        if (!empty($machine)) {
            $where['uo.machine_id'] = ['IN', $machine];
        }
        if (!empty($kw)) {
            if (is_numeric($kw)) {
                $where['uo.place_id'] = $kw;
            } else {
                $where['m.location'] = $kw;
            }
        }

        return Db::table('user_order')->alias('uo')
            ->join('machine m', 'uo.machine_id = m.machine_id', 'LEFT')
            ->where($where)
        // ->whereTime('uo.time_paid', 'week')
            ->sum('uo.order_amount');
    }
    /**
     * 订单列表
     */
    public static function order_list($agent_id, $begin_time, $end_time, $machine = '', $kw = '')
    {
        $where = [
            'uo.agent_id'  => $agent_id,
            'uo.time_paid' => [['>=', $begin_time], ['<', $end_time]],
            'uo.paid'      => 1,
        ];
        if (!empty($machine)) {
            $where['uo.machine_id'] = ['IN', $machine];
        }
        if (!empty($kw)) {
            $where['m.location'] = $kw;
        }

        $order = Db::table('user_order')->alias('uo')
            ->field('uo.*, m.name, m.location,au.remark AS uname')
            ->join('machine m', 'uo.machine_id = m.machine_id', 'LEFT')
            ->join('agent_user au', 'uo.agent_id = au.agent_id AND uo.wx_openid = au.wx_openid', 'LEFT')
            ->where($where)
            ->order('uo.time_paid DESC')
            ->select();
        return $order;
    }

    public static function machine_count($agent_id, $machine = '')
    {
        $where = ['agent_id' => $agent_id];
        if (!empty($machine)) {
            $where['machine_id'] = ['IN', $machine];
        }
        return Db::table('machine')->where($where)->count();
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
     * 订单分组统计
     * @param  array   $ids        需要統统计的ID
     * @param  string  $key        ID的类型
     * @param  integer $timetype   统计的时间的类型
     * @param  int  $begin_time 开始时间
     * @param  int  $end_time   结束时间
     * @return array
     */
    public static function order_group($ids = [], $key = 'place_id', $timetype = 0, $begin_time = null, $end_time = null)
    {
        $where[$key]   = ['IN', $ids];
        $where['paid'] = 1;

        //快捷时间处理
        if ($timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        } else {
            $begin_time = empty($begin_time) ? '' : strtotime($begin_time) + stat_period_compat();
            $end_time   = empty($end_time) ? '' : strtotime($end_time) + 86400 + stat_period_compat();
        }
        if ($begin_time && $end_time) {
            $where['time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['time_paid'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['time_paid'] = ['<', $end_time];
        }
        $list = Db::table('user_order')->field("COUNT(order_id) AS order_num, SUM(sale_user_profit) as sale_user_profit, SUM(order_amount) AS order_amount, SUM(order_amount*agent_div) AS agent_div, SUM(order_amount*channel_div) AS channel_div, $key")->where($where)->group($key)->select();
        if ($list) {
            $list = array_column($list, null, $key);
        }
        return $list;
    }

    /**
     * 退款分组统计
     * @param  array   $ids        需要統统计的ID
     * @param  string  $key        ID的类型
     * @param  integer $timetype   统计的时间的类型
     * @param  int  $begin_time 开始时间
     * @param  int  $end_time   结束时间
     * @return array
     */
    public static function refund_group($ids = [], $key = 'place_id', $timetype = 0, $begin_time = null, $end_time = null)
    {
        $where[$key]       = ['IN', $ids];
        $where['paid']     = 1; // 已支付
        $where['refunded'] = 1; // 已退款

        //快捷时间处理
        if ($timetype > 0) {
            $times      = timetype_delay($timetype);
            $begin_time = $times['begin_time'];
            $end_time   = $times['end_time'];
        } else {
            $begin_time = empty($begin_time) ? '' : strtotime($begin_time) + stat_period_compat();
            $end_time   = empty($end_time) ? '' : strtotime($end_time) + 86400 + stat_period_compat();
        }
        if ($begin_time && $end_time) {
            $where['time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['time_paid'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['time_paid'] = ['<', $end_time];
        }
        $list = Db::table('user_order')->field("SUM(amount_refunded) AS refund_amount, $key")->where($where)->group($key)->select();
        if ($list) {
            $list = array_column($list, null, $key);
        }
        return $list;
    }

    public static function order_group2($ids = [], $key = 'place_id', $begin_time = null, $end_time = null)
    {
        $where[$key]   = ['IN', $ids];
        $where['paid'] = 1;

        if ($begin_time && $end_time) {
            $where['time_paid'] = [['>=', $begin_time], ['<', $end_time]];
        } elseif ($begin_time) {
            $where['time_paid'] = ['>=', $begin_time];
        } elseif ($end_time) {
            $where['time_paid'] = ['<', $end_time];
        }

        return Db::table('user_order')
            ->where($where)
            ->group($key)
            ->column($key . ', COUNT(order_id) AS order_num, SUM(order_amount) AS order_amount, SUM(order_amount*channel_div) AS channel_div, SUM(order_amount*agent_div) AS agent_div, SUM(order_amount*place_div) AS place_div', $key);
    }

    /**
     * 设备分组统计
     */
    public static function machine_group($ids = [], $key = 'agent_id')
    {
        $where[$key] = ['IN', $ids];

        return Db::table('machine')
            ->where($where)
            ->group($key)
            ->column($key . ', COUNT(machine_id) AS machine_num, create_time', null, $key);
    }

    /**
     * 场地数分组统计
     */
    public static function place_group($ids = [], $key = 'agent_id', $state = false)
    {
        $where[$key] = ['IN', $ids];

        if ($state !== false) {
            if (is_array($state)) {
                $where['state'] = ['in', $state];
            } else {
                $where['state'] = $state;
            }
        }

        return Db::table('place')
            ->where($where)
            ->group($key)
            ->column($key . ', COUNT(place_id) AS place_num, start_time, end_time', null, $key);
    }

    /**
     * 场所消费数据汇总
     * @param  int $placeId 场所ID
     * @param  date $day 哪天 2017-01-23
     * @return
     */
    public static function genConsumeReport($placeId, $day)
    {
        if (!$placeId || !$day) {
            return false;
        }

        $startTime = strtotime($day . ' 00:00:00');
        $endTime   = strtotime($day . ' 23:59:59');

        //汇总扫码的数量
        $where = [
            'place_id'    => $placeId,
            'create_time' => ['between', [$startTime, $endTime]],
        ];
        $scanNum = Db::table('machine_scan_log')->where($where)->count();

        //汇总扫码已经支付的数量
        $where = [
            'place_id'    => $placeId,
            'create_time' => ['between', [$startTime, $endTime]],
            'paid'        => 1,
        ];
        $payNum = Db::table('user_order')->where($where)->count();

        //汇总扫码但是没有支付的数量
        $where = [
            'place_id'    => $placeId,
            'create_time' => ['between', [$startTime, $endTime]],
            'paid'        => 0,
        ];
        $unpayNum = Db::table('user_order')->where($where)->count();

        $formatDay = trim(str_replace('-', '', $day));
        $where     = [
            'place_id' => $placeId,
            'day'      => $formatDay,
        ];

        //插入数据
        $data = [
            'scan_num'        => $scanNum,
            'pay_order_num'   => $payNum,
            'unpay_order_num' => $unpayNum,
            'update_time'     => time(),
        ];

        $consumeTable = 'consume_day_report';
        $tmp          = Db::table($consumeTable)->field('id')->where($where)->find();
        if ($tmp) {
            $id  = $tmp['id'];
            $res = Db::table($consumeTable)->where(['id' => $id])->update($data);
        } else {
            $data = array_merge($data, $where);
            $res  = Db::table($consumeTable)->insert($data);
        }

        return $res;
    }

    /**
     * 获取消费记录汇总列表
     * @param  int $page 页码
     * @param  int $size 条数
     * @param  int $placeId 场所ID 134 / [134,278,331]
     * @param  date $day 日期 '2017-10-23' / ['2017-10-23', 2017-10-30]
     * @return array
     */
    public static function getConsumeReport($page = 1, $size = 15, $placeId = false, $day = false)
    {
        $where = [];
        $query = ['page' => $page];

        if ($placeId !== false) {
            if (is_array($placeId)) {
                $where['place_id'] = ['in', $placeId];
            } else {
                $where['place_id'] = $placeId;
            }

            $query['place_id'] = $placeId;
        }

        if ($day !== false) {
            if (is_array($day) && isset($day[0]) && isset($day[1])) {
                $day[0] = substr($day[0], 0, 10);
                $day[1] = substr($day[1], 0, 10);

                $where['day'] = ['between', $day];
            } else {
                $where['day'] = $day;
            }

            $query['day'] = $day;
        }

        $consumeTable = 'consume_day_report';
        $orderStr     = "id desc";
        $tmp          = Db::table($consumeTable)->where($where)->order($orderStr)->paginate($size, false, ['query' => $query]);
        $list         = $tmp->all();
        $page         = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }
}

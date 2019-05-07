<?php

namespace mgcore\model;

use think\Db;
use think\Model;

class Machine extends Model
{
    //设备表
    private $_machineTable = 'machine';

    private $_gradeLogTable = 'machine_upgrade_log';

    private $_logTable = 'machine_log';

    //获取设备信息
    public static function getmachine($machine_id = 0, $agent_id = 0, $place_id = 0)
    {
        if ($machine_id) {
            return Db::table('machine')->where(['machine_id' => $machine_id])->select();
        }
        $where = [];
        if ($agent_id) {
            $where['agent_id'] = $agent_id;
        }
        if ($place_id) {
            $where['place_id'] = is_numeric($place_id) ? $place_id : ['in', $place_id];
        }
        $where['product_state'] = 4;

        return Db::table('machine')->where($where)->select();
    }

    /**
     * 获取机器详情
     * @author dwer
     * @date   2017-08-01
     *
     * @param  int $machineId 设备ID
     * @param  string $field 字段
     * @return array
     */
    public function getInfo($machineId, $field = '*')
    {
        if (!$machineId) {
            return [];
        }

        $info = Db::table($this->_machineTable)->where(['machine_id' => $machineId])->field($field)->find();
        return $info ? $info : [];
    }

    /**
     * 删除数据包日志
     * @author dwer
     * @date   2017-08-01
     *
     * @param  int $endTime 这个时间点之前的数据删除
     * @return array
     */
    public function delPackageLog($endTime)
    {
        $where = [
            'create_time' => ['ELT', strtotime($endTime)],
        ];

        $res = Db::table('machine_packet_log')->where($where)->delete();
        return $res;
    }

    /**
     * 添加设备信息
     * @author dwer
     * @date   2017-09-15
     *
     * @param array $data
     */
    public function add($data)
    {
        if (!$data) {
            return false;
        }

        $res = Db::table('machine')->insert($data);
        return $res;
    }

    /**
     * 更新设备的IMEI
     * @author dwer
     * @date   2017-09-15
     *
     * @param  int $machineId
     * @param  string $imei
     * @return
     */
    public function updateImei($machineId, $imei)
    {
        if (!$machineId || !$imei) {
            return false;
        }

        $where = [
            'machine_id' => $machineId,
        ];
        $data = ['mac' => $imei, 'update_time' => time()];

        $res = Db::table('machine')->where($where)->update($data);
        return $res;
    }

    /**
     * 更新设备的在线状态
     * @author dwer
     * @date   2017-09-15
     *
     * @param  int $machineId
     * @param  bool $isOnline
     * @param  string $mac
     * @return
     */
    public function updateOnline($machineId, $isOnline = true, $mac = '')
    {
        if (!$machineId) {
            return false;
        }

        $where = [
            'machine_id' => $machineId,
        ];
        $data = [
            'active_time' => time(),
        ];

        if ($isOnline) {
            $data['online']      = 1;
            $data['online_time'] = time();
        } else {
            //不更新离线时间，因为运营那边需要根据这个实际的下线时间判断故障
            //$data['offline_time'] = time();
            $data['online'] = 0;
        }

        $res = Db::table('machine')->where($where)->update($data);

        //添加数据包记录
        $packageData = [
            'machine_id'  => $machineId,
            'mac'         => $mac,
            'event'       => $isOnline ? 1 : 11,
            'time'        => time(),
            'create_time' => time(),
        ];
        $logRes = Db::table($this->_logTable)->insert($packageData);

        return $res;
    }

    /**
     * 清空之前绑定的imei
     * @author dwer
     * @date   2017-09-20
     *
     * @param  string $imei
     * @return bool
     */
    public function emptyImei($imei)
    {
        if (!$imei) {
            return false;
        }

        $where = [
            'mac'        => $imei,
            'version_id' => 1,
        ];

        $data = [
            'mac'         => '',
            'remark'      => "曾经imei:{$imei}",
            'update_time' => time(),
        ];

        $res = Db::table('machine')->where($where)->update($data);
        return $res === false ? false : true;
    }

    /*
     * 获取场所下面的设备数量
     * @author dwer
     * @date   2017-09-15
     *
     * @param  int $placeId
     * @return int
     */
    public function getNumByPlace($placeId)
    {
        if (!$placeId) {
            return 0;
        }

        $where = [
            'place_id'      => $placeId,
            'product_state' => 4,
        ];

        $num = $this->table($this->_machineTable)->where($where)->count();
        return intval($num);
    }

    /*
     * 按按摩椅版本和控制板版本获取设备数量
     * @author dwer
     * @date   2017-09-15
     *
     * @param  int $rmId 按摩椅版本
     * @param  string $version 控制板版本
     * @return int
     */
    public function getNumByRm($rmId, $version = '')
    {
        $where = [
            'rm_id' => $rmId,
        ];
        if ($version) {
            $where['version'] = $version;
        }

        $num = $this->table($this->_machineTable)->where($where)->count();
        return intval($num);
    }

    /**
     * 获取需要升级的设备列表
     * @author dwer
     * @date   2017-10-31
     *
     * @return
     */
    public function getUpgradeList($rmId, $upgradeVersion, $size = 50, $orderType = 'desc')
    {
        if (!$rmId || !$upgradeVersion) {
            return [];
        }

        $where = [
            'rm_id'      => $rmId,
            'version_id' => 1,
            'version'    => ['NEQ', $upgradeVersion],
            'state'      => 0, //空闲状态
            'online'     => 1, //在线
        ];

        $field = 'machine_id';
        $order = 'machine_id desc';
        if ($orderType == 'asc') {
            $order = 'machine_id asc';
        }
        $page = "1,{$size}";

        $res = $this->table($this->_machineTable)->where($where)->order($order)->page($page)->column($field);
        return $res;
    }

    /**
     * 添加设备的升级日志
     * @author dwer
     * @date   2017-10-29
     *
     * @param  int $machineId
     * @param  int $placeId
     * @param  string $imei
     * @param  string $currentVersion
     */
    public function addUpgradeLog($machineId, $rmId, $placeId, $imei, $currentVersion)
    {
        if (!$machineId) {
            return false;
        }

        $data = [
            'machine_id'      => $machineId,
            'rm_id'           => $rmId,
            'place_id'        => $placeId,
            'imei'            => $imei,
            'current_version' => $currentVersion,
            'add_time'        => time(),
        ];

        $res = $this->table($this->_gradeLogTable)->insert($data);
        return $res;
    }

    /**
     * 获取升级日志
     * @author dwer
     * @date   2017-10-31
     *
     * @param  int $rmId
     * @param  int $page
     * @param  int $size
     * @return [type]
     */
    public function getUpgradeLog($rmId, $page = 1, $size = 20)
    {
        if (!$rmId) {
            return ['list' => [], 'page' => 0];
        }

        $where = [];
        $query = ['page' => $page];

        $where['rm_id'] = $rmId;
        $query['rm_id'] = $rmId;

        $field    = "log.*,p.place_name";
        $join     = "p.place_id = log.place_id";
        $orderStr = "log.id desc";
        $tmp      = $this->table($this->_gradeLogTable . ' log')->field($field)->where($where)->order($orderStr)->join('place p', $join)->paginate($size, false, ['query' => $query]);
        $list     = $tmp->all();
        $page     = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }

    /**
     * 获取设备的在线数量和最近更新的时间点
     * @author dwer
     * @date   2017-12-09
     *
     * @param  string $type 类型 wifi/2G
     * @return array
     */
    public function getRealtimeStatus($type = 'wifi')
    {
        $where = [
            'online' => 1,
        ];

        if ($type == 'wifi') {
            $where['version_id'] = 0;
        } else {
            $where['version_id'] = 1;
        }

        //获取总数
        $count = $this->table($this->_machineTable)->where($where)->count();

        //获取最近上报状态的10台设备，以及上报的时间
        $page  = "0,5";
        $order = "active_time desc";
        $join  = "machine.place_id = place.place_id";
        $field = "machine.name, machine.mac, machine.online, machine.active_time, place.place_name";
        $list  = $this->table($this->_machineTable)->where($where)->field($field)->order($order)->join('place', $join, 'left')->page($page)->select();

        $list = $list ? $list : [];
        return ['count' => $count, 'list' => $list];
    }
     /***
      * 获取设备生产状态和绑定状态数据
      */
      public function factory_machine($where){
        $count = Db::table('vender')
        ->alias('v')
        ->join('machine m', 'm.factory_id = v.vender_id', 'LEFT')
        ->where($where)
        ->count();
         return $count;
    }


      /***
       * $machine['machine_type']  1普通 2一体 3蓝牙 
       * $machine['app_protocol_type'] 0 脉冲 1串口
       * 
       * 
       */
      public static function start_machine($machine_id,$time,$order_id = 0,$check_order = false){
        $machine  = Db::table('machine')->where(['machine_id' => $machine_id])->find();
        file_put_contents('log3336666.php', var_export($machine, true), FILE_APPEND);
        //普通设备
        if($machine['machine_type'] == 1){
            if($machine['app_protocol_type'] == 0){
                self::ordinary_start_machine($machine,$time);
            }else{
                self::control_start_machine($machine,$time);
            }
        //一体板设备    
        }elseif($machine['machine_type'] == 2){
                self::start_pillows($machine,$time);
        }else{
            if($machine['app_protocol_type'] == 0){
                self::ordinary_start_machine($machine,$time);
            }else{
                self::control_start_machine($machine,$time);
            } 
         //蓝牙设备 
        }
      }
      /***
       * 一体版设备
       */
      public static function start_pillows($machine,$time){
        $devtype = 2;
        $params  = http_build_query([
          //'platform_id' => $has_order['platform_id'],
            'devtype'     => $devtype,//脉冲/电机
            'imei'        => $machine['mac'],
            'duration'    => $time*60,
        ]);
        file_put_contents('logyitingban.php', var_export($params, true), FILE_APPEND);
        http_request('http://link.moogapay.com:21880/?' . $params);//http://link.moogapay.com:21880/?imei=868575023902077&topic=deveventreq&devtype=0&duration=600
      }

      /***
       * 普通脉冲设备
       */
	   public static function ordinary_start_machine($machine,$time){
             //FF06 B20002  2828 04


             //FF06 03B21A  2828 1F
            $machine_width = (isset($machine['machine_width'])) ? $machine['machine_width'] : 40;
            $width = dechex($machine_width);
            //加入强制启动队列，指定时间如果还未启动，则强制启动
            $time_min = $time+1;
            $hextime  = str_pad(dechex($time_min % 256), 2, '0', STR_PAD_LEFT);
            $hexsum   = str_pad(dechex((0x03 + 0xB2 + $time_min + $machine_width + $machine_width) % 256), 2, '0', STR_PAD_LEFT);
            $val      = strtoupper('FF0603B2' . $hextime . $width . $width . $hexsum);
            $params   = http_build_query([
                'platform_id' => $machine['platform_id'],
                'pk'          => Config('gw_config.product_key'),
                'async'       => 1, //异步执行，不等待执行结果
                'gw_did'      => $machine['gw_did'],
                'emcpacket'   => $val,
                'delay'       => Config('start_delay_time'), //16秒后服务器强制启动
                'check_order' => 0, //检查订单是否使用，考虑与下面的order_id合并，只要判断订单号是否传入
                'order_id'    => 0,
                // 'check_order' => isset($has_order['check_order'])?0:1, //检查订单是否使用，考虑与下面的order_id合并，只要判断订单号是否传入
                // 'order_id'    => $has_order['order_id'],
            ]);
            
            http_request('http://127.0.0.1:12346/?' . $params);
            //file_put_contents('log55888888.php', var_export($params, true), FILE_APPEND);
        }


         //启动普通串口设备
	  public static function control_start_machine($machine,$time){
		$time_min = $time;
		$hextime  = str_pad(dechex($time_min % 256), 2, '0', STR_PAD_LEFT);
		$hexsum   = str_pad(dechex((0x03 + 0xA2 + $time_min + 0x00 + 0x02) % 256), 2, '0', STR_PAD_LEFT);
		$val      = strtoupper('FF0603A2' . $hextime . '0002' . $hexsum);
		$params   = http_build_query([
			'platform_id' => $machine['platform_id'],
			'pk'          => Config('gw_config.product_key'),
			'async'       => 1, //异步执行，不等待执行结果
			'gw_did'      => $machine['gw_did'],
			'emcpacket'   => $val,
			'delay'       => 0, //16秒后服务器强制启动
			// 'check_order' => isset($has_order['check_order'])?0:1, //检查订单是否使用，考虑与下面的order_id合并，只要判断订单号是否传入
			// 'order_id'    => $has_order['order_id'],
			 'check_order' => 0, //检查订单是否使用，考虑与下面的order_id合并，只要判断订单号是否传入
			 'order_id'    => 0,
		]);
		// file_put_contents('log569.php', var_export($params, true), FILE_APPEND);
		http_request('http://127.0.0.1:12346/?' . $params);
    }
    /***
     * 更新设备状态
     */
    public static function update_machine_state($has_order){
        $timeend = $has_order['order_time']*60+time();
        $machineup = [
            'work_time'         =>  ['exp', 'work_time+'.$has_order['order_time'].''],
            'work_count'        =>  ['exp', 'work_count+1'],
            'profit'            =>  ['exp',  'profit+'.$has_order['order_amount'].''],
            'state'             =>  3,
            'work_endtime'      =>  $timeend,
        ];
        Db::table('machine')->where(['machine_id'=>$has_order['machine_id']])->update($machineup);
    }

         /***
     * 设备控制记录
     */
    public static function machine_control_log($has_order){
        //记录控制的结果
        $controlData = [
            'machine_id'  => $has_order['machine_id'],
            'mac'         => $has_order['mac'],
            'order_id'    => $has_order['order_id'],
            'uid'         => $has_order['uid'],
            'wx_openid'   => $has_order['wx_openid'],
            'is_auto'     => 1,
            'event'       => 1, //控制启动/叠加
            'retcode'     => 0 ,
            'create_time' => time(),
        ];
        Db::table('machine_control_log')->insert($controlData);
        $orderData = [
            'use_time' => time(),
            'cmd_sent_count' => 1,
            'cmd_sent_time'  => time(),
        ];
        //默认控制成功
        if($has_order['machine_type'] == 2 || $has_order['app_protocol_type'] == 1){
            
        }else{
            $orderData['is_used']   = 1;
        }
        Db::table('user_order')->where(['order_id' =>$has_order['order_id']])->update($orderData);
    }

}

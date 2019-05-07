<?php
/**
 * 场所相关模型
 * @author dwer
 * @date   2017-07-24
 *
 */

namespace mgcore\model;

use think\Model;

class Place extends Model
{
    private $_placeTable = 'place';
    private $_extTable   = 'place_ext';

    //默认数据库配置
    protected $connection = 'database';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 获取场所信息
     * @param  int $placeId 场所ID
     * @return array
     */
    public function getInfo($placeId, $field = '*', $isGetExt = false)
    {
        if (!$placeId) {
            return [];
        }

        if ($isGetExt) {
            $res = $this->table($this->_placeTable . ' place')
                ->join($this->_extTable . ' ext', 'ext.place_id = place.place_id', 'left')
                ->field($field)
                ->where(['place.place_id' => $placeId])
                ->find();
        } else {
            $res = $this->table($this->_placeTable)->field($field)->where(['place_id' => $placeId])->find();
        }

        return $res ? $res : [];
    }

    /**
     * 获取所有场所ID列表
     * @author dwer
     * @date   2017-08-21
     *
     * @return array
     */
    public function getIdList()
    {
        $where = ['state' => 0];
        $field = 'place_id';

        $idList = $this->table($this->_placeTable)->where($where)->column($field);
        return $idList;
    }

    /**
     * 通过代理ID获取场所列表
     * @param $agentId 代理商ID
     * @param $field 需要查询的内容
     *
     * @return array
     */
    public function getListByAgent($agentId, $field = 'place_id, place_name')
    {
        if (!$agentId) {
            return [];
        }

        $order = 'place_id desc';
        $where = ['state' => 0];
        if (is_array($agentId)) {
            $where['agent_id'] = ['in', $agentId];
        } else {
            $where['agent_id'] = $agentId;
        }

        $res = $this->table($this->_placeTable)->field($field)->where($where)->order($order)->select();
        return $res ? $res : [];
    }

    /**
     * 通过场所ID获取场所列表
     * @param $agentId 代理商ID
     * @param $field 需要查询的内容
     * @param $state 需要查询场所状态数组
     *
     * @return array
     */
    public function getListById($placeIdList = [], $field = 'place_id, place_name', $state = [0], $extendField = false)
    {
        $order = 'place_id desc';

        if ($state && is_array($state)) {
            $where = ['place.state' => ['in', $state]];
        }

        if (is_array($placeIdList) && $placeIdList) {
            $where['place.place_id'] = ['in', $placeIdList];
        }

        $field = $this->_getJoinField($field, 'place');

        if($extendField) {
            $extendField = $this->_getJoinField($extendField, 'ext');
            if($extendField) {
                $field = array_merge($field, $extendField);
            }
            $join = "place.place_id = ext.place_id";

            $res = $this->table($this->_placeTable . ' place')->join($this->_extTable . ' ext', $join, 'left')->field($field)->where($where)->order($order)->select();
        } else {
            $res = $this->table($this->_placeTable . ' place')->field($field)->where($where)->order($order)->select();
        }

        return $res ? $res : [];
    }

    /**
     * 通过区域ID获取场所列表
     * @param $cityId 代理商ID
     * @param $field 需要查询的内容
     *
     * @return array
     */
    public function getListByCity($cityId, $field = 'place_id, place_name')
    {
        if (!$cityId) {
            return [];
        }

        $order = 'place_id desc';
        $where = ['state' => 0];
        if (is_array($cityId)) {
            $where['area'] = ['in', $cityId];
        } else {
            $where['area'] = $cityId;
        }

        $res = $this->table($this->_placeTable)->field($field)->where($where)->order($order)->select();
        return $res ? $res : [];
    }

    /**
     * 获取场所列表
     * @author dwer
     * @date   2017-09-18
     *
     * @param  int/arr $agentId 代理商ID
     * @param  int/arr $channelId
     * @param  string $field
     * @return array
     */
    public function getList($agentId = false, $channelId = false, $field = '*')
    {
        $where = ['state' => 0];
        $order = 'place_id desc';

        if ($agentId) {
            if (is_array($agentId)) {
                $where['agent_id'] = ['in', $agentId];
            } else {
                $where['agent_id'] = $agentId;
            }
        }

        if ($channelId) {
            if (is_array($channelId)) {
                $where['channel_id'] = ['in', $channelId];
            } else {
                $where['channel_id'] = $channelId;
            }
        }

        $res = $this->table($this->_placeTable)->field($field)->where($where)->order($order)->select();
        return $res ? $res : [];
    }

    /**
     * 获取场所所在城市省份信息
     * @param   int       $placeId    场所id
     * @return  Array
     */
    public function getPlaceAreaInfo($placeId)
    {
        $placeInfo = $this->table($this->_placeTable)
            ->alias('p')
            ->field('a.area_id, a.area_pid')
            ->join('area a', 'p.area = a.area_id')
            ->where(['p.place_id' => $placeId])
            ->find();
        return $placeInfo;
    }

    /**
     * 场所风控不通过
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @param  string $remark      备注
     * @return bool
     */
    public function reject($placeIdList, $remark = '')
    {
        if (!$placeIdList) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 3, 'update_time' => time()];

        $res = $this->table($this->_placeTable)->where($where)->update($data);

        if ($res == false) {
            return false;
        }

        if ($remark) {
            $data = ['remark' => $remark];
            $this->table($this->_extTable)->where($where)->update($data);
        }

        return true;
    }

    /**
     * 场所不需要设备
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @param  string $remark      备注
     * @return bool
     */
    public function factoryReject($placeIdList, $remark = '')
    {
        if (!$placeIdList) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 5, 'update_time' => time()];

        $res = $this->table($this->_placeTable)->where($where)->update($data);

        if ($res == false) {
            return false;
        }

        if ($remark) {
            $data = ['remark' => $remark, 'is_emery' => 0];
            $this->table($this->_extTable)->where($where)->update($data);
        }

        return true;
    }

    /**
     * 场所风控撤销
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @return bool
     */
    public function revoke($placeIdList)
    {
        if (!$placeIdList) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 2, 'update_time' => time()];
        $res   = $this->table($this->_placeTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        }

        $data = ['remark' => '', 'handle_customer' => 0];
        $res  = $this->table($this->_extTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 客服撤销操作
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @return bool
     */
    public function customerRevoke($placeIdList)
    {
        if (!$placeIdList) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 4, 'update_time' => time()];
        $res   = $this->table($this->_placeTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        }

        $data = ['remark' => '', 'handle_factory' => 0, 'is_emery' => 0];
        $res  = $this->table($this->_extTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 场所风控分配任务
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @param  string $customerId      客服ID
     * @return bool
     */
    public function assignCustomer($placeIdList, $customerId)
    {
        if (!$placeIdList || !$customerId) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 4, 'update_time' => time()];
        $res   = $this->table($this->_placeTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        }

        $data = ['handle_customer' => $customerId];
        $res  = $this->table($this->_extTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 客服分配任务给厂商
     * @author dwer
     * @date   2017-09-18
     *
     * @param  array $placeIdList 场所ID数组
     * @param  int $factoryId  厂商ID
     * @param  bool $isEmery  是否加急
     * @return bool
     */
    public function assignFactory($placeIdList, $factoryId, $isEmery = false)
    {
        if (!$placeIdList || !$factoryId) {
            return false;
        }

        $where = ['place_id' => ['in', $placeIdList]];
        $data  = ['state' => 6, 'update_time' => time()];
        $res   = $this->table($this->_placeTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        }

        $data = ['handle_factory' => $factoryId, 'is_emery' => 0];
        if ($isEmery) {
            $data['is_emery'] = 1;
        }
        $res = $this->table($this->_extTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 场所设备发货
     * @author dwer
     * @date   2017-09-18
     *
     * @param  int $placeId 场所ID
     * @param  string $machineInfo 多台设备编号用分号","隔开
     * @param  string $expressInfo 物流单号
     * @return bool
     */
    public function inputTrack($placeId, $machineInfo, $expressInfo)
    {
        if (!$placeId || !$machineInfo || !$expressInfo) {
            return false;
        }

        $where = ['place_id' => $placeId];
        $data  = ['state' => 7, 'update_time' => time()];
        $res   = $this->table($this->_placeTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        }

        $data = ['express_time' => time(), 'express_info' => $expressInfo, 'machine_info' => $machineInfo];
        $res  = $this->table($this->_extTable)->where($where)->update($data);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 强行修改场所的状态
     * @author dwer
     * @date   2017-10-25
     *
     * @param  int $placeId
     * @param  int $state
     * @return bool
     */
    public function changeState($placeId, $state)
    {
        if (!$placeId) {
            return false;
        }

        $where = ['place_id' => $placeId];
        $data  = [
            'update_time' => time(),
            'state'       => $state,
        ];

        $res = $this->table($this->_placeTable)->where($where)->update($data);
        return $res === false ? false : true;
    }


    /**
     * 获取场所的设备数量及签约时间
     * @author dwer
     * @date   2017-10-25
     *
     * @param  int $placeId
     * @param  int $state
     * @return bool
     */
    public function getUseDayAndMachineNum($placeList = [])
    {
        $field = 'p.place_id, p.start_time, count(m.machine_id) as machine_num';
        $where = ['p.place_id'    => ['IN', $placeList]];
        $res = $this->table($this->_placeTable . ' p ')->field($field)->join('machine m', ' p.place_id = m.place_id')->group('p.place_id')->where($where)->select();
        return $res;
    }


    /**
     * 通过场所id获取代理id
     * @author xiexy
     * @date   2017-11-29
     *
     * @param  integer  $placeId
     * @param  string   $field
     * @return array
     */
    public function getAgentByPlaceList($placeId = [], $field = "DISTINCT(agent_id)")
    {
        $where = ['place_id' => ['IN', $placeId]];
        $res   = $this->table($this->_placeTable)->field($field)->where($where)->select();
        return $res;
    }
}

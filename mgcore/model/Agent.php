<?php
/**
 * 代理商相关模型
 * @author dwer
 * @date   2017-07-17
 *
 */

namespace mgcore\model;

use think\Db;

class Agent
{
    private $_placeTable   = 'place';
    private $_noticeTable  = 'agent_notice_config';
    private $_agentTable   = 'agent';
    private $_machineTable = 'machine';

    /**
     * 获取代理商下面的场所数量
     * @author dwer
     * @date   2017-07-17
     *
     * @param int $agentId
     * @return int
     */
    public function getPlaceNum($agentId)
    {
        $agentId = intval($agentId);
        if (!$agentId) {
            return 0;
        }

        $where = [
            'agent_id' => $agentId,
            'state'    => 0,
        ];

        $count = Db::table($this->_placeTable)->where($where)->count();
        return $count;
    }

    /**
     * 获取代理商下面的设备数量
     * @author dwer
     * @date   2017-07-17
     *
     * @param int $agentId
     * @return int
     */
    public function getMachineNum($agentId)
    {
        $agentId = intval($agentId);
        if (!$agentId) {
            return 0;
        }

        $where = [
            'agent_id'      => $agentId,
            'product_state' => 4,
        ];

        $count = Db::table($this->_machineTable)->where($where)->count();
        return $count;
    }

    /**
     * 获取需要进行代理商收益通知的用户
     * @author dwer
     * @date   2017-07-18
     *
     * @return array
     */
    public function getNoticeUser()
    {
        $where = [
            'event'     => 2,
            'all_agent' => 0, //如果有设置全部代理商的用户先排除，这部分数据太多了
        ];

        $field      = 'agent_id,wx_openid';
        $res        = Db::table($this->_noticeTable)->field($field)->where($where)->select();
        $agentIdArr = array_column($res, 'agent_id');
        $agentList  = $this->getList($agentIdArr, 'agent_id, name');

        $noticeUser = [];
        foreach ($res as $item) {
            $agentId = $item['agent_id'];
            if (!isset($agentList[$agentId])) {
                continue;
            }

            if (!isset($item['wx_openid'])) {
                $noticeUser[$item['wx_openid']] = [];
            }

            $noticeUser[$item['wx_openid']][] = $agentList[$agentId];
        }

        return $noticeUser;
    }

    /**
     * 根据代理商ID数组获取信息
     * @author dwer
     * @date   2017-07-18
     *
     * @param  array $agentIdArr 代理商ID数组
     * @param  string $field 字段
     * @return array
     */
    public function getList($agentIdArr, $field = '*')
    {
        if (!$agentIdArr || !is_array($agentIdArr)) {
            return [];
        }

        $where = ['agent_id' => ['in', $agentIdArr]];
        $tmp   = Db::table($this->_agentTable)->where($where)->field($field)->select();

        $res = [];
        foreach ($tmp as $item) {
            $res[$item['agent_id']] = $item;
        }

        return $res;
    }
}

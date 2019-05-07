<?php
/**
 * 渠道商相关模型
 * @author cyw
 * @date   2017-08-07
 *
 */

namespace mgcore\model;

use think\Db;

class Channel
{
    private $_channelTable = 'channel';

    /**
     * 获取渠道商信息
     * @param  int $channelId 渠道商ID
     * @return array
     */
    public function getInfo($channelId, $field = '*')
    {
        if (!$channelId) {
            return [];
        }

        $res = Db::table($this->_channelTable)->where(['channel_id' => $channelId])->field($field)->find();
        return $res ? $res : [];
    }
}

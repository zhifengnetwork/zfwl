<?php
namespace mgcore\model;

use think\Db;

/**
 * 区域相关的模型封装
 * @author dwer
 * @date   2017-07-13
 *
 */
class Area
{
    private $_areaTable = 'area';

    /**
     * 根据区域ID获取所有的市
     * @param  int $areaId 区域ID
     * @return array
     */
    public function getInfo($areaId)
    {
        if (!$areaId) {
            return [];
        }

        $tmp = Db::table($this->_areaTable)->where(['area_id' => $areaId])->find();
        return $tmp ? $tmp : [];
    }

    /**
     * 根据区域ID获取所有的市
     * @param  int $areaId 区域ID
     * @return array
     */
    public function getCity($areaId)
    {
        if (!$areaId) {
            return [];
        }

        $tmp = Db::table($this->_areaTable)->where(['area_id' => $areaId])->find();
        if (!$tmp) {
            return [];
        }

        $resArea = [];
        if ($tmp['area_pid'] == 0) {
            //省
            $res = Db::table($this->_areaTable)->where(['area_pid' => $areaId])->select();
            foreach ($res as $item) {
                $resArea[] = $item['area_id'];
            }
        } else {
            $resArea = [$areaId];
        }

        return $resArea;
    }
}

<?php
namespace mgcore\model;

use mgcore\model\Place;
use think\Db;

/**
 * 价格相关的模型封装
 * @author dwer
 * @date   2017-07-13
 *
 */
class Price
{
    private $_serviceTable        = 'service';
    private $_placeServiceTable   = 'place_service';
    private $_machineServiceTable = 'machine_service';

    /**
     * 获取场所的价格列表
     * @param  int $placeId 场所ID
     * @return array
     */
    public function getByPlace($placeId)
    {
        if (!$placeId) {
            return [];
        }

        $placeModel = new Place();
        $placeInfo  = $placeModel->getInfo($placeId);
        $serviceId  = isset($placeInfo['service_id']) && $placeInfo['service_id'] ? $placeInfo['service_id'] : 0;
        if ($serviceId) {
            //获取价格组数据
            $field = 'type, time, price, desc, sort';
            $res   = Db::table($this->_serviceTable)->where(['service_id' => $serviceId])->order('sort desc')->column($field, 'type');
            $type  = 'standard';
        } else {
            //获取自定义价格数据
            $field = 'type, time, price, desc, sort';
            $res   = Db::table($this->_placeServiceTable)->where(['place_id' => $placeId])->order('sort desc')->column($field, 'type');
            $type  = 'custom';
        }

        return $res ? ['type' => $type, 'list' => $res] : [];
    }

    /**
     * 根据场所获取价格列表
     * @param  array $placeIds 场所ID
     * @return array
     */
    public static function getPriceByPlace($placeIds = [])
    {
        $list  = [];
        if($placeIds){
            $fields           =  "id, type, time, price, `desc`, `sort`";
            $placeIdStr       =  trim(implode(',', $placeIds), ',');
            $order            =  " ORDER BY time ASC ";
            $serviceArr       =  Db::query("SELECT $fields FROM service WHERE service_id IN
                                ( SELECT service_id FROM place WHERE place_id IN( $placeIdStr ) AND service_id != 0) $order");

            $placeServiceArr  =  Db::query("SELECT  $fields FROM place_service WHERE place_id IN
                                ( SELECT place_id  FROM  place  WHERE  place_id IN( $placeIdStr ) AND service_id = 0) $order");

            if($serviceArr){
                foreach( $serviceArr as $key => $info ){ $serviceArr[$key]['table'] = 'service'; }
            }
            if($placeServiceArr){
                foreach( $placeServiceArr as $key => $info ){ $placeServiceArr[$key]['table'] = 'place_service'; }
            }

            $list = array_merge($serviceArr, $placeServiceArr);
        }
        return $list;
    }


    /**
     * 获取设备的价格列表
     * @param  int $machineId 设备ID
     * @return array
     */
    public function getByMachine($machineId)
    {

    }
}

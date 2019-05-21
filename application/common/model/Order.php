<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Order extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    public function getAddressRegionAttr($value, $data)
    {
        $regions = Db::name('region')->where('id', 'IN', [$data['province'], $data['city'], $data['district'], $data['twon']])->order('level desc')->select();
        $address = '';
        if ($regions) {
            foreach ($regions as $regionKey => $regionVal) {
                $address = $regionVal['name'] . $address;
            }
        }
        return $address;
    }

    public function getPayStatusDetailAttr($value, $data)
    {
        $pay_status = config('PAY_STATUS');
        return $pay_status[$data['pay_status']];
    }

    public function getShippingStatusDetailAttr($value, $data)
    {
        $shipping_status = config('SHIPPING_STATUS');
        return $shipping_status[$data['shipping_status']];
    }

    /**
     * 订单支付期限
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getFinallyPayTimeAttr($value, $data)
    {
        return $data['add_time'] + config('finally_pay_time');
    }


    /**
     * 订单发票
     * @return string
     */
    public function invoice()
    {
        return $this->hasOne('invoice', 'order_id', 'order_id');
    }

    public function getShippingStatusDescAttr($value, $data)
    {
        $config = config('SHIPPING_STATUS');
        return $config[$data['shipping_status']];
    }

     /**
     * 订单详细收货地址
     * @param $value
     * @param $data
     * @return string
     */
    public function getFullAddressAttr($value, $data)
    {
        $province = Db::name('region')->where(['area_id' => $data['province']])->value('area_name');
        $city     = Db::name('region')->where(['area_id' => $data['city']])->value('area_name');
        $district = Db::name('region')->where(['area_id' => $data['district']])->value('area_name');
        $address = $province . '，' . $city . '，' . $district . '，' . $data['address'];
        return $address;
    }

    /***
     * 修改订单支付状态
     */
    public function update_order(){

    }

}

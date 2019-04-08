<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;

class Order extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    //外部搜索调用
    public static $status_list = [
        1  => '待付款',
        2  => '待发货',
        3  => '已发货',
        4  => '已完成',
        5  => '退货退款',
    ];

    public static $type_list = [
        1 => '微信',
        2 => '支付宝',
        3 => '银行卡',
    ];

    public function getStatusAttr($value)
    {
        return self::$status_list[$value];
    }

    public function getTypeAttr($value)
    {
        return self::$type_list[$value];
    }

}

<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;

class Goods extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    public function getGoodsList ($keyword = '') {
        $where = [];
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if (!empty($keyword)){
            //商品搜索
            $where['goods_name'] = ['like','%'.str_replace(" ",'',$keyword).'%'];
        }
        $field = 'goods_id,goods_name,desc,limited_start
        ,limited_end,img,price,original_price,stock';
        $list = $this->where($where)->field($field)->select();
        return $list;
    }
}

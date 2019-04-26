<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;

class Goods extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    public function getGoodsList ($keyword = '',$cat_id = '',$page = 1) {
        $where = [];
        $where_cat = [];
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $field = 'goods_id,goods_name,desc,limited_start
        ,limited_end,img,price,original_price,stock';
        if (!empty($keyword)){
            //商品搜索
            $where['goods_name'] = ['like','%'.str_replace(" ",'',$keyword).'%'];
        }
        if (!empty($cat_id)){
            $list = $this->where($where)->where(function ($query) use ($cat_id) {
                $query->where('cat_id1', $cat_id)->whereor('cat_id2', $cat_id);})
                ->field($field)->paginate(12,false,['page'=>$page]);
        }else{
            $list = $this->where($where)->field($field)->paginate(12,false,['page'=>$page]);
        }
        return $list;
    }
}

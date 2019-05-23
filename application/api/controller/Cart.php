<?php
/**
 * 购物车API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\CartLogic;
use think\Request;
use think\Db;

class Cart extends ApiBase
{
    
    /*
     * 请求获取购物车列表
     */
    public function cartlist()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $cart_where['user_id'] = $user_id;
        $cartM = model('Cart');
        $cart_res = $cartM->cartList1($cart_where);

        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功','data'=>$cart_res]);
    }

    /**
     * 购物车总数
     */
    public function cart_sum(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $cart_where['user_id'] = $user_id;
        $num = Db::table('cart')->where($cart_where)->sum('goods_num');

        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功','data'=>$num]);
    }

    /**
     * 加入 | 修改 购物车
     */
    public function addCart()
    {   

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $sku_id = Request::instance()->param("sku_id", '', 'intval');
        $cart_number = Request::instance()->param("cart_number", 1, 'intval');
        $act = input('act');
        
        $sku_res = Db::name('goods_sku')->where('sku_id', $sku_id)->field('price,inventory,frozen_stock,goods_id')->find();

        if (empty($sku_res)) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'该商品不存在！','data'=>'']);
        }

        if ($cart_number > ($sku_res['inventory']-$sku_res['frozen_stock'])) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'该商品库存不足！','data'=>'']);
        }

        $goods = Db::table('goods')->where('goods_id',$sku_res['goods_id'])->field('single_number,most_buy_number')->find();

        if( $cart_number > $goods['single_number'] ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>"超过单次购买数量！同类商品单次只能购买{$goods['single_number']}个",'data'=>'']);
        }

        $order_goods_num = Db::table('order_goods')->alias('og')
                            ->join('order o','o.order_id=og.order_id')
                            ->where('o.order_status','neq',3)
                            ->where('og.goods_id',$sku_res['goods_id'])
                            ->where('og.user_id',$user_id)
                            ->sum('og.goods_num');
        
        $num =  $cart_number + $order_goods_num;
        if( $num > $goods['most_buy_number'] ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'超过最多购买量！','data'=>'']);
        }

        $cart_where = array();
        $cart_where['user_id'] = $user_id;
        $cart_where['goods_id'] = $sku_res['goods_id'];
        
        $act_where = [];
        if($act){
            $act_where['sku_id'] = ['not in',$sku_id];
        }

        $cart_goods_num = Db::table('cart')->where($cart_where)->where($act_where)->sum('goods_num');

        $num = $cart_number + $cart_goods_num;
        if( $num > $goods['single_number'] ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>"超过单次购买数量！同类商品单次只能购买{$goods['single_number']}个",'data'=>'']);
        }
        if( $num > $goods['most_buy_number'] ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'超过最多购买量！','data'=>'']);
        }

        $cart_where['sku_id'] = $sku_id;
        $cart_res = Db::table('cart')->where($cart_where)->field('id,goods_num')->find();

        if ($cart_res) {

            $new_number = $cart_res['goods_num'] + $cart_number;
            if($act){
                $new_number = $cart_number;
            }

            if ($new_number <= 0) {
                $result = Db::table('cart')->where('id',$cart_res['id'])->delete();
                $this->ajaxReturn(['status' => -2 , 'msg'=>'该购物车商品已删除！','data'=>'']);
            }

            if ($sku_res['inventory'] >= $new_number) {
                $update_data = array();
                $update_data['id'] = $cart_res['id'];
                $update_data['goods_num'] = $new_number;
                $update_data['subtotal_price'] = $new_number * $sku_res['price'];
                $result = Db::table('cart')->update($update_data);
                $cart_id = $cart_res['id'];
            } else {
                $this->ajaxReturn(['status' => -2 , 'msg'=>'该商品库存不足！','data'=>'']);
            }
        } else {
            $cartData = array();
            $goods_res = Db::name('goods')->where('goods_id',$sku_res['goods_id'])->field('goods_name,price,original_price')->find();
            $cartData['goods_id'] = $sku_res['goods_id'];
            $cartData['goods_name'] = $goods_res['goods_name'];
            $cartData['sku_id'] = $sku_id;
            $cartData['user_id'] = $user_id;
            $cartData['market_price'] = $goods_res['original_price'];
            $cartData['goods_price'] = $sku_res['price'];
            $cartData['member_goods_price'] = $sku_res['price'];
            $cartData['goods_num'] = $cart_number;
            $cartData['subtotal_price'] = $cart_number * $sku_res['price'];
            $cartData['add_time'] = time();
            
            $sku_attr = action('Goods/get_sku_str', $sku_id);
            $cartData['spec_key_name'] = $sku_attr;
            $cart_id = Db::table('cart')->insertGetId($cartData);
        }

        if($cart_id) {
            $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>$cart_id]);
        } else {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'系统异常！','data'=>'']);
        }
    }

    /**
     * 删除购物车
     */
    public function delCart()
    {   
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $idStr = Request::instance()->param("id", '', 'htmlspecialchars');
        
        $where['id'] = array('in', $idStr);
        $where['user_id'] = $user_id;
        $cart_res = Db::table('cart')->where($where)->column('id');
        if (empty($cart_res)) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'购物车不存在！','data'=>'']);
        }
        
        $res = Db::table('cart')->delete($cart_res);
        if ($res) {
            $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>'']);
        } else {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'系统异常！','data'=>'']);
        }
    }


    /**
     * 将商品加入购物车.
     *
     * @param token 登录凭证
     */
    // public function addcart()
    // {
    //     $user_id = $this->get_user_id();
    //     if(!$user_id){
    //         $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
    //     }




    //     $data = '购物车数据';
    //     $this->ajaxReturn(['status' => 0 , 'msg'=>'加入购物车成功','data'=>$data]);
    // }

    
    /*
     * 请求获取购物车列表
     */
    // public function cartlist()
    // {

    //     // $user_id = $this->get_user_id();
    //     // if(!$user_id){
    //     //     $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
    //     // }
    //     $user_id   = 52974;
    //     $cartLogic = new CartLogic();
    //     $cartLogic->setUserId($user_id);
    //     $data      = $cartLogic->getCartList();//用户购物车
    //     $seller    = Db::name('seller')->select();
    //     /*foreach ($data as $k=>$v) {
    //         if($v['goods']['seller_id']==$seller[0]['seller_id']){
    //             $v['seller_name']=$seller[0]['seller_name'];
    //         }else{
    //             $v['seller_name']="";
    //         }
    //     }*/
    //     foreach($data as $k=>$v){
    //         unset($v['user_id']);
    //         unset($v["session_id"]);
    //         unset($v["goods_id"]);
    //         unset($v["goods_name"]);
    //         unset($v["market_price"]);
    //         unset($v["member_goods_price"]);
    //         unset($v["item_id"]);
    //         unset($v["spec_key"]);
    //         unset($v["bar_code"]);
    //         unset($v["add_time"]);
    //         unset($v["prom_type"]);
    //         unset($v["prom_id"]);
    //         unset($v["sku"]);
    //         unset($v["combination_group_id"]);
    //     }
    //     $res[0] = array(
    //         'seller_id'=> 0,
    //         'seller_name'=>'ZF智丰自营',
    //         'data'=>$data,
    //     );
    //     $this->ajaxReturn(['status' => 0 , 'msg'=>'购物车列表成功','data'=>$res]);
    // }



    
    
}

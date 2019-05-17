<?php
/**
 * 订单API
 */
namespace app\api\controller;
// use app\common\model\Users;
// use app\common\logic\UsersLogic;
// use app\common\logic\Integral;
// use app\common\logic\Pay;
// use app\common\logic\PlaceOrder;
// use app\common\logic\PreSellLogic;
// use app\common\logic\UserAddressLogic;
// use app\common\logic\CouponLogic;
// use app\common\logic\CartLogic;
// use app\common\logic\OrderLogic;
// use app\common\model\Combination;
// use app\common\model\PreSell;
// use app\common\model\Shop;
// use app\common\model\SpecGoodsPrice;
// use app\common\model\Goods;
// use app\common\util\TpshopException;
use think\Loader;
use think\Db;

class Order extends ApiBase
{


    /**
     * 购物车提交订单
     */
    public function temporary()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }

        //购物车商品
        $idStr = input('cart_id');
        
        $cart_where['id'] = array('in',$idStr);
        $cart_where['user_id'] = $user_id;
        $cartM = model('Cart');
        $cart_res = $cartM->cartList($cart_where);
        if(!$cart_res){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'购物车商品不存在！','data'=>'']);
        }
        
        // 查询地址
        $addr_data['ua.user_id'] = $user_id;
        $addressM = Model('UserAddr');
        $addr_res = $addressM->getAddressList($addr_data);
        if($addr_res){
            foreach($addr_res as $key=>$value){
                $addr = $value['p_cn'] . $value['c_cn'] . $value['d_cn'] . $value['s_cn'];
                $addr_res[$key]['address'] = $addr . $addr_res[$key]['address'];
                unset($addr_res[$key]['p_cn'],$addr_res[$key]['c_cn'],$addr_res[$key]['d_cn'],$addr_res[$key]['s_cn']);
            }
        }
        
        $data['goods_res'] = $cart_res;
        $data['addr_res'] = $addr_res;
        
        $pay = Db::table('sysset')->value('sets');
        $pay = unserialize($pay)['pay'];

        $pay_type = config('PAY_TYPE');
        $arr = [];
        $i = 0;
        foreach($pay as $key=>$value){
            if($value){
                $arr[$i]['pay_type'] = $pay_type[$key]['pay_type'];
                $arr[$i]['pay_name'] = $pay_type[$key]['pay_name'];
                $i++;
            }
        }

        $data['pay_type'] = $arr;
        

        $shipping_price = 0;
        $goods_ids = '';
        foreach($cart_res as $key=>$value){

            //处理运费
            $goods_res = Db::table('goods')->field('shipping_setting,shipping_price,delivery_id,less_stock_type')->where('goods_id',$value['goods_id'])->find();
            if($goods_res['shipping_setting'] == 1){
                $shipping_price = sprintf("%.2f",$shipping_price + $goods_res['shipping_price']);   //计算该订单的总价
            }else if($goods_res['shipping_setting'] == 2){
                if( !$goods_res['delivery_id'] ){
                    $deliveryWhere['is_default'] = 1;
                }else{
                    $deliveryWhere['delivery_id'] = $goods_res['delivery_id'];
                }
                $delivery = Db::table('goods_delivery')->where($deliveryWhere)->find();
                if( $delivery ){
                    if($delivery['type'] == 2){
                        $shipping_price = sprintf("%.2f",$shipping_price + $delivery['firstprice']);   //计算该订单的总价
                        $number = $value['goods_num'] - $delivery['firstweight'];
                        if($number > 0){
                            $number = ceil( $number / $delivery['secondweight'] );  //向上取整
                            $xu = sprintf("%.2f",$delivery['secondprice'] * $number );   //续价
                            $shipping_price = sprintf("%.2f",$shipping_price + $xu);   //计算该订单的总价
                        }
                    }
                }
            }

            $goods_ids .= ',' . $value['goods_id'];
        }
        $goods_ids = ltrim($goods_ids,',');

        $data['shipping_price'] = $shipping_price;

        $coupon = Db::table('coupon_get')->alias('cg')
                    ->join('coupon c','c.coupon_id=cg.coupon_id','LEFT')
                    ->field('c.coupon_id,c.title,c.price,c.start_time,c.end_time')
                    ->where('c.goods_id','in',$goods_ids)
                    ->where('cg.user_id',$user_id)
                    ->where('cg.is_use',0)
                    ->where('c.start_time','<',time())
                    ->where('c.end_time','>',time())
                    ->select();
        $data['coupon'] = $coupon;

        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功','data'=>$data]);
    }


    /**
     * 提交订单
     * user_id
     * cart_id
     * addr_id
     * pay_type
     * invoice_id
     */
    public function submitOrder()
    {   
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $cart_str = input("cart_id");
        $addr_id = input("address_id");
        $coupon_id = input("coupon_id");
        $pay_type = input("pay_type");
        $user_note = input("user_note", '', 'htmlspecialchars');

        // 查询地址是否存在
        $AddressM = model('UserAddr');

        $addrWhere = array();
        $addrWhere['address_id'] = $addr_id;
        $addrWhere['user_id'] = $user_id;
        $addr_res = $AddressM->getAddressFind($addrWhere);
        
        if (empty($addr_res)) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'该地址不存在！','data'=>'']);
        }
        
        //购物车商品
        $cart_where['id'] = array('in',$cart_str);
        $cart_where['user_id'] = $user_id;
        $cartM = model('Cart');
        $cart_res = $cartM->cartList($cart_where);
        if(!$cart_res){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'购物车商品不存在！','data'=>'']);
        }
        
        $order_amount = '0'; //订单价格
        $order_goods = [];  //订单商品
        $sku_goods = [];  //去库存
        $shipping_price = '0'; //订单运费
        $i = 0;
        $cart_ids = ''; //提交成功后删掉购物车
        $goods_ids = '';//商品IDS
        foreach($cart_res as $key=>$value){
            $goods_ids .= ',' . $value['goods_id'];

            //处理运费
            $goods_res = Db::table('goods')->field('shipping_setting,shipping_price,delivery_id,less_stock_type')->where('goods_id',$value['goods_id'])->find();
            if($goods_res['shipping_setting'] == 1){
                $shipping_price = sprintf("%.2f",$shipping_price + $goods_res['shipping_price']);   //计算该订单的物流费用
            }else if($goods_res['shipping_setting'] == 2){
                if( !$goods_res['delivery_id'] ){
                    $deliveryWhere['is_default'] = 1;
                }else{
                    $deliveryWhere['delivery_id'] = $goods_res['delivery_id'];
                }
                $delivery = Db::table('goods_delivery')->where($deliveryWhere)->find();
                if( $delivery ){
                    if($delivery['type'] == 2){
                        //件数
                        $shipping_price = sprintf("%.2f",$shipping_price + $delivery['firstprice']);   //计算该订单的物流费用
                        $number = $value['goods_num'] - $delivery['firstweight'];
                        if($number > 0){
                            $number = ceil( $number / $delivery['secondweight'] );  //向上取整
                            $xu = sprintf("%.2f",$delivery['secondprice'] * $number );   //续价
                            $shipping_price = sprintf("%.2f",$shipping_price + $xu);   //计算该订单的物流费用
                        }
                    }else{
                        //重量的待处理
                    }
                }

            }

            $cart_ids .= ',' . $value['cart_id'];
            $order_amount = sprintf("%.2f",$order_amount + $value['subtotal_price']);   //计算该订单的总价
            $cat_id = Db::table('goods')->where('goods_id',$value['goods_id'])->value('cat_id1');
            foreach($value['spec'] as $k=>$v){
                $order_goods[$i]['goods_id'] = $v['goods_id'];
                $order_goods[$i]['user_id'] = $v['user_id'];
                $order_goods[$i]['less_stock_type'] = $goods_res['less_stock_type'];
                $order_goods[$i]['cat_id'] = $cat_id;
                $order_goods[$i]['goods_name'] = $v['goods_name'];
                $order_goods[$i]['goods_sn'] = $v['goods_sn'];
                $order_goods[$i]['goods_num'] = $v['goods_num'];
                $order_goods[$i]['final_price'] = $v['goods_price'];
                $order_goods[$i]['goods_price'] = $v['goods_price'];
                $order_goods[$i]['member_goods_price'] = $v['member_goods_price'];
                $order_goods[$i]['sku_id'] = $v['sku_id'];
                $order_goods[$i]['spec_key_name'] = $v['spec_key_name'];
                $order_goods[$i]['delivery_id'] = $goods_res['delivery_id'];
                $i++;
            }
        }

        $coupon_price = 0;
        $goods_ids = ltrim($goods_ids,',');
        if($coupon_id){
            $couponRes = Db::table('coupon_get')->alias('cg')
                    ->join('coupon c','c.coupon_id=cg.coupon_id','LEFT')
                    ->field('c.coupon_id,c.title,c.price,c.start_time,c.end_time')
                    ->where('c.goods_id','in',$goods_ids)
                    ->where('cg.user_id',$user_id)
                    ->where('cg.is_use',0)
                    ->where('c.start_time','<',time())
                    ->where('c.end_time','>',time())
                    ->select();
            if($couponRes){
                foreach($couponRes as $key=>$value){
                    if($value['coupon_id'] = $coupon_id){
                        $coupon_price = $value['price'];
                    }
                }
            }
        }
        
        $cart_ids = ltrim($cart_ids,',');
        
        Db::startTrans();
        $goods_price = $order_amount;
        $order_amount = sprintf("%.2f",$order_amount + $shipping_price);    //商品价格+物流价格=订单金额

        $orderInfoData['order_sn'] = date('YmdHis',time()) . mt_rand(10000000,99999999);
        $orderInfoData['user_id'] = $user_id;
        $orderInfoData['order_status'] = 1;         //订单状态 0:待确认,1:已确认,2:已收货,3:已取消,4:已完成,5:已作废
        $orderInfoData['pay_status'] = 0;       //支付状态 0:未支付,1:已支付,2:部分支付,3:已退款,4:拒绝退款
        $orderInfoData['shipping_status'] = 0;       //商品配送情况;0:未发货,1:已发货,2:部分发货,3:已收货,4:退货
        $orderInfoData['pay_type'] = $pay_type;    //支付方式 1:余额支付,2:后台付款,4:在线支付,5:微信支付,6:支付宝支付,7:银联支付,7:货到付款
        $orderInfoData['consignee'] = $addr_res['consignee'];       //收货人
        $orderInfoData['province'] = $addr_res['province'];
        $orderInfoData['city'] = $addr_res['city'];
        $orderInfoData['district'] = $addr_res['district'];
        $orderInfoData['twon'] = $addr_res['twon'];
        $orderInfoData['address'] = $addr_res['address'];
        $orderInfoData['mobile'] = $addr_res['mobile'];
        $orderInfoData['user_note'] = $user_note;       //备注
        $orderInfoData['add_time'] = time();
        $orderInfoData['coupon_price'] = $coupon_price;     //优惠金额
        $orderInfoData['shipping_price'] = $shipping_price;     //物流费(待完善)
        $orderInfoData['goods_price'] = $goods_price;     //商品价格
        $orderInfoData['order_amount'] = $order_amount;     //订单金额
        
        if($coupon_price){
            $orderInfoData['coupon_id'] = $coupon_id;
            $orderInfoData['total_amount'] = sprintf("%.2f",$order_amount - $coupon_price);       //总金额(实付金额)
        }else{
            $orderInfoData['total_amount'] = $order_amount;       //总金额(实付金额)
        }

        $order_id = Db::table('order')->insertGetId($orderInfoData);
        
        // 添加订单商品
        foreach($order_goods as $key=>$value){
            $order_goods[$key]['order_id'] = $order_id;
            //拍下减库存
            if($value['less_stock_type']==1){
                Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('inventory',$value['goods_num']);
            }
            unset($order_goods[$key]['less_stock_type']);
        }

        //添加使用优惠券记录
        if($coupon_price){
            Db::table('coupon')->where('coupon_id',$coupon_id)->update(['is_use'=>1,'use_time'=>time()]);
        }
        
        $res = Db::table('order_goods')->insertAll($order_goods);
        if (!empty($res)) {
            //将商品从购物车删除
            Db::table('cart')->where('id','in',$cart_str)->delete();
            
            Db::commit();
            $this->ajaxReturn(['status' => 1 ,'msg'=>'提交成功！','data'=>$order_id]);
        } else {
            Db::rollback();
            $this->ajaxReturn(['status' => -2 , 'msg'=>'提交订单失败！','data'=>'']);
        }
    }



   /**
    * 订单列表
    */
    public function order_list()
    {   
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $type = input('type');
        if(!$type) $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
        
        $where = [];

        if ($type=='dfk')$where = array('order_status' => 1 ,'pay_status'=>0 ,'shipping_status' =>0); //待付款
        if ($type=='dfh')$where = array('order_status' => 1 ,'pay_status'=>1 ,'shipping_status' =>0); //待发货
        if ($type=='dsh')$where = array('order_status' => 1 ,'pay_status'=>1 ,'shipping_status' =>1); //待收货
        if ($type=='dpj')$where = array('order_status' => 4 ,'pay_status'=>1 ,'shipping_status' =>3); //待评价
        if ($type=='yqx')$where = array('order_status' => 3); //已取消


        $where['o.user_id'] = $user_id;
        $where['gi.main'] = 1;

        $order_list = Db::table('order')->alias('o')
                        ->join('order_goods og','og.order_id=o.order_id','LEFT')
                        ->join('goods_img gi','gi.goods_id=og.goods_id','LEFT')
                        ->join('goods g','g.goods_id=og.goods_id','LEFT')
                        ->where($where)
                        ->group('og.order_id')
                        ->field('o.order_id,o.order_sn,og.goods_name,gi.picture img,og.spec_key_name,og.goods_price,g.original_price,og.goods_num,o.order_status,o.pay_status,o.shipping_status')
                        ->select();
        
        if($order_list){
            foreach($order_list as $key=>&$value){
                if( $value['order_status'] == 1 && $value['pay_status'] == 0 && $value['shipping_status'] == 0 ){
                    $value['status'] = 1;   //待付款
                }else if( $value['order_status'] == 1 && $value['pay_status'] == 1 && $value['shipping_status'] == 0 ){
                    $value['status'] = 2;   //待发货
                }else if( $value['order_status'] == 1 && $value['pay_status'] == 1 && $value['shipping_status'] == 1 ){
                    $value['status'] = 3;   //待收货
                }else if( $value['order_status'] == 4 && $value['pay_status'] == 1 && $value['shipping_status'] == 3 ){
                    $value['status'] = 4;   //待评价
                    
                    //是否评价
                    $comment = Db::table('goods_comment')->where('order_id',$value['order_id'])->find();
                    if($comment){
                        $value['comment'] = 1;
                    }else{
                        $value['comment'] = 0; 
                    }

                }else if( $value['order_status'] == 3 && $value['pay_status'] == 0 && $value['shipping_status'] == 0 ){
                    $value['status'] = 5;   //已取消
                }
            }
        }
        // pred($order_list);
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$order_list]);
    }


    /**
    * 订单详情
    */
    public function order_detail()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $order_id = input('order_id');

        $where['o.user_id'] = $user_id;
        $where['o.order_id'] = $order_id;

        $order = Db::name('order')->alias('o')->where($where)->find();
        if(!$order){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在','data'=>'']);
        }

        $field = array(
            'o.order_id',//订单ID
            'o.order_sn',//订单编号
            'o.order_status',//订单状态
            'o.pay_status',//支付状态
            'o.shipping_status',//商品配送情况
            'o.pay_type',//支付类型
            'o.consignee',//收货人
            'o.mobile',//收货人手机号
            'o.province',//省
            'o.city',//市
            'o.district',//区
            'o.twon',//街道
            'o.address',//地址
            'o.coupon_price',//优惠券抵扣
            'o.order_amount',//订单总价
            'o.total_amount',//应付款金额
            'o.add_time',//下单时间
            'o.shipping_name',//物流名称
            'o.shipping_price',//物流费用
            'o.user_note',//订单备注
            'o.pay_time',//支付时间
            'o.user_money',//使用余额
        );

        $order = Db::table('order')->alias('o')->where($where)->field($field)->find();

        $pay_type = config('PAY_TYPE');
        foreach($pay_type as $key=>$value){
            if($value['pay_type'] == $order['pay_type']){
                $order['pay_type'] = $value;
            }
        }

        if( $order['order_status'] == 1 && $order['pay_status'] == 0 && $order['shipping_status'] == 0 ){
            $order['status'] = 1;   //待付款
        }else if( $order['order_status'] == 1 && $order['pay_status'] == 1 && $order['shipping_status'] == 0 ){
            $order['status'] = 2;   //待发货
        }else if( $order['order_status'] == 1 && $order['pay_status'] == 1 && $order['shipping_status'] == 1 ){
            $order['status'] = 3;   //待收货
        }else if( $order['order_status'] == 4 && $order['pay_status'] == 1 && $order['shipping_status'] == 3 ){
            $order['status'] = 4;   //待评价
        }else if( $order['order_status'] == 3 && $order['pay_status'] == 0 && $order['shipping_status'] == 0 ){
            $order['status'] = 5;   //已取消
        }
        
        $order['goods_res'] = Db::table('order_goods')->field('goods_id,goods_name,goods_num,spec_key_name,goods_price')->where('order_id',$order['order_id'])->select();
        foreach($order['goods_res'] as $key=>$value){
            $order['goods_res'][$key]['original_price'] = Db::table('goods')->where('goods_id',$value['goods_id'])->value('original_price');
            $order['goods_res'][$key]['img'] = Db::table('goods_img')->where('goods_id',$value['goods_id'])->where('main',1)->value('picture');
        }

        $order['province'] = Db::table('region')->where('area_id',$order['province'])->value('area_name');
        $order['city'] = Db::table('region')->where('area_id',$order['city'])->value('area_name');
        $order['district'] = Db::table('region')->where('area_id',$order['district'])->value('area_name');
        $order['twon'] = Db::table('region')->where('area_id',$order['twon'])->value('area_name');

        $order['address'] = $order['province'].$order['city'].$order['district'].$order['twon'].$order['address'];
        unset($order['province'],$order['city'],$order['district'],$order['twon']);
        // pred($order);
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$order]);
    }

    /**
    * 修改状态
    */
    public function edit_status(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $order_id = input('order_id');
        $status = input('status');

        if($status != 1 && $status != 3 && $status != 4){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
        }

        $order = Db::table('order')->where('order_id',$order_id)->where('user_id',$user_id)->field('order_status,pay_status,shipping_status')->find();
        if(!$order) $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在！','data'=>'']);

        if( $order['order_status'] == 1 && $order['pay_status'] == 0 && $order['shipping_status'] == 0 ){
            if($status != 1) $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);

            $res = Db::table('order')->update(['order_id'=>$order_id,'order_status'=>3]);
        }else if( $order['order_status'] == 1 && $order['pay_status'] == 1 && $order['shipping_status'] == 1 ){
            if($status != 3) $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
            $res = Db::table('order')->update(['order_id'=>$order_id,'order_status'=>4,'shipping_status'=>3]);
        }else if( $order['order_status'] == 4 && $order['pay_status'] == 1 && $order['shipping_status'] == 3 ){
            if($status != 4) $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
            $res = Db::table('order')->where('order_id',$order_id)->delete();
        }

        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>'']);
    }

    /**
    * 订单商品评论
    */
    public function order_comment(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $comments = input('comments','[{"order_id":"1404","goods_id":18,"sku_id":18,"content":"sadsadsadsadsadas","star_rating":1,"img":["21321321321","23213213213","213123213213"]}]');
        $comments = json_decode($comments ,true);


        pred($comments);
        $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>'']);

        $order_id = input('order_id');
        $goods_id = input('goods_id');
        $sku_id = input('sku_id');
        $content = input('content');
        $star_rating = input('star_rating');
        $img = input('img');
    }

    /**
    * 获取订单商品评论列表
    */
    public function order_comment_list(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $order_id = input('order_id');

        $order = Db::table('order')->where('order_id',$order_id)->where('user_id',$user_id)->field('order_status,pay_status,shipping_status')->find();
        if(!$order) $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在！','data'=>'']);

        if( $order['order_status'] == 4 && $order['pay_status'] == 1 && $order['shipping_status'] == 3 ){
            $order_goods = Db::table('order_goods')->alias('og')
                            ->join('goods_img gi','gi.goods_id=og.goods_id')
                            ->where('gi.main',1)
                            ->where('og.order_id',$order_id)
                            ->field('og.goods_id,og.sku_id,og.goods_name,og.goods_num,og.spec_key_name,gi.picture img')
                            ->select();
            $this->ajaxReturn(['status' => 1 , 'msg'=>'成功！','data'=>$order_goods]);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误！','data'=>'']);
        }

    }

}

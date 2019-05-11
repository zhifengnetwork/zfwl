<?php
/**
 * 订单API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\Integral;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\logic\PreSellLogic;
use app\common\logic\UserAddressLogic;
use app\common\logic\CouponLogic;
use app\common\logic\CartLogic;
use app\common\logic\OrderLogic;
use app\common\model\Combination;
use app\common\model\PreSell;
use app\common\model\Shop;
use app\common\model\SpecGoodsPrice;
use app\common\model\Goods;
use app\common\util\TpshopException;
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
        $pay_type = input("pay_type",2);
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
        
        
        $order_amount = ''; //订单价格
        $order_goods = [];  //订单商品
        $sku_goods = [];  //去库存
        $shipping_price = ''; //订单运费
        $i = 0;
        $cart_ids = ''; //提交成功后删掉购物车
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
                // $shipping_price //运费待处理

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
        
        $cart_ids = ltrim($cart_ids,',');
        
        Db::startTrans();

        $orderInfoData['order_sn'] = date('YmdHis',time()) . mt_rand(10000000,99999999);
        $orderInfoData['user_id'] = $user_id;
        $orderInfoData['order_status'] = 0;         //订单状态 0:待确认,1:已确认,2:已收货,3:已取消,4:已完成,5:已作废
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
        $orderInfoData['shipping_price'] = $shipping_price;     //物流费(待完善)
        $orderInfoData['order_amount'] = $order_amount;     //订单金额
        $orderInfoData['total_amount'] = $order_amount;       //总金额(实付金额)
        $orderInfoData['coupon_price'] = 0;              //优惠金额
        
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
        
        $res = Db::table('order_goods')->insertAll($order_goods);
        if (!empty($res)) {
            //将商品从购物车删除
            Db::table('cart')->where('id','in',$cart_str)->delete();
            
            Db::commit();
            $this->ajaxReturn(['status' => 1 ,'msg'=>'提交成功！','data'=>'']);
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
        // $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$type]);
        $type = I('type');
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        /*if ($type=='WAITSEND')$data = array('order_status' => [0,1],'shipping_status' =>1,'pay_code'=>'cod'); //'待发货',货到付款
        if ($type=='WAITSEND')$data = array('pay_status'=>1,'order_status'=>[0,1],'shipping_status'=>0,'pay_code'=>['not in','cod']);//'待发货',非货到付款*/
        if ($type=='WAITSEND')$data = array('tp_order.order_status' => ['in','0,1'],);//'待发货'
        if ($type=='WAITPAY')$data = array('tp_order.pay_status'=>0,'tp_order.order_status'=>0,'tp_order.pay_code'=>['not in','cod'],); //'待支付',
        if ($type=='WAITRECEIVE')$data = array('tp_order.shipping_status'=>1,'order_status'=>1,);//'待收货',
        if ($type=='WAITCCOMMENT')$data = array('tp_order.order_status'=>2,);//'待评价',
        // $data = '订单列表数据';
        $data['tp_order.user_id'] = $user_id;
        /*$name = array(
            'tp_order.order_id',//订单id
            'tp_order.add_time',//下单时间
            'tp_order_goods.goods_name',//商品名称
            'tp_order_goods.spec_key_name',//商品规格名
            'tp_order_goods.goods_price',//本店价格
            'tp_order_goods.goods_num',//购买数
            'tp_order.order_amount',//应付金额
            'seller_name',//商家名称
            'tp_goods.original_img',//商品上传原始图
        );*/
        $order = Db::name('order')->join('tp_order_goods','tp_order.order_id=tp_order_goods.order_id','right')->join('tp_seller','tp_order_goods.seller_id = tp_seller.seller_id','left')->join('tp_goods','tp_goods.goods_id = tp_order_goods.goods_id')->where($data)->select();
        foreach($order as &$k){
            $k['original_img']=SITE_URL.$k['original_img'];
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$order]);
    }


    /**
    * 订单
    */
    public function order_detail()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $order_id = I('id');
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>null]);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>null]);
        }
        $name = array(
            'tp_order.order_id',//订单ID
            'tp_order.order_sn',//订单编号
            'tp_order.order_status',//订单状态
            'tp_order.consignee',//收货人
            'tp_order.country',//国家
            'tp_order.province',//省份
            'tp_order.city',//城市
            'tp_order.district',//县区
            'tp_order.twon',//乡镇
            'tp_order.address',//地址
            'seller_name',//商家名称
            'tp_goods.original_img',//商品上传原始图
            'tp_order_goods.goods_name',//商品名称
            'tp_order_goods.spec_key_name',//商品规格名
            'tp_order_goods.goods_price',//本店价格
            'tp_order_goods.goods_num',//购买数
            'tp_order.shipping_price',//邮费
            'tp_order.total_amount',//订单总价
            'tp_order.order_amount',//应付金额
            'tp_order.pay_time',//支付时间
            'tp_order.pay_name',//支付方式名称
            'tp_order.mobile',//手机号
            'tp_order.user_money',//使用余额
        );
        $data = Db::name('order')->join('tp_order_goods','tp_order.order_id=tp_order_goods.order_id','right')->join('tp_seller','tp_order_goods.seller_id = tp_seller.seller_id','left')->join('tp_goods','tp_goods.goods_id = tp_order_goods.goods_id')->field($name)->where('tp_order.order_id',$order_id)->find();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * 提交订单
     */
	 public function post_order(){   
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }	

        $address_id    = input("address_id/d", 0); //  收货地址id
        $invoice_title = input('invoice_title');  // 发票  
        $taxpayer      = input('taxpayer');       // 纳税人识别号
        $invoice_desc  = input('invoice_desc');       // 发票内容
        $coupon_id     = input("coupon_id/d"); //  优惠券id
        $pay_points    = input("pay_points/d", 0); //  使用积分
        $user_money    = input("user_money/f", 0); //  使用余额
        $user_note     = input("user_note/s", ''); // 用户留言
        $pay_pwd       = input("pay_pwd/s", ''); // 支付密码
        $goods_id      = input("goods_id/d",0); // 商品id
        $goods_num     = input("goods_num/d",0);// 商品数量
        $item_id       = input("item_id/d",0); // 商品规格id
        $action        = input("action/d",0); // 立即购买
        $shop_id       = input('shop_id/d', 0);//自提点id
        $take_time = input('take_time/d');//自提时间
        $consignee = input('consignee/s');//自提点收货人
        $mobile = input('mobile/s');//自提点联系方式
        $is_virtual = input('is_virtual/d',0);
        $data = input('request.');
        $cart_validate = Loader::validate('Cart');
        if($is_virtual === 1){
            $cart_validate->scene('is_virtual');
        }
        if (!$cart_validate->check($data)) {
            $error = $cart_validate->getError();
            $this->ajaxReturn(['status' => -4, 'msg' => $error, 'result' => '']);  //留言长度不符或收货人错误
        }
        $address = Db::name('user_address')->where("address_id", $address_id)->find();
        $cartLogic = new CartLogic();
        $pay = new Pay();
        try {
            $cartLogic->setUserId($user_id);
            if ($action === 1) {
                $cartLogic->setGoodsModel($goods_id);
                $cartLogic->setSpecGoodsPriceById($item_id);
                $cartLogic->setGoodsBuyNum($goods_num);
                $buyGoods = $cartLogic->buyNow();
                $cartList[0] = $buyGoods;
                $pay->payGoodsList($cartList);
            } else {
                $userCartList = $cartLogic->getCartList(1);
                $cartLogic->checkStockCartList($userCartList);
                $pay->payCart($userCartList);
            }
            $pay->setUserId($user_id)
                ->setShopById($shop_id)
                ->delivery($address['district'])
                ->orderPromotion()
                ->useCouponById($coupon_id)
                ->useUserMoney($user_money)
                ->usePayPoints($pay_points);
            // 提交订单
			$placeOrder = new PlaceOrder($pay);
			$placeOrder->setUserAddress($address)
				->setConsignee($consignee)
				->setMobile($mobile)
				->setInvoiceTitle($invoice_title)
				->setUserNote($user_note)
				->setTaxpayer($taxpayer)
				->setInvoiceDesc($invoice_desc)
				->setPayPsw($pay_pwd)
				->setTakeTime($take_time)
				->addNormalOrder();
			$cartLogic->clear();
			$order = $placeOrder->getOrder();
			$this->ajaxReturn(['status' => 0, 'msg' => '提交订单成功', 'data' => ['order_sn' => $order['order_sn']] ]);

        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }	
	 }
	  
}

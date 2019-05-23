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

    /**
     *	处理发货单
     * @param array $data  查询数量
     * @return array
     * @throws \think\Exception
     */
    public function deliveryHandle($data){
       
        $orderObj = $this->where(['order_id'=>$data['order_id']])->find();
        $order =$orderObj->append(['full_address','orderGoods'])->toArray();
        $orderGoods= $order['orderGoods'];
		$selectgoods = $data['goods'];
        if($data['shipping'] == 1){
            if (!$this->updateOrderShipping($data,$order)){
                return array('status'=>0,'msg'=>'操作失败！！');
            }
        }
		$data['order_sn'] = $order['order_sn'];
		$data['delivery_sn'] = $this->get_delivery_sn();
		$data['zipcode'] = $order['zipcode'];
		$data['user_id'] = $order['user_id'];
		$data['admin_id'] = session('admin_id');
		$data['consignee'] = $order['consignee'];
		$data['mobile'] = $order['mobile'];
		$data['country'] = $order['country'];
		$data['province'] = $order['province'];
		$data['city'] = $order['city'];
		$data['district'] = $order['district'];
		$data['address'] = $order['address'];
		$data['shipping_price'] = $order['shipping_price'];
		$data['create_time'] = time();
		
    	if($data['send_type'] == 0 || $data['send_type'] == 3){
			$did = M('delivery_doc')->add($data);
		}else{
			$result = $this->submitOrderExpress($data,$orderGoods);
			if($result['status'] == 1){
				$did = $result['did'];
			}else{
				return array('status'=>0,'msg'=>$result['msg']);
			}
		}
		$is_delivery = 0;
		foreach ($orderGoods as $k=>$v){
			if($v['is_send'] >= 1){
				$is_delivery++;
			}			
			if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
				$res['is_send'] = 1;
				$res['delivery_id'] = $did;
				$r = M('order_goods')->where("rec_id=".$v['rec_id'])->save($res);//改变订单商品发货状态
				$is_delivery++;
			}
		}
		$update['shipping_time'] = time();
		$update['shipping_code'] = $data['shipping_code'];
		$update['shipping_name'] = $data['shipping_name'];
		if($is_delivery == count($orderGoods)){
			$update['shipping_status'] = 1;
		}else{
			$update['shipping_status'] = 2;
		}
		M('order')->where("order_id=".$data['order_id'])->save($update);//改变订单状态
		$s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志
		
		//商家发货, 发送短信给客户
		$res = checkEnableSendSms("5");
		if ($res && $res['status'] ==1) {
		    $user_id = $data['user_id'];
		    $users = M('users')->where('user_id', $user_id)->getField('user_id , nickname , mobile' , true);
		    if($users){
		        $nickname = $users[$user_id]['nickname'];
		        $sender = $users[$user_id]['mobile'];
		        $params = array('user_name'=>$nickname , 'consignee'=>$data['consignee']);
		        $resp = sendSms("5", $sender, $params,'');
		    }
		}

        // 发送微信模板消息通知
        $wechat = new WechatLogic;
        $wechat->sendTemplateMsgOnDeliver($data);
        
		if($s && $r){
			return array('status'=>1,'printhtml'=>isset($result['printhtml']) ? $result['printhtml'] : '');
		}else{
			return array('status'=>0,'msg'=>'发货失败');
		}
     }

    /**
     * 修改订单发货信息
     * @param array $data
     * @param array $order
     * @return bool|mixed
     */
    public function updateOrderShipping($data=[],$order=[]){
        $updata['shipping_code'] = $data['shipping_code'];
        $updata['shipping_name'] = $data['shipping_name'];
        M('order')->where(['order_id'=>$data['order_id']])->save($updata); //改变物流信息
        $updata['invoice_no'] = $data['invoice_no'];
        $delivery_res = M('delivery_doc')->where(['order_id'=>$data['order_id']])->save($updata);  //改变售后的信息
        if ($delivery_res){
            return $this->orderActionLog($order['order_id'],'订单修改发货信息',$data['note']);//操作日志
        }else{
            return false;
        }

    }

}

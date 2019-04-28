<?php
namespace app\admin\controller;

use app\common\model\Order as OrderModel;
use app\common\model\OrderGoods as OrdeGoodsModel;
use Overtrue\Wechat\Payment\Business;
use Overtrue\Wechat\Payment\QueryRefund;
use Overtrue\Wechat\Payment\Refund;
use \think\Db;
use think\Exception;

//物流api
use app\home\controller\Api;

class Order extends Common
{
   /**
     * 订单列表
     */
    public function index()
    {

        $params_key       = ['status', 'pay_status',  'kw', 'order_id','invoice_no'];

        //携带参数
        $where            = $this->get_where($params_key, $param_arr);
        $list             = OrderModel::alias('uo')->field('uo.*,d.order_id as order_idd,d.invoice_no')
                ->join("delivery_doc d",'uo.order_id=d.order_id','LEFT')
                ->join("users a",'uo.user_id=a.user_id','LEFT')
                ->where($where)
                ->order('uo.order_id DESC')
                ->paginate(10, false, ['query' => $where]);
        // 导出设置
        $param_arr['tpl_type'] = 'export';
        // 模板变量赋值
        $this->assign('list', $list);
        //订单状态
        $this->assign('status_list', config('ORDER_STATUS'));
        //支付方式
        $this->assign('type_list',config('PAY_TYPE'));
        //支付状态
        $this->assign('pay_status',config('PAY_STATUS'));
        
        $this->assign('param_arr', $param_arr);

        $this->assign('meta_title', '订单列表');
        return $this->fetch();
      
    }
    
    /**
     * 订单详情
     */
    public function edit(){
        $order_id       = input('order_id','');
        $orderGoodsMdel = new OrdeGoodsModel();
        $orderModel     = new OrderModel();
        $order_info     =  $orderModel->where(['order_id'=>$order_id])->find();
        $orderGoods     =  $orderGoodsMdel::all(['order_id'=>$order_id,'is_send'=>['lt',2]]);
        
         //订单状态
         $this->assign('order_status', config('ORDER_STATUS'));
         //支付方式
         $this->assign('type_list',config('PAY_TYPE'));
        //物流
        // $Api = new Api;
        // $data = M('delivery_doc')->where('order_id', $order_id)->find();
        // $shipping_code = $data['shipping_code'];
        // $invoice_no = $data['invoice_no'];
        // $result = $Api->queryExpress($shipping_code, $invoice_no);
        // if ($result['status'] == 0) {
        //     $result['result'] = $result['result']['list'];
        // }
        // $this->assign('invoice_no', $invoice_no);
        // $this->assign('result', $result);
        $this->assign('orderGoods', $orderGoods);
        $this->assign('order_info', $order_info);
        $this->assign('meta_title', '订单详情');
        return $this->fetch();
    }



    public function refund(){


    }

    /**
     * 导出订单
     */
    public function export()
    {
    
        try {
             
            $begin_time = strtotime(input('begin_time'));
            $end_time   = strtotime(input('end_time'));
    
            $where['uo.create_time'] = array('BETWEEN', "$begin_time, $end_time");
           //每次导出数据量
           $perNum   = 10000;
           //总数据量
           $totalNum =  Db::table('order')->alias('uo')->where($where)->count();
           //循环取数据次数
           $times    = ceil($totalNum / $perNum);
    
           $fileName = '订单数据详情-' . date('YmdHi', time());
          
            
            header('Content-Type: application/vnd.ms-execl');
            header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

            //以写入追加的方式打开php标准输出流
            $fp     = fopen('php://output', 'a');

            $title  =  ['订单号','商品名称', '订单金额','最终订单金额', '订单状态','支付状态','支付方式','下单时间','支付时间'];
            $iconvTitle = array_map( function ($v) { return iconv('UTF-8', 'GBK', $v); }, $title);

            fputcsv($fp, $iconvTitle);
          

            //分批写入文件
            for ($i = 1; $i <= $times; $i++) {
                $list = Db::table('order')->alias('uo')
                    ->field('uo.id,uo.good_name,uo.price,uo.end_price,uo.status,uo.pid,uo.type,uo.create_time,uo.pay_time')
                    ->where($where)
                    ->order('uo.id DESC')
                    ->limit(($i - 1) * $perNum, $perNum)
                    ->select();
                   
                //转码写入
                foreach ($list as $key => &$item) {
                    $item['id']               = $item['id'];
                    $item['good_name']        = mb_convert_encoding($item['good_name'], "GBK", "UTF-8");;
                    $item['price']            = $item['price'] ;
                    $item['end_price']        = $item['end_price'] ;
                    $item['status']           = mb_convert_encoding($item['status'], "GBK", "UTF-8");
                    $item['pid']              = $item['pid'];
                    $item['type']             = mb_convert_encoding($item['type'], "GBK", "UTF-8");
                    $item['create_time']      = date('Y-m-d H:i:s', $item['create_time']) . "\t";
                    $item['pay_time']         = date('Y-m-d H:i:s', $item['pay_time']) . "\t";
                    fputcsv($fp, $item);
                }
                //刷新缓冲区
                ob_flush();
            }
        } catch (Exception $e) {
            pft_log('admin/order/explode', json_encode([$where, $e->getMessage()]));
            $this->error('导出订单错误: ' . $e->getMessage());
        }

    }


    /**
     * 获取where条件，一般用于列表或者筛选，整体项目结构统一
     * TODO: 获取的where对应的表前缀可能有点问题，输入变量与筛选字段的区别，...
     * @param  array $params_key  条件key数组
     * @param  array &$params_arr 结果参数数组，便于调用处使用
     * @return array              $where
     */
    private function &get_where($params_key, &$params_arr)
    {
        $begin_time  = input('begin_time', '');
        $end_time    = input('end_time', '');
        $order_id    = input('order_id', '');
        $invoice_no  = input('invoice_no', '');
        $status      = input('status/d',0);
        $kw          = input('kw', '');
        $invoice_no  = input('invoice_no', '');
        $type        = input('type/d', 0);
        $where = [];
        if (!empty($order_id)) {
            $where['uo.order_id']    = $order_id;
        }
        if (!empty($invoice_no)) {
            $where['d.invoice_no']   = $invoice_no;
        }
        if($status > 0){
            $where['uo.order_status'] = $status;
        }

        if($type >  0){
            $where['uo.pay_status'] = $type;
        }

        if(!empty($kw)){
            is_numeric($kw)?$where['a.mobile'] = $kw:$where['a.realname'] = $kw;
        }
       

        // if ($begin_time && $end_time) {
        //     $where['uo.create_time'] = [['EGT', $begin_time], ['LT', $end_time]];
        // } elseif ($begin_time) {
        //     $where['uo.create_time'] = ['EGT', $begin_time];
        // } elseif ($end_time) {
        //     $where['uo.create_time'] = ['LT', $end_time];
        // }
        $params_arr = array(
            'begin_time'      => $begin_time,
            'end_time'        => $end_time,
        );
        $this->assign('kw', $kw);
        $this->assign('invoice_no', $invoice_no);
        $this->assign('status', $status);
        $this->assign('type', $type);
        $this->assign('order_id', $order_id);
        $this->assign('begin_time', empty($begin_time)?date('Y-m-d'):$begin_time);
        $this->assign('end_time', empty($end_time)?date('Y-m-d'):$end_time);
        $this->assign('params_arr', $params_arr);
        
        return $where;
    }

      /**
     * 判断登录用户是否有退款的权限
     * @author dwer
     * @date   2017-11-06
     *
     * @return bool
     */
    private function _hasRefundAuth()
    {
      if (TERRACE_ID == 1006) {
          return in_array($this->mginfo['mgid'], Config::get('order_refund_mgid')) ? true : false;
      } else {
          return in_array($this->_loginRole,  ['super_manager', 'manager']) ? true : false;
      }
    }





   
     
   
}

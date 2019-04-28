<?php
namespace app\admin\controller;

use think\Db;
use app\common\model\Order as OrderModel;
/**
 * 首页
 */
class Total extends Common
{
    public function index()
    {
        $this->assign('meta_title', '财务首页');
        return $this->fetch();
        # code...
    }


     /**
     * 余额记录
     */
    public function balance_logs()
    {
        $params_key       = ['shipping_status', 'pay_status',  'kw', 'order_id','good_name'];

        //携带参数
        // $where            = $this->get_where($params_key, $param_arr);
        $where = array();
        
        $list             = OrderModel::alias('uo')->field('*')
            ->where($where)
            ->order('order_id DESC')
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
        $this->assign('meta_title', '余额记录');
        return $this->fetch();
    }


    /**
     * 积分记录
     */
    public function integral_logs()
    {

        $params_key       = ['shipping_status', 'pay_status',  'kw', 'order_id','good_name'];

        //携带参数
        // $where            = $this->get_where($params_key, $param_arr);
        $where = array();
        
        $list             = OrderModel::alias('uo')->field('*')
            ->where($where)
            ->order('order_id DESC')
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

        $this->assign('meta_title', '积分记录');
        return $this->fetch();
    }
    /***
     * 财务数据
     */
    public function finance()
    {
        $this->assign('meta_title', '财务数据');
        return $this->fetch();
    }
    /***
     * 业务数据
     */
    public function business()
    {
        $this->assign('meta_title', '业务数据');
        return $this->fetch();
    }

}
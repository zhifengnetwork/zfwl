<?php
namespace app\admin\controller;

use think\Db;
use app\common\model\Order as OrderModel;
use app\common\model\Member as MemberModel;
use think\Request;
/**
 * 首页
 */
class Finance extends Common
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

    /***
     * 余额充值
     */
    public function balance_recharge()
    {
        $uid     = input('id/d',27);
        $profile = MemberModel::get($uid);
        if (Request::instance()->isPost()){
            $num = input('num/f');
            if($num <= 0){
                $this->error('输入的金额有误');
            }
            MemberModel::setCredit($profile['openid'],$profile['id'],'credit2', $num, array(UID, '余额充值'));
            $this->success('充值成功', url('member/member_edit',['id' => $profile['id']]));
        }
        $this->assign('profile', $profile);
        $this->assign('meta_title', '余额充值');
        return $this->fetch();
    }
    /***
     * 积分充值
     */
    public function integral_recharge()
    {
         $uid     = input('id/d',27);
         $profile = MemberModel::get($uid);
        if (Request::instance()->isPost()){
            $num = input('num/f');
            if($num <= 0){
                $this->error('输入的积分有误');
            }
            MemberModel::setCredit($profile['openid'], $profile['id'],'credit1', $num, array(UID, '积分充值'));
            $this->success('充值成功', url('member/member_edit',['id' => $profile['id']]));

        }
        $this->assign('profile', $profile);
        $this->assign('meta_title', '积分充值');
        return $this->fetch();
    }




}
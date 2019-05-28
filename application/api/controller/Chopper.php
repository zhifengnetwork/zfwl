<?php
namespace app\api\controller;
use app\common\model\GoodsChopper;
use think\Db;

class Chopper extends ApiBase
{
    /**
    * 砍一刀商品列表
    */
    public function goods_list(){
        
        $page = input('page');
        $where['gg.is_show'] = 1;
        $where['gg.is_delete'] = 0;
        $where['gg.status'] = 2;
        $where['g.is_del'] = 0;
        $where['g.is_show'] = 1;
        $where['gi.main'] = 1;

        $list = Db::table('goods_chopper')->alias('gg')
                ->join('goods g','g.goods_id=gg.goods_id','LEFT')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where($where)
                ->field('gg.chopper_id,g.goods_id,gg.already_amount,gg.surplus_amount,gg.start_time,gg.end_time,gg.sort,g.goods_name,g.desc,gi.picture img')
                ->paginate(6,false,['page'=>$page]);
        if($list){
            foreach($list as $key=>&$value){
                //拿出砍价商品规格价格最低的来显示
                $value['price'] = Db::table('goods_sku')->where('goods_id',$value['goods_id'])->where('inventory','>',0)->min('groupon_price');
            }
        }
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$list]);
    }

    /**
     * 砍一刀详情
     */
    public function chopper_edit(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $where['gg.is_show']   = 1;
        $where['gg.is_delete'] = 0;
        $where['gg.status']    = 2;
        $where['g.is_del']     = 0;
        $where['g.is_show']    = 1;
        $where['gi.main']      = 1;
        $list = Db::table('goods_chopper')->alias('gg')
                ->join('goods g','g.goods_id = gg.goods_id','LEFT')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where($where)
                ->field('gg.chopper_id,g.goods_id,gg.already_amount,gg.surplus_amount,gg.start_time,gg.end_time,gg.sort,g.goods_name,g.desc,gi.picture img')
                ->find();
             
    }


    /***
     * 砍一刀接口
     */

    public function chopper(){
        
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $chopper_id = input('chopper_id/d',0);
        if($chopper_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>'']);
        }
        $chopper = GoodsChopper::get($chopper_id);
        $info    = Db::name('user_chopper')->where(['status' => 1])->order('id desc')->find();
        $dt_end_price = unserialize($chopper['dt_end_price']);
        $section = unserialize($chopper['section']);
        $what    = $info?$info['what'] + 1: 1;
        if($info){
             if($info['what'] == 1){
                $amount = $chopper['second_amount']; 
             }elseif($info['what'] == 2){
                $amount =  $chopper['third_amount'];
             }elseif($section['start'] >= $what && $section['end'] <= $what){
                $amount =  $section['amount'];
             }else{
                if($chopper['dt_end_num'] == 0){
                    $amount = 0.01;
                }else{
                    $amount = $dt_end_price[$chopper['dt_end_num']];
                }      
             }
        }else{
            $amount = $chopper['first_amount'];
        }
        
        $insert = [
            'chopper_id'   => $chopper['chopper_id'],
            'user_id'      => $user_id,
            'goods_id'     => $chopper['goods_id'],
            'status'       => 1,
            'what'         => $what,
            'create_time'  => time(),
            'amount'       => $amount
        ];
        // 启动事务
        Db::startTrans();
        $perid = Db::name('user_chopper')->strict(false)->insertGetId($insert);

        if($perid !== false){
          $update = [
              'participants'   =>  Db::raw('participants+1'),
              'chopper_num'    =>  Db::raw('chopper_num+1'),
              'already_amount' =>  Db::raw('already_amount-'.$amount.''),
              'dt_end_num'     =>  Db::raw('dt_end_num-1'),
          ]; 
          $res = GoodsChopper::where(['chopper_id' => $chopper['chopper_id']])->update($update);
          if($res !== false){
                // 提交事务
                Db::commit();
                $this->ajaxReturn(['status' => 1 , 'msg'=>'成功砍掉'.$amount.'元！','data'=>'']);
          }
            Db::rollback();
            $this->ajaxReturn(['status' => -2 , 'msg'=>'砍价失败，请重试！','data'=>'']);
        }else{
            Db::rollback();
            $this->ajaxReturn(['status' => -2 , 'msg'=>'砍价失败，请重试！','data'=>'']);
        }
        



    }

    
}

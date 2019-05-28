<?php
namespace app\admin\controller;

use app\common\model\Clock as ClockModel;
use think\Request;
use think\Db;
/**
 * 基本配置管理控制器
 */
class Clock extends Common
{
    /**
     * 打卡配置
     */
    public function index()
    {

        $ClockModel= new ClockModel();
        $settingInfo=$ClockModel->getSetting();
        $timeList=$ClockModel->getTime();
        if(Request::instance()->isPost()){
            $data = input('post.');
            //图片处理
            if( isset($data['img']) && !empty($data['img'][0])){

                    $saveName = request()->time().rand(0,99999) . '.png';
                    $img=base64_decode($data['img'][0]);
                    //生成文件夹
                    $names = "clock" ;
                    $name = "public/upload/images/clock/";
                    if (!file_exists(ROOT_PATH .$name)){
                        mkdir(ROOT_PATH .$name,0777,true);
                    }
                    //保存图片到本地
                    file_put_contents(ROOT_PATH .$name.$saveName,$img);
                    unset($data['img'][0]);
                    $data['img']= $names."/".$saveName;
            }
            if ( Db::table('clock')->where(['id'=>1])->update($data) ) {
                $this->success("打卡配置更新成功!");
            }else{
                $this->success("打卡配置更新失败!");
            }

        }
        return $this->fetch('clock/index',[ 'meta_title'    =>  '打卡设置','timeList'=>$timeList,'settingInfo'=>$settingInfo]);
    }

    /**
     * 参与打卡用户列表
     */

    public function join_user(){

        $kw = input('realname', '');
        $join_time = input('join_time', '');
        $where = [];
        if(!empty($kw)){
            $where['a.realname']=$kw;
        }
        //查询第一次参与的用户
        if(!empty($join_time)){
            $begin_time=$join_time." 00:00:00";
            $end_time=$join_time." 23:59:59";
            $where['clock_user.join_time'] = [['EGT', strtotime($begin_time)], ['LT', strtotime($end_time)]];
        }
        $carryParameter = [
            'kw'               => $kw,
            'join_time'        => $join_time,
        ];
       $userList=Db::name("clock_user") ->join("member a",'a.id=clock_user.uid','LEFT')->where($where)->field('clock_user.id,clock_user.pay_money,clock_user.get_money,clock_user.status,clock_user.join_day,clock_user.join_time,a.realname')->order("clock_user.id DESC")->paginate(20, false,['query' => $carryParameter]);
       return $this->fetch('clock/join_user',[ 'meta_title'    =>  '打卡用户列表','list'=>$userList,'realname'=>$kw]);
    }

    /**
     * 打卡列表
     */

    public  function day_list(){

        $kw = input('realname', '');
        $punch_time = input('punch_time', '');
        $status = input('status', '');
        $where = [];
        if(!empty($kw)){
            $where['a.realname']=$kw;
        }
        if(!empty($status)){
            $where['clock_day.status']=$status;
        }
        //查询某一天打卡用户
        if(!empty($punch_time)){
            $begin_time=$punch_time." 00:00:00";
            $end_time=$punch_time." 23:59:59";
            $where['clock_day.punch_time'] = [['EGT', strtotime($begin_time)], ['LT', strtotime($end_time)]];
        }
        $carryParameter = [
            'kw'               => $kw,
            'punch_time'        => $punch_time,
            'status'        => $status,
        ];

        $dayList=Db::name("clock_day") ->join("member a",'a.id=clock_day.uid','LEFT')->where($where)->field('clock_day.id,clock_day.punch_time,clock_day.money,clock_day.status,a.realname')->order("clock_day.punch_time DESC")->paginate(20, false,['query' => $carryParameter]);
        return $this->fetch('clock/day_list',[ 'meta_title'    =>  '打卡列表','list'=>$dayList,'realname'=>$kw,'status'=> $status]);

    }

    /**
     * 打卡交易明细
     */

    public function balance_list(){

        $kw = input('realname', '');
        $create_time = input('create_time', '');
        $type = input('type', '');
        $where = [];
        if(!empty($kw)){
            $where['a.realname']=$kw;
        }
        if(!empty($type)){
            $where['clock_balance_log.type']=$type;
        }
        //查询某一天的交易情况
        if(!empty($create_time)){
            $begin_time=$create_time." 00:00:00";
            $end_time=$create_time." 23:59:59";
            $where['clock_balance_log.create_time'] = [['EGT', strtotime($begin_time)], ['LT', strtotime($end_time)]];
        }
        $carryParameter = [
            'kw'               => $kw,
            'create_time'        => $create_time,
            'type'        => $type,
        ];
        $logList=Db::name("clock_balance_log") ->join("member a",'a.id=clock_balance_log.uid','LEFT')->where($where)->field('clock_balance_log.id,clock_balance_log.create_time,clock_balance_log.log,clock_balance_log.type,a.realname')->order("clock_balance_log.create_time DESC")->paginate(1, false,['query' => $carryParameter]);
        return $this->fetch('clock/balance_list',[ 'meta_title'    =>  '打卡交易明细列表','list'=>$logList,'realname'=>$kw,'type'=>$type]);
    }

}

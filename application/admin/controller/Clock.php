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

       $userList=Db::name("clock_user") ->join("member a",'a.id=clock_user.uid','LEFT')->field('clock_user.id,clock_user.pay_money,clock_user.get_money,clock_user.status,clock_user.join_day,clock_user.join_time,a.realname')->order("clock_user.id DESC")->select();
       return $this->fetch('clock/join_user',[ 'meta_title'    =>  '打卡用户列表','list'=>$userList]);
    }

    /**
     * 打卡列表
     */

    public  function day_list(){

        $dayList=Db::name("clock_day") ->join("member a",'a.id=clock_day.uid','LEFT')->field('clock_day.id,clock_day.punch_time,clock_day.money,clock_day.status,a.realname')->order("clock_day.punch_time DESC")->select();
        return $this->fetch('clock/day_list',[ 'meta_title'    =>  '打卡列表','list'=>$dayList]);

    }

    /**
     * 打卡交易明细
     */

    public function balance_list(){

        $logList=Db::name("clock_balance_log") ->join("member a",'a.id=clock_balance_log.uid','LEFT')->field('clock_balance_log.id,clock_balance_log.create_time,clock_balance_log.log,clock_balance_log.type,a.realname')->order("clock_balance_log.create_time DESC")->select();
        return $this->fetch('clock/balance_list',[ 'meta_title'    =>  '打卡交易明细列表','list'=>$logList]);

    }

}

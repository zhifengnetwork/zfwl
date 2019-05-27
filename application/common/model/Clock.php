<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;



class Clock extends Model
{

    protected $autoWriteTimestamp = true;
    public   $timeArr=['0:00','1:00','2:00','3:00','4:00','5:00','6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00'];

    //获取打卡配置信息
    public function  getSetting(){
        $settingInfo=Db::name('clock')->where(['id'=>1])->find();
        if(empty($settingInfo)){
            $settingInfo['id']=1;
            $settingInfo['title']=null;
            $settingInfo['banner']=null;
            $settingInfo['join_money']=null;
            $settingInfo['clock_money']=null;
            $settingInfo['money']=null;
            $settingInfo['start_time']=null;
            $settingInfo['end_time']=null;
            $settingInfo['clock_rule']=null;
            $settingInfo['status']=1;
        }
        return $settingInfo;

    }

    //获取打卡时间段
    public function getTime(){
        return $this->timeArr;
    }



}

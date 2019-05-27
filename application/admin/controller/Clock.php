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
     *
     */
     public function updateSetting(){


         print_r($_POST);
     }

    /**
     * 参与打卡用户列表
     */

    public function join_user(){


    }

    /**
     * 打卡列表
     */

    public  function day_list(){


    }

    /**
     * 打卡交易明细
     */

    public function balance_list(){



    }

}

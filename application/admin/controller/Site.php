<?php
namespace app\admin\controller;

use think\Db;
use think\Config;

class Site extends Common
{   
    public function _initialize()
    {   
        parent::_initialize();
        $this->info = Db::table('site')->find();
    }

    public function index()
    {

        if( request()->isPost() ){
            $data = input('post.');

            if( isset($data['logo']) ) $data['logo'] = $this->base_img($data['logo'],'logo');

            if( isset($data['logo_mobile']) ) $data['logo_mobile'] = $this->base_img($data['logo_mobile'],'logo_mobile');

            if($data['id']){
                Db::table('site')->update($data,$data['id']);
            }else{
                Db::table('site')->insert($data);
            }
            $this->success('修改成功!');
        }

        return $this->fetch('',[
            'meta_title'    =>  '网站设置',
            'info'  =>  $this->info,
        ]);
    }

    public function base_img($base,$images=''){
        $saveName = request()->time().rand(0,99999) . '.png';

        $img=base64_decode($base);
        //生成文件夹
        $names = "site" ;
        $name = "site/" .date('Ymd',time()) ;
        echo ROOT_PATH .Config('c_pub.img');die;
        if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
            mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
        } 
        //保存图片到本地
        file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$img);

        if($this->info[$images]){
            @unlink( ROOT_PATH .Config('c_pub.img') . $this->info[$images] );
        }
        return $name.$saveName;
    }

}

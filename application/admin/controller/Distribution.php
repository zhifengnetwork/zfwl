<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;

/*
 * 分销管理
 */
class Distribution extends Common
{

    /*
     * 分销中心入口
     */
    public function index(){



        return $this->fetch('',[
            'meta_title'    =>  '分销列表',
            // 'info'  =>  $info,
        ]);
    }

    /*
     * 分销中心入口
     */
    public function distribution_center(){
        
        $shop_id = session('admin_user_auth.mgid');
        $info = Db::table('distribution_set')->where('shop_id',$shop_id)->find();

        if( request()->isPost() ){
            $data = input('post.');
            
            if( isset($data['cover_img']) ) $data['cover_img'] = $this->base_img($data['cover_img'],'distribution_set','cover_img',$info['cover_img']);

            if($info){
                $data['id'] = $info['id'];
                $res = Db::name('distribution_set')->update($data);
            }else{
                $data['shop_id'] = $shop_id;
                $res = Db::name('distribution_set')->insert($data);
            }

            if( $res !== false ){
                $this->success('修改成功！');
            }
            $this->success('修改失败！');
        }

        return $this->fetch('',[
            'meta_title'    =>  '分销中心入口',
            'info'  =>  $info,
        ]);
    }

    /*
     * 分销设置
     */
    public function distribution_set(){
        
        $shop_id = session('admin_user_auth.mgid');
        $info = Db::table('distribution_set')->where('shop_id',$shop_id)->find();

        if( request()->isPost() ){
            $data = input('post.');
            
            if( isset($data['cover_img']) ) $data['cover_img'] = $this->base_img($data['cover_img'],'distribution_set','cover_img',$info['cover_img']);

            if($info){
                $data['id'] = $info['id'];
                $res = Db::name('distribution_set')->update($data);
            }else{
                $data['shop_id'] = $shop_id;
                $res = Db::name('distribution_set')->insert($data);
            }

            if( $res !== false ){
                $this->success('修改成功！');
            }
            $this->success('修改失败！');
        }

        return $this->fetch('',[
            'meta_title'    =>  '分销设置',
            'info'  =>  $info,
        ]);
    }

    /*
     * 分销关系
     */
    public function distribution_relations(){
        $shop_id = session('admin_user_auth.mgid');
        $info = Db::table('distribution_set')->where('shop_id',$shop_id)->find();

        if( request()->isPost() ){
            $data = input('post.');
            
            if($info){
                $data['id'] = $info['id'];
                $res = Db::name('distribution_set')->update($data);
            }else{
                $data['shop_id'] = $shop_id;
                $res = Db::name('distribution_set')->insert($data);
            }

            if( $res !== false ){
                $this->success('修改成功！');
            }
            $this->success('修改失败！');
        }

        return $this->fetch('',[
            'meta_title'    =>  '分销关系',
            'info'  =>  $info,
        ]);
    }
}

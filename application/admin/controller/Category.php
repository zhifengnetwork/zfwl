<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;

/*
 * 分类管理
 */
class Category extends Common
{
    /*
     * 分类列表
     */
    public function index()
    {   
        $list  = Db::table('category')->order('sort DESC,cat_id ASC')->select();
        $list  = getTree1($list);
        return $this->fetch('category/index',[
            'list'          =>  $list,
            'meta_title'    =>  '分类列表',
        ]);
    }

    /*
     * 添加新分类 | 添加子分类
     */
    public function add()
    {   

        $pid = input('pid');

        if( Request::instance()->isPost() ){
            $data = input('post.');
            if( !$data['cat_name'] ){
                $this->error('分类名称必须填写！');
            }
            
            if( isset($data['img']) ){
                
                    $saveName = request()->time().rand(0,99999) . '.png';
    
                    $img=base64_decode($data['img']);
                    //生成文件夹
                    $names = "category" ;
                    $name = "category/" .date('Ymd',time()) ;
                    if (!file_exists(ROOT_PATH .'upload/images/'.$names)){ 
                        mkdir(ROOT_PATH .'upload/images/'.$names,0777,true);
                    } 
                    //保存图片到本地
                    file_put_contents(ROOT_PATH .'upload/images/'.$name.$saveName,$img);
    
                    $data['img'] = $name.$saveName;
            }
            
            if($pid){
                $data['level'] = Db::table('category')->where('cat_id',$pid)->value('level') + 1;
            }
            
            if ( Db::table('category')->insert($data) ) {
                $this->success('添加成功', url('category/index'));
            } else {
                $this->error('添加失败');
            }
        }

        if($pid){
            $meta_title = "添加子分类";
        }else{
            $meta_title = "添加新分类";
        }

        return $this->fetch('category/add',[
            'meta_title'  =>  $meta_title,
        ]);
    }

    /*
     * 修改分类
     */
    public function edit(){
        $cat_id = input('cat_id');

        if(!$cat_id){
            $this->error('参数错误！');
        }
        $info = Db::table('category')->find($cat_id);
        
        if( Request::instance()->isPost() ){
            $data = input('post.');

            if( !$data['cat_name'] ){
                $this->error('分类名称必须填写！');
            }

            if( isset($data['img']) ){
                
                $saveName = request()->time().rand(0,99999) . '.png';

                $img=base64_decode($data['img']);
                //生成文件夹
                $names = "category" ;
                $name = "category/" .date('Ymd',time()) ;
                if (!file_exists(ROOT_PATH .'upload/images/'.$names)){ 
                    mkdir(ROOT_PATH .'upload/images/'.$names,0777,true);
                } 
                //保存图片到本地
                file_put_contents(ROOT_PATH .'upload/images/'.$name.$saveName,$img);

                $data['img'] = $name.$saveName;

                if($info['img']){
                    @unlink( ROOT_PATH .'upload/images/' . $info['img'] );
                }
            }

            if ( Db::table('category')->update($data) !== false ) {
                $this->success('修改成功', url('category/index'));
            } else {
                $this->error('修改失败');
            }
        }

        return $this->fetch('category/edit',[
            'meta_title'  =>    '编辑分类',
            'info'        =>    $info,
        ]);
    }
    
    /*
     * 删除分类
     */
    public function del(){
        $cat_id = input('cat_id');
        if(!$cat_id){
            jason(100,'参数错误');
        }
        $info = Db::table('category')->find($cat_id);
        if(!$info){
            jason(100,'参数错误');
        }
        if( Db::table('category')->where('pid',$cat_id)->find() ){
            jason(100,'该分类含有下级分类，不能删除');
        }

        if( Db::table('category')->where('cat_id',$cat_id)->delete() ){
            if( $info['img'] ){
                @unlink( ROOT_PATH .'upload/images/' . $info['img'] );
            }
            jason(200,'删除分类成功！');
        }

    }

    /*
     * 分类层级设置
     */
    public function category_set(){

        $info = Db::table('category_set')->find();
        if( Request::instance()->isPost() ){
            $data = input('post.');

            if( isset($data['img']) ){
                
                $saveName = request()->time().rand(0,99999) . '.png';

                $img=base64_decode($data['img']);
                //生成文件夹
                $names = "category_set" ;
                $name = "category_set/" .date('Ymd',time()) ;
                if (!file_exists(ROOT_PATH .'upload/images/'.$names)){ 
                    mkdir(ROOT_PATH .'upload/images/'.$names,0777,true);
                } 
                //保存图片到本地
                file_put_contents(ROOT_PATH .'upload/images/'.$name.$saveName,$img);

                $data['img'] = $name.$saveName;

                if($info['img']){
                    @unlink( ROOT_PATH .'upload/images/' . $info['img'] );
                }
            }

            if($data['set_id']){
                $res = Db::name('category_set')->update($data);
                
            }else{
                $res = Db::name('category_set')->insert($data);
            }

            if( $res !== false ){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！');
            }
            
        }

        return $this->fetch('category/category_set',[
            'meta_title'  =>    '分类层级设置',
            'info'        =>    $info,
        ]);
    }

}
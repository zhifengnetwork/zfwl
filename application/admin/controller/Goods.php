<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;

/*
 * 商品管理
 */
class Goods extends Common
{
    /*
     * 商品列表
     */
    public function index()
    {   

        $where = [];
        $pageParam = ['query' => []];

        $where['g.is_del'] = 0;
        // $pageParam['query']['is_del'] = 0;

        $is_show = input('is_show');
        if( $is_show ){
            $where['g.is_show'] = $is_show;
            $pageParam['query']['is_show'] = $is_show;
        }else if($is_show === '0'){
            $is_show = 0;
            $where['g.is_show'] = $is_show;
            $pageParam['query']['is_show'] = $is_show;
        }

        $goods_name = input('goods_name');
        if( $goods_name ){
            $where["g.goods_name"] = ['like', "%{$goods_name}%"];
            $pageParam['query']['goods_name'] = ['like', "%{$goods_name}%"];
        }

        $cat_id1 = input('cat_id1');
        if( $cat_id1 ){
            $where['g.cat_id1'] = $cat_id1;
            $pageParam['query']['cat_id1'] = $cat_id1;
        }
        
        $cat_id2 = input('cat_id2');
        if( $cat_id2 ){
            $where['g.cat_id2'] = $cat_id2;
            $pageParam['query']['cat_id2'] = $cat_id2;
        }

        $list  = Db::table('goods')->alias('g')->order('goods_id DESC')->where($where)->paginate(10,false,$pageParam);

        //商品一级分类
        $cat_id11 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id22 = Db::table('category')->where('level',2)->select();

        return $this->fetch('goods/index',[
            'list'          =>  $list,
            'is_show'       =>  $is_show,
            'goods_name'    =>  $goods_name,
            'cat_id1'       =>  $cat_id1,
            'cat_id2'       =>  $cat_id2,
            'cat_id11'      =>  $cat_id11,
            'cat_id22'      =>  $cat_id22,
            
            'meta_title'    =>  '商品列表',
        ]);
    }

    /*
     * 添加商品
     */
    public function add()
    {   

        if( Request::instance()->isPost() ){
            $data = input('post.');

            //验证
            $validate = Loader::validate('Goods');
            if(!$validate->scene('add')->check($data)){
                $this->error( $validate->getError() );
            }

            if( isset( $data['goods_attr'] ) ){
                if( in_array( 6 , $data['goods_attr']  ) ){
                    $data['limited_start'] = strtotime( $data['limited_start'] );
                    $data['limited_end'] = strtotime( $data['limited_end'] );
                }
                $data['goods_attr'] = implode( ',' , $data['goods_attr'] );
            }
            $data['add_time'] = strtotime( $data['add_time'] );

            if( isset($data['img']) ){
                
                $saveName = request()->time().rand(0,99999) . '.png';

                $img=base64_decode($data['img']);
                //生成文件夹
                $names = "goods" ;
                $name = "goods/" .date('Ymd',time()) ;
                if (!file_exists(ROOT_PATH .'upload/images/'.$names)){ 
                    mkdir(ROOT_PATH .'upload/images/'.$names,0777,true);
                } 
                //保存图片到本地
                file_put_contents(ROOT_PATH .'upload/images/'.$name.$saveName,$img);

                $data['img'] = $name.$saveName;

            }

            if ( Db::table('goods')->insert($data) ) {
                $this->success('添加成功', url('goods/add'));
            } else {
                $this->error('添加失败');
            }

        }

        //商品属性
        $goods_attr = Db::table('goods_attr')->select();
        //商品一级分类
        $cat_id1 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id2 = Db::table('category')->where('level',2)->select();

        return $this->fetch('goods/add',[
            'meta_title'    =>  '添加商品',
            'goods_attr'    =>  $goods_attr,
            'cat_id1'       =>  $cat_id1,
            'cat_id2'       =>  $cat_id2,
        ]);
    }

    /*
     * 修改商品
     */
    public function edit(){
        $goods_id = input('goods_id');

        if(!$goods_id){
            $this->error('参数错误！');
        }
        $info = Db::table('goods')->find($goods_id);
        if($info['goods_attr']){
            $info['goods_attr'] = explode(',',$info['goods_attr']);
        }
        
        if( Request::instance()->isPost() ){
            $data = input('post.');

            //验证
            $validate = Loader::validate('Goods');
            if(!$validate->scene('edit')->check($data)){
                $this->error( $validate->getError() );
            }

            if( isset( $data['goods_attr'] ) ){
                if( in_array( 6 , $data['goods_attr']  ) ){
                    $data['limited_start'] = strtotime( $data['limited_start'] );
                    $data['limited_end'] = strtotime( $data['limited_end'] );
                }
                $data['goods_attr'] = implode( ',' , $data['goods_attr'] );
            }
            $data['add_time'] = strtotime( $data['add_time'] );

            if( isset($data['img']) ){
                
                $saveName = request()->time().rand(0,99999) . '.png';

                $img=base64_decode($data['img']);
                //生成文件夹
                $names = "goods" ;
                $name = "goods/" .date('Ymd',time()) ;
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
            
            if ( Db::table('goods')->update($data) !== false ) {
                $this->success('修改成功', url('goods/index'));
            } else {
                $this->error('修改失败');
            }
        }

        //商品属性
        $goods_attr = Db::table('goods_attr')->select();
        //商品一级分类
        $cat_id1 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id2 = Db::table('category')->where('level',2)->select();

        return $this->fetch('goods/edit',[
            'meta_title'  =>    '编辑商品',
            'info'        =>    $info,
            'goods_attr'  =>  $goods_attr,
            'cat_id1'     =>  $cat_id1,
            'cat_id2'     =>  $cat_id2,
        ]);
    }
    
    /*
     * ajax 删除商品
     */
    public function del(){
        $goods_id = input('goods_id');
        if(!$goods_id){
            jason(100,'参数错误');
        }
        $info = Db::table('goods')->find($goods_id);
        if(!$info){
            jason(100,'参数错误');
        }

        if( Db::table('goods')->where('goods_id',$goods_id)->update(['is_del'=>1]) ){
            jason(200,'删除商品成功！');
        }else{
            jason(100,'删除商品失败！');
        }

    }

    /*
     * ajax 上架/下架
     */
    public function is_show(){
        $goods_id = input('goods_id');
        $is_show  = input('is_show');
        if(!$goods_id){
            jason(100,'参数错误');
        }
        $info = Db::table('goods')->find($goods_id);
        if(!$info){
            jason(100,'参数错误');
        }

        if( Db::table('goods')->where('goods_id',$goods_id)->update(['is_show'=>$is_show]) ){
            jason(200);
        }
        jason(100,'失败');

    }

    /*
     * ajax 批量上架/批量下架
     */
    public function is_show_all(){
        $goods_id = input('goods_id');
        $is_show  = input('is_show');
        if(!$goods_id){
            jason(100,'参数错误');
        }

        if( Db::table('goods')->where('goods_id','in',$goods_id)->update(['is_show'=>$is_show]) ){
            jason(200);
        }
        jason(100,'失败');

    }
    
    /*
     * ajax 批量删除
     */
    public function del_all(){
        $goods_id = input('goods_id');
        if(!$goods_id){
            jason(100,'参数错误');
        }

        if( Db::table('goods')->where('goods_id','in',$goods_id)->update(['is_del'=>1]) ){
            jason(200);
        }
        jason(100,'失败');

    }

    /*
     * 商品规格管理
     */
    public function sku_index(){

        $where = [];
        $pageParam = ['query' => []];

        $sku_name = input('sku_name');
        if( $sku_name ){
            $where["sku_name"] = ['like', "%{$sku_name}%"];
            $pageParam['query']['sku_name'] = ['like', "%{$sku_name}%"];
        }

        $list  = Db::table('goods_sku')->order('sku_id DESC')->where($where)->paginate(10,false,$pageParam);

        return $this->fetch('goods/sku_index',[
            'list'          =>  $list,
            'sku_name'      =>  $sku_name,
            
            'meta_title'    =>  '商品规格管理',
        ]);
    }

    /*
     * 添加商品规格
     */
    public function goods_sku_add(){

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['sku_name'] || !$data['sku_value'] ){
                $this->error('请填写完整！');
            }

            $data['create_time'] = time();
            $data['update_time'] = time();

            if ( Db::table('goods_sku')->insert($data) ) {
                $this->success('添加成功', url('goods/goods_sku_add'));
            } else {
                $this->error('添加失败');
            }

        }

        return $this->fetch('goods/goods_sku_add',[
            'meta_title'    =>  '添加商品规格',
        ]);
    }

    /*
     * 修改商品规格
     */
    public function goods_sku_edit(){
        $sku_id = input('sku_id');
        
        if(!$sku_id){
            $this->error('参数错误！');
        }

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['sku_name'] || !$data['sku_value'] ){
                $this->error('请填写完整！');
            }

            $data['update_time'] = time();

            if ( Db::table('goods_sku')->update($data) ) {
                $this->success('修改成功', url('goods/goods_sku_edit'));
            } else {
                $this->error('修改失败');
            }

        }

        $info = Db::table('goods_sku')->find($sku_id);
        
        return $this->fetch('goods/goods_sku_edit',[
            'info'          =>  $info,
            'meta_title'    =>  '修改商品规格',
        ]);
    }

    /*
     * ajax 删除商品规格
     */
    public function goods_sku_del(){
        $sku_id = input('sku_id');
        if(!$sku_id){
            jason(100,'参数错误');
        }
        $info = Db::table('goods_sku')->find($sku_id);
        if(!$info){
            jason(100,'参数错误');
        }

        if( Db::table('goods')->where('sku_id',$sku_id)->delete() ){
            jason(200,'删除商品规格成功！');
        }else{
            jason(100,'删除商品规格失败！');
        }

    }

}

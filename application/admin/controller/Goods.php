<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;
use think\Config;
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
            
            //规格处理
            $sku_keys = array_keys($data['attr_td']);
            $sku = [];
            foreach ($data['attr_td'][$sku_keys[0]] as $key => $value) {
                $sku[$key]['sku_attr'] = '{';
                foreach ($sku_keys as $k => $v) {
                    $sku[$key]['sku_attr'] .= '"' . $v . '"' . ':' . $data['attr_td'][$v][$key] . ',';
                }
                $sku[$key]['sku_attr'] = rtrim($sku[$key]['sku_attr'],',') . '}';
                $sku[$key]['market_price']    = $data['market_price'][$key];
                $sku[$key]['stock']           = $data['stocks'][$key];
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
                $names =  "goods" ;
                $name  =  "goods/" .date('Ymd',time()) ;
                if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
                    mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
                } 
                //保存图片到本地
                file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$img);

                $data['img'] = $name.$saveName;

            }
            $goods_id = Db::table('goods')->strict(false)->insertGetId($data);
            if ( $goods_id ) {
                //库存
                foreach ($sku as $key => $value) {
                    $sku[$key]['goods_id'] = $goods_id;
                    Db::name('goods_sku')->insert($sku[$key]);
                }
                $this->success('添加成功', url('goods/add'));
            } else {
                $this->error('添加失败');
            }

        }

        //商品类型
        $goods_type = Db::table('goods_type')->select();
        //商品属性
        $goods_attr = Db::table('goods_attr')->select();
        //商品一级分类
        $cat_id1 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id2 = Db::table('category')->where('level',2)->select();

        return $this->fetch('goods/add',[
            'meta_title'    =>  '添加商品',
            'goods_type'    =>  $goods_type,
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

            //规格处理
            $sku_keys = array_keys($data['attr_td']);
            $sku = [];
            $new_sku = [];
            foreach ($data['attr_td'][$sku_keys[0]] as $key => $value) {
                if(isset($data['sku_id'][$key])){

                    $sku[$key]['sku_attr'] = '{';
                    foreach ($sku_keys as $k => $v) {
                        $sku[$key]['sku_attr'] .= '"' . $v . '"' . ':' . $data['attr_td'][$v][$key] . ',';
                    }
                    $sku[$key]['sku_attr'] = rtrim($sku[$key]['sku_attr'],',') . '}';

                    $sku[$key]['sku_id']          = $data['sku_id'][$key];
                    $sku[$key]['market_price']    = $data['market_price'][$key];
                    $sku[$key]['stock']           = $data['stocks'][$key];

                    Db::table('goods_sku')->update($sku[$key]);
                }else{
                    $new_sku[$key]['sku_attr'] = '{';
                    foreach ($sku_keys as $k => $v) {
                        $new_sku[$key]['sku_attr'] .= '"' . $v . '"' . ':' . $data['attr_td'][$v][$key] . ',';
                    }
                    $new_sku[$key]['sku_attr'] = rtrim($new_sku[$key]['sku_attr'],',') . '}';

                    $new_sku[$key]['goods_id']        = $data['goods_id'];
                    $new_sku[$key]['market_price']    = $data['market_price'][$key];
                    $new_sku[$key]['stock']           = $data['stocks'][$key];

                    Db::table('goods_sku')->insert($new_sku[$key]);
                }
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
                if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
                    mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
                } 
                //保存图片到本地
                file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$img);

                $data['img'] = $name.$saveName;

                if($info['img']){
                    @unlink( ROOT_PATH .Config('c_pub.img') . $info['img'] );
                }
            }
            
            if ( Db::table('goods')->strict(false)->update($data) !== false ) {
                $this->success('修改成功', url('goods/index'));
            } else {
                $this->error('修改失败');
            }
        }

        //sku
        $sku = Db::table('goods_sku')->where('goods_id','=',$info['goods_id'])->select();
        if($sku){
            foreach ($sku as $key => $value) {
                $sku[$key]['sku_attr'] = json_decode( $value['sku_attr'] ,true );
            }
        }

        $spec = Db::table('goods_spec')->where('type_id','=',$info['type_id'])->select();
        if( $spec ){
            foreach ($spec as $key => $value) {
                $spec[$key]['spec_value'] = Db::table('goods_spec_val')->where('spec_id','=',$value['spec_id'])->select();
            }
        }
        
        //商品类型
        $goods_type = Db::table('goods_type')->select();
        //商品属性
        $goods_attr = Db::table('goods_attr')->select();
        //商品一级分类
        $cat_id1 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id2 = Db::table('category')->where('level',2)->select();

        return $this->fetch('goods/edit',[
            'meta_title'  =>  '编辑商品',
            'info'        =>  $info,
            'sku'         =>  $sku,
            'spec'        =>  $spec,
            'goods_type'  =>  $goods_type,
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
            jason([],'参数错误',0);
        }
        $info = Db::table('goods')->find($goods_id);
        if(!$info){
            jason([],'参数错误',0);
        }

        if( Db::table('goods')->where('goods_id',$goods_id)->update(['is_del'=>1]) ){
            jason([],'删除商品成功！');
        }else{
            jason([],'删除商品失败！',0);
        }

    }

    /*
     * ajax 上架/下架
     */
    public function is_show(){
        $goods_id = input('goods_id');
        $is_show  = input('is_show');
        if(!$goods_id){
            jason([],'参数错误',0);
        }
        $info = Db::table('goods')->find($goods_id);
        if(!$info){
            jason([],'参数错误',0);
        }

        if( Db::table('goods')->where('goods_id',$goods_id)->update(['is_show'=>$is_show]) ){
            jason(200);
        }
        jason([],'失败',0);

    }

    /*
     * ajax 批量上架/批量下架
     */
    public function is_show_all(){
        $goods_id = input('goods_id');
        $is_show  = input('is_show');
        if(!$goods_id){
            jason([],'参数错误',0);
        }

        if( Db::table('goods')->where('goods_id','in',$goods_id)->update(['is_show'=>$is_show]) ){
            jason([]);
        }
        jason([],'失败',0);

    }
    
    /*
     * ajax 批量删除
     */
    public function del_all(){
        $goods_id = input('goods_id');
        if(!$goods_id){
            jason([],'参数错误',0);
        }

        if( Db::table('goods')->where('goods_id','in',$goods_id)->update(['is_del'=>1]) ){
            jason([]);
        }
        jason([],'失败',0);

    }

    public function goods_type_list(){

        $where = [];
        $pageParam = ['query' => []];

        $type_name = input('type_name');
        if( $type_name ){
            $where["type_name"] = ['like', "%{$type_name}%"];
            $pageParam['query']['type_name'] = ['like', "%{$type_name}%"];
        }

        $list  = Db::table('goods_type')->order('type_id DESC')->where($where)->paginate(10,false,$pageParam);

        return $this->fetch('',[
            'list'          =>  $list,
            'type_name'      =>  $type_name,
            'meta_title'    =>  '商品规格管理',
        ]);
    }

    public function goods_type_add(){

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['type_name'] ){
                $this->error('请填写商品类型名称！');
            }

            if ( Db::table('goods_type')->insert($data) ) {
                $this->success('添加成功', url('goods/goods_type_list'));
            } else {
                $this->error('添加失败');
            }
        }

        return $this->fetch('',[
            'meta_title'    =>  '添加商品类型',
        ]);
    }

    public function goods_type_edit(){
        $type_id = input('type_id');
        
        if(!$type_id){
            $this->error('参数错误！');
        }

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['type_name'] ){
                $this->error('请填写商品类型名称！');
            }

            if ( Db::table('goods_type')->update($data) ) {
                $this->success('修改成功', url('goods/goods_type_list'));
            } else {
                $this->error('修改失败');
            }

        }

        $info = Db::table('goods_type')->find($type_id);
        
        return $this->fetch('',[
            'info'          =>  $info,
            'meta_title'    =>  '修改商品类型',
        ]);
    }

    
    public function goods_type_del(){
        $type_id = input('type_id');
        if(!$type_id){
            jason([],'参数错误',0);
        }
        $info = Db::table('goods_type')->find($type_id);
        if(!$info){
            jason([],'参数错误',0);
        }
        $spec = Db::name('goods_spec')->where('type_id','=',$type_id)->find();
        if($spec){
            jason([],'该类型含有规格，不能删除！',0);
        }

        if( Db::table('goods_type')->where('type_id',$type_id)->delete() ){
            jason([],'删除商品规格成功！');
        }else{
            jason([],'删除商品规格失败！',0);
        }

    }

    public function goods_spec_list(){
        $where = [];
        $pageParam = ['query' => []];

        $type_id = input('type_id');
        if(!$type_id){
            $this->error('参数错误！');
        }

        $where["type_id"] = ['eq', "{$type_id}"];
        $pageParam['query']['type_id'] = ['like', "%{$type_id}%"];

        $spec_name = input('spec_name');
        if( $spec_name ){
            $where["spec_name"] = ['like', "%{$spec_name}%"];
            $pageParam['query']['spec_name'] = ['like', "%{$spec_name}%"];
        }

        $list  = Db::table('goods_spec')->order('spec_id DESC')->where($where)->paginate(10,false,$pageParam);

        return $this->fetch('',[
            'list'          =>  $list,
            'spec_name'      =>  $spec_name,
            'meta_title'    =>  '商品规格管理',
        ]);
    }

    public function goods_spec_add(){

        if( Request::instance()->isPost() ){
            $data = input('post.');

            $data['type_id'] = input('type_id');
            if(!$data['type_id']){
                $this->error('参数错误！');
            }
            
            if( !$data['spec_name'] ){
                $this->error('请填写商品规格名称！');
            }
            
            if ( Db::table('goods_spec')->insert($data) ) {
                $this->success('添加成功', url('goods/goods_spec_list',['type_id'=>$data['type_id']],false));
            } else {
                $this->error('添加失败');
            }
        }

        return $this->fetch('',[
            'meta_title'    =>  '添加商品规格',
        ]);
    }

    public function goods_spec_edit(){
        $spec_id = input('spec_id');
        
        if(!$spec_id){
            $this->error('参数错误！');
        }
        
        $info = Db::table('goods_spec')->find($spec_id);

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['spec_name'] ){
                $this->error('请填写商品规格名称！');
            }

            if ( Db::table('goods_spec')->update($data) ) {
                $this->success('修改成功', url('goods/goods_spec_list',['type_id'=>$info['type_id']],false));
            } else {
                $this->error('修改失败');
            }

        }
        
        return $this->fetch('',[
            'info'          =>  $info,
            'meta_title'    =>  '修改商品规格',
        ]);
    }

    
    public function goods_spec_del(){
        $spec_id = input('spec_id');
        if(!$spec_id){
            jason([],'参数错误',0);
        }
        $info = Db::table('goods_spec')->find($spec_id);
        if(!$info){
            jason([],'参数错误',0);
        }
        $spec = Db::name('goods_spec_val')->where('spec_id','=',$spec_id)->find();
        if($spec){
            jason([],'该规格含有规格值，不能删除！',0);
        }

        if( Db::table('goods_spec')->where('spec_id',$spec_id)->delete() ){
            jason([],'删除商品规格成功！');
        }else{
            jason([],'删除商品规格失败！',0);
        }
    }

    public function goods_spec_val_list(){
        $where = [];
        $pageParam = ['query' => []];

        $spec_id = input('spec_id');
        if(!$spec_id){
            $this->error('参数错误！');
        }

        $where["spec_id"] = ['eq', "{$spec_id}"];
        $pageParam['query']['spec_id'] = ['like', "%{$spec_id}%"];

        $val_name = input('val_name');
        if( $val_name ){
            $where["val_name"] = ['like', "%{$val_name}%"];
            $pageParam['query']['val_name'] = ['like', "%{$val_name}%"];
        }

        $list  = Db::table('goods_spec_val')->order('val_id DESC')->where($where)->paginate(10,false,$pageParam);

        return $this->fetch('',[
            'list'          =>  $list,
            'val_name'      =>  $val_name,
            'meta_title'    =>  '商品规格值管理',
        ]);
    }

    public function goods_spec_val_add(){

        if( Request::instance()->isPost() ){
            $data = input('post.');

            $data['spec_id'] = input('spec_id');
            if(!$data['spec_id']){
                $this->error('参数错误！');
            }
            
            if( !$data['val_name'] ){
                $this->error('请填写商品规格值名称！');
            }
            
            if ( Db::table('goods_spec_val')->insert($data) ) {
                $this->success('添加成功', url('goods/goods_spec_val_list',['spec_id'=>$data['spec_id']],false));
            } else {
                $this->error('添加失败');
            }
        }

        return $this->fetch('',[
            'meta_title'    =>  '添加商品规格值',
        ]);
    }

    public function goods_spec_val_edit(){
        $val_id = input('val_id');
        
        if(!$val_id){
            $this->error('参数错误！');
        }
        
        $info = Db::table('goods_spec_val')->find($val_id);

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            if( !$data['val_name'] ){
                $this->error('请填写商品规格值名称！');
            }

            if ( Db::table('goods_spec_val')->update($data) ) {
                $this->success('修改成功', url('goods/goods_spec_val_list',['spec_id'=>$info['spec_id']],false));
            } else {
                $this->error('修改失败');
            }

        }
        
        return $this->fetch('',[
            'info'          =>  $info,
            'meta_title'    =>  '修改商品规格值',
        ]);
    }

    
    public function goods_spec_val_del(){
        $val_id = input('val_id');
        if(!$val_id){
            jason([],'参数错误',0);
        }
        $info = Db::table('goods_spec_val')->find($val_id);
        if(!$info){
            jason([],'参数错误',0);
        }

        if( Db::table('goods_spec_val')->where('val_id',$val_id)->delete() ){
            jason([],'删除商品规格成功！');
        }else{
            jason([],'删除商品规格失败！',0);
        }
    }


    /**
     * ajax规格
     */
    public function spec(){
        $type_id = input('type_id','1');

        if(!$type_id){
            return false;
        }

        $res = Db::table('goods_spec')->where('type_id','=',$type_id)->select();

        foreach ($res as $key => $value) {
            $res[$key]['spec_value'] = Db::table('goods_spec_val')->where('spec_id','=',$value['spec_id'])->select();
        }

        return json($res);
    }

    /**
     * ajax删除sku
     */
    public function del_sku(){
        if( request()->isAjax() ){
            $sku_id = input('sku_id');
            if( Db::table('goods_sku')->where('sku_id','=',$sku_id)->delete() ){
                jason([],'删除商品规格成功！');
            }else{
                jason([],'删除商品规格成功！',0);
            }
        }
    }

    /**
     * 配送方式列表
     */
    public function goods_delivery_list(){

        $where = [];
        $pageParam = ['query' => []];

        $name = input('name');
        if( $name ){
            $where["name"] = ['like', "%{$name}%"];
            $pageParam['query']['name'] = ['like', "%{$name}%"];
        }

        $list = Db::table('goods_delivery')->order('delivery_id DESC')->where($where)->paginate(10,false,$pageParam);

        return $this->fetch('',[
            'name'          =>  $name,
            'list'          =>  $list,
            'meta_title'    =>  '配送方式列表',
        ]);
    }

    /**
     * 添加配送方式
     */
    public function goods_delivery_add(){

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            //验证
            $validate = Loader::validate('Delivery');
            if(!$validate->scene('add')->check($data)){
                $this->error( $validate->getError() );
            }

            $data['areas'] = array();
            if(isset($data['citys'])){
                foreach($data['citys'] as $key=>$value){
                    $data['areas']['citys'][$key]            = $data['citys'][$key];
                    $data['areas']['firstweight_qt'][$key]   = $data['firstweight_qt'][$key];
                    $data['areas']['firstprice_qt'][$key]    = $data['firstprice_qt'][$key];
                    $data['areas']['secondweight_qt'][$key]  = $data['secondweight_qt'][$key];
                    $data['areas']['secondprice_qt'][$key]   = $data['secondprice_qt'][$key];
                }
            }
            $data['areas'] = serialize($data['areas']);

            if($data['is_default']){
                Db::table('goods_delivery')->where('delivery_id','neq',0)->update(['is_default'=>0]);
            }
            
            if ( Db::table('goods_delivery')->strict(false)->insert($data) ) {
                $this->success('添加成功', url('goods/goods_delivery_list','',false));
            } else {
                $this->error('添加失败');
            }
        }

        $areas = file_get_contents(ROOT_PATH . 'public/upload/areas');
        $areas = unserialize($areas);

        return $this->fetch('',[
            'meta_title'    =>  '添加配送方式',
            'areas'         =>  $areas,
        ]);
    }

    /**
     * 修改配送方式
     */
    public function goods_delivery_edit(){

        $delivery_id = input('delivery_id');
        if(!$delivery_id) $this->error('参数错误！');
        $info = Db::table('goods_delivery')->find($delivery_id);

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            //验证
            $validate = Loader::validate('Delivery');
            if(!$validate->scene('edit')->check($data)){
                $this->error( $validate->getError() );
            }

            $data['areas'] = array();
            if(isset($data['citys'])){
                foreach($data['citys'] as $key=>$value){
                    $data['areas']['citys'][$key]            = $data['citys'][$key];
                    $data['areas']['firstweight_qt'][$key]   = $data['firstweight_qt'][$key];
                    $data['areas']['firstprice_qt'][$key]    = $data['firstprice_qt'][$key];
                    $data['areas']['secondweight_qt'][$key]  = $data['secondweight_qt'][$key];
                    $data['areas']['secondprice_qt'][$key]   = $data['secondprice_qt'][$key];
                }
            }
            $data['areas'] = serialize($data['areas']);

            if($data['is_default']){
                Db::table('goods_delivery')->where('delivery_id','neq',0)->update(['is_default'=>0]);
            }
            
            if ( Db::table('goods_delivery')->strict(false)->update($data) ) {
                $this->success('修改成功', url('goods/goods_delivery_list','',false));
            } else {
                $this->error('修改失败');
            }
        }

        $info['areas'] = unserialize($info['areas']);

        $areas = file_get_contents(ROOT_PATH . 'public/upload/areas');
        $areas = unserialize($areas);

        return $this->fetch('',[
            'meta_title'    =>  '修改配送方式',
            'info'          =>  $info,
            'areas'         =>  $areas,
        ]);
    }

    /**
     * 删除配送方式
     */
    public function goods_delivery_del(){
        if( request()->isAjax()){
            $delivery_id = input('delivery_id');
            if( Db::table('goods_delivery')->where('delivery_id','=',$delivery_id)->delete()){
                jason([],'删除配送方式成功！');
            }else{
                jason([],'删除配送方式成功！',0);
            }
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/19
 * Time: 15:57
 */

namespace app\admin\controller;
use think\Db;

class Shop extends Common
{
    public function _initialize () {
        parent::_initialize();
      
        $info = session('admin_user_auth');
        $this->admin_id = $info['mgid'];
    }
    public function index () {

    }

    public function getKeysList () {
        $list = model('DiyKeys')->where('status',1)->select();
        return json($list);
    }

    public function editShop () {
        if (request()->isPost()){
            $id = request()->param('id',0,'intval');
            $page_name = request()->param('page_name');
            $data = request()->param('data');
            if (empty($page_name)){
                return json(['code'=>0,'msg'=>'请填写页面名称']);
            }else if (!empty($data)){
                $res = model('DiyEweiShop')->edit($data,$this->admin_id,$page_name,$id);
                if ($res){
                    return json(['code'=>1,'msg'=>'保存成功']);
                }else{
                    return json(['code'=>0,'msg'=>'保存失败']);
                }

            }else{
                return json(['code'=>0,'msg'=>'首页不能为空，请您添加组件']);
            }
        }
    }

    public function getShopData () {

        $res = model('DiyEweiShop')->getShopData();
        if (!empty($res)){
            return json(['code'=>1,'msg'=>'','data'=>$res]);
        }else{
            return json(['code'=>0,'msg'=>'没有数据，请添加','data'=>'']);
        }
    }

    public function gooodsList () {
        $keyword = request()->param('keyword');
        $page = request()->param('page');
        $list = model('Goods')->getGoodsList($keyword,0,$page);
        if (!empty($list)){
            return json(['code'=>1,'msg'=>'','data'=>$list]);
        }else{
            return json(['code'=>0,'msg'=>'还没有商品哦','data'=>$list]);
        }
    }

    public function categoryList () {
        $list  = Db::table('category')->order('sort DESC,cat_id ASC')->select();
        if (!empty($list)){
            $list  = getTree1($list);
            return json(['code'=>1,'msg'=>'','data'=>$list]);
        }else{
            return json(['code'=>0,'msg'=>'没有数据哦','data'=>$list]);
        }
    }


    public function shop_img(){
        $img      = input('img');
        if(empty($img)){
            $this->ajaxReturn(['code'=>0,'msg'=>'上传图片不能为空','data'=>'']);
        }
        $saveName       = request()->time().rand(0,99999) . '.png';
        $base64_string  = explode(',', $img);
        $imgs           = base64_decode($base64_string[1]);
        //生成文件夹
        $names = "shops";
        $name  = "shops/" .date('Ymd',time());
        if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
            mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
        } 
        //保存图片到本地
        $r   = file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$imgs);
        $this->ajaxReturn(['code'=>1,'msg'=>'ok','data'=>SITE_URL.'/upload/images/'.$name.$saveName]);
    }

    public function ajaxReturn($data){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }






    

   
}
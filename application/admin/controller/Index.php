<?php
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Db;

/**
 * 首页
 */
class Index extends Common
{
    public function index()
    { 
        $where= [];
        $where['status'] = ['>=',0];
        $list       = Db::table('diy_ewei_shop')
                    ->where($where)
                    ->order('id')
                    ->paginate(10, false, ['page' => request()->param('page')]);
        $this->assign('list', $list);
        $this->assign('meta_title', '店铺装修');
        return $this->fetch();
    }


    public function page_edit(){
        $id  = request()->param('id',0,'intval');
        $this->assign('id',$id);
        $this->assign('meta_title', '页面编辑');
        return $this->fetch();
    }


    public function page_add(){
        $this->assign('meta_title', '页面新增');
        return $this->fetch();
    }

    public function page_enable () {
        $id = request()->param('id',0,'intval');
        $status = request()->param('id',0,'status');
        if (!empty($id)){
            $getPage = model('DiyEweiShop')->where(['id'=>$id])->find();
            if (!empty($getPage)){
                if ($getPage['status'] == $status){
                    if ($status == 0){
                        return json(['code'=>0, 'msg'=>'该页面已经是禁用了','data'=>[]]);
                    }else{
                        return json(['code'=>0, 'msg'=>'该页面已经是启用了','data'=>[]]);
                    }
                }else{
                    if ($status == 1){
                        $getThisEnablePage = model('DiyEweiShop')->where(['status'=>1])->find();
                        if (!empty($getThisEnablePage)){
                            model('DiyEweiShop')->where(['id'=>$getThisEnablePage['id']])->update(['status'=>0]);
                        }
                    }
                    $updateThisPage = model('DiyEweiShop')->where(['id'=>$id])->update(['status'=>$status]);
                    if ($updateThisPage){
                        return json(['code'=>1, 'msg'=>'操作成功','data'=>[]]);
                    }else{
                        return json(['code'=>0, 'msg'=>'操作失败','data'=>[]]);
                    }
                }
            }else{
                return json(['code'=>0, 'msg'=>'页面不存在！','data'=>[]]);
            }
        }else{
            return json(['code'=>0, 'msg'=>'id不存在','data'=>[]]);
        }
    }

    public function page_delete () {
        $id = request()->param('id',0,'intval');
        if (!empty($id)) {
            $getPage = model('DiyEweiShop')->where(['id' => $id])->find();
            if (!empty($getPage)){
                $delete = model('DiyEweiShop')->where(['id'=>$id])->update(['status'=>-1]);
                if ($delete){
                    return json(['code'=>1, 'msg'=>'操作成功','data'=>[]]);
                }else{
                    return json(['code'=>0, 'msg'=>'操作失败','data'=>[]]);
                }
            }else{
                return json(['code'=>0, 'msg'=>'页面不存在！','data'=>[]]);
            }
        }else{
                return json(['code'=>0, 'msg'=>'id不存在','data'=>[]]);
            }
    }

    /***
     * 支付方式
     */
    public function pay_set(){
        if( Request::instance()->isPost() ){
            $data = input('post.');
            $path = ROOT_PATH . '/data/cert';
            if (!file_exists($path)){ 
                mkdir($path,0777,true);
            }
            $sec = '';
            if (!empty($_FILES['weixin_cert_file']['name'])){
                $sec['cert'] = $this->upload_cert('weixin_cert_file');
            }
            if (!empty($_FILES['weixin_key_file']['name'])){
                $sec['key'] = $this->upload_cert('weixin_key_file');
            }
            if (!empty($_FILES['weixin_root_file']['name'])){
                $sec['root'] = $this->upload_cert('weixin_root_file');
            }
            $update['sets']    =   serialize($data);
            if(!empty($sec)){
                $update['sec'] =   serialize($sec);
            }
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($update);
            if($res){
                $this->success('编辑成功', url('index/pay_set'));
            }
            $this->error('编辑失败');
        }
        $sysset = Db::table('sysset')->field('*')->find();
      
        $set    = unserialize($sysset['sets']);
        $sec    = unserialize($sysset['sec']);
      
        $this->assign('sec', $sec);
        $this->assign('set', $set);
        $this->assign('meta_title', '支付方式');
        return $this->fetch();
    }

    /***
     * 支付参数
     */
    public function pay_content(){
        $sysset     = Db::table('sysset')->field('*')->find();
        $set        = unserialize($sysset['sets']);
        $payment    = unserialize($sysset['payment']);
       
        $set    = unserialize($sysset['sets']);
        if(Request::instance()->isPost()){
            $patdata = input('post.');
            if($patdata['pay']['alipay'] == 1){
                if(empty($patdata['alipay']['account'])){
                     $this->error('支付宝账号不能为空');
                }
                if(empty($patdata['alipay']['partner'])){
                    $this->error('合作者身份不能为空');
                }
                if(empty($patdata['alipay']['secret'])){
                    $this->error('支付宝校验密钥不能为空');
                }
            }
            if($patdata['pay']['weixin'] == 1){
                if(empty($patdata['wechat']['appid'])){
                    $this->error('微信appid不能为空');
                }
                if(empty($patdata['wechat']['secret'])){
                    $this->error('微信secret不能为空');
                }
                if(empty($patdata['wechat']['key'])){
                    $this->error('商户密钥不能为空');
                }
                if(empty($patdata['wechat']['account_name'])){
                    $this->error('微信账户名称不能为空');
                }
                if(empty($patdata['wechat']['mchid'])){
                    $this->error('微信支付商户号不能为空');
                }
                if(empty($patdata['wechat']['apikey'])){
                    $this->error('商户支付密钥不能为空');
                }
            }
            $set['pay']['weixin'] = $patdata['pay']['weixin'];
            $set['pay']['alipay'] = $patdata['pay']['alipay'];
            $update['sets']    = serialize($set);
            unset($patdata['pay']);
            $update['payment']  = serialize($patdata);
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($update);
            if($res !== false ){
                $this->success('编辑成功', url('index/pay_content'));
            }
            $this->error('编辑失败');
        }
        $this->assign('set', $set);
        $this->assign('payment', $payment);
        $this->assign('meta_title', '支付参数');
        return $this->fetch();
    }
    /**
     * 支付交易设置
     */
    public function pay_py(){
            $sysset     = Db::table('sysset')->field('*')->find();
            $set        = unserialize($sysset['sets']);
        if(Request::instance()->isPost()){
            $trade = input('post.');
            
            $set['trade']   = $trade['trade'];
            
            $sysset['sets'] = serialize($set);
            
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($sysset);
            if($res !== false ){
                $this->success('编辑成功', url('index/pay_py'));
            }
            $this->error('编辑失败');
        }
    
        $this->assign('set', $set);
        $this->assign('meta_title', '支付交易设置');
        return $this->fetch();

    }
    /**
     * 商城提醒
     */
    public function notice(){
        $sysset     = Db::table('sysset')->field('*')->find();
        $set        = unserialize($sysset['sets']);
      
        if(Request::instance()->isPost()){
            $notice          = input('post.');

            $set['notice']   = $notice['notice'];
            
            $sysset['sets'] = serialize($set);
            
            $res = Db::table('sysset')->where(['uniacid' => 3])->update($sysset);
            if($res !== false ){
                $this->success('编辑成功', url('index/notice'));
            }
            $this->error('编辑失败');
        }
        $this->assign('newtype', $set['notice']['newtype']);
        $this->assign('set', $set);
        $this->assign('meta_title', '支付交易设置');
        return $this->fetch();
    }




    public function upload_cert($file_name){
        $dephp_2 = $file_name . '_1.pem';
        $dephp_4 = $_FILES[$file_name]['name'];
        $dephp_5 = $_FILES[$file_name]['tmp_name'];
        if (!empty($dephp_4) && !empty($dephp_5)){
            $dephp_6 = strtolower(substr($dephp_4, strrpos($dephp_4, '.')));
            if ($dephp_6 != '.pem'){
                $dephp_7 = "";
                if ($file_name == 'weixin_cert_file'){
                    $dephp_7 = 'CERT文件格式错误';
                }else if ($file_name == 'weixin_key_file'){
                    $dephp_7 = 'KEY文件格式错误';
                }else if ($file_name == 'weixin_root_file'){
                    $dephp_7 = 'ROOT文件格式错误';
                }
                $this->error($dephp_7);
            }
            return file_get_contents($dephp_5);
        }
        return "";
    }





}

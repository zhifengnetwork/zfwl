<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class User extends ApiBase
{

    /*
     *  注册接口
     */
    public function register(){
        $mobile = input('mobile');
        $email  = input('email');
        $password    = input('password');
        $code   = input('code');
        $uid    = input('uid',0);

        $member = Db::table('member')->where('mobile',$mobile)->value('id');
		
		if ( $member ) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'此手机号已注册，请直接登录！']);
        }

        if($uid){
            $uid = Db::table('member')->where('mobile',$mobile)->value('id');
            if(!$uid){
                $this->ajaxReturn(['status' => -2 , 'msg'=>'邀请人账号不存在！']);
            }
        }

        $res = action('PhoneAuth/phoneAuth',[$mobile,$code]);
        if( $res === '-1' ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
		}else if( !$res ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
		}

        if( strlen($password) < 6 ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'密码长度必须大于或6位！','data'=>'']);
        }
        
        
        $id = Db::table('member')->insertGetId(['mobile'=>$mobile,'uid'=>$uid,'createtime'=>time()]);
        if(!$id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'注册失败，请重试！','data'=>'']);
        }

        $salt = create_salt();
        $password = md5( $salt . $password);

        Db::table('mc_members')->insert(['uid'=>$id,'mobile'=>$mobile,'createtime'=>time(),'salt'=>$salt,'password'=>$password]);

        $data['token'] = $this->create_token($id);
        $data['mobile'] = $mobile;
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'注册成功！','data'=>$data]);
    }

    /*
     *  登录接口
     */
    public function login(){
        $mobile   = input('mobile');
        $password = input('password');
        $code     = input('code');

        $res = action('PhoneAuth/phoneAuth',[$mobile,$code]);
        if( $res === '-1' ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
		}else if( !$res ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
		}

        $data = Db::table("mc_members")->where('mobile',$mobile)
            ->field('uid,password,salt')
            ->find();

        if(!$data){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'手机不存在或错误！']);
        }

        $password = md5( $data['salt'] . $password);
        
        if ($password != $data['password']) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'登录密码错误！']);
        }

        unset($data['password'],$data['salt']);
        //重写
        $data['token'] = $this->create_token($data['uid']);
        $this->ajaxReturn(['status' => 1 , 'msg'=>'登录成功！','data'=>$data]);
    }











    public function userinfo(){
        //解密token
        $user_id = $this->uid;
        if(!empty($user_id)){
            $data = Db::name("users")->where('user_id',$user_id)->field('user_id,nickname,user_money,head_pic,agent_user,first_leader,realname,mobile,is_distribut,is_agent')->find();
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $this->ajaxRe·turn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);

    }
    
    public function reset_pwd(){//重置密码
        $user_id = $this->uid;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $password1 = input('password');
        $password = md5('TPSHOP'.$password1);
        $find = Db::name('users')->where('user_id',$user_id)->field('password')->find();
        if ($password == $find['password']){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'新密码和旧密码不能相同']);
        }else{
            $data = array('password'=>$password);
            $update = Db::name('users')->where('user_id',$user_id)->data($data)->update();
            if($update){
                $this->ajaxReturn(['status' => 0 , 'msg'=>'修改成功']);
            }else{
                $this->ajaxReturn(['status' => -1 , 'msg'=>'修改失败']);
            }
        }
    }

    /*
    注册接口
     */
    // public function reg(){
    //     if (IS_POST) {
    //         $mobile = I('useriphone');
    //         $password = I('password');
    //         $user = Db::name('user')->where('mobile',$mobile)->find();
    //         if($user){
    //             $this->ajaxReturn(['status' => -1 , 'msg'=>'手机号码已存在','data'=>'']);
    //         }else{

    //         }
    //         $this->ajaxReturn($data);
    //     }
    // }
    // 
    // 
    
    /**
     * 头像上传
     */
      public function update_head_pic(){

            $user_id = $this->uid;
            if($user_id != ""){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('image');
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->validate(['size'=>204800,'ext'=>'jpg,png,gif']);
                $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    $data = SITE_URL.'/public/upload/'.$info->getSaveName(); //输出路径
                    // ROOT_PATH . DS.
                    
                }else{
                    $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
                }

            }
            $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 地址管理列表
     * +---------------------------------
    */
    public function address_list(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $data =  db('user_address')->where('user_id', $user_id)->select();
        $region_list = db('region')->cache(true)->getField('id,name');
        foreach ($data as $k => $v) {
            $v['province']=$region_list[$v['province']];
            $v['city']=$region_list[$v['city']];
            $v['district'] = $region_list[$v['district']];
            $v['twon']=$region_list[$v['twon']];
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 添加地址
     * +---------------------------------
    */
    public function add_address()
    {
        $user_id = $this->get_user_id();
        if (IS_POST) {
            $post_data = input('post.');
            $logic = new UsersLogic();
            $data = $logic->add_address($user_id, 0, $post_data);
      
            if ($data['status'] != 1){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'添加失败','data'=>$data]);
            } else {
                // $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->se   lect();
                $post_data['address_id'] = $data['result'];
                $this->ajaxReturn(['status' => 0 , 'msg'=>'添加成功','data'=>$post_data]);
            }
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
       
    }

    /**
     * +---------------------------------
     * 删除地址
     * +---------------------------------
    */
    public function del_address()
    {
        $user_id = $this->get_user_id();
        $id = I('get.id/d');
        $address = M('user_address')->where("address_id", $id)->find();
        if(!$address){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        $row = M('user_address')->where(array('user_id' => $user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $user_id)->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if (!$row)
            $this->ajaxReturn(['status' => 0 , 'msg'=>'删除地址成功','data'=>$row]);
        else
            $this->ajaxReturn(['status' => -1 , 'msg'=>'删除失败','data'=>'']);
    }
    
    /**
     * +---------------------------------
     * 地址编辑
     * +---------------------------------
    */
    public function edit_address()
    {
        $user_id = $this->get_user_id();
        $id = I('id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $user_id))->find();
        if(!$address){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        if (IS_POST) {
            $post_data = input('post.');
            // $source = $post_data['source'];
            $logic = new UsersLogic();
            $data = $logic->add_address($user_id, $id, $post_data);
            if ($data['status'] != 1){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'修改地址失败','data'=>$data]);
            } else {
                $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $user_id))->find();
                //获取省份
                $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
                $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
                $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
                $data = [
                    'address' => $address,
                    'province' => $p,
                    'city' => $c,
                    'district' => $d
                ];
                $this->ajaxReturn(['status' => 0 , 'msg'=>'修改地址成功','data'=>$data]);
            }
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
    }

}

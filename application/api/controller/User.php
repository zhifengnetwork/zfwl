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
        $mobile      = input('mobile');
        $email       = input('email');
        $password    = input('password');
        $code        = input('code');
        $uid         = input('uid',0);

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
        
        $agenttime = 0;
        $agentid = 0;
        if($uid){
            $agentid = $uid;
            $agenttime = time();
        }
        $salt = create_salt();
        $password = md5( $salt . $password);
        
        $id = Db::table('member')->insertGetId(['mobile'=>$mobile,'uid'=>$uid,'agentid'=>$agentid,'agenttime'=>$agenttime,'isagent'=>1,'salt'=>$salt,'password'=>$password,'createtime'=>time()]);
        if(!$id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'注册失败，请重试！','data'=>'']);
        }
        // Db::table('mc_members')->insert(['uid'=>$id,'mobile'=>$mobile,'createtime'=>time(),'salt'=>$salt,'password'=>$password]);

        $data['token'] = $this->create_token($id);
        $data['mobile'] = $mobile;
        $data['id'] = $id;
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'注册成功！','data'=>$data]);
    }

    /*
     *  登录接口
     */
    public function login(){
        $mobile   = input('mobile');
        $password = input('password');
        // $code     = input('code');
        
        // $res = action('PhoneAuth/phoneAuth',[$mobile,$code]);
        // if( $res === '-1' ){
        //     $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
        // }else if( !$res ){
        //     $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
        // }

        $data = Db::table("member")->where('mobile',$mobile)
            ->field('id,password,mobile,salt')
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
        $data['token']    = $this->create_token($data['id']);
    
        $this->ajaxReturn(['status' => 1 , 'msg'=>'登录成功！','data'=>$data]);
    }


    /*
     *  找回密码接口
     */
    public function zhaohuipwd(){
        $mobile    = input('mobile');
        $password1 = input('password1');
        $password2 = input('password2');
        $code      = input('code');
        
        $res = action('PhoneAuth/phoneAuth',[$mobile,$code]);
        if( $res === '-1' ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
        }else if( !$res ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
        }

        $data = Db::table("member")->where('mobile',$mobile)
            ->field('id,password,mobile,salt')
            ->find();

        if(!$data){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'手机不存在或错误！']);
        }

        if($password1 != $password2){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'确认密码不相同！！']);
        }

        // if( strlen($password2) < 6 ){
        //     $this->ajaxReturn(['status' => -2 , 'msg'=>'密码长度必须大于或6位！','data'=>'']);
        // }
        $salt     = create_salt();
        $password = md5($salt . $password2);

        $update['salt']     = $salt;
        $update['password'] = $password;

        $res =  Db::name('member')->where(['mobile' => $mobile])->update($update);


 
        if ($res == false) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'修改密码失败']);
        }

        $member['token'] = $this->create_token($data['id']);
        $member['mobile'] = $mobile;
        $member['id'] = $data['id'];
    
        $this->ajaxReturn(['status' => 1 , 'msg'=>'修改密码成功！','data'=>$member]);
    }






    /**
     * 用户信息
     */
    public function userinfo(){
        $user_id = $this->get_user_id();
        if(!empty($user_id)){
            $data = Db::name("member")->alias('m')
                    ->join('user u','m.id=u.uid','LEFT')
                    ->field('m.id,m.mobile,m.realname,m.pwd,m.avatar,m.gender,m.birthyear,m.birthmonth,m.birthday,m.mailbox,u.wx_nickname,wx_headimgurl')
                    ->where(['m.id' => $user_id])
                    ->find();
            if(empty($data)){
                $this->ajaxReturn(['status' => -2 , 'msg'=>'会员不存在！','data'=>'']);
            }    
            $data['is_pwd'] = !empty($data['pwd'])?1:0;

            $res = Db::table("user_address")->where(['user_id'=>$data['id']])
                    ->field('*')
                    ->find();
            $data['is_address'] = $res?1:0;
            unset($data['pwd'],$data['id']);
            if(empty($data['mobile'])){
                $this->ajaxReturn(['status' => -2 , 'msg'=>'未绑定手机！','data'=>$data]);
            }
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$data]);
    }
    
    public function reset_pwd(){//重置密码
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $password1   = input('password1');
        $password2   = input('password2');
        if($password1 != $password2){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'确认密码错误','data'=>'']);
        }
        $member = Db::name('member')->where(['id' => $user_id])->field('id,password,pwd,mobile,salt')->find();
        $type     = input('type');//1登录密码 2支付密码
        $code     = input('code');
        $mobile   = $member['mobile'];
        $res      = action('PhoneAuth/phoneAuth',[$mobile,$code]);
        if( $res === '-1' ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
        }else if( !$res ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
        }
        if($type == 1 ){
            $stri = 'password';
        }else{
            $stri = 'pwd';
        }
            $password = md5($member['salt'] . $password2);
        if ($password == $member[$stri]){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'新密码和旧密码不能相同']);
        }else{
            $data = array($stri=>$password);
            $update = Db::name('member')->where('id',$user_id)->data($data)->update();
            if($update){
                $this->ajaxReturn(['status' => 1 , 'msg'=>'修改成功']);
            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'修改失败']);
            }
        }
        
    }
    /***
     * 邮箱编辑
     */
    public function reset_mailbox(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        $mailbox   = input('mailbox');
        $data = [
            'mailbox' => $mailbox
        ];
        $update = Db::name('member')->where(['id' => $user_id])->data($data)->update();
        if($update){
            $this->ajaxReturn(['status' => 1 , 'msg'=>'修改成功']);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'修改失败']);
        }


    }

    /**
     * 头像上传
     */
      public function update_head_pic(){

            $user_id  = $this->get_user_id();
            $head_img = input('head_img');
            if(empty($head_img)){
                $this->ajaxReturn(['code'=>0,'msg'=>'上传图片不能为空','data'=>'']);
            }
            $saveName       = request()->time().rand(0,99999) . '.png';
            $base64_string  = explode(',', $head_img);
            $imgs           = base64_decode($base64_string[1]);
            //生成文件夹
            $names = "head";
            $name  = "head/" .date('Ymd',time());
            if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
                mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
            }
            //保存图片到本地
            $r   = file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$imgs);
            if(!$r){
                $this->ajaxReturn(['status'=>-2,'msg'=>'上传出错','data' =>'']);
            }
            Db::name('member')->where(['id' => $user_id])->update(['avatar' => SITE_URL.'/upload/images/'.$name.$saveName]);

            $this->ajaxReturn(['status'=>1,'msg'=>'修改成功','data'=>SITE_URL.'/upload/images/'.$name.$saveName]);
           
    }

    /**
     * +---------------------------------
     * 地址组件原数据
     * +---------------------------------
    */
    public function get_address(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在','data'=>'']);
        }
        //第一种方法
        //$province_list  =  Db::name('region')->field('*')->where(['area_type' => 1])->column('area_id,area_name');
        // $city_list      =  Db::name('region')->field('*')->where(['area_type' => 2])->column('area_id,area_name');
        // $county_list    =  Db::name('region')->field('*')->where(['area_type' => 3])->column('area_id,area_name');
        // $data = [
        //     'province_list' => $province_list,
        //     'city_list'     => $city_list,
        //     'county_list'   => $county_list,
        // ];
        //第二种方法
        $list  = Db::name('region')->field('*')->select();
        foreach($list as $v){
           if($v['area_type'] == 1){
              $address_list['province_list'][$v['code'] * 10000]=  $v['area_name'];
           }
           if($v['area_type'] == 2){
              $address_list['city_list'][$v['code'] *100]=  $v['area_name'];
           }
           if($v['area_type'] == 3){
              $address_list['county_list'][$v['code']]=  $v['area_name'];
           }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'获取地址成功','data'=>$address_list]);
    }




    /**
     * +---------------------------------
     * 地址管理列表
     * +---------------------------------
    */
    public function address_list(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2, 'msg'=>'用户不存在','data'=>'']);
        }
        $data        =  Db::name('user_address')->where('user_id', $user_id)->select();
        $region_list =  Db::name('region')->field('*')->column('area_id,area_name');
        foreach ($data as &$v) {
            $v['province'] = $region_list[$v['province']];
            $v['city']     = $region_list[$v['city']];
            $district      = Db::name('region')->where(['area_id' => $v['district']])->value('code');
            $v['code']     = $district;
            $v['district'] = $region_list[$v['district']];
        
            if($v['twon'] == 0){
                $v['twon']     = '';
            }else{
                $v['twon'] = $region_list[$v['twon']];
            }
            
        }
        unset($v);
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 添加地址
     * +---------------------------------
    */
    public function add_address()
    {
            $user_id   = $this->get_user_id();
            $post_data = input('post.');
            $addressM  = Model('UserAddr');
            $return    = $addressM->add_address($user_id, 0, $post_data);
            $this->ajaxReturn($return);
    }

    

    /**
     * +---------------------------------
     * 地址编辑
     * +---------------------------------
    */
    public function edit_address()
    {
        $user_id = $this->get_user_id();
        $id      = input('address_id');
        $address = Db::name('user_address')->where(array('address_id' => $id, 'user_id' => $user_id))->find();
        if(!$address){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        $post_data = input('post.');
        $addressM  = Model('UserAddr');
        $return    = $addressM->add_address($user_id, $id, $post_data);
        $this->ajaxReturn($return);
    }



    /**
     * +---------------------------------
     * 删除地址
     * +---------------------------------
    */
    public function del_address()
    {
        $user_id = $this->get_user_id();
        $id      = input('address_id/d',86);
        $address = Db::name('user_address')->where(["address_id" => $id])->find();
        if(!$address){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'地址id不存在！','data'=>'']);
        }
        $row =  Db::name('user_address')->where(array('user_id' => $user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = Db::name('user_address')->where(["user_id" => $user_id])->find();
            $address2 && Db::name('user_address')->where(["address_id" => $address2['address_id']])->update(array('is_default' => 1));
        }
        if ($row !== false)
            $this->ajaxReturn(['status' => 1 , 'msg'=>'删除地址成功','data'=>$row]);
        else
            $this->ajaxReturn(['status' => -2 , 'msg'=>'删除失败','data'=>'']);
    }


   /**
     * +---------------------------------
     * 验证支付密码
     * +---------------------------------
    */
    public function check_pwd()
    {
        $user_id    = $this->get_user_id();
        $pwd        = input('pwd/d');
        $member     = Db::name('member')->where(["id" => $user_id])->find();
        if(!$member){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'用户不存在！','data'=>'']);
        }
        $password = md5($member['salt'] . $pwd);
        if($member['pwd'] !== $password){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'支付密码错误！','data'=>'']);
        }
        $this->ajaxReturn(['status' => 1 , 'msg'=>'密码正确！','data'=>'']);
    }

    /**
     * +---------------------------------
     * 修改生日和昵称
     * +---------------------------------
    */

    public function set_reabir()
    {
        $user_id    = $this->get_user_id();
        $birthyear  = input('birthyear');
        $birthmonth = input('birthmonth');
        $birthday   = input('birthday');
        $realname   = input('realname');
        $type       = input('type',1);
        if($type == 1){
            if(empty($realname)){
                $this->ajaxReturn(['code'=>0,'msg'=>'昵称不能为空','data'=>'']);
            }
            $update['realname'] = $realname;
        }else{
            $update['birthyear']  = $birthyear;
            $update['birthmonth'] = $birthmonth;
            $update['birthday']   = $birthday;
        }
        $member     = Db::name('member')->where(["id" => $user_id])->update($update);
        if($member !== false){
            $this->ajaxReturn(['status' => 1 , 'msg'=>'修改成功','data'=>'']);
        }
        $this->ajaxReturn(['status' => -2 , 'msg'=>'修改失败','data'=>'']);
    }


    /***
     * 手机号换绑
     */

    public function change_mobile(){

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -2, 'msg'=>'用户不存在','data'=>'']);
        } 
        $new_mobile = input('mobile');
        $code       = input('code');

        $member = Db::table('member')->where(['id' => $user_id])->find();

        if($member['mobile'] == $new_mobile){
             $this->ajaxReturn(['status' => -2 , 'msg'=>'手机号不能相同！','data'=>'']);
        }
       
        $res        = action('PhoneAuth/phoneAuth',[$new_mobile,$code]);
        if( $res === '-1'){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码已过期！','data'=>'']);
        }else if( !$res ){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'验证码错误！','data'=>'']);
        }
      
        $res = Db::table('member')->where(['id' => $user_id])->update(['mobile' => $new_mobile]);

        if($res !== false){
            $this->ajaxReturn(['status' => 1 , 'msg'=>'换绑成功','data'=>'']);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'换绑失败','data'=>'']);
        }

    }


}

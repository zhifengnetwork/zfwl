<?php
/**
 * 继承
 */
namespace app\api\controller;
use app\common\util\jwt\JWT;
use think\Db;
use think\Controller;
use app\common\model\Config;
use think\Request;
use think\Session;


class ApiBase extends Controller
{
    protected $uid;
    protected $user_name;

    public function _initialize () {
//        config((new Config)->getConfig());
//        if (session('admin_user_auth')) {
//            $this->uid = session('admin_user_auth.uid');
//            $this->user_name = session('admin_user_auth.user_name');
//        } else {
//            exit(json_encode(['code'=>0,'msg'=>'您未登录，请登录！']));
//        }
        $controller = Request::instance()->controller();
        $access = $this->freeLoginController();
        if (!isset($access[$controller])){
            //一定需要登录
            $this->uid = $this->get_user_id();
        }else{
            //可以获取user_id就获取，没有就当作游客访问
            $headers = $this->em_getallheaders();
            if (isset($headers['Token'])){
                $res = $this->decode_token($headers['Token']);
                if ($res && isset($res['iat']) && isset($res['exp'])
                    && isset($res['user_id']) && $res['iat']<=$res['exp']){
                    $this->uid = $res['user_id'];
                }
            }
        }
    }

    /*
     *  开放有可能不需登录controller
     */
    private function freeLoginController () {
        $controller = [
            'Shop' => 'shop',
        ];
        return $controller;
    }

    public function ajaxReturn($data){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }


    /**
     * 获取user_id
     */
    public function get_user_id(){
        $headers = [];
        $headers = $this->em_getallheaders();


        if(!isset($headers['Token'])){
            exit(json_encode(['status' => -1 , 'msg'=>'token不存在','data'=>null]));
        }
        $token = $headers['Token'];
        $res = $this->decode_token($token);

        if(!$res){
            exit(json_encode(['status' => -1 , 'msg'=>'token已过期','data'=>null]));

        }

        if(!isset($res['iat']) || !isset($res['exp']) || !isset($res['user_id']) ){
            exit(json_encode(['status' => -1 , 'msg'=>'token已过期：'.$res,'data'=>null]));
        }

        if($res['iat']>$res['exp']){
            exit(json_encode(['status' => -1 , 'msg'=>'token已过期','data'=>null]));
        }
        
        
       return $res['user_id'];
       
    }

    /**
     *
     *接收头信息
     **/
    public function em_getallheaders()
    {
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * 解密token
     */
    public function decode_token($token){
        $key = 'zhelishimiyao';
        $payload = json_decode(json_encode(JWT::decode($token, $key, ['HS256'])),true);
        return $payload;
    }

    /**
     * 空
     */
    public function _empty(){
        $this->ajaxReturn(['status' => -1 , 'msg'=>'接口不存在','data'=>null]);
    }
}

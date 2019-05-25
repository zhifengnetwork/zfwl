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
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:*");
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header('Content-Type:application/json; charset=utf-8');
        config((new Config)->getConfig());
        if (session('admin_user_auth')) {
            $this->uid = session('admin_user_auth.uid');
            $this->user_name = session('admin_user_auth.user_name');
        } else {
            $action = strtolower(Request::instance()->controller() . '/' . Request::instance()->action());
            $action_array[] = strtolower('goods/categoryList');
            $action_array[] = strtolower('goods/category');
            $action_array[] = strtolower('goods/goodsDetail');
            $action_array[] = strtolower('phoneauth/verifycode');
            $action_array[] = strtolower('user/register');
            $action_array[] = strtolower('user/login');
            $action_array[] = strtolower('pay/alipay_notify');
            if (in_array($action, $action_array)) {
                return;
            }
            $user_id = $this->decode_token(input('token'));
            if(empty($user_id)) exit(json_encode(['code'=>0,'msg'=>'您未登录，请登录！']));

        }
    }

    private  static $redis = null;
    /*获取redis对象*/
    protected function getRedis(){
        if(!self::$redis instanceof \Redis){
            // pred(Config('redis_options'));
            self::$redis = new \Redis();
            self::$redis->connect(Config('REDIS_HOST'),Config('REDIS_PORT'));
            self::$redis->auth(Config('REDIS_AUTH'));
            self::$redis->select(0);
        }
        return self::$redis;
    }

    /*
     *  开放有可能不需登录controller
     */
    private function freeLoginController () {
        $controller = [
            'Shop' => 'shop',
//            'User' => 'user',
        ];
        return $controller;
    }

    public function ajaxReturn($data){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 生成token
     */
    public function create_token($user_id){
        $time = time();
        $payload = array(
            "iss"=> "DC",
            "iat"=> $time ,  
            "exp"=> $time + 36000 , 
            "user_id"=> $user_id
        );
        $key = 'zhelishimiyao';
        $token = JWT::encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null);
        return $token;
    }

    /**
     * 解密token
     */
    public function decode_token($token){
        $key     = 'zhelishimiyao';
        $payload = json_decode(json_encode(JWT::decode($token, $key, ['HS256'])),true);
        return $payload;
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
     * 获取user_id
     */
    public function get_user_id(){
        $headers = $this->em_getallheaders();

        $token   = input('token'); 
        // if(){
             
        // }
        // $token = $headers['Token'];
        if(!$token){
              $this->ajaxReturn(['status' => -1 , 'msg'=>'token不存在','data'=>null]);
        }

        $res = $this->decode_token($token);

        if(!$res){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'token已过期','data'=>null]);

        }

        if(!isset($res['iat']) || !isset($res['exp']) || !isset($res['user_id']) ){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'token已过期：'.$res,'data'=>null]);
        }

        if($res['iat']>$res['exp']){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'token已过期','data'=>null]);
        }
       return $res['user_id'];
       
    }
    /**
     * 空
     */
    public function _empty(){
        $this->ajaxReturn(['status' => -1 , 'msg'=>'接口不存在','data'=>null]);
    }
}

<?php
/**
 * 继承
 */
namespace app\api\controller;
use app\common\util\jwt\JWT;
use think\Db;
use think\Controller;


class ApiBase extends Controller
{

    public function ajaxReturn($data){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
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
        $key = 'zhelishimiyao';
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

        $token = $headers['Token'];
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

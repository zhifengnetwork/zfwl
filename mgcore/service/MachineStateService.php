<?php
/**
 * 按摩椅控制统一封装
 *
 *  @author dwer.cn
 *  @date 2017-08-22
 */

namespace mgcore\service;

use \mgcore\model\Machine;
use \mgcore\service\ControlService;
use \think\Cache;
use \think\Config;

class MachineStateService
{

    /**
     * 主动同步按摩椅的状态
     * @author dwer
     * @date   2017-11-10
     *
     * @return
     */
    public static function syncStatus($machineId)
    {
        $machineId = intval($machineId);
        if (!$machineId) {
            return false;
        }

        //获取设备信息
        $machineModel = new Machine();
        $machineInfo  = $machineModel->getInfo($machineId);

        //查询现在的状态
        $controlService = new ControlService();
        $runInfo        = $controlService->query($machineInfo);

        if (!$runInfo) {
            return false;
        }

        $versionId = $runInfo['version_id'];
        $runData   = $runInfo['data'];

        if ($versionId == 0) {
            $isOnline = isset($runData['is_online']) && $runData['is_online'] ? true : false;
        } else {
            $isOnline = isset($runData['online']) && $runData['online'] ? true : false;
        }

        //更新设备的状态
        $res = $machineModel->updateOnline($machineId, $isOnline, $machineInfo['mac']);

        //记录日志
        pft_log('machine_sync_status', json_encode([$machineId, $runInfo]));

        //返回
        return $res ? true : false;
    }

    public static function start_machine($mac, $did)
    {
        //产品EMC
        $product_key    = Config::get('gw_config_v1.product_key');
        $product_secret = Config::get('gw_config_v1.product_secret');
        //应用-WEIXIN
        $appid = Config::get('gw_config_v1.appid');

        $headers = <<<EOT
Accept:*/*
Accept-Encoding:gzip, deflate
Accept-Language:zh-CN,zh;q=0.8
Cache-Control:no-cache
Content-Type: application/json
Origin:http://m2m.gizwits.com:8080
Pragma:no-cache
Proxy-Connection:keep-alive
Referer:http://m2m.gizwits.com:8080/app
User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36
X-Gizwits-Application-Id:{$appid}
EOT;
        $headers = explode("\r\n", $headers);

        // 用机智云的HTTP API注册+登录，获取{uid,token,expire_at}
        // $url = 'http://api.gizwits.com/app/users';
        // $data = '{"username":"jjc","password":"19841002"}';
        // $ret = request_anmoyi($url, $headers, $data);//eg.{"token": "9983466cf90e4d0fb8951cc2a6632d59", "uid": "2f2f2874594249e391535def85fb7fda", "expire_at": 1463285933}
        // var_dump($ret);die;

        // 用机智云的HTTP API登录
        $options = [
            'type'   => 'File',
            'path'   => CACHE_PATH,
            'prefix' => '',
            'expire' => 0,
        ];
        Cache::connect($options);
        $uid   = Cache::get('gw_httpapi_uid');
        $token = Cache::get('gw_httpapi_token');

        if (!$uid || !$token) {
            $url  = 'http://api.gizwits.com/app/login';
            $data = '{"username":"jjc","password":"19841002"}';
            // echo "http login: ".$data."\n";
            $ret = request_anmoyi($url, $headers, $data); //eg.{"token": "9983466cf90e4d0fb8951cc2a6632d59", "uid": "2f2f2874594249e391535def85fb7fda", "expire_at": 1463285933}
            if (!$ret || !empty($ret['error_code'])) {
                echo "http login: fail\n";
                exit;
            }
            $uid   = $ret['uid'];
            $token = $ret['token'];
            Cache::set('gw_httpapi_uid', $uid, $ret['expire_at'] - time() - 600);
            Cache::set('gw_httpapi_token', $token, $ret['expire_at'] - time() - 600);
        }

        // 用机智云的HTTP API获取绑定设备
        // $headers[] = 'X-Gizwits-Application-Id: '.$appid;
        // $headers[] = 'X-Gizwits-User-token: '.$token;
        // $url = 'http://api.gizwits.com/app/bindings?show_disabled=1&limit=20&skip=0';
        // echo "http show bindings: \n";
        // $ret = request_anmoyi($url, $headers);
        // $devices = $ret['devices'];
        // $did = $devices[0]['did'];

        // 用机智云的HTTP API绑定设备，获取
        $timestamp  = time();
        $sign       = md5($product_secret . $timestamp);
        $headers[]  = 'X-Gizwits-Application-Id: ' . $appid;
        $headers[]  = 'X-Gizwits-User-token: ' . $token;
        $headers[]  = 'X-Gizwits-Timestamp: ' . $timestamp;
        $headers[]  = 'X-Gizwits-Signature: ' . $sign;
        $dev_mac    = $mac;
        $dev_remark = '我其实是备注';
        $dev_alias  = '我叫别名';
        $url        = 'http://api.gizwits.com/app/bind_mac';
        $data       = '{"product_key":"' . $product_key . '","mac":"' . $dev_mac . '","remark":"' . $dev_remark . '","dev_alias":"' . $dev_alias . '"}';
        // echo "http bind mac: ".$data."\n";
        $ret = request_anmoyi($url, $headers, $data); //eg.{"ws_port": 8080, "port_s": 8883, "is_disabled": false, "mac": "18fe34d3c8e0","dev_alias": "\u6211\u53eb\u522b\u540d", "is_online": false, "wss_port": 8880, "remark":"\u6211\u5176\u5b9e\u662f\u5907\u6ce8", "did": "TpioZ8kkJvC6q6RQUMvjmb", "host":"sandbox.gizwits.com", "product_key": "7bb221181ac5434584631d3dc3f64c38", "port": 1883, "passcode": "GIABDKCRYE", "type": "center_control"}
        // var_dump($ret);

        $headers[] = 'X-Gizwits-Application-Id: ' . $appid;
        $headers[] = 'X-Gizwits-User-token: ' . $token;
        $url       = 'http://api.gizwits.com/app/control/' . $did;
        // $data = '{"attrs":{"Motor_Speed":2}}';
        // $data = '{"attrs":{"Emc_Mode":1}}';
        $data = '{"attrs":{"Emc_Mode":"5分钟"}}';
        // $led_r = Cache::get($mac.'_led_r');
        // if ($led_r == null) {
        //     $led_r = 100;
        // } else {
        //     $led_r += 100;
        // }
        // if ($led_r > 255) {
        //     $led_r = 0;
        // }
        // Cache::set($mac.'_led_r', $led_r);
        // $data = '{"attrs":{"LED_R":'.$led_r.'}}';

        // echo "http control device: {$did}\n";
        $ret = request_anmoyi($url, $headers, $data);

        //file_put_contents('control_result.txt', var_export($ret, true)."\r\n\r\n", FILE_APPEND);
        pft_log('control_result', json_encode([$ret]));

        return ($ret && empty($ret['error_code']));
    }

    public static function stop_machine()
    {
        # code...
    }

    // 控制设备
    public static function control_machine($mac, $did, $val)
    {
        $api_type = Config::get('gw_config.api_type');
        if (!$api_type) {
            $api_type == 'ent';
        }

        if ($api_type == 'open') {
            return self::openapi_control_machine($mac, $did, $val);
        } else {
            return self::entapi_control_machine($mac, $did, $val);
        }
    }

    // gw V2 + emc V3 + entapi
    public static function entapi_control_machine($mac, $did, $val)
    {
        // 企业信息
        $enterprise_id     = Config::get('gw_config.enterprise_id');
        $enterprise_secret = Config::get('gw_config.enterprise_secret');
        // 产品信息
        $product_key    = Config::get('gw_config.product_key');
        $product_secret = Config::get('gw_config.product_secret');

        // 获取token
        $option = [
            'type'   => 'File',
            'path'   => CACHE_PATH,
            'prefix' => '',
            'expire' => 0,
        ];
        Cache::connect($option);

        $cache_key = 'gw_entapi_token_' . $enterprise_id . '_' . $product_key;
        $token     = Cache::get($cache_key);
        if (!$token) {
            $login_url = 'http://enterpriseapi.gizwits.com/v1/products/<product_key>/access_token';
            $url       = str_replace('<product_key>', $product_key, $login_url);
            $data      = [
                'enterprise_id'     => $enterprise_id,
                'enterprise_secret' => $enterprise_secret,
                'product_secret'    => $product_secret,
            ];
            $payload = json_encode($data);
            $ret     = request_anmoyi($url, null, $payload);
            if (!$ret || !empty($ret['error_code'])) {
                return 1006;
            }
            $token = $ret['token'];
            Cache::set($cache_key, $token, $ret['expire_at'] - time() - 600);
        }

        // 发送控制指令
        $control_url = 'http://enterpriseapi.gizwits.com/v1/products/<product_key>/devices/<did>/control';
        $url         = str_replace(['<product_key>', '<did>'], [$product_key, $did], $control_url);
        $data        = [
            'attrs' => [
                'emc' => $val, //eg.'FF0603AA000000AD',
            ],
        ];
        $payload = json_encode($data);

        $headers[] = 'Authorization: token ' . $token;
        $ret       = request_anmoyi($url, $headers, $payload);

        $date = date('Y-m-d H:i:s');
        file_put_contents('control_result.txt', "[{$date}] entapi control: {$url}\r\n{$payload}\r\nret:".var_export($ret, true)."\r\n\r\n", FILE_APPEND);
        pft_log('entapi_control_machine', json_encode([$payload, $ret]));

        return $ret ? 1006 : 0;
    }

    // gw V2 + emc V3 + openapi
    public static function openapi_control_machine($mac, $did, $val)
    {
        //产品EMC
        $product_key    = Config::get('gw_config.product_key');
        $product_secret = Config::get('gw_config.product_secret');
        //应用-WEIXIN
        $appid = Config::get('gw_config.appid');

        $headers = <<<EOT
Accept:*/*
Accept-Encoding:gzip, deflate
Accept-Language:zh-CN,zh;q=0.8
Cache-Control:no-cache
Content-Type: application/json
Origin:http://m2m.gizwits.com:8080
Pragma:no-cache
Proxy-Connection:keep-alive
Referer:http://m2m.gizwits.com:8080/app
User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36
X-Gizwits-Application-Id:{$appid}
EOT;
        $headers = explode("\r\n", $headers);

        // 用机智云的HTTP API注册+登录，获取{uid,token,expire_at}
        // $url = 'http://api.gizwits.com/app/users';
        // $data = '{"username":"jjc","password":"19841002"}';
        // $ret = request_anmoyi($url, $headers, $data);//eg.{"token": "9983466cf90e4d0fb8951cc2a6632d59", "uid": "2f2f2874594249e391535def85fb7fda", "expire_at": 1463285933}
        // var_dump($ret);die;

        // 用机智云的HTTP API登录
        $options = [
            'type'   => 'File',
            'path'   => CACHE_PATH,
            'prefix' => '',
            'expire' => 0,
        ];

        Cache::connect($options);
        $uid   = Cache::get('gw_openapi_uid_' . $appid);
        $token = Cache::get('gw_openapi_token_' . $appid);
        if (!$uid || !$token) {
            $url  = 'http://api.gizwits.com/app/login';
            $data = '{"username":"jjc","password":"19841002"}';
            // echo "http login: ".$data."\n";
            $ret = request_anmoyi($url, $headers, $data); //eg.{"token": "9983466cf90e4d0fb8951cc2a6632d59", "uid": "2f2f2874594249e391535def85fb7fda", "expire_at": 1463285933}
            if (!$ret || !empty($ret['error_code'])) {
                return 1006;
                //echo "http login: fail\n";
                exit;
            }
            $uid   = $ret['uid'];
            $token = $ret['token'];
            Cache::set('gw_openapi_uid_' . $appid, $uid, $ret['expire_at'] - time() - 600);
            Cache::set('gw_openapi_token_' . $appid, $token, $ret['expire_at'] - time() - 600);
        }

        // 用机智云的HTTP API绑定设备
        $timestamp  = time();
        $sign       = md5($product_secret . $timestamp);
        $headers[]  = 'X-Gizwits-Application-Id: ' . $appid;
        $headers[]  = 'X-Gizwits-User-token: ' . $token;
        $headers[]  = 'X-Gizwits-Timestamp: ' . $timestamp;
        $headers[]  = 'X-Gizwits-Signature: ' . $sign;
        $dev_mac    = $mac;
        $dev_remark = '我其实是备注';
        $dev_alias  = '我叫别名';
        $url        = 'http://api.gizwits.com/app/bind_mac';
        $data       = '{"product_key":"' . $product_key . '","mac":"' . $dev_mac . '","remark":"' . $dev_remark . '","dev_alias":"' . $dev_alias . '"}';
        // echo "http bind mac: ".$data."\n";
        $ret = request_anmoyi($url, $headers, $data);

        $headers[] = 'X-Gizwits-Application-Id: ' . $appid;
        $headers[] = 'X-Gizwits-User-token: ' . $token;
        $url       = 'http://api.gizwits.com/app/control/' . $did;
        $data      = [
            'attrs' => [
                'emc' => $val, //'FF0603AA000000AD',
            ],
        ];
        $payload = json_encode($data);

        // echo "http control device: {$did}\n";
        $ret = request_anmoyi($url, $headers, $payload);

        $date = date('Y-m-d H:i:s');
        file_put_contents('control_result.txt', "[{$date}] openapi control: {$url}\r\n{$payload}\r\nret:".var_export($ret, true)."\r\n\r\n", FILE_APPEND);
        pft_log('openapi_control_machine', json_encode([$payload, $ret]));

        return $ret ? 1006 : 0;
    }

    /**
     * 获取设备列表
     * @author dwer
     * @date   2017-08-22
     *
     * @param  array &$list 设备列表
     * @return array
     */
    public static function machine_list(&$list)
    {
        if (!$list) {
            return [];
        }

        //产品EMC
        $product_key    = Config::get('gw_config.product_key');
        $product_secret = Config::get('gw_config.product_secret');
        $appid          = Config::get('gw_config.appid');

        $headers = <<<EOT
Accept:*/*
Accept-Encoding:gzip, deflate
Accept-Language:zh-CN,zh;q=0.8
Cache-Control:no-cache
Content-Type: application/json
Origin:http://m2m.gizwits.com:8080
Pragma:no-cache
Proxy-Connection:keep-alive
Referer:http://m2m.gizwits.com:8080/app
User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36
X-Gizwits-Application-Id:{$appid}
EOT;
        $headers = explode("\r\n", $headers);

        // 用机智云的HTTP API登录
        $options = [
            'type'   => 'File',
            'path'   => CACHE_PATH,
            'prefix' => '',
            'expire' => 0,
        ];
        Cache::connect($options);
        $uid   = Cache::get('gw_httpapi_uid');
        $token = Cache::get('gw_httpapi_token');

        if (!$uid || !$token) {
            $url  = 'http://api.gizwits.com/app/login';
            $data = '{"username":"jjc","password":"19841002"}';

            //eg.{"token": "9983466cf90e4d0fb8951cc2a6632d59", "uid": "2f2f2874594249e391535def85fb7fda", "expire_at": 1463285933}
            $ret = request_anmoyi($url, $headers, $data);
            if (!$ret || !empty($ret['error_code'])) {
                return false;
            }
            $uid   = $ret['uid'];
            $token = $ret['token'];
            Cache::set('gw_httpapi_uid', $uid, $ret['expire_at'] - time() - 600);
            Cache::set('gw_httpapi_token', $token, $ret['expire_at'] - time() - 600);
        }

        // 用机智云的HTTP API获取绑定设备
        $headers[] = 'X-Gizwits-Application-Id: ' . $appid;
        $headers[] = 'X-Gizwits-User-token: ' . $token;
        $url       = 'http://api.gizwits.com/app/bindings?show_disabled=1&skip=0';
        $ret       = request_anmoyi($url, $headers);
        $devices   = $ret['devices'];

        if (!$devices || !is_array($devices)) {
            return false;
        }

        foreach ($list as &$machine) {
            foreach ($devices as $gwmachine) {
                if ($machine->gw_did == $gwmachine['did']) {
                    if (!$gwmachine['is_online']) {
                        $machine->new_state = 3;
                    } elseif ($machine->state == 3) {
                        $machine->new_state = 0;
                    }
                    break;
                }
            }
        }

        return $list;
    }
}

if (!function_exists('request_anmoyi')) {
    function request_anmoyi($url, $headers = null, $data = null, $method = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        if ($method) {
            switch (strtoupper($method)) {
                case 'GET':
                    curl_setopt($ch, CURLOPT_HTTPGET, 1);
                    break;
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    break;
                default:
                    break;
            }
        } else {
            if ($data) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        if ($headers) {
            !is_array($headers) && ($headers = explode("\r\n", $headers));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header
        }
        $response = curl_exec($ch); //执行CURL

        $date = date('Y-m-d H:i:s');
        if ($response === false) {
            $err_no  = curl_errno($ch);
            $err_msg = curl_error($ch);

            //@file_put_contents('control_result.txt', "[{$date}] request_anmoyi : {$url}\r\n{$data}\r\nresponse : {$response}\r\nret:($err_no)".var_export($err_msg, true)."\r\n\r\n", FILE_APPEND);
            pft_log('request_anmoyi', json_encode(['1', $url, $data, $response, $err_no, $err_msg]));

            return [
                'error_code'    => '7001',
                'error_message' => 'shit gizwits entapi',
                'detail'        => '',
            ];
        } elseif (strpos($response, '{') === false) {
            //@file_put_contents('control_result.txt', "[{$date}] request_anmoyi : {$url}\r\n{$data}\r\nresponse : {$response}\r\n\r\n", FILE_APPEND);
            pft_log('request_anmoyi', json_encode(['2', $url, $data, $response]));

            return [
                'error_code'    => '7002',
                'error_message' => 'shit gizwits entapi',
                'detail'        => '',
            ];
        }

        // echo "http recv: ".$response."\n";
        curl_close($ch); //关闭CURL
        return json_decode($response, true);
    }
}

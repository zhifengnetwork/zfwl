<?php
/**
 * 机智云企业API
 * @author dwer
 * @date   2017-08-23
 */

namespace mgcore\library;

use \think\Cache;

class GizwitsControl
{
    private $enterprise_id;
    private $enterprise_secret;
    private $product_key;
    private $product_secret;

    private $login_url   = 'http://enterpriseapi.gizwits.com/v1/products/<product_key>/access_token';
    private $control_url = 'http://enterpriseapi.gizwits.com/v1/products/<product_key>/devices/<did>/control';
    private $query_url   = 'http://enterpriseapi.gizwits.com/v1/products/<product_key>/device_detail?mac=<mac>';

    //控制类型
    private $_controlArr = [
        'start', //启动按摩椅
        'stop', //停止
        'pause', //暂停
        'recover', //解除暂停(即恢复)
        'mode', //模式切换
        'back', //靠背调整
        'pressure', //气压调整
        'strength', //力道控制
    ];

    public function __construct($enterprise_id, $enterprise_secret, $product_key, $product_secret)
    {
        $this->enterprise_id     = $enterprise_id;
        $this->enterprise_secret = $enterprise_secret;
        $this->product_key       = $product_key;
        $this->product_secret    = $product_secret;
    }

    /**
     * 设备控制
     * @author dwer
     * @date   2017-08-23
     *
     * @param  string $did 机智云设备标识
     * @param  string $action 控制类型
     * @param  array $data 控制参数
     * @return array
     */
    public function control($did, $action, $data = [])
    {
        if (!$did || !in_array($action, $this->_controlArr)) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        //获取授权信息
        $tokenInfo = $this->_getToken();
        $code      = $tokenInfo['code'];
        if ($code == 0) {
            return $tokenInfo;
        }
        $token = $tokenInfo['token'];

        //指令解析
        $instruction = 'FF0603';
        switch ($action) {
            case 'start':
                //启动按摩椅
                $time     = isset($data['time']) ? $data['time'] : 5; //05 5分钟
                $mode     = isset($data['mode']) ? $data['mode'] : '00'; //00=AUTO1, 01=AUTO2, 02=AUTO3
                $pressure = isset($data['num']) ? $data['num'] : '00'; //01=气压档位1, 02=气压档位2, 03=气压档位3

                $time       = $time <= 0 ? 5 : $time;
                $sendAction = 'A2';
                $pressure   = $this->_formatNum($pressure);
                $mode       = $this->_formatNum($mode);
                $timeRes    = $this->_timeHandle($time);

                $instruction .= $sendAction . $timeRes['hextime'] . $mode . $pressure . $timeRes['hexsum'];
                break;
            case 'stop':
                //停止
                $sendAction = 'A5';
                $instruction .= $sendAction . '000000A8';
                break;
            case 'pause':
                //暂停
                $sendAction = 'A8';
                $instruction .= $sendAction . '000000AB';
                break;
            case 'recover':
                //解除暂停(即恢复)
                $sendAction = 'A9';
                $instruction .= $sendAction . '000000AC';
                break;
            case 'mode':
                //模式切换
                $sendAction = 'A3';

                $mode       = isset($data['mode']) ? $data['mode'] : '00'; //00=AUTO1, 01=AUTO2, 02=AUTO3
                $timeRes    = $this->_timeHandle($mode);
                $mode       = $this->_formatNum($mode);
                $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                break;
            case 'back':
                //靠背调整
                $type = isset($data['type']) ? $data['type'] : 'up'; //up=靠背升, down=靠背降, recover=复位
                $time = isset($data['time']) ? $data['time'] : 3; //上升3秒
                $time = $time <= 0 ? 3 : $time;

                if ($type == 'up') {
                    $sendAction = '02';
                    $timeRes    = $this->_timeHandle($time);
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else if ($type == 'down') {
                    $sendAction = '04';
                    $timeRes    = $this->_timeHandle($time);
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else {
                    $sendAction = '06';
                    $instruction .= $sendAction . '00000009';
                }
                break;
            case 'pressure':
                //气压控制
                $type     = isset($data['type']) ? $data['type'] : 'up'; //up=气压加强, down=气压减弱, recover=自动
                $strength = isset($data['num']) ? $data['num'] : 2; //加强2档
                $strength = $strength <= 0 ? 2 : $strength;
                $timeRes  = $this->_timeHandle($strength);

                if ($type == 'up') {
                    $sendAction = '12';
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else if ($type == 'down') {
                    $sendAction = '13';
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else {
                    $sendAction = '11';
                    $instruction .= $sendAction . '00000000';
                }
                break;
            case 'strength':
                //力道控制
                $type = isset($data['type']) ? $data['type'] : 'up'; //up=力道加强, down=力道减弱，recover=恢复
                $num  = isset($data['num']) ? $data['num'] : 1; //力道加强/减弱多少

                if ($type == 'up') {
                    $sendAction = '16';
                    $timeRes    = $this->_timeHandle($num);
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else if ($type == 'down') {
                    $sendAction = '18';
                    $timeRes    = $this->_timeHandle($num);
                    $instruction .= $sendAction . $timeRes['hextime'] . '0000' . $timeRes['hexsum'];
                } else {
                    $sendAction = '1A';
                    $instruction .= $sendAction . '00000000';
                }
                break;
            default:
                break;
        }

        //发送请求
        $instruction = strtoupper($instruction);
        $url         = str_replace(['<product_key>', '<did>'], [$this->product_key, $did], $this->control_url);
        $data        = [
            //'FF0603AA000000AD',
            'attrs' => ['emc' => $instruction],
        ];

        $payload = json_encode($data);
        $headers = ['Authorization: token ' . $token];
        $tmpRes  = $this->_curlPost($url, $payload, $headers, $timeout = 10);
        $status  = $tmpRes['status'];

        if ($status == 'success') {
            //接口请求成功
            return ['code' => 1, 'msg' => '设备控制成功'];
        } else {
            //接口请求失败
            return ['code' => 0, 'msg' => $tmpRes['msg'] . "[{$tmpRes['errno']}]"];
        }
    }

    /**
     * 通过接口获取设备的状态
     * @param  string $mac
     * @return array
     */
    public function query($mac)
    {
        if (!$mac) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        //获取授权信息
        $tokenInfo = $this->_getToken();
        $code      = $tokenInfo['code'];
        if ($code == 0) {
            return $tokenInfo;
        }
        $token = $tokenInfo['token'];

        //发送请求
        $url     = str_replace(['<product_key>', '<mac>'], [$this->product_key, $mac], $this->query_url);
        $headers = ['Authorization: token ' . $token];
        $tmpRes  = $this->_curlPost($url, [], $headers, $timeout = 10, 'GET');
        $status  = $tmpRes['status'];

        if ($status == 'success') {
            //接口请求成功
            $data = [
                'did'         => $tmpRes['res']['did'],
                'is_online'   => $tmpRes['res']['is_online'],
                'is_disabled' => $tmpRes['res']['is_disabled'],
                'type'        => $tmpRes['res']['type'],
            ];

            return ['code' => 1, 'data' => $data];
        } else {
            //接口请求失败
            return ['code' => 0, 'msg' => $tmpRes['msg'] . "[{$tmpRes['errno']}]"];
        }
    }

    /**
     * 获取登录授权信息
     * @author dwer
     * @date   2017-08-27
     *
     * @return []
     */
    private function _getToken()
    {
        //获取token
        $option = ['type' => 'File', 'path' => CACHE_PATH, 'prefix' => '', 'expire' => 0];
        Cache::connect($option);

        $cache_key = 'gw_entapi_token_' . $this->enterprise_id . '_' . $this->product_key;
        $token     = Cache::get($cache_key);
        if (!$token) {
            $url  = str_replace('<product_key>', $this->product_key, $this->login_url);
            $data = [
                'enterprise_id'     => $this->enterprise_id,
                'enterprise_secret' => $this->enterprise_secret,
                'product_secret'    => $this->product_secret,
            ];

            $payload = json_encode($data);
            $tmp     = $this->_curlPost($url, $payload, $headers = [], $timeout = 10);
            $status  = $tmp['status'];

            if ($status == 'success') {
                $res = $tmp['res'];

                if (isset($res['token'])) {
                    $token  = $res['token'];
                    $expire = $res['expire_at'];
                } else {
                    $msg = isset($res['error_message']) ? $res['error_message'] : '授权失败';
                    return ['code' => 0, 'msg' => $msg];
                }

                //设置缓存
                Cache::set($cache_key, $token, $expire - time() - 600);
            } else {
                //如果是授权失败直接返回
                return ['code' => 0, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
            }
        }

        //返回token信息
        return ['code' => 1, 'token' => $token];
    }

    /**
     * CURL 提交请求数据
     * @author dwer
     * @date   2017-08-27
     *
     * @param  string $url
     * @param  array $headers
     * @param  array $data
     * @param  string $method
     * @return array
     */
    private function _curlPost($url, $data = [], $headers = [], $timeout = 20, $method = 'POST')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

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

        //设置header
        if ($headers) {
            if (!is_array($headers)) {
                $headers = explode("\r\n", $headers);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        //执行CURL
        $response = curl_exec($ch);

        //记录机智云控制返回的详细报文
        pft_log('giz_control', json_encode([$response]));

        if ($response === false) {
            $errCode = curl_errno($ch);
            $errMsg  = curl_error($ch);

            curl_close($ch);
            return ['status' => 'error', 'errno' => $errCode, 'msg' => $errMsg];
        } elseif (strpos($response, '{') === false) {
            //返回额数据不是json
            curl_close($ch);
            return ['status' => 'error', 'errno' => -1, 'msg' => '返回额数据不是json'];
        } else {
            //关闭连接
            curl_close($ch);

            $tmpRes = @json_decode($response, true);
            $tmpRes = is_array($tmpRes) ? $tmpRes : [];

            if(isset($tmpRes['error_code'])) {
                //接口报错了
                $errorMessage = isset($tmpRes['error_message']) ? $tmpRes['error_message'] : '';

                return ['status' => 'error', 'errno'=> $tmpRes['error_code'], 'msg' => $errorMessage];
            } else {
                return ['status' => 'success', 'res' => $tmpRes];
            }
        }
    }

    /**
     * 获取16进制数据
     * @author dwer
     * @date   2017-08-27
     *
     * @param int $minute 分钟数
     *
     * @return ['hextime' => '0B', 'hexsum' => 'B4']
     */
    private function _timeHandle($minute = 10)
    {
        $hextime = str_pad(dechex($minute % 256), 2, '0', STR_PAD_LEFT);
        $hexsum  = str_pad(dechex((0x03 + 0xA2 + $minute + 0x00 + 0x00) % 256), 2, '0', STR_PAD_LEFT);

        return ['hextime' => $hextime, 'hexsum' => $hexsum];
    }

    /**
     * 将数字进行格式化 1 => 01
     * @author dwer
     * @date   2017-08-23
     *
     * @param  int $num
     * @return string
     */
    private function _formatNum($num)
    {
        $num = intval($num);
        if ($num <= 0) {
            $num = '00';
        } else if ($num >= 100) {
            $num = '99';
        } else if ($num < 10) {
            $num = "0{$num}";
        } else {
            $num = strval($num);
        }

        return $num;
    }
}

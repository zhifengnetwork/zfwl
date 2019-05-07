<?php
/**
 * 自建物联平台控制接口
 *
 * @author dwer
 * @date   2017-08-23
 *
 *  指令详细说明
 *
 *  1.查询运行状态(action=00)：00 00 00 00
 *
 *  2.不定时启动(action=A1)： A1 00 00 02
 *  param1：预留
 *  param2：00  AUTO1
 *  param3：02  气压档位2
 *
 *  3.定时启动/叠加(action=A2)：A2 05 00 02
 *  param1：05  启动/叠加5分钟
 *  param2：00  AUTO1
 *  param3：02  气压档位2
 *
 *  4.切换AUTO模式(action=A3)：A3 02 00 00
 *  param1：02  01=颈部按摩，02=全身放松，03=腰背按摩
 *
 *  5.停止(action=A5)：A5 00 00 00
 *
 *  6.暂停(action=A8)：A8 00 00 00
 *
 *  7.解除暂停(即恢复) (action=A9)：A9 00 00 00
 *
 *  8.靠背升(action=02)：02 05 00 00
 *  param1：05  上升5秒
 *
 *  9.靠背降(action=04)：04 03 00 00
 *  param1：03  下降3秒
 *
 *  10.靠背复位(action=06)：06 00 00 00
 *
 *  11.自动气压开关(action=11)：11 00 00 00
 *
 *  12.气压加强(action=12)：12 01 00 00 16
 *  param1：01  加强1档
 *
 *  13.气压减弱(action=13)：13 02 00 00
 *  param1：02  减弱2档
 *
 *  14.播放语音(action=46)：46 02 06 00
 *  param1：02  播放第2段
 *  param2：06  播放6秒
 *
 *  15. 停止播放语音(action=49)：49 00 00 00
 *
 *  16.加音量(action=4B)：4B 01 00 00
 *  param1：01  加强1
 *
 *  17.减音量(action=4D)：4D 02 00 00
 *  param1：02  减弱2
 *
 *  18.力道加强(action=16)：16 01 00 00
 *  param1：01  加强1档
 *
 *  19.力道减弱(action=18)：18 02 00 00
 *  param1：02  减弱2档
 *
 */

namespace mgcore\library;

class MiControl
{
    private $_apiAddr = 'http://iot.miyixia.cn:10637/';

    //控制类型
    private $_controlArr = [
        'start', //启动按摩椅
        'stop', //停止
        'pause', //暂停
        'recover', //解除暂停(即恢复)
        'mode', //模式切换
        'back', //靠背调整
        'pressure', //气压调整
        'voice', //语音控制
        'volume', //音量控制
        'strength', //力道控制
    ];

    /**
     *
     * @author dwer
     * @date   2017-08-23
     *
     * @return array
     */
    public function __construction($apiAddr = '')
    {
        if ($apiAddr) {
            $this->_apiAddr = $apiAddr;
        }

    }

    //设置请求地址
    public function setApiAddr($apiAddr)
    {
        $this->_apiAddr = $apiAddr;
    }

    /**
     * 设备控制
     * @author dwer
     * @date   2017-08-23
     *
     * @param  string $action 控制类型
     * @param  array $data 控制参数
     * @return array
     */
    public function control($imei, $action, $data = [])
    {
        if (!$imei || !in_array($action, $this->_controlArr)) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        //具体指令
        $sendAction = '';
        $param1     = '00';
        $param2     = '00';
        $param3     = '00';

        switch ($action) {
            case 'start':
                //启动按摩椅
                $time     = isset($data['time']) ? $data['time'] : 0; //05 5分钟
                $mode     = '02'; //isset($data['mode']) ? $data['mode'] : '00'; //00=AUTO1, 01=AUTO2, 02=AUTO3 - 默认AUTO2
                $pressure = isset($data['num']) ? $data['num'] : '02'; //01=气压档位1, 02=气压档位2, 03=气压档位3

                $sendAction = $time > 0 ? 'A2' : 'A1';
                $param1     = $this->_formatNum($time);
                $param2     = $this->_formatNum($mode);
                $param3     = $this->_formatNum($pressure);
                break;
            case 'stop':
                //停止
                $sendAction = 'A5';
                break;
            case 'pause':
                //暂停
                $sendAction = 'A8';
                break;
            case 'recover':
                //解除暂停(即恢复)
                $sendAction = 'A9';
                break;
            case 'mode':
                //模式切换
                $sendAction = 'A3';

                $mode   = isset($data['mode']) ? $data['mode'] : '00'; //00=AUTO1, 01=AUTO2, 02=AUTO3
                $param1 = $this->_formatNum($mode);
                break;
            case 'back':
                //靠背调整
                $type = isset($data['type']) ? $data['type'] : 'up'; //up=靠背升, down=靠背降, recover=复位
                $time = isset($data['time']) ? $data['time'] : 3; //上升5秒
                $time = $time <= 0 ? 3 : $time;

                if ($type == 'up') {
                    $sendAction = '02';
                    $param1     = $this->_formatNum($time);
                } else if ($type == 'down') {
                    $sendAction = '04';
                    $param1     = $this->_formatNum($time);
                } else {
                    $sendAction = '06';
                }
                break;
            case 'pressure':
                //气压控制
                $type     = isset($data['type']) ? $data['type'] : 'up'; //up=气压加强, down=气压减弱, recover=自动
                $strength = isset($data['num']) ? $data['num'] : 2; //加强2档
                $strength = $strength <= 0 ? 2 : $strength;

                if ($type == 'up') {
                    $sendAction = '12';
                    $param1     = $this->_formatNum($strength);
                } else if ($type == 'down') {
                    $sendAction = '13';
                    $param1     = $this->_formatNum($strength);
                } else {
                    $sendAction = '11';
                }
                break;
            case 'voice':
                //播放语音
                $type = isset($data['type']) ? $data['type'] : 'play'; //play=播放, stop=暂停
                $num  = isset($data['num']) ? $data['num'] : 1; //播放第几段
                $time = isset($data['time']) ? $data['time'] : 06; //播放几秒

                if ($type == 'play') {
                    $sendAction = '46';
                    $param1     = $this->_formatNum($num);
                    $param2     = $this->_formatNum($time);
                } else {
                    $sendAction = '49';
                }
                break;
            case 'volume':
                //音量控制
                $type = isset($data['type']) ? $data['type'] : 'up'; //up=播放+, down=音量-
                $num  = isset($data['num']) ? $data['num'] : 1; //音量加强/减弱多少

                $sendAction = $type == 'up' ? '4B' : '4D';
                $param1     = $this->_formatNum($num);
                break;
            case 'strength':
                //力道控制
                $type = isset($data['type']) ? $data['type'] : 'up'; //up=力道加强, down=力道减弱
                $num  = isset($data['num']) ? $data['num'] : 1; //力道加强/减弱多少

                $sendAction = $type == 'up' ? '16' : '18';
                $param1     = $this->_formatNum($num);
                break;
            default:
                break;
        }

        $data = [
            'imei'   => $imei,
            'action' => $sendAction,
            'param1' => $param1,
            'param2' => $param2,
            'param3' => $param3,
        ];

        //日志添加最后实际发送的指令
        pft_log('real_control', json_encode([$data]));

        $params = http_build_query($data);
        $url    = $this->_apiAddr . 'control' . '?' . $params;

        $tmp = $this->_curlPost($url);
        if ($tmp['status'] == 'success') {
            $res = $tmp['res'];
            $res = @json_decode($res, true);
            if ($res && $res['success'] == true) {
                //接口调用成功
                $retData = ['code' => 1, 'msg' => '设备控制成功'];
            } else if ($res) {
                //接口返回错误
                $retData = ['code' => 0, 'msg' => $res['errorMsg'] . "[{$res['errorCode']}]"];
            } else {
                //格式错误
                $retData = ['code' => 0, 'msg' => '接口返回数据错误'];
            }
        } else {
            $retData = ['code' => 0, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        }

        return $retData;
    }

    /**
     * 查询设备的状态
     *
     * @author dwer
     * @date   2017-08-23
     *
     * @param  string $imei
     * @return array
     */
    public function query($imei)
    {
        if (!$imei) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        $params = http_build_query(['imei' => $imei]);
        $url    = $this->_apiAddr . 'query' . '?' . $params;

        $tmp = $this->_curlPost($url);
        if ($tmp['status'] == 'success') {
            $res = $tmp['res'];
            $res = @json_decode($res, true);
            if ($res && $res['success'] == true) {
                //接口调用成功
                $data = [
                    'imei'     => $res['imei'],
                    'online'   => $res['online'],
                    'state'    => $res['state'],
                    'signal'   => $res['signal'],
                    'infrared' => $res['infrared'],
                ];
                $retData = ['code' => 1, 'data' => $data, 'msg' => '查询成功'];
            } else if ($res) {
                //接口返回错误
                $retData = ['code' => 0, 'msg' => $res['errorMsg'] . "[{$res['errorCode']}]"];
            } else {
                //格式错误
                $retData = ['code' => 0, 'msg' => '接口返回数据错误'];
            }
        } else {
            $retData = ['code' => 0, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        }

        return $retData;
    }

    /**
     * 设备固件进行升级
     *
     * @author dwer
     * @date   2017-08-23
     *
     * @param  string $imei
     * @return array
     */
    public function upgrade($imei)
    {
        $params = http_build_query(['imei' => $imei]);
        $url    = $this->_apiAddr . 'upgrade' . '?' . $params;

        $tmp = $this->_curlPost($url);
        if ($tmp['status'] == 'success') {
            $res = $tmp['res'];
            $res = @json_decode($res, true);
            if ($res && $res['success'] == true) {
                //接口调用成功
                $retData = ['code' => 1, 'msg' => '设备升级成功'];
            } else if ($res) {
                //接口返回错误
                $retData = ['code' => 0, 'msg' => $res['errorMsg'] . "[{$res['errorCode']}]"];
            } else {
                //格式错误
                $retData = ['code' => 0, 'msg' => '接口返回数据错误'];
            }
        } else {
            $retData = ['code' => 0, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        }

        return $retData;
    }

    /**
     * 将数字进行16进制格式化 10 => 0a
     * @author dwer
     * @date   2017-08-23
     *
     * @param  int $num
     * @return string
     */
    private function _formatNum($num)
    {
        //先转换成16进制
        $num    = intval($num);
        $hexNum = strval(dechex($num));
        $len    = strlen($hexNum);

        if ($len <= 0) {
            $res = '00';
        } elseif ($len == 1) {
            $res = '0' . $hexNum;
        } elseif ($len == 2) {
            $res = $hexNum;
        } else {
            //长度超过了255
            $res = 'ff';
        }

        return $res;
    }
    /**
     * CURL 提交请求数据
     *
     * @author dwer
     * @date   2016-04-11
     * @param string $url 请求URL
     * @param string $postData 请求发送的数据
     * @param int $port 请求端口
     * @param int $timeout 超时时间
     * @param array $httpHeaders 请求头信息
     * @param array userPwdArr [user,pwd]  CURLOPT_USERPWD 的设置参数
     * @return bool|mixed
     */
    private function _curlPost($url, $postData = [], $port = 80, $timeout = 25, $httpHeaders = [], $userPwdArr = [])
    {
        //超时时间处理
        $timeout = intval($timeout);
        $timeout = $timeout <= 0 ? 25 : $timeout;

        $ch       = curl_init();
        $url_info = parse_url($url);
        if ($url_info['scheme'] == 'https') {
            $port = 443;
        } else {
            $port = isset($url_info['port']) ? $url_info['port'] : $port;
        }

        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if ((is_array($httpHeaders) || is_object($httpHeaders)) && count($httpHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }

        if ($userPwdArr && is_array($userPwdArr)) {
            curl_setopt($ch, CURLOPT_USERPWD, "{$userPwdArr[0]}:{$userPwdArr[1]}");
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $res = curl_exec($ch);

        //错误处理
        $errCode = curl_errno($ch);
        if ($errCode > 0) {
            $curlError = curl_error($ch);
            curl_close($ch);

            return ['status' => 'error', 'errno' => $errCode, 'msg' => $curlError];
        } else {
            //获取HTTP码
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode != 200) {
                curl_close($ch);
                return ['status' => 'error', 'errno' => $httpCode, 'msg' => $res];
            } else {
                curl_close($ch);
                return ['status' => 'success', 'res' => $res];
            }
        }
    }
}

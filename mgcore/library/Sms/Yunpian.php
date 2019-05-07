<?php
/**
 * 云片短信发送服务
 * @author dwer
 * @date   2017-08-17
 *
 */
namespace mgcore\library\Sms;

use \think\Config;

class Yunpian
{
    private $_url     = 'https://sms.yunpian.com/v2/sms/single_send.json';
    private $_apiKey  = '';
    private $_tplList = [];
    private $_logPath = 'yunpian';

    //信息初始化
    public function __construct()
    {
        $config = Config::get('sms.yunpian');

        $this->_apiKey  = $config['api_key'];
        $this->_tplList = $config['tpl'];
    }

    /**
     * 发送短信
     * @author dwer
     * @date   2017-08-17
     *
     * @param  string $mobile 手机号
     * @param  string $tpl 模板标识
     * @param  array $param 参数
     * @return array ['status' => success/fail, 'msg' => '错误信息']
     */
    public function send($mobile, $tpl, $param)
    {
        $res = $this->_check($mobile, $tpl, $param);
        if ($res[0] == false) {
            return ['status' => 'fail', 'msg' => $res[1]];
        }

        $tplId   = $res[2];
        $content = $res[3];

        $res = $this->_request($mobile, $content);
        return $res;
    }

    /**
     * 具体发送请求
     * @author dwer
     * @date   2017-08-17
     *
     * @param  string $mobile 手机号
     * @param  string $content 发送内容
     * @return bool
     */
    private function _request($mobile, $content)
    {
        $data = http_build_query([
            'text'   => $content,
            'apikey' => $this->_apiKey,
            'mobile' => $mobile,
        ]);
        $header = ['Accept:application/json; charset=utf-8;', 'Content-Type:application/x-www-form-urlencoded;charset=utf-8;'];

        $res    = curl_post($this->_url, $data, 443, 25, $header);
        $status = $res['status'];
        if ($status == 'error') {
            //请求错误，写入日志
            pft_log($this->_logPath, json_encode([$mobile, $content, $res['errno'], $res['msg']]));

            return ['status' => 'fail', 'msg' => $res['msg']];
        } else {
            $jsonData = $res['res'];
            $tmp      = @json_decode($jsonData, true);
            if (!$tmp || !is_array($tmp)) {
                //请求错误，写入日志
                pft_log($this->_logPath, json_encode([$mobile, $content, $res, '返回数据不是json']));

                return ['status' => 'fail', 'msg' => '短信提供商接口错误'];
            } else {
                $code = $tmp['code'];
                if ($code == 0) {
                    return ['status' => 'success'];
                } else {
                    $errMsg = $tmp['msg'];
                    pft_log($this->_logPath, json_encode([$mobile, $content, $res, $errMsg]));

                    return ['status' => 'fail', 'msg' => $errMsg];
                }

            }
        }
    }

    /**
     * 内容校验
     * @author dwer
     * @date   2017-08-17
     *
     * @param  string $mobile 手机号
     * @param  string $tpl 模板标识
     * @param  array $param 参数
     * @return array
     */
    private function _check($mobile, $tpl, $param)
    {
        if (!$mobile || !$tpl) {
            return [false, '参数错误'];
        }

        if (!is_mobile_number($mobile)) {
            return [false, '手机号码错误'];
        }

        if (!$this->_tplList || !array_key_exists($tpl, $this->_tplList)) {
            return [false, '模板错误'];
        }

        $tmp        = $this->_tplList[$tpl];
        $tplId      = $tmp['tpl_id'];
        $tplContent = $tmp['tpl_content'];

        $tplParams = [];
        for ($i = 1; $i <= count($param); $i++) {
            $tplParams['{' . $i . '}'] = $param[$i - 1];
        }

        $keys    = array_keys($tplParams);
        $vals    = array_values($tplParams);
        $content = str_replace($keys, $vals, $tplContent);
        return ['true', '数据正常', $tplId, $content];
    }
}

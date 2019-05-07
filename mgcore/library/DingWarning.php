<?php
/**
 * 钉钉告警封装类
 * @author dwer
 * @date   2017-10-18
 *
 */
namespace mgcore\library;

use \think\Config;

class DingWarning
{
    private $_accessToken = '';
    private $_apiUrl      = '';

    public function __construct($warnType = 'default')
    {
        $config = Config::get('ding_warning');
        if (in_array($warnType, $config)) {
            $this->_accessToken = $config[$warnType];
        } else {
            $this->_accessToken = $config['default'];
        }

        $this->_apiUrl = 'https://oapi.dingtalk.com/robot/send?access_token=' . $this->_accessToken;
    }

    public function send($title, $markdownMsg, $atMobiles = [])
    {
        if (!$title || !$markdownMsg) {
            return false;
        }

        $data = [
            'msgtype'  => 'markdown',
            'markdown' => [
                'title' => $title,
                'text'  => $markdownMsg,
            ],
        ];

        if ($atMobiles) {
            $data['at']['atMobiles'] = $atMobiles;
        }

        $jsonData = json_encode($data);

        $httpHeaders = ['Content-Type: application/json;charset=utf-8'];
        $res         = curl_post($this->_apiUrl, $jsonData, $port = 443, $timeout = 25, $httpHeaders);

        if ($res['status'] == 'success') {
            return true;
        } else {
            pft_log('ding_warning', json_encode([$this->_apiUrl, $jsonData, $res]));
            return false;
        }
    }
}

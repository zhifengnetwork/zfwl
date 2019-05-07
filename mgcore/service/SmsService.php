<?php
/**
 * 短信服务层
 * @auther dwer.cn
 * @date 2017-09-14
 */
namespace mgcore\service;

use mgcore\library\Sms\Yunpian;
use mgcore\library\Sms\Cloud;

class SmsService
{
    private static $_channel = 'Yunpian'; //Qcloud

    /**
     * 切换短信通道
     * @param   $channel 短信通道
     * @return 
     */
    public static function init($channel) {
        self::$_channel = $channel;
    }

    /**
     * 发送短信 - 后面可以做自动切换重试等机制
     * @author dwer
     * @date   2017-08-17
     *
     * @param  string $mobile 手机号
     * @param  string $tpl 模板标识
     * @param  array $params 参数
     * @return array ['status' => success/fail, 'msg' => '错误信息']
     */
    public static function send($mobile, $tpl, $params) {
        if(self::$_channel == 'Yunpian') {
            $handle = new Yunpian();

            $res = $handle->send($mobile, $tpl, $params);
            return $res;
        } else {
            return false;
        }
    }
}

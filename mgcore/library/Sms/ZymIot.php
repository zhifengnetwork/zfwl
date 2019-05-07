<?php
/**
 * 中移（深圳）物联网发送短信给2g的物联卡
 * @author dwer
 * @date   2017-08-17
 *
 */
namespace mgcore\library\Sms;

class ZymIot
{
    private $_apiUrl   = 'http://www.zym2m.cn/CRM/Contents/AjaxHandle/SMSMobile.ashx?type=addSend';
    private $_username = 'xiaomikeji';

    /**
     *  发送短信给物联卡
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的SIM卡号
     * @param  $content 发送的内容
     * @return
     */
    public function send($sim, $content)
    {
        if (!$sim || !$content) {
            return ['code' => 203, 'msg' => '参数错误'];
        }

        $data = [
            'PhoneNums'  => $sim,
            'MsgContent' => $content,
            'username'   => $this->_username,
            'dataType'   => 'text',

        ];

        $tmp    = curl_post($this->_apiUrl, $data);
        $status = $tmp['status'];
        if ($status != 'success') {
            //网络请求出错
            return ['code' => 204, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        } else {
            //接口请求正常
            $res = $tmp['res'];
            if ($res == 'Success') {
                return ['code' => 200, 'msg' => '发送成功'];
            } else {
                return ['code' => 205, 'msg' => '发送失败：' . $res];
            }
        }
    }

}

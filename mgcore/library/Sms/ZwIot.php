<?php
/**
 * 中网物联科技发送短信给2g的物联卡
 * @author dwer
 * @date   2017-11-28
 *
 */
namespace mgcore\library\Sms;

class ZwIot
{
    private $_apiUrl = 'https://www.58sim.com:443/2.0/sms/send';
    private $_appid  = '102420130722';
    private $_secret = 'd18525de1248207bd272d6b4198a7d3d';

    /**
     *  发送短信给物联卡
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的SIM卡号
     * @param  $content 发送的内容
     * @return
     */
    public function send($iccid, $content)
    {
        if (!$iccid || !$content) {
            return ['code' => 203, 'msg' => '参数错误'];
        }

        $data = [
            'iccid'     => $iccid,
            'text'      => $content,
            'timestamp' => time() . '000', //毫秒
        ];

        $tmp = $this->_post($data);

        $status = $tmp['status'];
        if ($status != 'success') {
            //网络请求出错
            return ['code' => 204, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        } else {
            //接口请求正常
            $res    = $tmp['res'];
            $resArr = json_decode($res, true);
            if (!$resArr || !is_array($resArr)) {
                return ['code' => 205, 'msg' => '发送失败：' . $res];
            }

            //0: 正常
            //440 ~ 499: 客户端相关问题
            //510 ~ 599 : 服务端相关问题
            //600 + : 业务状态码
            $code    = $resArr['code'];
            $message = $resArr['detail'];

            if ($code == 0) {
                return ['code' => 200, 'msg' => '发送成功'];
            } else {
                return ['code' => 205, 'msg' => '发送失败：' . $message];
            }
        }
    }

    /**
     * 加密和发送请求
     * @author dwer
     * @date   2017-11-30
     *
     * @param  array $data
     * @return array
     */
    private function _post($data)
    {
        $data['appid'] = $this->_appid;
        ksort($data, SORT_STRING);

        $signStr = '';
        foreach($data as $key => $val) {
            $signStr .= $key . '=' . $val . '&';
        }
        $tmpSign = trim($signStr, '&');

        $tmpSign      = $tmpSign . $this->_secret;
        $sign         = hash('sha256', $tmpSign);
        $data['sign'] = $sign;

        $res = curl_post($this->_apiUrl, $data);
        return $res;
    }

}

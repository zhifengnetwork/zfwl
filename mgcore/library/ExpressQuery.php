<?php
/**
 * 物流信息查询
 * @author dwer
 * @date   2017-10-18
 *
 */
namespace mgcore\library;

use think\Cache;
use \think\Config;

class ExpressQuery
{
    private $_appCode = '';
    private $_apiUrl  = 'http://jisukdcx.market.alicloudapi.com/express/query';

    public function __construct()
    {
        $config         = Config::get('express.aliyun');
        $this->_appCode = $config['AppCode'];
    }

    /**
     * 获取物流信息
     * @author dwer
     * @date   2017-10-18
     *
     * @param  $orderNo
     * @return array ['code' => 200, 'msg' => 'ok', 'data' => $data]
     *         $data = [
     *                'number' => '1202516745301',
     *                'type' => 'yunda',
     *                'deliverystatus' => 3,
     *                'issign' => 1,
     *                'list' => [
     *                    [
     *                      'time' : '2017-01-07 16:02:43',
     *                      'status' : '湖南省炎陵县公司快件已被 已签收 签收'
     *                    ]
     *                ]
     *              ]
     */
    public function get($orderNo)
    {

        $orderNo = strval($orderNo);
        if (!$orderNo) {
            return ['code' => 203, 'msg' => '参数错误'];
        }

        //尝试查询奥佳华提供的接口
        $res = $this->queryByOgawa($orderNo);
        if (!empty($res['list'])) {
            return ['code' => 200, 'msg' => 'ok', 'data' => $res];
        }

        if (!$this->_appCode) {
            return ['code' => 203, 'msg' => 'appCode配置不存在'];
        }

        $param = [
            'number' => $orderNo,
            'type'   => 'auto',
        ];
        $queryUrl = $this->_apiUrl . '?' . http_build_query($param);
        $headers  = ["Authorization:APPCODE " . $this->_appCode];
        $res      = curl_post($queryUrl, [], $port = 80, $timeout = 25, $headers);
        $status   = $res['status'];

        if ($status == 'error') {
            //接口出问题了
            $errno = $res['errno'];
            $msg   = $res['msg'];
            return ['code' => 204, 'msg' => $msg . "[$errno]"];
        } else {
            $data = @json_decode($res['res'], true);

            if ($data) {
                if ($data['status'] == 0) {
                    //数据成功返回
                    return ['code' => 200, 'msg' => 'ok', 'data' => $data['result']];
                } else {
                    return ['code' => 205, 'msg' => $data['msg']];
                }
            } else {
                return ['code' => 204, 'msg' => '返回数据格式错误'];
            }
        }
    }


    /**
     * 查询物流接口(奥佳华)
     * @author xiexy
     * @date   2017-12-04
     *
     * @param  $expressNo 物流单号
     * @return array
     */
    public function queryByOgawa($expressNo)
    {
        if ($expressNo) {
            $expressInfo = [];
            $expressList = [];
            $requestUrl  = Config::get('ogawa_api.query_url');
            $partnerKey  = Config::get('ogawa_api.partner_key');

            $requestUrl .= "?logiCode={$expressNo}&partnerKey={$partnerKey}";
            $param       = "logiCode{$expressNo}partnerKey{$partnerKey}";

            $mtime       = msectime();
            $nonce       = rand(1, 1000000);
            $signature   = $this->_createSingn($param, $mtime, $nonce);

            if (!$signature) {
                return false;
            }

            $signature   = bytesToStr(getBytes(md5($signature)));
            $header      = [
                "partnerKey:" . $partnerKey,
                'timestamp:'  . $mtime,
                'nonce:'      . $nonce,
                'signature:'  . strtoupper($signature)
            ];

            $requestRes  = http_request($requestUrl, $header, '', 'GET');
            pft_log('ogawa_api/query', json_encode($requestRes));

            if (isset($requestRes['Status']) && $requestRes['Status'] == 200) {
                foreach ($requestRes['Data']['Route'] as $info) {
                    $expressList[] = [
                        'status' => $info['remark'],
                        'time'   => $info['accept_time']
                    ];
                }
                $expressInfo['list'] = $expressList;
            }
            return $expressInfo;
        }
    }


    /**
     * 获取token (奥佳华)
     * @author xiexy
     * @date   2017-12-04
     *
     * @param  $orderNo
     * @return string
     */
    private function _getToken()
    {
        $requestUrl  = Config::get('ogawa_api.token_url');
        $partnerKey  = Config::get('ogawa_api.partner_key');
        $token       = '';
        //接口地址
        $requestUrl .= "?partnerKey={$partnerKey}";
        //设置请求头
        $header      = [
            'partnerKey:' . $partnerKey,
            'timestamp:'  . msectime(),
            'nonce:'      . rand(1, 1000000),
        ];

        $requestRes  = http_request($requestUrl, $header, '', 'GET');
        pft_log('ogawa_api/token', json_encode($requestRes));

        if ($requestRes['Status'] == '200') {
            $resData = $requestRes['Data'];
            $token   = $resData['SignToken'];
        }

        return $token;
    }


    /**
     * 生成物流接口签名(奥佳华)
     * @author xiexy
     * @date   2017-12-04
     * @param  $param        查询参数字符串
     * @param  $mtime        时间戳毫秒
     * @param  $nonce        随机数
     * @return array
     */
    private function _createSingn($param, $mtime, $nonce)
    {
        $partnerKey  = Config::get('ogawa_api.partner_key');
        $token       = $this->_getToken();

        if (!$token) {
            return false;
        }

        $str    =  $mtime . $nonce . $partnerKey . $token . $param;
        $strArr = str_split($str);
        sort($strArr);
        $newStr = implode('', $strArr);
        return $newStr;
    }
}

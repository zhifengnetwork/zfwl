<?php
/**
 * 物联网卡信息查询
 * @author dwer
 * @date   2017-08-17
 *
 */
namespace mgcore\library;

class IotQuery
{
    private $_apiUrl   = 'http://120.24.94.92:8089/';
    private $_username = 'xiaomikeji';
    private $_key      = '15b15dded01fa6957c700703fe88e76f';

    /**
     * 在线信息实时查询（移动）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function onlineInfo($iccid)
    {
        $apiName = 'api/Service/Gprsrealsingle';

    }

    /**
     * 用户余额信息实时查询
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function balanceInfo($iccid)
    {
        $apiName = 'api/Service/Balancerealsingle';
    }

    /**
     * 开关机信息实时查询（移动）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function onAndOffInfo($iccid)
    {
        $apiName = 'api/Service/Onandoffrealsingle';

    }

    /**
     * 用户当月短信查询
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function usedInfo($iccid)
    {
        $apiName = 'api/Service/Smsusedinfosingle';

    }

    /**
     * 码号信息查询
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return {'code' : 200, 'data' => {'IMSI' => '460041057005614', 'MSISDN' : '1064705705117', 'ICCID':'898607B0101770527114'}}
     */
    public function cardInfo($iccid)
    {
        $iccid = strval($iccid);
        if (!$iccid) {
            return ['code' => 203, 'msg' => 'iccid不能为空'];
        }

        $apiName = 'api/Service/Cardinfo';
        $data    = [
            'username'  => $this->_username,
            'key'       => $this->_key,
            'iccid'     => $iccid,
            'timestamp' => time(),
        ];

        $apiUrl = $this->_apiUrl . $apiName . '?' . $this->_getQueryParams($data);
        $tmp    = curl_post($apiUrl, []);
        $status = $tmp['status'];

        if ($status != 'success') {
            //网络请求出错
            return ['code' => 204, 'msg' => $tmp['msg'] . "[{$tmp['errno']}]"];
        } else {
            //接口请求正常
            $res = @json_decode($tmp['res'], true);
            if (!$res) {
                return ['code' => 205, 'msg' => '接口返回数据不是json格式'];
            }

            $resStatus = $res['Status'];
            if ($resStatus != 1) {
                return ['code' => 206, 'msg' => $res['Message']];
            }

            $data = $res['Data'];
            return ['code' => 200, 'msg' => '数据获取成功', 'data' => $data];
        }
    }

    /**
     * 获取充值套餐
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function packageInfo($iccid)
    {
        $apiName = 'api/Service/QueryGprsPackage';

    }

    /**
     * 流量充值（支持移动，广东移动，电信）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function recharge($iccid)
    {
        $apiName = 'api/Service/GprsRecharge';

    }

    /**
     * 流量查询（支持移动，电信）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function gprsInfo($iccid)
    {
        $apiName = 'api/Service/QueryGprs';

    }

    /**
     * 卡状态实时查询（支持电信,广东移动）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function cardStatusInfo($iccid)
    {
        $apiName = 'api/Service/QueryCardStatus';

    }

    /**
     * 卡状态实时查询（支持电信,广东移动）
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $iccid 物联卡的ICCID
     * @return
     */
    public function cardPackageInfo($iccid)
    {
        $apiName = 'api/Service/QueryCardPackage';

    }

    /**
     * 获取加密后的请求参数
     * 参数签名,请求参数按 a-z 排序，然后以 32 位小写 md5 加密即可（下面的加密参数已排序）signature=md5(key=xxx&sim=14765004176&timestamp=1491537216&username=jckj)
     *
     * @author dwer
     * @date   2017-10-22
     *
     * @param  $queryData array 请求的参数数组
     * @return
     */
    private function _getQueryParams(array $queryData)
    {
        //排序
        ksort($queryData);

        $queryParams = http_build_query($queryData);
        $signature   = md5($queryParams);

        $queryParams .= "&signature={$signature}";

        return $queryParams;
    }

}

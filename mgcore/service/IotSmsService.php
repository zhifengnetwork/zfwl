<?php
/**
 * 物联卡短信服务
 *
 *  @author dwer.cn
 *  @date 2017-08-26
 */
namespace mgcore\service;

use \mgcore\library\IotQuery;
use \mgcore\library\Sms\ZymIot as IotSms;
use \mgcore\model\Machine;
use \think\Cache;

class IotSmsService
{

    private $_error_log   = 'iot_sms/error';
    private $_success_log = 'iot_sms/success';

    //启动短信前缀
    private $_smsContent = 'XM[AMY]={time};';

    public function send($orderNo, $machine, $minute)
    {
        $orderNo = strval($orderNo);
        $minute  = intval($minute);

        if (!$orderNo || !$machine || !$minute) {
            return false;
        }

        //发送控制短信
        if (!is_array($machine)) {
            $model       = new Machine();
            $machineInfo = $model->getInfo($machine);
        } else {
            $machineInfo = $machine;
        }

        $iccid = $machineInfo['iccid'];
        if (!$iccid) {
            //iccid不存在
            return false;
        }

        //iccid做下转换,第七位由原来的数字替换为B
        $iccid = substr($iccid, 0, 6) . 'B' . substr($iccid, 7);

        //数据缓存24小时，一笔订单只能发送一次启动短信
        $cacheKey  = "iotsms:{$orderNo}";
        $cacheData = Cache::get($cacheKey);
        $cacheData = $cacheData ? $cacheData : ['res' => 0, 'error_times' => 0];

        if ($cacheData && ($cacheData['res'] || $cacheData['error_times'] >= 3)) {
            return false;
        }

        //通过iccid获取sim卡号
        $iotQueryLib = new IotQuery();
        $cardInfo    = $iotQueryLib->cardInfo($iccid);
        $code        = $cardInfo['code'];

        if ($code != 200) {
            //sim卡号查询错误
            $cacheData['error_times'] += 1;
            Cache::set($cacheKey, $cacheData, 86400);
            pft_log($this->_error_log, json_encode(['sim query error', $orderNo, $machineInfo['machine_id'], $cardInfo['msg']]));

            return false;
        }

        $data = $cardInfo['data'];
        $sim  = isset($data['MSISDN']) ? $data['MSISDN'] : '';
        if (!$sim) {
            $cacheData['error_times'] += 1;
            Cache::set($cacheKey, $cacheData, 86400);
            pft_log($this->_error_log, json_encode(['sim query error', $orderNo, $machineInfo['machine_id'], $data]));

            return false;
        }

        //通过接口直接发送短信
        $iotSmsModel  = new IotSms();
        $formatNum    = $this->_formatTime($minute);
        $startContent = str_replace('{time}', $formatNum, $this->_smsContent);
        $res          = $iotSmsModel->send($sim, $startContent);
        $code         = $res['code'];

        if ($code != 200) {
            //发送错误
            $cacheData['error_times'] += 1;
            Cache::set($cacheKey, $cacheData, 86400);
            pft_log($this->_error_log, json_encode(['send error', $orderNo, $machineInfo['machine_id'], $res]));

            return false;
        } else {
            //发送成功
            $cacheData['res'] = 1;
            Cache::set($cacheKey, $cacheData, 86400);

            pft_log($this->_success_log, json_encode(['success', $orderNo, $machineInfo['machine_id'], $res]));
            return true;
        }
    }

    //填充时间
    private function _formatTime($minute)
    {
        $minute    = intval($minute);
        $formatNum = '005';

        if ($minute > 999 || $minute <= 0) {
            $formatNum = '005';
        } else {
            $formatNum = str_pad($minute, 3, '0', STR_PAD_LEFT);
        }
        return $formatNum;
    }

}

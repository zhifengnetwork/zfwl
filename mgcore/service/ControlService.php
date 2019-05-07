<?php
/**
 * 按摩椅控制统一封装
 *
 *  @author dwer.cn
 *  @date 2017-08-26
 */
namespace mgcore\service;

use \mgcore\library\GizwitsControl;
use \mgcore\library\MiControl;
use \mgcore\model\Machine;
use \think\Config;

class ControlService
{
    private $_machineInfo = [];

    /**
     * 启动设备
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @param int $minute 定时启动分钟数 - 5分钟
     * @param int $mode 模式：00=AUTO1, 01=AUTO2, 02=AUTO3
     * @param int $pressure 气压：01=气压档位1, 02=气压档位2, 03=气压档位3
     * @return array
     */
    public function start($machine, $minute = 5, $mode = 0, $pressure = 0)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            pft_log('control/fail', json_encode(['start', $initInfo]));
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'start';
        $data   = [
            'time' => $minute,
            'mode' => $mode,
            'num'  => $pressure,
        ];
        $res = $controlLib->control($controlKey, $action, $data);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['start', $info, $data, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['start', $info, $data, $res]));
            return false;
        }
    }

    /**
     * 停止设备
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @return array
     */
    public function stop($machine)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'stop';
        $res    = $controlLib->control($controlKey, $action);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['stop', $info, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['stop', $info, $res]));
            return false;
        }
    }

    /**
     * 暂停设备
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @return array
     */
    public function pause($machine)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'pause';
        $res    = $controlLib->control($controlKey, $action);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['pause', $info, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['pause', $info, $res]));
            return false;
        }
    }

    /**
     * 解除暂停(即恢复)
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @return array
     */
    public function recover($machine)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'recover';
        $res    = $controlLib->control($controlKey, $action);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['recover', $info, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['recover', $info, $res]));
            return false;
        }
    }

    /**
     * 模式切换
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @param int $mode 模式：0=AUTO1, 01=AUTO2, 02=AUTO3
     * @return array
     */
    public function mode($machine, $mode = 0)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'mode';
        $data   = ['mode' => $mode];
        $res    = $controlLib->control($controlKey, $action, $data);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['mode', $info, $data, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['mode', $info, $data, $res]));
            return false;
        }
    }
    /**
     * 靠背调整
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @param int $type up=靠背升, down=靠背降, recover=复位
     * @param int $time 上升/下降3秒
     * @return array
     */
    public function back($machine, $type = 'up', $time = 3)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'back';
        $data   = ['type' => $type, 'time' => $time];
        $res    = $controlLib->control($controlKey, $action, $data);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['back', $info, $data, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['back', $info, $data, $res]));
            return false;
        }
    }

    /**
     * 气压控制
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @param int $type up=气压加强, down=气压减弱, recover=自动
     * @param int $num 加强2档
     * @return array
     */
    public function pressure($machine, $type = 'up', $num = 2)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'pressure';
        $data   = ['type' => $type, 'num' => $num];
        $res    = $controlLib->control($controlKey, $action, $data);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['pressure', $info, $data, $res]));
            return true;
        } else {
            pft_log('control/fail', json_encode(['pressure', $info, $data, $res]));
            return false;
        }
    }

    /**
     * 力道控制
     *  @author dwer.cn
     *  @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @param int $type p=力道加强, down=力道减弱
     * @param int $num //力道加强/减弱多少
     * @return array
     */
    public function strength($machine, $type = 'up', $num = 2)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $initInfo['control_key'];

        $action = 'strength';
        $data   = ['type' => $type, 'num' => $num];
        $res    = $controlLib->control($controlKey, $action, $data);

        //控制结果写入日志
        if ($res['code'] == 1) {
            pft_log('control/success', json_encode(['strength', $info, $data, $res]));
            return true;
        } else {    
            pft_log('control/fail', json_encode(['strength', $info, $data, $res]));
            return false;
        }
    }

    /**
     * 获取设备的运行状态
     * 不同物联平台返回的不太一样
     *
     * @author dwer.cn
     * @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @return array
     */
    public function query($machine)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];
        $controlKey = $info['mac']; //机智云和自建物联云都是通过 mac/imei去获取数据额

        $action = 'pressure';
        $res    = $controlLib->query($controlKey);

        //控制结果写入日志
        if ($res['code'] == 1) {
            //机智云返回数据 version_id = 0
            // 'did'         => $tmpRes['res']['did'],
            // 'is_online'   => $tmpRes['res']['is_online'],
            // 'is_disabled' => $tmpRes['res']['is_disabled'],
            // 'type'        => $tmpRes['res']['type'],

            // 自建物联云 version_id = 1
            // 'imei'       => $res['imei'],
            // 'online'     => $res['online'],
            // 'signal'     => $res['signal'],
            // 'state'      => $res['state'],
            // 'infrared'   => $res['infrared'],

            pft_log('query/success', json_encode(['pressure', $info, $res]));

            //返回版本和数据
            return ['version_id' => $info['version_id'], 'data' => $res['data']];
        } else {
            pft_log('query/fail', json_encode(['pressure', $info, $res]));
            return false;
        }
    }

    /**
     * 自建物联设备升级
     *
     * @author dwer.cn
     * @date 2017-08-26
     *
     * @param int/array $machine 设备ID或是设备数组
     * @return array
     */
    public function upgrade($machine)
    {
        $initInfo = $this->_init($machine);
        if ($initInfo['code'] == 0) {
            //参数错误
            //return $initInfo;
            return false;
        }

        $info       = $initInfo['machine_info'];
        $controlLib = $initInfo['lib'];

        $imei = $info['mac']; //机智云和自建物联云都是通过 mac/imei去获取数据额

        if ($info['version_id'] == 0) {
            return ['code' => 0, '非自建物联设备，不能升级。'];
        } else if(!$imei){
            return ['code' => 0, 'imei不存在'];
        } else {
            $res = $controlLib->upgrade($imei);

            //控制结果写入日志
            if ($res['code'] == 1) {
                pft_log('upgrade/success', json_encode([$info, $res]));
                //返回版本和数据
                return ['code' => 1, 'msg' => '升级成功'];
            } else {
                pft_log('upgrade/fail', json_encode([$info, $res]));
                return ['code' => 0, 'msg' => $res['msg']];
            }
        }
    }

    /**
     * 初始化设备信息
     * @param int/array $machine 设备ID或是设备数组
     *                  gw_did, mac, $version_id, server_addr, machine_id
     * @return array
     */
    private function _init($machine)
    {
        $info = $this->_getMachineInfo($machine);
        if (!$info) {
            return ['code' => 0, 'msg' => '设备信息错误'];
        }

        if (!in_array($info['version_id'], [0, 1])) {
            return ['code' => 0, 'msg' => '该版本设备不支持'];
        }

        if ($info['version_id'] == 1) {
            $apiAddr    = $info['server_addr'];
            $controlKey = $info['mac'];

            $controlLib = new MiControl($apiAddr);
        } else if ($info['version_id'] == 0) {
            // 企业信息
            $entId     = Config::get('gw_config.enterprise_id');
            $entSecret = Config::get('gw_config.enterprise_secret');
            // 产品信息
            $proKey     = Config::get('gw_config.product_key');
            $proSecret  = Config::get('gw_config.product_secret');
            $controlKey = $info['gw_did'];

            $controlLib = new GizwitsControl($entId, $entSecret, $proKey, $proSecret);
        }

        //返回
        return ['code' => 1, 'machine_info' => $info, 'control_key' => $controlKey, 'lib' => $controlLib];
    }

    /**
     * @param  int/array $machine 设备信息或是设备ID
     * @return array
     */
    private function _getMachineInfo($machine)
    {
        if (is_array($machine)) {
            if (isset($machine['gw_did']) && isset($machine['mac']) && isset($machine['machine_id'])
                && isset($machine['version_id']) && isset($machine['server_addr'])) {
                return $machine;
            } else {
                return false;
            }
        } else {
            $machineModel = new Machine();
            $field        = 'gw_did, mac, version_id, server_addr, machine_id';
            $machineInfo  = $machineModel->getInfo($machine, $field);

            return $machineInfo;
        }
    }

}

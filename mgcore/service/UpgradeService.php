<?php
/**
 * 按摩椅升级统一封装
 *
 *  @author dwer.cn
 *  @date 2017-08-26
 */
namespace mgcore\service;

use app\admin\model\RmBoard;
use \mgcore\library\MiControl;
use \mgcore\model\Machine;

class UpgradeService
{

    private $_rmModel        = null;
    private $_machineModel   = null;
    private $_controlService = null;

    /**
     * 获取需要升级的设备列表
     * @author dwer
     * @date   2017-10-31
     *
     * @return
     */
    public function getList($orderType = 'desc', $size = 50)
    {
        //获取需要
        $rmModel      = $this->_getRmModel();
        $machineModel = $this->_getMachineModel();

        $res = $rmModel->getNeedUpgradeList();
        if (!$res) {
            return [];
        }

        $upgradeList = [];
        foreach ($res as $item) {
            $rmId           = $item['rm_id'];
            $upgradeVersion = $item['upgrade_version'];

            $machineList = $machineModel->getUpgradeList($rmId, $upgradeVersion, $size, $orderType);
            if ($machineList) {
                $upgradeList = array_merge($upgradeList, $machineList);
            }
        }

        $upgradeList = array_unique($upgradeList);

        //返回
        return $upgradeList;
    }

    /**
     * 升级按摩椅控制板版本
     * @author dwer
     * @date   2017-10-31
     *
     * @return
     */
    public function run($machineId)
    {
        if (!$machineId) {
            return ['code' => 203, 'msg' => '参数错误'];
        }

        $machineModel = $this->_getMachineModel();
        $machineInfo  = $machineModel->getInfo($machineId);
        if (!$machineInfo) {
            return ['code' => 204, 'msg' => '找不到设备信息'];
        }

        if ($machineInfo['version_id'] == 0) {
            return ['code' => 205, 'msg' => '之前wifi版本不能升级'];
        }

        if (!$machineInfo['mac']) {
            return ['code' => 205, 'msg' => '设备不存在imei'];
        }

        if (!in_array($machineInfo['state'], [0])) {
            return ['code' => 205, 'msg' => '不是空闲状态不能升级'];
        }

        $imei           = $machineInfo['mac'];
        $placeId        = $machineInfo['place_id'];
        $currentVersion = $machineInfo['version'];
        $rmId           = $machineInfo['rm_id'];

        if (!$rmId) {
            return ['code' => 205, 'msg' => '没有设置按摩椅版本信息'];
        }

        //获取按摩板版本信息
        $rmModel = $this->_getRmModel();
        $rmInfo  = $rmModel->getInfo($rmId);
        if (!$rmInfo) {
            return ['code' => 205, 'msg' => '获取不到按摩椅版本信息'];
        }
        $upgradeVersion = $rmInfo['upgrade_version'];
        if (!$upgradeVersion || ($upgradeVersion == $currentVersion)) {
            return ['code' => 206, 'msg' => '不存在升级版本或是已经处于最新版本'];
        }

        //发送升级指令
        $controlBiz = $this->_getControlService();
        $res        = $controlBiz->upgrade($imei);
        $code       = $res['code'];

        if ($code == 1) {
            //写入日志
            $machineModel->addUpgradeLog($machineId, $rmId, $placeId, $imei, $currentVersion);
            return ['code' => 200, 'msg' => '升级指令发送成功'];
        } else {
            $msg = $res['msg'];
            return ['code' => 500, 'msg' => $msg];
        }
    }

    //获取模型
    private function _getRmModel()
    {
        if (!$this->_rmModel) {

            $this->_rmModel = new RmBoard();
        }

        return $this->_rmModel;
    }

    //获取模型
    private function _getMachineModel()
    {
        if (!$this->_machineModel) {

            $this->_machineModel = new Machine();
        }

        return $this->_machineModel;
    }

    //获取模型
    private function _getControlService()
    {
        if (!$this->_controlService) {

            $this->_controlService = new MiControl();
        }

        return $this->_controlService;
    }
}

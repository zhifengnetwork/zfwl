<?php
namespace app\api\controller;

use app\api\model\Config as ConfigModel;

/**
 * 配置控制器
 */
class Config extends Common
{
    /**
     * 游戏服务配置
     */
    public function game_config()
    {
        $data = ConfigModel::game_config();
        $this->result(...$data);
    }
}

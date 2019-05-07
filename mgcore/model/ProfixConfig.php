<?php
/**
 * 咪小二分润模型
 *
 */
namespace mgcore\model;

use think\Model;

class ProfixConfig extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_profitConfigTable = 'sale_user_profit_idv';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    public function getConfigList($field = 'province, city, places, worker_profit, manager_profit', $order = 'id DESC')
    {
        $configList = $this->table($this->_profitConfigTable)->field($field)->order($order)->select();
        return $configList;
    }

}

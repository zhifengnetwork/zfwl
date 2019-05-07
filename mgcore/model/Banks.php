<?php
/**
 * 咪铺用户相关的模型封装
 * @author dwer
 * @date   2017-09-03
 *
 */

namespace mgcore\model;

use think\Config;
use think\Model;

class Banks extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_banksTable     = 'banks';
    private $_bankAreaTable  = 'bank_area';
    private $_subbranchTable = 'bank_subbranch';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 获取所有的银行及其代码
     * @author dwer
     * @date   2016-07-08
     *
     * @param  $page
     * @param  $size
     * @return
     */
    public function getBanks($page = 1, $size = 200)
    {
        $res = $this->table($this->_banksTable)->page("$page,$size")->field('code, name')->select();
        return $res;
    }

    /**
     * 获取所有银行所在的省份及其代码
     * @author dwer
     * @date   2016-07-08
     *
     * @return
     */
    public function getBankProvince()
    {
        $where = [
            'parent_id' => 0,
        ];

        $res = $this->table($this->_bankAreaTable)->where($where)->field('area_id as code, area_name as name')->select();
        return $res;
    }

    /**
     * 根据省份ID获取市及其代码
     * @author dwer
     * @date   2016-07-08
     *
     * @param  $provinceId
     * @return
     */
    public function getCity($provinceId)
    {
        if (!$provinceId) {
            return false;
        }

        $where = [
            'parent_id' => intval($provinceId),
        ];

        $res = $this->table($this->_bankAreaTable)->where($where)->field('area_id as code, area_name as name')->select();
        return $res;
    }

    /**
     * 获取支行信息
     * @author dwer
     * @date   2016-07-08
     *
     * @param  $cityId 城市代码 1620 => 大同市
     * @param  $bankId 银行代码 504 => 恒生银行
     * @param  $searchName 模糊搜索 词
     * @param  $page 第几页
     * @param  $size 条数
     * @return
     */
    public function getSubbranch($cityId = 0, $bankId = 0, $searchName = '', $page = 1, $size = 500)
    {
        $cityId     = intval($cityId);
        $bankId     = intval($bankId);
        $searchName = strval($searchName);
        $page       = intval($page);
        $size       = intval($size);

        $where = [];

        if ($cityId) {
            $where['city'] = $cityId;
        }

        if ($bankId) {
            $where['bank_id'] = $bankI;
        }

        if ($searchName !== '') {
            $where['name'] = ['like', "%{$searchName}%"];
        }

        $page  = "{$page},{$size}";
        $field = 'code,name,phone';

        $count = $this->table($this->_subbranchTable)->field($field)->where($where)->count();
        $list  = $this->table($this->_subbranchTable)->field($field)->where($where)->page($page)->select();

        return $list === false ? false : ['count' => $count, 'list' => $list];
    }

}

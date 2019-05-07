<?php
/**
 * 用戶账户余额处理模型
 *
 */
namespace mgcore\model;

use think\Model;
use think\Config;

class SaleUserMoney extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_userMoneyTable = 'user_money';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 获取账户余额
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId    咪小二id
     * @return   array
     */
    public function getBalanceByUid($userId)
    {
        $balance = $this->table($this->_userMoneyTable)->field('amount, frozen_amount, version')->where(['uid' => (int) $userId])->find();
        return $balance;
    }

    /**
     * 增加账户余额
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @param    $increaseAmount  金额
     * @return   array
     */
    public function increaseBalance($uid, $increaseAmount, $version)
    {
        $data = [
            'amount'      => $increaseAmount,
            'update_time' => time(),
            'version'     => ['exp', 'version + 1'],
        ];

        $res = $this->table($this->_userMoneyTable)->where(['uid' => $uid, 'version' => $version])->update($data);
        return $res;
    }


    /**
     * 扣除账户余额
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @param    $increaseAmount  金额
     * @return   array
     */
    public function descreaseBalance($uid, $increaseAmount, $version)
    {
        $data = [
            'amount'      => $increaseAmount,
            'update_time' => time(),
            'version'     => ['exp', 'version + 1'],
        ];

        $res = $this->table($this->_userMoneyTable)->where(['uid' => $uid, 'version' => $version])->update($data);
        return $res;
    }

    /**
     * 初始化账户信息
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @return   array
     */
    public function initAccount($uid, $saleUserId)
    {
        $info = [
            'uid'      => (int) $uid,
            'suid'     => $saleUserId,
            'add_time' => time(),
        ];

        $res = $this->table($this->_userMoneyTable)->insert($info);
        return $res;
    }

    /**
     * 冻结账户金额
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @param    $amount          冻结的金额
     * @return   boolean
     */
    public function freezeFund($saleUserId, $amount)
    {
        $data = [
            'frozen_amount' => ['exp', 'frozen_amount + ' . $amount],
            'amount'        => ['exp', 'amount - ' . $amount],
        ];

        $res = $this->table($this->_userMoneyTable)->where(['suid' => (int) $saleUserId])->update($data);
        return $res;
    }

    /**
     * 减少账户冻结金额
     * @author   xiexy
     * @date     2017-09-25
     * @param    $saleUserId      咪小二id
     * @param    $amount          冻结的金额
     * @return   boolean
     */
    public function reduceFreezeAmount($saleUserId, $amount)
    {
        $data = [
            'frozen_amount' => ['exp', 'frozen_amount - ' . $amount],
        ];

        $res = $this->table($this->_userMoneyTable)->where(['suid' => (int) $saleUserId])->update($data);
        return $res;
    }
}

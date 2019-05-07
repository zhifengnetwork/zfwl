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

class User extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_userTable = 'user';

    //是否通过手机验证 1-是 0 -否
    const IS_CHECK  = 1;
    const NOT_CHECK = 0;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 获取用户信息
     * @author dwer
     * @date   2017-09-03
     *
     * @param  int $uid
     * @param  string $field
     * @return array
     */
    public function getInfo($uid, $field = '*')
    {
        if (!$uid) {
            return [];
        }

        $info = $this->table($this->_userTable)->where(['uid' => $uid])->field($field)->find();
        return $info ? $info : [];
    }

    /**
     * 更新用户的手机号
     * @author dwer
     * @date   2017-09-03
     *
     * @param  int $uid 用户ID
     * @param  int $mobile 手机号
     * @param  bool $isChecked 是否通过手机验证码验证
     * @return array
     */
    public function updateMobile($uid, $mobile, $isChecked = false)
    {
        if (!$uid || !$mobile) {
            return false;
        }

        $data = [
            'mobile'      => $mobile,
            'update_time' => time(),
        ];
        if ($isChecked) {
            $data['is_checked'] = 1;
        }

        $res = $this->table($this->_userTable)->where(['uid' => $uid])->update($data);
        return $res ? true : false;
    }

    /**
     * 通过微信OPENID获取用户信息
     * @author dwer
     * @date   2017-09-03
     *
     * @param  int $openId
     * @param  string $field
     * @return array
     */
    public function getInfoByOpenId($openId, $field = '*')
    {
        if (!$openId) {
            return [];
        }

        $info = $this->table($this->_userTable)->where(['wx_openid' => strval($openId)])->field($field)->find();
        return $info ? $info : [];
    }

    /**
     * 通过手机号获取用户信息
     * @author dwer
     * @date   2017-09-03
     *
     * @param  int $openId
     * @param  string $field
     * @return array
     */
    public function getInfoByMobile($mobile, $field = '*')
    {
        if (!$mobile) {
            return [];
        }

        $info = $this->table($this->_userTable)->where(['mobile' => strval($mobile)])->field($field)->find();
        return $info ? $info : [];
    }


    /**
     * 用户是否通过手机验证
     * @author dwer
     * @date   2017-09-03
     *
     * @param  string    $openId
     * @param  integer   $uid
     * @return boolean
     */
    public function isUserChecked($openid, $uid = 0)
    {
        if (!$openid && !$uid) {
            return false;
        }

        $condition = [];
        $openid && $condition['wx_openid'] = $openid;
        $uid    && $condition['uid']       = $uid;

        $result    = $this->table($this->_userTable)->where($condition)->field('is_checked')->find();
        if (!$result || $result['is_checked'] == self::NOT_CHECK) {
            return false;
        }

        return true;
    }
}

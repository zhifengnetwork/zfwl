<?php
namespace mgcore\model;

use think\Model;

/**
 * 后台用户相关的模型封装
 * @author dwer
 * @date   2017-07-13 dog
 *
 */
class MgUser extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_userTable = 'mg_user';
    private $_roleTable = 'role_user';

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 根据用户ID获取信息
     * @param  int $userIdArr 用户ID
     * @return array
     */
    public function getList($userIdArr, $field = 'mgid, name')
    {
        if (!$userIdArr) {
            return [];
        }

        $res = [];
        $tmp = $this->table($this->_userTable)->where(['mgid' => ['in', $userIdArr]])->select();

        foreach ($tmp as $item) {
            $res[$item['mgid']] = $item;
        }

        return $res;
    }

    /**
     * 通过角色获取用户列表
     * @author dwer
     * @date   2017-10-19
     *
     * @param  array $roleIdArr 角色ID数组
     * @return array
     */
    public function getListByRole($roleIdArr, $page = 1, $size = 50)
    {
        if (!$roleIdArr) {
            return [];
        }

        $where = [
            'rid'            => ['in', $roleIdArr],
            'role.status'    => 1,
            'user.is_delete' => 0,
        ];
        $page  = "{$page},{$size}";
        $join  = "role.uid = user.mgid";
        $field = "mgid,name,username,mobile";

        $res = $this->table($this->_roleTable . " role")->join($this->_userTable . ' user', $join, 'LEFT')->field($field)->where($where)->page($page)->select();
        return $res ? $res : [];
    }
}

<?php
namespace app\api\controller;

use app\api\model\UserBase;

/**
 * 前台用户控制器
 */
class User extends Common
{
    /**
     * 实名认证(無效代碼)
     */
    public function realname()
    {
        $token = input('token', '');
        $uid   = think_decrypt($token);
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }

        $name   = input('name');
        $idcard = input('idcard');
        if (empty($name)) {
            $this->result('', 1, '请输入姓名');
        }

        if (empty($idcard)) {
            $this->result('', 1, '请输入身份证号');
        }

        $info = UserBase::where('uid', $uid)->field('name,idcard')->find();

        if (!empty($info['idcard'])) {
            $this->result('', 1, '已实名不能再次提交');
        }

        $userBase = new UserBase;
        $res      = $userBase->save(['name' => $name, 'idcard' => $idcard], ['uid' => $uid]);

        if ($res) {
            $this->result('', 1, '失败');
        }
        $this->result('认证成功', 0);
    }
}

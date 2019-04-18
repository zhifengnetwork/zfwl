<?php
namespace app\api\controller;

use app\api\model\Account as AccountModel;
use app\common\model\UserBase;

/**
 * 客户端账号控制器
 */
class Account extends Common
{
    /**
     * 发送验证码
     */
    public function get_code()
    {
        $data = AccountModel::get_code();
        $this->result(...$data);
    }

    /**
     * 游客添加密码和绑定电话
     */
    public function bind_tel()
    {
        $data = AccountModel::bind_tel();
        $this->result(...$data);
    }

    /**
     * 修改用户信息
     */
    public function update_user_info()
    {
        $data = AccountModel::update_user_info();
        $this->result(...$data);
    }

    /**
     * 短信重置密码接口
     */
    public function sms_reset_password()
    {
        $data = AccountModel::sms_reset_password();
        $this->result(...$data);
    }

    /**
     * 绑定支付宝
     */
    public function bind_ali_account()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }

        $alipayAccount = input('ali_account', '');
        $alipayName    = input('ali_name', '');

        if (!$alipayAccount || !$alipayName) {
            $this->result('', 1, '支付宝账号、姓名不能为空');
        }

        /* if (UserBase::where('uid', $uid)->value('alipay_account')) {
        $this->result('', 1, '不能重复绑定');
        }*/

        /*if (UserBase::where('alipay_account', $alipayAccount)->value('alipay_account')) {
        $this->result('', 1, '该支付宝账号已被绑定');
        }*/

        $data = [
            'alipay_name'    => $alipayName,
            'alipay_account' => $alipayAccount,
        ];

        if (UserBase::where('uid', $uid)->update($data)) {
            $this->result('', 0, '绑定成功');
        }
        $this->result('', 1, '绑定失败');
    }

    /**
     * 绑定银行卡
     */
    public function bind_bank_account()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }

        $bankAccount    = input('bank_account', '');
        $cardholderName = input('cardholder_name', '');

        if (!$bankAccount || !$cardholderName) {
            $this->result('', 1, '银行账号、姓名不能为空');
        }

        /*if (!$this->isChineseName($cardholderName)) {
        $this->result('', 1, '姓名不合法');
        }*/

        if (UserBase::where('uid', $uid)->value('bank_account')) {
            $this->result('', 1, '不能重复绑定');
        }

        if (UserBase::where('bank_account', $bankAccount)->value('bank_account')) {
            $this->result('', 1, '该银行账号已被绑定');
        }

        $data = [
            'bank_account'    => $bankAccount,
            'cardholder_name' => $cardholderName,
        ];

        if (UserBase::where('uid', $uid)->update($data)) {
            $this->result('', 0, '绑定成功');
        }
        $this->result('', 1, '绑定失败');
    }

    /**
     * func 验证中文姓名
     * @param $name
     * @return bool
     */
    private function isChineseName($name)
    {
        if (preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/', $name)) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户信息
     * (因为客户端获取用户信息后，没有走登入流程，账号密码没有名文保存)
     * 带优化
     */
    public function getUserInfo()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }
        $where['uid'] = $uid;
        $userBase     = UserBase::where($where)->field('uid,headimg,sex,nickname,tel,alipay_account,bank_account')->find();
        $result       = [
            'token'        => think_encrypt($userBase->uid, '', 7 * 86400),
            'uid'          => $userBase->uid,
            'headimg'      => $userBase->headimg,
            'sex'          => $userBase->sex,
            'tel'          => $userBase->tel,
            'nickname'     => $userBase->nickname,
            'bank_account' => $userBase->bank_account,
            'ali_account'  => $userBase->alipay_account,
            'uno'          => $userBase->tel ? $userBase->tel : $userBase->uid,
        ];
        $this->result($result, 0, 'success');
    }
}

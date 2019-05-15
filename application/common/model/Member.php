<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    /***
     * 充值积分and余额
     */
    public static function setBalance($uid = '',$type = '',  $num = 0, $data = array()){
            $balance_info  = get_balance($uid,$type);
            $dephp_11      = $balance_info['balance'] + $num;

            Db::name('member_balance')->where(['user_id' => $uid,'balance_type' => $balance_info['balance_type']])->update(['balance' => $dephp_11]);
           
            $dephp_12 = array('user_id' => $uid, 'balance_type' => $balance_info['balance_type'], 'old_balance' => $balance_info['balance'], 'balance' => $dephp_11,'create_time' => time(), 'account_id' => intval($data[0]), 'note' => $data[1]);
            Db::name('menber_balance_log')->insert($dephp_12);
    }

    public static function getBalance($uid = '',$type = ''){
        $balance  = Db::name('member_balance')->where(['user_id' => $uid ,'balance_type' => $type])->value('balance');
        return $balance;
    }

  
    public static  function getLevels(){
        $Leve = Db::table('member_level')->order('level')->select();
        return $Leve;
    }
    function getLevel($dephp_0){
        global $_W;
        if (empty($dephp_0)){
            return false;
        }
        $dephp_7 = m('member') -> getMember($dephp_0);
        if (empty($dephp_7['level'])){
            return array('discount' => 10);
        }
        $dephp_17 = pdo_fetch('select * from ' . tablename('sz_yi_member_level') . ' where id=:id and uniacid=:uniacid order by level asc', array(':uniacid' => $_W['uniacid'], ':id' => $dephp_7['level']));
        if (empty($dephp_17)){
            return array('discount' => 10);
        }
        return $dephp_17;
    }
  
    public static function getGroups(){
        $Group = Db::table('member_group')->order('id')->select();
        return $Group;
    }
    public static function getGroup($dephp_0){
        if (empty($dephp_0)){
            return false;
        }
        $dephp_7 = self::getMember($dephp_0);
        return $dephp_7['groupid'];
    }
 


}

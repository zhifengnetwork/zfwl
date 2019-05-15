<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;
 
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

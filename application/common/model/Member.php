<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;
    /**
     *获取第一级下级
     *
     * @param  $dephp_0 openid 或者id
     * @param  $type 1分销商和非分销商 2分销商 3非分销商
     * @return array
     */

    
    public function getNextL($dephp_0,$type = 1){

        global $_W;
        $dephp_1 = intval($dephp_0);

 
 
        if (empty($dephp_1)){
            $user = pdo_fetch('select * from ' . tablename('sz_yi_member') . " where  openid = '{$dephp_0}' and uniacid='{$_W['uniacid']}' limit 1"  );
        }else{
            $user = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where id = :id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $dephp_1));
        }  


 

        if(empty($user)) return array();



        $user_id = $user['id'];


        $conditions = '' ;
        //查询出下级为分销商的会员$type为1时获取所有
        if($type==2){
             $conditions = " and isagent = '1' ";
        }elseif($type==3){
             $conditions = " and isagent = '0' ";
        }


        $sql = 'select * from '.tablename('sz_yi_member')." where agentid = '{$user_id}' $conditions  ";




     //   return  pdo_fetchall($sql);
        $res =  pdo_fetchall($sql);
      

        return $res ;

    }
 	
	
	
	     
    public function getNextL_1($dephp_0,$type = 1){

        global $_W;
        $dephp_1 = intval($dephp_0);
        if (empty($dephp_1)){
            $user = pdo_fetch('select * from ' . tablename('sz_yi_member') . " where  openid = '{$dephp_0}' and uniacid='{$_W['uniacid']}'   limit 1"  );
        }else{
            $user = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where id = :id and uniacid=:uniacid   limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $dephp_1));
        }  
        if(empty($user)) return array();
        $user_id = $user['id'];


        $conditions = '' ;
        //查询出下级为分销商的会员$type为1时获取所有
        if($type==2){
             $conditions = " and isagent = '1' and agentlevel!=0 ";
        }elseif($type==3){
             $conditions = " and isagent = '0' and agentlevel!=0 ";
        }

        $sql = 'select * from '.tablename('sz_yi_member')." where agentid = '{$user_id}' $conditions  ";
     //   return  pdo_fetchall($sql);
        $res =  pdo_fetchall($sql);
        return $res ;

    }
	
	
	
   public function getAllNextL_1($dephp_0,$type = 1){
        
           global $_W;

		   $commission = p('commission')->getSet();
	
	
			$level = $commission['level'];
			$level_arr = array();
	
			if($type==1){
				$level_arr[1] = $this->getNextL($dephp_0,1); 
			}elseif($type==2){
				$level_arr[1] = $this->getNextL($dephp_0,2); 
			}
			$i=2;
			for ( ; $i <= $level ; $i++) {
	
			   if(empty($level_arr[$i-1])) break;
	
			   $agentid_arr = array();
	
			   foreach ($level_arr[$i-1] as $key => $value) {
	
				  if($value['isagent']==1){
					 $agentid_arr[] = $value['id'];
				  }
	
			   }
			   if(empty($agentid_arr)) break;
			   $agentid_arr = implode(',',$agentid_arr);
	
				
	
			   if($type==2){
					 $sql = 'select * from ' . tablename('sz_yi_member') . " where agentid in ({$agentid_arr}) and uniacid = '{$_W['uniacid']}' and isagent = 1  and agentlevel!=0";
			   }elseif($type==1){
					 $sql = 'select * from ' . tablename('sz_yi_member') . " where   agentid in ({$agentid_arr}) and uniacid = '{$_W['uniacid']}'  and agentlevel!=0 ";
			   }
	
			   $level_arr[$i] = pdo_fetchall($sql); 
	 
			}
	
			return $level_arr;
    }

	
 
    /**
     *获取所有下级
     *
     * @param  $dephp_0t openid 或者id
     * @param  $type 1分销商和非分销商 2 分销商
     * @return mixed
     */

 
    public function getAllNextL($dephp_0,$type = 1){
        
           global $_W;

       $commission = p('commission')->getSet();


        $level = $commission['level'];
        $level_arr = array();

        if($type==1){
            $level_arr[1] = $this->getNextL($dephp_0,1); 
        }elseif($type==2){
            $level_arr[1] = $this->getNextL($dephp_0,2); 
        }
        $i=2;
        for ( ; $i <= $level ; $i++) {

           if(empty($level_arr[$i-1])) break;

           $agentid_arr = array();

           foreach ($level_arr[$i-1] as $key => $value) {

              if($value['isagent']==1){
                 $agentid_arr[] = $value['id'];
              }

           }
           if(empty($agentid_arr)) break;
           $agentid_arr = implode(',',$agentid_arr);

            

           if($type==2){
                 $sql = 'select * from ' . tablename('sz_yi_member') . " where agentid in ({$agentid_arr}) and uniacid = '{$_W['uniacid']}' and isagent = 1 ";
           }elseif($type==1){
                 $sql = 'select * from ' . tablename('sz_yi_member') . " where   agentid in ({$agentid_arr}) and uniacid = '{$_W['uniacid']}' ";
           }

           $level_arr[$i] = pdo_fetchall($sql); 


 


                
        }
 


 
        return $level_arr;
    }






    public function getInfo($dephp_0 = ''){
        global $_W;
        $dephp_1 = intval($dephp_0);
        if ($dephp_1 == 0){
            $dephp_2 = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_0));
        }else{
            $dephp_2 = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where id=:id  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $dephp_1));
        }
        if (!empty($dephp_2['uid'])){
            load() -> model('mc');
            $dephp_1 = mc_openid2uid($dephp_2['openid']);
            $dephp_3 = mc_fetch($dephp_1, array('credit1', 'credit2', 'birthyear', 'birthmonth', 'birthday', 'gender', 'avatar', 'resideprovince', 'residecity', 'nickname'));
            $dephp_2['credit1'] = $dephp_3['credit1'];
            $dephp_2['credit2'] = $dephp_3['credit2'];
            $dephp_2['birthyear'] = empty($dephp_2['birthyear']) ? $dephp_3['birthyear'] : $dephp_2['birthyear'];
            $dephp_2['birthmonth'] = empty($dephp_2['birthmonth']) ? $dephp_3['birthmonth'] : $dephp_2['birthmonth'];
            $dephp_2['birthday'] = empty($dephp_2['birthday']) ? $dephp_3['birthday'] : $dephp_2['birthday'];
            $dephp_2['nickname'] = empty($dephp_2['nickname']) ? $dephp_3['nickname'] : $dephp_2['nickname'];
            $dephp_2['gender'] = empty($dephp_2['gender']) ? $dephp_3['gender'] : $dephp_2['gender'];
            $dephp_2['sex'] = $dephp_2['gender'];
            $dephp_2['avatar'] = empty($dephp_2['avatar']) ? $dephp_3['avatar'] : $dephp_2['avatar'];
            $dephp_2['headimgurl'] = $dephp_2['avatar'];
            $dephp_2['province'] = empty($dephp_2['province']) ? $dephp_3['resideprovince'] : $dephp_2['province'];
            $dephp_2['city'] = empty($dephp_2['city']) ? $dephp_3['residecity'] : $dephp_2['city'];
        }
        if (!empty($dephp_2['birthyear']) && !empty($dephp_2['birthmonth']) && !empty($dephp_2['birthday'])){
            $dephp_2['birthday'] = $dephp_2['birthyear'] . '-' . (strlen($dephp_2['birthmonth']) <= 1 ? '0' . $dephp_2['birthmonth'] : $dephp_2['birthmonth']) . '-' . (strlen($dephp_2['birthday']) <= 1 ? '0' . $dephp_2['birthday'] : $dephp_2['birthday']);
        }
        if (empty($dephp_2['birthday'])){
            $dephp_2['birthday'] = '';
        }
        return $dephp_2;
    }
    public function getMember($dephp_0 = ''){
        global $_W;
        $dephp_1 = intval($dephp_0);
        if (empty($dephp_1)){
            $dephp_2 = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_0));
        }else{
            $dephp_2 = pdo_fetch('select * from ' . tablename('sz_yi_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $dephp_1));
        }
        if (!empty($dephp_2)){
            $dephp_0 = $dephp_2['openid'];
            if (empty($dephp_2['uid'])){
                $dephp_4 = m('user') -> followed($dephp_0);
                if ($dephp_4){
                    load() -> model('mc');
                    $dephp_1 = mc_openid2uid($dephp_0);
                    if (!empty($dephp_1)){
                        $dephp_2['uid'] = $dephp_1;
                        $dephp_5 = array('uid' => $dephp_1);
                        if ($dephp_2['credit1'] > 0){
                            mc_credit_update($dephp_1, 'credit1', $dephp_2['credit1']);
                            $dephp_5['credit1'] = 0;
                        }
                        if ($dephp_2['credit2'] > 0){
                            mc_credit_update($dephp_1, 'credit2', $dephp_2['credit2']);
                            $dephp_5['credit2'] = 0;
                        }						
                        if (!empty($dephp_5)){
                            pdo_update('sz_yi_member', $dephp_5, array('id' => $dephp_2['id']));
                        }
                    }
                }
            }
            $dephp_6 = $this -> getCredits($dephp_0);
            $dephp_2['credit1'] = $dephp_6['credit1'];
            $dephp_2['credit2'] = $dephp_6['credit2'];
        }
        return $dephp_2;
    }
    public function getMid(){
        global $_W;
        $dephp_0 = m('user') -> getOpenid();
        $dephp_7 = $this -> getMember($dephp_0);
        return $dephp_7['id'];
    }
    public function setCredit($dephp_0 = '', $dephp_8 = 'credit1', $dephp_6 = 0, $dephp_9 = array()){
        global $_W;
        load() -> model('mc');
        $dephp_1 = mc_openid2uid($dephp_0);
        if (!empty($dephp_1)){
            $dephp_10 = pdo_fetchcolumn("SELECT {$dephp_8} FROM " . tablename('mc_members') . ' WHERE `uid` = :uid', array(':uid' => $dephp_1));
            $dephp_11 = $dephp_6 + $dephp_10;
            if ($dephp_11 <= 0){
                $dephp_11 = 0;
            }
            pdo_update('mc_members', array($dephp_8 => $dephp_11), array('uid' => $dephp_1));
            if (empty($dephp_9) || !is_array($dephp_9)){
                $dephp_9 = array($dephp_1, '未记录');
            }
            $dephp_12 = array('uid' => $dephp_1, 'credittype' => $dephp_8, 'uniacid' => $_W['uniacid'], 'num' => $dephp_6, 'createtime' => TIMESTAMP, 'operator' => intval($dephp_9[0]), 'remark' => $dephp_9[1]);
            pdo_insert('mc_credits_record', $dephp_12);
        }else{
            $dephp_10 = pdo_fetchcolumn("SELECT {$dephp_8} FROM " . tablename('sz_yi_member') . ' WHERE  uniacid=:uniacid and openid=:openid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_0));
            $dephp_11 = $dephp_6 + $dephp_10;
            if ($dephp_11 <= 0){
                $dephp_11 = 0;
            }
            pdo_update('sz_yi_member', array($dephp_8 => $dephp_11), array('uniacid' => $_W['uniacid'], 'openid' => $dephp_0));
        }
    }
    public static function getCredit($dephp_0 = '', $dephp_8 = 'credit1'){
        $dephp_1 = mc_openid2uid($dephp_0);
        if (!empty($dephp_1)){
            $credit = Db::table('mc_members')->field('uid,credit1,credit2')->where(['uid' => $dephp_1])->find();
        }else{
            $credit = Db::table('member')->field('uid,openid,credit1,credit2')->where(['openid' => $dephp_0])->find();
        }
        return $credit[$dephp_8];
    }
    public function getCredits($dephp_0 = '', $dephp_13 = array('credit1', 'credit2')){
        global $_W;
        load() -> model('mc');
        $dephp_1 = mc_openid2uid($dephp_0);
        $dephp_8 = implode(',', $dephp_13);
        if (!empty($dephp_1)){
            return pdo_fetch("SELECT {$dephp_8} FROM " . tablename('mc_members') . ' WHERE `uid` = :uid limit 1', array(':uid' => $dephp_1));
        }else{
            return pdo_fetch("SELECT {$dephp_8} FROM " . tablename('sz_yi_member') . ' WHERE  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_0));
        }
    }
    public function checkMember($dephp_0 = ''){
        global $_W, $_GPC;
        if (strexists($_SERVER['REQUEST_URI'], '/web/')){
            return;
        }
        if (empty($dephp_0)){
            $dephp_0 = m('user') -> getOpenid();
        }
        if (empty($dephp_0)){
            return;
        }
     
        $dephp_7 = m('member') -> getMember($dephp_0);
        $dephp_14 = m('user') -> getInfo();
        $dephp_4 = m('user') -> followed($dephp_0);
        $dephp_1 = 0;
        $dephp_15 = array();
        load() -> model('mc');
        if ($dephp_4){
            $dephp_1 = mc_openid2uid($dephp_0);
            $dephp_15 = mc_fetch($dephp_1, array('realname', 'mobile', 'avatar', 'resideprovince', 'residecity', 'residedist'));
        }
        $dephp_16 = false;
        if (empty($dephp_7)){
            if ($dephp_4){
                $dephp_1 = mc_openid2uid($dephp_0);
                $dephp_15 = mc_fetch($dephp_1, array('realname', 'mobile', 'avatar', 'resideprovince', 'residecity', 'residedist'));
            }
            //字符串转义 2017年4月14日16:43:53

            function filterEmoji($nickname) {

                $clean_text = "";

                $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
                $clean_text = preg_replace($regexEmoticons, '', $nickname);

                $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
                $clean_text = preg_replace($regexSymbols, '', $clean_text);

                $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
                $clean_text = preg_replace($regexTransport, '', $clean_text);

                $regexMisc = '/[\x{2600}-\x{26FF}]/u';
                $clean_text = preg_replace($regexMisc, '', $clean_text);

                $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
                $clean_text = preg_replace($regexDingbats, '', $clean_text);

                return $clean_text;
            }
            $dephp_15['realname'] = filterEmoji($dephp_15['realname']);
            $dephp_14['nickname'] = filterEmoji($dephp_14['nickname']);
            if ((empty($dephp_14['nickname']) && $dephp_14) || (empty($dephp_14['nickname']) && $dephp_15)) {
                $dephp_14['nickname'] = '(非法昵称)';
            }

            $dephp_15['avatar'] = rtrim($dephp_15['avatar'],'0123') . 132;
            $upmc['avatar'] = $dephp_15['avatar'];

            $dephp_7 = array('uniacid' => $_W['uniacid'], 'uid' => $dephp_1, 'openid' => $dephp_0, 'realname' => !empty($dephp_15['realname']) ? $dephp_15['realname'] : '', 'mobile' => !empty($dephp_15['mobile']) ? $dephp_15['mobile'] : '', 'nickname' => !empty($dephp_15['nickname']) ? $dephp_15['nickname'] : $dephp_14['nickname'], 'avatar' => !empty($dephp_15['avatar']) ? $dephp_15['avatar'] : $dephp_14['avatar'], 'gender' => !empty($dephp_15['gender']) ? $dephp_15['gender'] : $dephp_14['sex'], 'province' => !empty($dephp_15['residecity']) ? $dephp_15['resideprovince'] : $dephp_14['province'], 'city' => !empty($dephp_15['residecity']) ? $dephp_15['residecity'] : $dephp_14['city'], 'area' => !empty($dephp_15['residedist']) ? $dephp_15['residedist'] : '', 'createtime' => time(), 'status' => 0);
            $dephp_16 = true;
            pdo_insert('sz_yi_member', $dephp_7);

            pdo_update('mc_members',$upmc,array('uniacid'=>$_W['uniacid'],'uid'=>$dephp_15['uid']));

        }else{
            $dephp_5 = array();
            if ($dephp_14['nickname'] != $dephp_7['nickname']){
                $dephp_5['nickname'] = $dephp_14['nickname'];
            }

            $dephp_14['avatar'] = rtrim($dephp_14['avatar'],'0123') . 132;

            if ($dephp_14['avatar'] != $dephp_7['avatar']){
                $dephp_5['avatar'] = $dephp_14['avatar'];
            }
            if (!empty($dephp_5)){
                pdo_update('sz_yi_member', $dephp_5, array('id' => $dephp_7['id']));
            }
        }
        if (p('commission')){
            p('commission') -> checkAgent();
        }
        if (p('poster')){
            p('poster') -> checkScan();
        }
        if($dephp_16 && is_weixin()){
        }
    }
    function getLevels(){
        global $_W;
        return pdo_fetchall('select * from ' . tablename('sz_yi_member_level') . ' where uniacid=:uniacid order by level asc', array(':uniacid' => $_W['uniacid']));
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
    function upgradeLevel($dephp_0){
        global $_W;
        if (empty($dephp_0)){
            return;
        }
        $dephp_18 = m('common') -> getSysset('shop');
        $dephp_19 = intval($dephp_18['leveltype']);
        $dephp_7 = m('member') -> getMember($dephp_0);
        if (empty($dephp_7)){
            return;
        }
        $dephp_17 = false;
        if (empty($dephp_19)){
            $dephp_20 = pdo_fetchcolumn('select ifnull( sum(og.realprice),0) from ' . tablename('sz_yi_order_goods') . ' og ' . ' left join ' . tablename('sz_yi_order') . ' o on o.id=og.orderid ' . ' where o.openid=:openid and o.status=3 and o.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_7['openid']));
           /* $dephp_17 = pdo_fetch('select * from ' . tablename('sz_yi_member_level') . " where uniacid=:uniacid  and {$dephp_20} >= ordermoney and ordermoney>0  order by level desc limit 1", array(':uniacid' => $_W['uniacid']));*/
		    $dephp_17 = pdo_fetch('select * from ' . tablename('sz_yi_member_level') . " where uniacid=:uniacid  and   ordermoney <=".$dephp_20." and ordermoney>0  order by level desc, ordermoney desc limit 1 ", array(':uniacid' => $_W['uniacid'])); 
			
        }else if ($dephp_19 == 1){
            $dephp_21 = pdo_fetchcolumn('select count(*) from ' . tablename('sz_yi_order') . ' where openid=:openid and status=3 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $dephp_7['openid']));
            $dephp_17 = pdo_fetch('select * from ' . tablename('sz_yi_member_level') . " where uniacid=:uniacid  and {$dephp_21} >= ordercount and ordercount>0  order by level desc, ordermoney desc  limit 1", array(':uniacid' => $_W['uniacid']));
			
        }
        if (empty($dephp_17)){
            return;
        }
        if ($dephp_17['id'] == $dephp_7['level']){
            return;
        }
        $dephp_22 = $this -> getLevel($dephp_0);
        $dephp_23 = false;
		
		/* print_r($dephp_22['level']);exit; */
        if (empty($dephp_22['id'])){
            $dephp_23 = true;
        }else{
            if ($dephp_17['level'] >= $dephp_22['level']){
                $dephp_23 = true;
            }
			
        }
		
        if ($dephp_23){
            pdo_update('sz_yi_member', array('level' => $dephp_17['id']), array('id' => $dephp_7['id']));
            m('notice') -> sendMemberUpgradeMessage($dephp_0, $dephp_22, $dephp_17);
        }
    }
    function getGroups(){
        global $_W;
        return pdo_fetchall('select * from ' . tablename('sz_yi_member_group') . ' where uniacid=:uniacid order by id asc', array(':uniacid' => $_W['uniacid']));
    }
    function getGroup($dephp_0){
        if (empty($dephp_0)){
            return false;
        }
        $dephp_7 = m('member') -> getMember($dephp_0);
        return $dephp_7['groupid'];
    }
    function setRechargeCredit($dephp_0 = '', $dephp_24 = 0){
        if (empty($dephp_0)){
            return;
        }
        global $_W;
        $dephp_25 = 0;
        $dephp_26 = m('common') -> getSysset(array('trade', 'shop'));
        if ($dephp_26['trade']){
            $dephp_27 = floatval($dephp_26['trade']['money']);
            $dephp_28 = intval($dephp_26['trade']['credit']);
            if ($dephp_27 > 0){
                if ($dephp_24 % $dephp_27 == 0){
                    $dephp_25 = intval($dephp_24 / $dephp_27) * $dephp_28;
                }else{
                    $dephp_25 = (intval($dephp_24 / $dephp_27) + 1) * $dephp_28;
                }
            }
        }
        if ($dephp_25 > 0){
            $this -> setCredit($dephp_0, 'credit1', $dephp_25, array(0, $dephp_26['shop']['name'] . '会员充值积分:credit2:' . $dephp_25));
        }
    }
    function writelog($dephp_29, $dephp_30 = 'Error'){
        $dephp_31 = fopen($dephp_30 . '.txt', 'a');
        fwrite($dephp_31, $dephp_29);
        fclose($dephp_31);
    }
    /**
         * ins_money_log
         *
         *  添加充值记录
         *
         * @param   String  $openid 
         */
            public function ins_money_log($openid='',$uniacid='',$title='余额充值',$money=0,$rechargetype='system',$credit='credit1'){

                    $logno = m('common')->createNO('member_log', 'logno', 'RC');
        
                    $data = array('openid' => $openid, 'logno' => $logno, 'uniacid' => $uniacid, 'type' => '0', 'createtime' => TIMESTAMP, 'status' => '1', 'title' => $title, 'money' => $money, 'rechargetype' => $rechargetype);
                    if($credit=='credit1'){
                            pdo_insert('sz_yi_member_points_log', $data);
                    }
                    else{
                           pdo_insert('sz_yi_member_log', $data); 
                    }
                    
            }



}

<?php
namespace app\api\model;
use think\Model;
use think\Db;

class UserAddr extends Model
{
    protected $table = 'user_address';

    public function getAddressList($where = array())
    {   

        $result = $this->alias('ua')
            ->field('ua.address_id,ua.consignee,ua.mobile,ua.address,ua.is_default')
            ->field('p.area_name as p_cn,c.area_name as c_cn,d.area_name as d_cn,s.area_name as s_cn')
            ->join('region p', 'p.area_id = ua.province', 'left')
            ->join('region c', 'c.area_id = ua.city', 'left')
            ->join('region d', 'd.area_id = ua.district', 'left')
            ->join('region s', 's.area_id = ua.twon', 'left')
            ->where($where)
            ->order('ua.is_default desc, ua.address_id asc')
            ->select();
        $result = ota($result);
        return $result;
    }

    public function getAddressFind($where = array())
    {   
        $result = $this->alias('ua')
            ->field('user_id,province,city,district,twon,address,consignee,mobile')
            // ->field('ua.address_id,ua.consignee,ua.mobile,ua.address,ua.is_default')
            // ->field('p.area_name as p_cn,c.area_name as c_cn,d.area_name as d_cn,s.area_name as s_cn')
            // ->join('region p', 'p.area_id = ua.province', 'left')
            // ->join('region c', 'c.area_id = ua.city', 'left')
            // ->join('region d', 'd.area_id = ua.district', 'left')
            // ->join('region s', 's.area_id = ua.twon', 'left')
            ->where($where)
            ->order('ua.is_default desc, ua.address_id asc')
            ->find();
        $result = ota($result);
        return $result;
    }
}

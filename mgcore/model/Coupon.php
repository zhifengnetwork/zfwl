<?php
/**
 * 优惠券相关模型
 * @author dwer
 * @date   2017-07-28
 *
 */

namespace mgcore\model;

use mgcore\model\Area;
use mgcore\model\Machine;
use mgcore\model\Place;
use think\Db;
use think\Exception;

class Coupon
{
    private $_couponTable  = 'coupon';
    private $_extTable     = 'coupon_ext';
    private $_listTable    = 'coupon_list';
    private $_provideTable = 'coupon_provide_log';

    //已经获取的可使用优惠券
    private $_receiveCouponList = [];

    private $_machineInfoList = [];

    private $_areaInfoList = [];

    private $_placeInfoList = [];

    // 优惠券状态 1-可用
    const NOMAL_STATUS = 1;

    //优惠券种类 2-反馈问题增送卷
    const ISSUE_REPORT_KIND = 2;

    /**
     * 用户领取优惠券
     * @author dwer
     * @date   2017-08-01
     *
     * @param  integer  $couponId 优惠券ID
     * @param  integer  $receiveNum 领取数量
     * @param  string  $openId 微信openid
     * @param  integer $userId 用户在本平台ID
     * @param  integer $saleUserId  咪小二id,通过咪小二活动领取的优惠券
     * @return array
     */
    public function receive($couponId, $receiveNum, $openId = '', $activityId, $userId = 0, $saleUserId = 0)
    {
        $receiveNum = intval($receiveNum);
        if ($receiveNum < 1 || !$couponId) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        //两个必须要有一个
        if (!$openId && !$userId) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        //获取优惠券信息
        $couponInfo = $this->getInfo($couponId);
        if (!$couponInfo) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        $limitNum   = intval($couponInfo['limit_num']);
        $provideNum = intval($couponInfo['provide_num']);

        //领取权限判断 - 这个逻辑暂时不加
        // if ($receiveNum > $limitNum) {
        //     return ['code' => 0, 'msg' => '领取数量超出'];
        // }

        // //总的领取数量是不是已经超过了
        // $hasReceiveNum = $this->_getReceiveNum($couponId, $openId, $userId);
        // if (($receiveNum + $hasReceiveNum) > $limitNum) {
        //     return ['code' => 0, 'msg' => '领取数量超出'];
        // }

        //获取还没有使用的优惠券
        $field = 'cst_id';
        $order = 'cst_id asc';
        $where = [
            'coupon_id' => $couponId,
            'status'    => 1,
        ];

        $tmpRes = Db::table($this->_listTable)->where($where)->field($field)->order($order)->page("0,{$receiveNum}")->select();
        if (count($tmpRes) < $receiveNum) {
            //优惠券不够了
            return ['code' => 0, 'msg' => '优惠券数量不够'];
        }

        $idArr = array_column($tmpRes, 'cst_id');
        $where = [
            'cst_id' => ['in', $idArr],
            'status' => 1,
        ];

        $data = [
            'status'       => 2,
            'receive_time' => time(),
            'activity_id'  => $activityId,
            'suid'         => $saleUserId,
        ];

        if ($openId) {
            $data['open_id'] = $openId;
        }
        if ($userId) {
            $data['user_id'] = $userId;
        }

        //开启事务
        Db::startTrans();

        //更新领到的优惠券
        $resNum = Db::table($this->_listTable)->where($where)->update($data);

        if (!$resNum || $resNum < $receiveNum) {
            Db::rollback();
            return ['code' => 0, 'msg' => '优惠券领取错误 - 1'];
        }

        //更新汇总信息
        $data = [
            'update_time' => time(),
            'receive_num' => ['exp', "receive_num+{$receiveNum}"],
        ];
        $res = Db::table($this->_couponTable)->where(['coupon_id' => $couponId])->update($data);
        if (!$res || $res < 1) {
            Db::rollback();
            return ['code' => 0, 'msg' => '优惠券领取错误 - 2'];
        }

        //如果优惠券已经领完了，就将优惠券的状态修改为不可用
        $where = [
            'coupon_id'   => $couponId,
            'receive_num' => ['EGT', $provideNum],
        ];
        $data = ['status' => 0];
        $res  = Db::table($this->_couponTable)->where($where)->update($data);

        if ($res === false) {
            Db::rollback();
            return ['code' => 0, 'msg' => '优惠券领取错误 - 3'];
        }

        Db::commit();
        return ['code' => 1, 'msg' => '优惠券领取成功'];
    }

    /**
     * 获取用户的优惠券
     * @author dwer
     * @date   2017-08-01
     *
     * @param  mix  $machine       设备信息/设备ID
     * @param  int  $serviceId     服务ID
     * @param  floot  $basePrice   价格
     * @param  int  $openId        openId
     * @param  int $userId        用户ID
     * @param  int $placeAreaCode 场所所在区域
     *
     * @return array
     *  {
     *      'way' => 1, //优惠方式 1=折扣 2=现金抵用
     *      'amount' => 500,//折扣-已经换算成小数形式，现金抵用-单位元
     *      'user_coupon_id' => 1000015, //优惠券ID
     *  }
     */
    public function getUserCoupon($machine, $serviceId, $basePrice, $openId = '', $userId = 0, $placeAreaCode = 0)
    {
        if (!$machine) {
            return [];
        }

        if (!$openId && !$userId) {
            return [];
        }

        //是否有优惠券可以使用
        $userCouponList = $this->_getReceiveCoupon($openId, $userId);

        if (!$userCouponList) {
            return [];
        }

        if (is_array($machine) && isset($machine['place_id']) && isset($machine['service_id'])) {
            $machineInfo = $machine;
        } else {
            //获取
            $machineInfo = $this->_getMachineInfo($machine);
        }

        $placeId          = $machineInfo['place_id'];
        $machineServiceId = $machineInfo['service_id'];

        // 场所信息
        $placeInfo = $this->_getPlaceInfo($placeId);
        $areaCode  = $placeInfo['area'];

        //获取设备所在省和市
        $province = '';
        $city     = '';
        if ($areaCode) {
            $areaInfo = $this->_getAreaInfo($areaCode);
            if ($areaInfo) {
                if ($areaInfo['area_pid']) {
                    $province = $areaInfo['area_pid'];
                    $city     = $areaInfo['area_id'];
                } else {
                    $province = $areaInfo['area_id'];
                }
            }
        }

        //返回的客户使用的优惠券数据
        $resCouponInfo = [];
        //可用优惠券
        $usableCoupon = [];
        $nowTime      = time();

        //判断是不是有优惠券可以使用
        foreach ($userCouponList as $item) {
            $tmpCouponId = $item['coupon_id'];
            if (!$tmpCouponId) {
                continue;
            }

            $couponInfo = $this->getInfo($tmpCouponId);
            if (!$couponInfo) {
                continue;
            }

            //判断是不是在有效期内
            if (($nowTime <= $couponInfo['start_time']) || ($nowTime >= $couponInfo['end_time'])) {
                continue;
            }

            //优惠折扣等信息
            $way          = $couponInfo['way'];
            $userCouponId = $item['cst_id'];
            if ($way == 1) {
                //折扣 - 换算成比例
                $amount = floatval($couponInfo['amount']) * 0.1;
            } else if ($way == 2) {
                //现金抵用 - 单位元
                $amount = floatval($couponInfo['amount']);
            } else {
                $amount = floatval($couponInfo['amount']);
            }

            //这张优惠券是否可以使用
            $isCanUse = false;
            //判断是不是通用优惠券
            if ($couponInfo['type'] == 1) {
                //无条件券，可以直接使用
                $isCanUse = true;
            } elseif ($couponInfo['type'] == 2) {
                //普通限制券
                $tmpProvince    = $couponInfo['province'];
                $tmpCity        = $couponInfo['city'];
                $tmpPlace       = $couponInfo['place'];
                $tmpServiceType = $couponInfo['service'];

                //判断是否限制
                if ($tmpPlace) {
                    //限制了使用场所
                    $tmpPlace = explode(',', $couponInfo['place']);
                    if (in_array($placeId, $tmpPlace)) {
                        //可以使用
                        $isCanUse = true;
                    }
                } elseif ($tmpCity) {
                    //限制了使用市
                    $cityArr = explode(',', $tmpCity);
                    if (in_array($city, $cityArr)) {
                        //可以使用
                        $isCanUse = true;
                    }
                } elseif ($tmpProvince) {
                    //限制了使用省
                    $provinceArr = explode(',', $tmpProvince);
                    if (in_array($province, $provinceArr)) {
                        //可以使用
                        $isCanUse = true;
                    }
                }

                if ($isCanUse && $tmpServiceType) {
                    //限制了使用产品
                    $tmpServiceType = $machineServiceId ? $tmpServiceType[1] : $tmpServiceType[2];
                    $tmpServiceType = explode(',', $tmpServiceType);
                    $isCanUse       = in_array($serviceId, $tmpServiceType) ? true : false;
                }
            }

            if (!$isCanUse) {
                //优惠券不能使用
                continue;
            } else {

                //计算优惠金额
                if ($way == 1) {
                    $disDiscount = 1 - $amount;
                    $disMoney    = $basePrice * $disDiscount;

                    if ($disMoney > $basePrice) {
                        $couponMoney = $basePrice;
                    } else {
                        $couponMoney = $disMoney;
                    }
                } else {
                    $disMoney = $amount;
                    if ($disMoney > $basePrice) {
                        $couponMoney = $basePrice;
                    } else {
                        $couponMoney = $disMoney;
                    }
                }

                $couponMoney = num_fmt($couponMoney, 2);
                $payMoney    = num_fmt(($basePrice - $couponMoney), 2);

                //可用优惠券
                $usableCoupon[] = [
                    'way'            => $way,
                    'amount'         => $amount,
                    'user_coupon_id' => $userCouponId,
                    'coupon_id'      => $couponInfo['coupon_id'],
                    'coupon_amount'  => $couponMoney,
                    'pay_money'      => $payMoney,
                ];
                $payMoneyArr[] = $payMoney;
            }
        }
        //可用优惠卷按照优惠力度从大到小顺序存储
        if ($usableCoupon) {
            array_multisort($payMoneyArr, SORT_ASC, $usableCoupon);
        }
        $resCouponInfo['usable_coupon'] = $usableCoupon;
        //返回优惠券信息
        return $resCouponInfo;
    }

    /**
     * 是否是合法的优惠券，如果是合法的优惠券就返回优惠信息
     * @author dwer
     * @date   2017-08-02
     *
     * @param  int $placeId 场所ID
     * @param  int $serviceType 产品ID
     * @param  int $basePrice 产品价钱 - 元
     * @param  int $userCouponId 用户优惠券ID
     * @param  string $openId 用户openid
     * @param  int $userId 用户ID
     *  {
     *      'way' => 1, //优惠方式 1=折扣 2=现金抵用
     *      'amount' => 500,//折扣-已经换算成小数形式，现金抵用-已经换算成分
     *      'user_coupon_id' => 1000015, //优惠券ID
     *  }
     */
    public function isValidCoupon($placeId, $serviceType, $basePrice, $userCouponId, $openId = '', $serviceId = 0, $userId = 0)
    {
        if (!$placeId || !$userCouponId || (!$openId && !$userId)) {
            return [];
        }

        //判断是不是拥有该优惠券
        $where = [
            'cst_id' => $userCouponId,
            'status' => 2,
        ];

        if ($openId) {
            $where['open_id'] = $openId;
        }
        if ($userId) {
            $where['user_id'] = $userId;
        }

        $info = Db::table($this->_listTable)->where($where)->find();
        if (!$info) {
            return [];
        }
        $couponId = $info['coupon_id'];

        //获取场地相关信息
        $placeInfo = (new Place())->getInfo($placeId);
        if (!$placeInfo) {
            return [];
        }
        $areaCode = $placeInfo['area'];

        //获取设备所在省和市
        $province = '';
        $city     = '';
        if ($areaCode) {
            $areaInfo = (new Area())->getInfo($areaCode);
            if ($areaInfo) {
                if ($areaInfo['area_pid']) {
                    $province = $areaInfo['area_pid'];
                    $city     = $areaInfo['area_id'];
                } else {
                    $province = $areaInfo['area_id'];
                }
            }
        }

        //返回的客户使用的优惠券数据
        $resCouponInfo = [];

        //获取优惠券的信息
        $couponInfo = $this->getInfo($couponId);
        if (!$couponInfo) {
            return [];
        }

        //判断是不是在有效期内
        $nowTime = time();
        if (($nowTime <= $couponInfo['start_time']) || ($nowTime >= $couponInfo['end_time'])) {
            return [];
        }

        $way = $couponInfo['way'];
        if ($way == 1) {
            //折扣 - 换算成比例
            $amount = floatval($couponInfo['amount']) * 0.1;
        } else if ($way == 2) {
            //现金抵用 - 单位元
            $amount = floatval($couponInfo['amount']);
        } else {
            $amount = floatval($couponInfo['amount']);
        }

        //这张优惠券是否可以使用
        $isCanUse = false;

        //判断是不是通用优惠券
        if ($couponInfo['type'] == 1) {
            //无条件券，可以直接使用
            $isCanUse = true;
        } elseif ($couponInfo['type'] == 2) {
            //普通限制券
            $tmpProvince    = $couponInfo['province'];
            $tmpCity        = $couponInfo['city'];
            $tmpPlace       = $couponInfo['place'];
            $tmpServiceType = $couponInfo['service'];

            //判断是否限制
            if ($tmpServiceType) {
                //判断是否限制
                if ($tmpPlace) {
                    //限制了使用场所
                    $tmpPlace = explode(',', $couponInfo['place']);
                    if (in_array($placeId, $tmpPlace)) {
                        //可以使用
                        $isCanUse = true;
                    }
                } elseif ($tmpCity) {
                    //限制了使用市
                    $cityArr = explode(',', $tmpCity);
                    if (in_array($city, $cityArr)) {
                        //可以使用
                        $isCanUse = true;
                    }
                } elseif ($tmpProvince) {
                    //限制了使用省
                    $provinceArr = explode(',', $tmpProvince);
                    if (in_array($province, $provinceArr)) {
                        //可以使用
                        $isCanUse = true;
                    }
                }

                if ($isCanUse && $tmpServiceType) {
                    //限制了使用产品
                    $tmpServiceType = $serviceType ? $tmpServiceType[1] : $tmpServiceType[2];
                    $tmpServiceType = explode(',', $tmpServiceType);
                    $isCanUse       = in_array($serviceId, $tmpServiceType) ? true : false;
                }
            }
        }
        if ($isCanUse) {
            //计算最后需要支付的金额
            if ($way == 1) {
                //折扣
                if ($amount < 1) {
                    $price = num_fmt($basePrice * $amount, 2);
                } else {
                    $price = num_fmt($basePrice, 2);
                }
            } else {
                //现金抵用
                $price = $basePrice - $amount;
                $price = $price <= 0 ? 0 : $price;
                $price = num_fmt($price, 2);
            }

            //优惠的金额
            $couponPrice = $basePrice - $price;
            $couponPrice = $couponPrice <= 0 ? 0 : $couponPrice;

            //返回优惠信息
            $resCouponInfo = [
                'way'            => $way,
                'amount'         => $amount,
                'coupon_id'      => $couponId,
                'user_coupon_id' => $userCouponId,
                'open_id'        => $openId,
                'price'          => $price,
                'coupon_price'   => $couponPrice,
                'origin_price'   => $basePrice,
            ];
        }

        return $resCouponInfo;
    }

    /**
     * 优惠券使用 - 单次只能使用一张优惠券
     * @author dwer
     * @date   2017-08-01
     *
     * @param  integer  $userCouponId 用户优惠券ID
     * @param  integer  $userCouponId 优惠券ID
     * @param  string  $openId 微信openid
     * @param  integer $userId 用户在本平台ID
     *
     * @return
     */
    public function useCoupon($userCouponId, $couponId, $openId = '', $userId = 0)
    {
        if ((!$openId && !$userId) || !$userCouponId || !$couponId) {
            return false;
        }

        //开启事务
        Db::startTrans();

        $where = [
            'cst_id' => $userCouponId,
            'status' => 2,
        ];

        //更新优惠券表
        $data = [
            'status'    => 3,
            'used_time' => time(),
        ];

        $res = Db::table($this->_listTable)->where($where)->update($data);
        if (!$res || $res < 1) {
            Db::rollback();
            return false;
        }
        $useNum = Db::table($this->_listTable)->where(['coupon_id' => $couponId, 'status' => 3])->count();
        //更新优惠券汇总信息
        $data = [
            'update_time' => time(),
            'used_num'    => (int) $useNum + 1,
        ];
        $res = Db::table($this->_couponTable)->where(['coupon_id' => $couponId])->update($data);
        if (!$res || $res < 1) {
            Db::rollback();
            return false;
        }

        Db::commit();
        return true;
    }

    /**
     * 前端显示的优惠券
     * @return
     */

    /**
     * 前端显示的优惠券
     * @param  int $placeId 场所
     * @param  int $province 省份
     * @param  int $city 市
     * @return array
     */
    public function getListForFront($placeId = false, $province = false, $city = false)
    {
        $field = 'coupon.coupon_id, coupon_name, start_time, end_time, way, amount, provide_num, receive_num';

        //获取通用优惠券
        $where = [
            'status' => 1,
            'type'   => 1,
        ];
        $commonRes = Db::table($this->_couponTable)->field($field)->page("0,10")->where($where)->order("coupon.coupon_id desc")->select();
        $commonRes = $commonRes ? $commonRes : [];

        $otherRes = [];
        if ($placeId || $province || $city) {
            //获取非通用优惠券
            $where = [
                'status' => 1,
                'type'   => 2,
            ];

            if ($placeId) {
                $where[] = ['exp', "FIND_IN_SET({$placeId},place) "];
            }

            if ($province) {
                $where[] = ['exp', "FIND_IN_SET({$province},province) "];
            }

            if ($city) {
                $where[] = ['exp', "FIND_IN_SET({$city},city) "];
            }

            $otherRes = Db::table($this->_couponTable)->join('coupon_ext', 'coupon.coupon_id = coupon_ext.coupon_id', 'LEFT')->field($field)->page("0,10")->where($where)->order("coupon.coupon_id desc")->select();
            $otherRes = $otherRes ? $otherRes : [];
        }
        $res = array_merge($commonRes, $otherRes);
        return $res ? $res : [];
    }

    /**
     * 后台获取优惠券列表
     * @author dwer
     * @date   2017-07-31
     *
     * @param  int $page
     * @param  int $size
     * @param  bool $couponName
     * @param  bool $status
     * @return array
     */
    public function getListForBack($page = 1, $size = 15, $couponName = false, $status = false)
    {
        $where = [];
        $query = ['page' => $page];

        if ($couponName !== false && strval($couponName) !== '') {
            $where['coupon_name'] = ['like', "%{$couponName}%"];

            $query['coupon_name'] = $couponName;
        }

        if ($status !== false) {
            if (is_array($status)) {
                $where['status'] = ['in', $status];
            } else {
                $where['status'] = $status;
            }

            $query['status'] = $status;
        }

        $orderStr = "coupon_id desc";
        $tmp      = Db::table($this->_couponTable)->where($where)->order($orderStr)->paginate($size, false, ['query' => $query]);
        $list     = $tmp->all();
        $page     = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }

    /**
     * 添加优惠券
     * @author dwer
     *
     * @param string  $couponName 优惠券名称
     * @param time  $startTime 有效期开始时间
     * @param time  $endTime 有效期结束时间
     * @param int  $way 优惠方式 1=折扣 2=现金抵用
     * @param float  $amount 优惠数额，折扣百分比或是现金（单位分）
     * @param int  $type 类型 1=无条件券，2=普通限制券，3=新注册券
     * @param int  $kind 种类 1=普通优惠券，2=反馈问题增送优惠券
     * @param int $limitNum 每个用户限制领取数量
     * @param string $province 可用省份，通过逗号分隔，空为所有
     * @param string $city 可用城市，通过逗号分隔，空为所有
     * @param string $place 可用场所，通过逗号分隔，空为所有
     * @param int $serviceType 价格类型 1=价格组，2=单独配置
     * @param string $service 可用商品，通过逗号分隔，空为所有
     * @param int $timeType 可用时间类型，1=不限时，2=每天，3=每周，4=每月
     * @param string $timeDesc 时间限制详情，{'day':[1,3,4],'time':['16:00','22:00']}
     *
     * @return bool
     */
    public function add($couponName, $startTime, $endTime, $way, $amount, $type, $kind, $limitNum = 1, $province = false, $city = false, $place = false, $couponServiceInfo = [], $timeType = false, $timeDesc = false)
    {
        try {
            Db::startTrans();
            $data = [
                'coupon_name' => $couponName,
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'way'         => $way,
                'amount'      => $amount,
                'status'      => 0,
                'type'        => $type,
                'kind'        => $kind,
                'limit_num'   => $limitNum,
                'add_time'    => time(),
                'update_time' => time(),
            ];
            $couponId = Db::table($this->_couponTable)->insert($data, false, true);
            if (!$couponId) {
                return false;
            }

            //限制信息
            $extData = ['coupon_id' => $couponId];

            if ($province !== false) {
                $extData['province'] = $province;
            }
            if ($city !== false) {
                $extData['city'] = $city;
            }
            if ($place !== false) {
                $extData['place'] = $place;
            }

            if ($timeType !== false) {
                $extData['time_type'] = $timeType;
            }
            if ($timeDesc !== false) {
                $extData['time_desc'] = $timeDesc;
            }

            if (!empty($couponServiceInfo)) {
                if ($couponServiceInfo['service']) {
                    $extData['service']      = trim(implode(',', $couponServiceInfo['service']), ',');
                    $extData['service_type'] = 1;
                    $extD[]                  = $extData;
                }

                if ($couponServiceInfo['place_service']) {
                    $extData['service']      = trim(implode(',', $couponServiceInfo['place_service']), ',');
                    $extData['service_type'] = 2;
                    $extD[]                  = $extData;
                }
            } else {
                $extD[] = ['coupon_id' => $couponId];
            }

            Db::table($this->_extTable)->insertAll($extD);
            Db::commit();
            return $couponId;
        } catch (Exception $e) {
            Db::rollback();
            echo $e->getMessage();exit;
            return false;
        }
    }

    /**
     * 添加优惠券
     * @author dwer
     *
     * @param int  $couponId 优惠券ID
     * @param string  $couponName 优惠券名称
     * @param time  $startTime 有效期开始时间
     * @param time  $endTime 有效期结束时间
     * @param int  $way 优惠方式 1=折扣 2=现金抵用
     * @param float  $amount 优惠数额，折扣百分比或是现金（单位分）
     * @param int  $type 类型 1=无条件券，2=普通限制券，3=新注册券
     * @param int $limitNum 每个用户限制领取数量
     * @param string $province 可用省份，通过逗号分隔，空为所有
     * @param string $city 可用城市，通过逗号分隔，空为所有
     * @param string $place 可用场所，通过逗号分隔，空为所有
     * @param int $serviceType 价格类型 1=价格组，2=单独配置
     * @param string $service 可用商品，通过逗号分隔，空为所有
     * @param int $timeType 可用时间类型，1=不限时，2=每天，3=每周，4=每月
     * @param string $timeDesc 时间限制详情，{'day':[1,3,4],'time':['16:00','22:00']}
     *
     * @return bool
     */
    public function update($couponId, $couponName, $startTime, $endTime, $way, $amount, $type, $kind, $limitNum = 1, $province = false, $city = false, $place = false, $couponServiceInfo, $timeType = false, $timeDesc = false)
    {
        $data = [
            'coupon_name' => $couponName,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'way'         => $way,
            'amount'      => $amount,
            'type'        => $type,
            'kind'        => $kind,
            'limit_num'   => $limitNum,
            'update_time' => time(),
        ];
        try {
            Db::startTrans();
            Db::table($this->_couponTable)->where(['coupon_id' => $couponId])->update($data);
            //限制信息
            $extData = [];
            $extData = ['coupon_id' => $couponId];
            if ($province !== false) {
                $extData['province'] = $province;
            }
            if ($city !== false) {
                $extData['city'] = $city;
            }
            if ($place !== false) {
                $extData['place'] = $place;
            }

            if ($timeType !== false) {
                $extData['time_type'] = $timeType;
            }

            if ($timeDesc !== false) {
                $extData['time_desc'] = $timeDesc;
            }

            if (!empty($couponServiceInfo)) {
                if ($couponServiceInfo['service']) {
                    $extData['service']      = trim(implode(',', $couponServiceInfo['service']), ',');
                    $extData['service_type'] = 1;
                    $extD[]                  = $extData;
                }

                if ($couponServiceInfo['place_service']) {
                    $extData['service']      = trim(implode(',', $couponServiceInfo['place_service']), ',');
                    $extData['service_type'] = 2;
                    $extD[]                  = $extData;
                }
            } else {
                $extD[] = ['coupon_id' => $couponId];
            }
            Db::table($this->_extTable)->where(['coupon_id' => $couponId])->delete();
            Db::table($this->_extTable)->insertAll($extD);
            Db::commit();
            return true;
        } catch (Exception $e) {
            pft_log('admin', 'update coupon fail:' . $e->getMessage());
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取优惠券信息
     * @param  int $couponId 优惠券ID
     * @param  bool $isFormat 是否格式化
     * @return array
     */
    public function getInfo($couponId, $isFormat = false)
    {
        $couponExt = [];
        if (!$couponId) {
            return [];
        }

        $baseInfo = Db::table($this->_couponTable)->where(['coupon_id' => $couponId])->find();

        if (!$baseInfo) {
            return [];
        }

        $extList = Db::table($this->_extTable)->where(['coupon_id' => $couponId])->select();
        if (!$extList) {
            return $baseInfo;
        }

        foreach ($extList as $extInfo) {
            $couponExt['service'][$extInfo['service_type']] = $extInfo['service'];
            $couponExt['place']                             = $extInfo['place'];
            $couponExt['city']                              = $extInfo['city'];
            $couponExt['province']                          = $extInfo['province'];
            $couponExt['time_desc']                         = $extInfo['time_desc'];
            $couponExt['service_type']                      = $extInfo['service_type'];
        }

        $info = array_merge($couponExt, $baseInfo);

        if ($isFormat) {
            //参数处理
            $info['start_time'] = date('Y-m-d', $info['start_time']);
            $info['end_time']   = date('Y-m-d', $info['end_time']);

            if ($info['province']) {
                $info['province'] = explode(',', $info['province']);
            }
            if ($info['city']) {
                $info['city'] = explode(',', $info['city']);
            }
            if ($info['place']) {
                $info['place'] = explode(',', $info['place']);
            }
            if ($info['time_desc']) {
                $info['time_desc'] = json_decode($info['time_desc']);
            }
        }
        return $info;
    }

    /**
     * 删除优惠券
     * @author dwer
     * @date   2017-07-31
     *
     * @param int $couponId 优惠券ID
     * @return bool
     */
    public function del($couponId)
    {
        if (!$couponId) {
            return false;
        }

        //开启事务
        Db::startTrans();

        //删除基础数据
        $where = [
            'coupon_id'   => $couponId,
            'receive_num' => 0,
        ];

        $res = Db::table($this->_couponTable)->where($where)->delete();
        if ($res < 1) {
            Db::rollback();
            return false;
        }

        //删除扩展数据
        $where = [
            'coupon_id' => $couponId,
        ];
        $res = Db::table($this->_extTable)->where($where)->delete();
        if ($res === false) {
            Db::rollback();
            return false;
        }

        //删除发放的优惠券
        $where = [
            'coupon_id' => $couponId,
        ];
        $res = Db::table($this->_listTable)->where($where)->delete();
        if ($res === false) {
            Db::rollback();
            return false;
        }

        //删除日志
        $where = [
            'coupon_id' => $couponId,
        ];
        $res = Db::table($this->_provideTable)->where($where)->delete();
        if ($res === false) {
            Db::rollback();
            return false;
        }

        //成功删除
        Db::commit();
        return true;
    }

    /**
     * 发放优惠券
     * @author dwer
     * @date   2017-07-31
     *
     * @param  int $couponId 优惠券ID
     * @param  int $num 发放数量
     * @param  int $opId 操作用户ID
     *
     * @return bool
     */
    public function provide($couponId, $num, $opId)
    {
        $num = intval($num);
        if (!$couponId || $num < 1 || !$opId) {
            return false;
        }

        //生成优惠码，多生成50个随机优惠码，如果重复就剔除，剔除后数量足够的就继续往下走
        $tmpNum  = $num + 50;
        $codeArr = $this->makeRandomCode($tmpNum);

        //将已经存在的剔除
        $where    = ['code' => ['in', $codeArr]];
        $field    = 'code';
        $tmp      = Db::table($this->_listTable)->where($where)->field($field)->select();
        $existArr = array_column($tmp, 'code');

        $avaibleCode = array_diff($codeArr, $existArr);
        if (!$avaibleCode || count($avaibleCode) < $num) {
            //生成的随机优惠码不够
            return false;
        }

        //批量数据
        $resCode = array_slice($avaibleCode, 0, $num);
        $data    = [];
        $time    = time();

        foreach ($resCode as $code) {
            $data[] = [
                'coupon_id'   => $couponId,
                'create_time' => $time,
                'status'      => 1,
                'code'        => $code,
            ];
        }

        //开启事务
        Db::startTrans();

        //插入优惠券列表
        $res = Db::table($this->_listTable)->insertAll($data);
        if (!$res) {
            Db::rollback();
            return false;
        }

        //更新汇总信息
        $updateData = [
            'update_time' => time(),
            'provide_num' => ['exp', "provide_num+{$num}"],
            'status'      => 1,
        ];

        $res = Db::table($this->_couponTable)->where(['coupon_id' => $couponId])->update($updateData);
        if ($res === false) {
            Db::rollback();
            return false;
        }

        //记录发放历史
        $logData = [
            'coupon_id'    => $couponId,
            'provide_num'  => $num,
            'provide_user' => $opId,
            'provide_time' => time(),
        ];

        $res = Db::table($this->_provideTable)->insert($logData, false, true);
        if (!$res) {
            Db::rollback();
            return false;
        }

        //发放删除
        Db::commit();
        return true;
    }

    /**
     * 优惠券是否可以删除
     * @author dwer
     * @date   2017-07-31
     *
     * @param int $couponId 优惠券ID
     * @return bool
     */
    public function isCanDel($couponId)
    {
        if (!$couponId) {
            return false;
        }

        $info = $this->getInfo($couponId);
        if (!$info) {
            return false;
        }

        if ($info['receive_num'] > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 优惠券是否可以修改
     * @author dwer
     * @date   2017-07-31
     *
     * @param int $couponId 优惠券ID
     * @return bool
     */
    public function isCanEdit($couponId)
    {
        return $this->isCanDel($couponId);
    }

    /**
     * 优惠券是否可以进行发放
     * @author dwer
     * @date   2017-07-31
     *
     * @param int $couponId 优惠券ID
     * @return bool
     */
    public function isCanProvide($couponId)
    {
        if (!$couponId) {
            return false;
        }

        $info = $this->getInfo($couponId);
        if (!$info) {
            return false;
        }

        //过期了
        if ($info['status'] == 2) {
            return false;
        }

        //判断时间
        if (time() >= $info['end_time']) {
            return false;
        }

        return true;
    }

    /**
     * 优惠券是不是可以领取
     * @param  int  $couponId
     * @return bool
     */
    public function isCanReceive($couponId)
    {
        return $this->isCanProvide($couponId);
    }

    /**
     * 用户在特定时间内是不是已经领取了优惠券
     * @author dwer
     * @date   2017-08-02
     *
     * @param  int $couponId 优惠券ID
     * @param  int $startTime 开始时间
     * @param  int $endTime 结束时间
     * @param  string $openId
     * @param  int $userId
     * @return bool
     */
    public function isUserReceive($couponId, $startTime, $endTime, $openId = '', $userId = 0)
    {
        if (!$couponId || (!$openId && !$userId)) {
            return false;
        }

        $where = [
            'coupon_id'    => $couponId,
            'receive_time' => ['between', [$startTime, $endTime]],
        ];
        if ($openId) {
            $where['open_id'] = $openId;
        }
        if ($userId) {
            $where['user_id'] = $userId;
        }

        $res = Db::table($this->_listTable)->where($where)->find();
        return $res ? true : false;
    }

    /**
     * 获取发放历史
     * @param  int $couponId
     * @return array
     */
    public function getProvideLog($couponId)
    {
        if (!$couponId) {
            return [];
        }

        $res = Db::table($this->_provideTable)->where(['coupon_id' => $couponId])->select();
        return $res;
    }

    /**
     * 将已经过期优惠券设置为过期
     * @return
     */
    public function monitorExpireCoupon()
    {
        //获取已经过期的优惠券
        $time  = time();
        $where = [
            'status'   => 1,
            'end_time' => ['elt', $time],
        ];

        $res = Db::table($this->_couponTable)->where($where)->field('coupon_id')->select();
        if (!$res) {
            return [];
        }

        $couponId = array_column($res, 'coupon_id');
        if (!$couponId) {
            return [];
        }

        //开启事务
        Db::startTrans();

        //将还没有使用的具体优惠券设置为过期
        $where = [
            'coupon_id' => ['in', $couponId],
            'status'    => ['in', [1, 2]],
        ];
        $data = [
            'status' => 4,
        ];
        $res = Db::table($this->_listTable)->where($where)->update($data);
        if ($res === false) {
            Db::rollback();
            return [$couponId, $res];
        }

        //将优惠券列表设置为过期
        $where = [
            'coupon_id' => ['in', $couponId],
        ];
        $data = [
            'status' => 2,
        ];
        $res = Db::table($this->_couponTable)->where($where)->update($data);
        if ($res === false) {
            Db::rollback();
            return [$couponId, $res];
        }

        //返回
        Db::commit();
        return [$couponId, $res];
    }

    /**
     * 生成优惠码
     * @author dwer
     * @date   2017-07-31
     *
     * @param  int $num 生成数量
     * @return array
     */
    public function makeRandomCode($num)
    {
        $codeArr = [];
        for ($i = 1; $i <= $num; $i++) {
            $tmpCode = $this->_makeCode();
            if ($tmpCode && !in_array($tmpCode, $codeArr)) {
                $codeArr[] = $tmpCode;
            }
        }

        return $codeArr;
    }

    /**
     * 获取用户已经领取的优惠券数量
     * @author dwer
     * @date   2017-08-01
     *
     * @param  integer  $couponId 优惠券ID
     * @param  string  $openId 微信openid
     * @param  integer $userId 用户在本平台ID
     * @return [type]
     */
    public function _getReceiveNum($couponId, $openId = '', $userId = 0)
    {
        $where = [
            'coupon_id' => $couponId,
        ];

        if ($openId) {
            $where['open_id'] = $openId;
        }
        if ($userId) {
            $where['user_id'] = $userId;
        }

        $receiveNum = Db::table($this->_listTable)->where($where)->count();
        return $receiveNum ? $receiveNum : 0;
    }

    /**
     * 生成不重复的优惠码
     * @author dwer
     * @date   2017-07-28
     * @return
     */
    private function _makeCode()
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));

        $tmp = md5($rand, true);
        $s   = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
        $res = '';
        for ($i = 0; $i < 8; $i++) {
            $g = ord($tmp[$i]);
            $res .= $s[($g ^ ord($tmp[$i + 8])) - $g & 0x1F];
        }
        return $res;
    }

    /**
     * 根据条件获取优惠券
     * @author xiexy
     * @date   2017-08-29
     * @return
     */
    public static function getCouponsByCondition($condition = [], $field = "*", $order = ['add_time' => "DESC"])
    {
        $coupons = Db::table('coupon')->where($condition)->field($field)->order($order)->select();
        return $coupons ? $coupons : [];
    }

    /**
     * 根据商品获取优惠券
     * @author xiexy
     * @date   2017-08-29
     * @return
     */
    public function getCouponByService($openId, $placeId, $service)
    {
        $where = [
            'status' => 2,
        ];
        if ($openId) {
            $where['open_id'] = $openId;
        }

        //是否有优惠券可以使用
        $userCouponList = Db::table($this->_listTable)->where($where)->select();
        if (!$userCouponList) {
            return [];
        }

        //获取设备相关信息
        $machineInfo = (new Machine())->getInfo($machineId, 'place_id, service_id');
        if (!$machineInfo) {
            return [];
        }
        $placeId   = $machineInfo['place_id'];
        $serviceId = $machineInfo['service_id'];
        //获取场地相关信息
        $placeInfo = (new Place())->getInfo($placeId);
        if (!$placeInfo) {
            return [];
        }
        $areaCode = $placeInfo['area'];

        //获取设备所在省和市
        $province = '';
        $city     = '';
        if ($areaCode) {
            $areaInfo = (new Area())->getInfo($areaCode);
            if ($areaInfo) {
                if ($areaInfo['area_pid']) {
                    $province = $areaInfo['area_pid'];
                    $city     = $areaInfo['area_id'];
                } else {
                    $province = $areaInfo['area_id'];
                }
            }
        }

        //返回的客户使用的优惠券数据
        $resCouponInfo = [];

        $nowTime = time();
    }

    /**
     * 释放被使用的优惠券
     * @author dwer
     * @date   2017-11-08
     *
     * @param  int $cstId 优惠券记录ID
     * @return bool
     */
    public function realse($cstId)
    {
        $cstId = intval($cstId);
        if (!$cstId) {
            return false;
        }

        $where = ['cst_id' => $cstId, 'status' => 3];
        $data  = ['status' => 2];

        $res = Db::table($this->_listTable)->where($where)->update($data);
        return $res >= 1 ? true : false;
    }

    /**
     * 获取用户手上已经获得的优惠券
     * @param  string  $openId
     * @param  integer $userId
     * @return array
     */
    private function _getReceiveCoupon($openId = '', $userId = 0)
    {
        if (!$openId && !$userId) {
            return [];
        }

        $key = $openId . '_' . $userId;
        if (!isset($this->_receiveCouponList[$key])) {

            //获取用户手上已经获得的优惠券
            $where = [
                'status' => 2,
            ];
            if ($openId) {
                $where['open_id'] = $openId;
            }
            if ($userId) {
                $where['user_id'] = $userId;
            }

            //是否有优惠券可以使用
            $userCouponList = Db::table($this->_listTable)->where($where)->select();

            $this->_receiveCouponList[$key] = $userCouponList ? $userCouponList : [];
        }

        return $this->_receiveCouponList[$key];
    }

    /**
     * 获取设备信息
     * @param  int $machineId
     * @return array
     */
    private function _getMachineInfo($machineId)
    {
        if (!$machineId) {
            return [];
        }

        if (!isset($this->_machineInfoList[$machineId])) {
            $machineInfo = (new Machine())->getInfo($machineId, 'place_id, service_id');

            $this->_machineInfoList[$machineId] = $machineInfo ? $machineInfo : [];
        }

        return $this->_machineInfoList[$machineId];
    }

    /**
     * 获取区域信息
     * @param  int $areaCode
     * @return array
     */
    private function _getAreaInfo($areaCode)
    {
        if (!$areaCode) {
            return [];
        }

        if (!isset($this->_areaInfoList[$areaCode])) {
            $areaInfo = (new Area())->getInfo($areaCode);

            $this->_areaInfoList[$areaCode] = $areaInfo ? $areaInfo : [];
        }

        return $this->_areaInfoList[$areaCode];
    }

    /**
     * 获取场所信息
     * @param  int $placeId
     * @return array
     */
    private function _getPlaceInfo($placeId)
    {
        if (!$placeId) {
            return [];
        }

        if (!isset($this->_placeInfoList[$placeId])) {
            $placeInfo = (new Place())->getInfo($placeId);

            $this->_placeInfoList[$placeId] = $placeInfo ? $placeInfo : [];
        }

        return $this->_placeInfoList[$placeId];
    }

    /**
     * 获取场所信息
     * @param  int $placeId
     * @return array
     *
     */
    public function getCouponReceiveInfo($cstId, $field = "*")
    {
        $info = Db::table($this->_listTable)->where(['cst_id' => $cstId])->field($field)->find();
        return $info;
    }


}

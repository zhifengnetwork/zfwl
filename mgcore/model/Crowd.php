<?php
/**
 * 众筹相关模型
 * @author dwer
 * @date   2017-09-01
 */

namespace mgcore\model;

use mgcore\model\User;
use think\Config;
use think\Model;
use think\Db;

class Crowd extends Model
{
    //默认数据库配置
    protected $connection = 'database';

    private $_productTable       = 'crowd_product'; //众筹产品
    private $_taskTable          = 'crowd_product_task'; //众筹产品任务表
    private $_crowdOrderTable    = 'crowd_order'; //众筹订单表
    private $_incomeTable        = 'crowd_income'; //用户众筹每日收益表
    private $_userTrowdTable     = 'user_crowd'; //用户众筹表
    private $_productIncomeTable = 'crowd_product_income'; // 众筹产品每日收益表

    //收益计算分割时间点
    private $_calcHour = 15;

    //初始化数据库连接 - 如果需要连接从库 - database_slave
    public function __construct($conn = '')
    {
        if ($conn && Config::get($conn)) {
            $this->connection = $conn;
        }
    }

    /**
     * 通过微信登陆
     * @author dwer
     * @date   2017-07-31
     *
     * @param $openId
     */
    public function loginByOpenId($openId)
    {
        if (!$openId) {
            return [];
        }

        $userModel = new User();
        $userInfo  = $userModel->getInfoByOpenId($openId, 'mobile, uid');
        if (!$userInfo) {
            return [];
        }

        if (!isset($userInfo['mobile']) || !$userInfo['mobile']) {
            return [];
        }

        //必须要有众筹信息
        $uid = $userInfo['uid'];
        $res = $this->table($this->_userTrowdTable)->where(['uid' => $uid])->field('uid, add_time')->find();
        if (!$res) {
            return [];
        }

        $loginData = [
            'uid'       => $uid,
            'wx_openid' => $openId,
            'add_time'  => $res['add_time'],
        ];
        return $loginData;
    }

    /**
     * 获取产品信息
     * @param int $cpId
     * @param  boolean $getExt
     * @return array
     */
    public function getProduct($cpId, $getExt = false)
    {
        if (!$cpId) {
            return [];
        }

        $table = "{$this->_productTable} p";
        $where = ['p.cp_id' => $cpId];
        if ($getExt) {
            $join = ["p.cp_id = task.cp_id"];
            $info = $this->table($table)->join("$this->_taskTable task", $join)->where($where)->find();
        } else {
            $info = $this->table($table)->where($where)->find();
        }

        return $info ? $info : [];
    }

    /**
     * 前台获取众筹商品列表
     * 获取已经上架过的商品
     *
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $page 页码
     * @param  int $size 条数
     * @param  string $status 状态 all=已经上架过的 online=上架的 offline=下架的
     * @param  int $type
     * @return array
     */
    public function getProductList($page = 1, $size = 15, $status = 'all', $type = 1)
    {
        $where = [
            'type' => $type,
        ];

        if ($status == 'all') {
            $where['status'] = ['in', [1, 2]];
        } else if ($status == 'online') {
            $where['status'] = 1;
        } else {
            $where['status'] = 2;
        }
        $pageStr  = "{$page},{$size}";
        $field    = 'cp_id,name,price,stock,left_stock,desc,used_num,ratio,status,online_time';
        $orderStr = "left_stock desc";

        $list  = [];
        $total = $this->table($this->_productTable)->where($where)->count();
        if ($total != 0) {
            $list = $this->table($this->_productTable)->where($where)->page($pageStr)->field($field)->order($orderStr)->select();
        }

        return ['total' => $total, 'list' => $list];
    }

    /**
     * 后台获取众筹商品列表
     * @author dwer
     * @date   2017-07-31
     *
     * @param  int $page
     * @param  int $size
     * @param  bool $name
     * @param  bool $status
     * @return array
     */
    public function getProductListBack($page = 1, $size = 15, $name = false, $status = false)
    {
        $where = [];
        $query = ['page' => $page];
        $join  = "p.cp_id = task.cp_id";

        if ($name !== false && strval($name) !== '') {
            $where['name'] = ['like', "%{$name}%"];
            $query['name'] = $name;
        }

        if ($status !== false) {
            if (is_array($status)) {
                $where['status'] = ['in', $status];
            } else {
                $where['status'] = $status;
            }

            $query['status'] = $status;
        }

        $orderStr = "p.cp_id desc";
        $tmp      = $this->table($this->_productTable . ' p')->where($where)->order($orderStr)->join($this->_taskTable . ' task', $join)->paginate($size, false, ['query' => $query]);
        $list     = $tmp->all();
        $page     = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }

    /**
     * 通过产品ID批量获取产品数据
     * @author dwer
     * @date   2017-09-14
     *
     * @param  array $cpIdArr
     * @param  string $field
     * @return
     */
    public function getProductListByIds($cpIdArr, $field = '*')
    {
        if (!$cpIdArr || !is_array($cpIdArr)) {
            return [];
        }

        $where = [
            'cp_id' => ['in', $cpIdArr],
        ];
        $res = $this->table($this->_productTable)->where($where)->field($field)->select();

        $list = [];
        foreach ($res as $item) {
            $list[$item['cp_id']] = $item;
        }

        return $list;
    }

    /**
     * 获取订单列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  integer $page 页码
     * @param  integer $size 条目数
     * @param  string  $status 状态 1=已支付 0=待支付 2=已退款
     * @param  integer $startTime 开始时间
     * @param  integer $endTime 结束时间
     * @param  boolean $cpName 产品名称
     * @param  boolean $mobile 手机号
     * @param  boolean $orderNo 订单号
     * @return [type]
     */
    public function getOrderList($page = 1, $size = 20, $status = 1, $startTime = 0, $endTime = 0, $cpName = false, $mobile = false, $orderNo = false)
    {
        $query = ['page' => $page];
        $where = [];

        if ($status !== false) {
            $query = ['status' => $status];
            $where = ['pay_status' => $status];
        }

        if ($startTime) {
            $tmpStartTime        = strtotime($startTime . ' 00:00:00');
            $where['order_time'] = ['EGT', $tmpStartTime];
            $query['start']      = $startTime;
        }
        if ($endTime) {
            $tmpEndTime          = strtotime($endTime . ' 23:59:59');
            $where['order_time'] = ['ELT', $tmpEndTime];
            $query['end']        = $endTime;
        }

        if ($cpName) {
            $where['cp_name'] = ['like', "%{$cpName}%"];
            $query['kw']      = $cpName;
        }

        if ($mobile !== false) {
            $where['mobile'] = $mobile;
            $query['kw']     = $mobile;
        }

        if ($orderNo !== false) {
            $where['order_no'] = $orderNo;
            $query['kw']       = $orderNo;
        }

        $orderStr = "order_time desc";
        $tmp      = $this->table($this->_crowdOrderTable)->where($where)->order($orderStr)->paginate($size, false, ['query' => $query]);
        $list     = $tmp->all();
        $page     = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }

    /**
     * 获取订单导出列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  string  $status 状态 1=已支付 0=待支付 2=已退款
     * @param  integer $startTime 开始时间
     * @param  integer $endTime 结束时间
     * @param  boolean $cpName 产品名称
     * @param  boolean $mobile 手机号
     * @param  boolean $orderNo 订单号
     * @return [type]
     */
    public function getOrderExportList($status = 1, $startTime = 0, $endTime = 0, $cpName = false, $mobile = false, $orderNo = false)
    {
        $where = [];
        if ($status !== false) {
            $where = ['pay_status' => $status];
        }

        if ($startTime) {
            $tmpStartTime        = strtotime($startTime . ' 00:00:00');
            $where['order_time'] = ['EGT', $tmpStartTime];
        }
        if ($endTime) {
            $tmpEndTime          = strtotime($endTime . ' 23:59:59');
            $where['order_time'] = ['ELT', $tmpEndTime];
        }

        if ($cpName) {
            $where['cp_name'] = ['like', "%{$cpName}%"];
        }

        if ($mobile !== false) {
            $where['mobile'] = $mobile;
        }

        if ($orderNo !== false) {
            $where['order_no'] = $orderNo;
        }

        $orderStr = "order_time desc";
        $tmp      = $this->table($this->_crowdOrderTable)->where($where)->order($orderStr)->select();
        return $tmp ? $tmp : [];
    }

    /**
     * 获取用户收益列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $page 页码
     * @param  int $size 条目数
     * @param  int $startDay 开始日期
     * @param  int $endDay 结束日期
     * @param  int $status 认筹状态
     * @param  int $redStatus 红包打款状态
     * @param  int $receiveStatus 红包领取状态
     * @param  int $mobile 手机号
     * @return array
     */
    public function getDayIncomeList($page = 1, $size = 20, $startDay = false, $endDay = false, $status = false, $redStatus = false, $receiveStatus = false, $mobile = '')
    {
        $query = ['page' => $page];
        $where = [];

        if ($startDay && $endDay) {
            $queryStartDay = str_replace('-', '', $startDay);
            $queryEndDay   = str_replace('-', '', $endDay);

            $query['start_day'] = $startDay;
            $query['end_day']   = $endDay;

            $where['income.day'] = ['between', [$queryStartDay, $queryEndDay]];
        }

        if ($status !== false) {
            $query['crowd_status'] = $status;
            $where['order.status'] = $status;
        }

        if ($redStatus !== false) {
            $query                  = ['red_status' => $redStatus];
            $where['income.status'] = $redStatus;
        }

        if ($receiveStatus !== false) {
            $query['receive_status']        = $receiveStatus;
            $where['income.receive_status'] = $receiveStatus;
        }

        if ($mobile) {
            $query['mobile']       = $mobile;
            $where['order.mobile'] = $mobile;
        }

        $field    = "income.id, income.open_id,income.day, order.mobile,income.status red_status, income.receive_status, order.pay_time crowd_time,order.status crowd_status,order.total_money,income.day_capital,income.day_income,income.day_refund,order.ratio,income.pay_time red_time";
        $orderStr = "income.id desc";
        $join     = "income.order_id = order.order_id";
        $table    = $this->_incomeTable . " income";
        $tmp      = $this->table($table)->field($field)->where($where)->join($this->_crowdOrderTable . " order", $join, 'left')->order($orderStr)->paginate($size, false, ['query' => $query]);

        $list = $tmp->all();
        $page = $tmp->render();

        return ['list' => $list, 'page' => $page];
    }

    /**
     * 获取用户收益导出列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $startDay 开始日期
     * @param  int $endDay 结束日期
     * @param  int $status 认筹状态
     * @param  int $redStatus 红包打款状态
     * @param  int $receiveStatus 红包领取状态
     * @param  int $mobile 手机号
     * @return array
     */
    public function getDayIncomeExportList($startDay = false, $endDay = false, $status = false, $redStatus = false, $receiveStatus = false, $mobile = '')
    {
        $query = [];
        $where = [];

        if ($startDay && $endDay) {
            $queryStartDay = str_replace('-', '', $startDay);
            $queryEndDay   = str_replace('-', '', $endDay);

            $query['start_day'] = $startDay;
            $query['end_day']   = $endDay;

            $where['income.day'] = ['between', [$queryStartDay, $queryEndDay]];
        }

        if ($status !== false) {
            $query['crowd_status'] = $status;
            $where['order.status'] = $status;
        }

        if ($redStatus !== false) {
            $query                  = ['red_status' => $redStatus];
            $where['income.status'] = $redStatus;
        }

        if ($receiveStatus !== false) {
            $query['receive_status']        = $receiveStatus;
            $where['income.receive_status'] = $receiveStatus;
        }

        if ($mobile) {
            $query['mobile']       = $mobile;
            $where['order.mobile'] = $mobile;
        }

        $field    = "income.id, income.open_id,income.day, order.mobile,income.status red_status, income.receive_status, order.pay_time crowd_time,order.status crowd_status,order.total_money,income.day_capital,income.day_income,income.day_refund,order.ratio,income.pay_time red_time";
        $orderStr = "income.id desc";
        $join     = "income.order_id = order.order_id";
        $table    = $this->_incomeTable . " income";
        $list     = $this->table($table)->field($field)->where($where)->join($this->_crowdOrderTable . " order", $join, 'left')->order($orderStr)->select();

        return $list ? $list : [];
    }

    /**
     * 获取用户认筹的数量
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $uid
     * @return int
     */
    public function getNums($uid)
    {
        $where = [
            'uid'        => $uid,
            'pay_status' => 1,
        ];

        $num = $this->table($this->_crowdOrderTable)->where($where)->sum('num');
        return $num ? intval($num) : 0;
    }

    /**
     * 获取用户认筹产品汇总信息
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $uid
     * @return int
     */
    public function getCrowdSummary($uid, $status = 'runing')
    {
        $summary = [
            'machine_nums' => 0,
            'total_money'  => 0,
        ];

        if (!$uid) {
            return $summary;
        }

        $statusArr = [
            'init'   => 0,
            'runing' => 1,
            'finish' => 2,
        ];

        $where['uid'] = $uid;
        if (isset($statusArr[$status])) {
            $where['status'] = $statusArr[$status];
        }

        $field = 'sum(num) machine_nums,sum(total_money) total_money';
        $tmp   = $this->table($this->_crowdOrderTable)->where($where)->field($field)->find();

        if ($tmp) {
            $summary['machine_nums'] = intval($tmp['machine_nums']);
            $summary['total_money']  = intval($tmp['total_money']);
        }

        return $summary;
    }

    /**
     * 判断交易流水号是否存在
     * @author dwer
     * @date   2017-09-14
     *
     * @param  string $orderNo
     * @return bool
     */
    public function isOrderNoExist($orderNo)
    {
        $where = ['order_no' => $orderNo];
        $res   = $this->table($this->_crowdOrderTable)->where($where)->field('order_no')->find();
        return $res ? true : false;
    }

    /**
     * 初始化众筹用户
     * @author dwer
     * @date   2017-07-31
     *
     * @param  int $openId
     * @param  int $mobile
     * @return array
     */
    public function registerUser($openId, $mobile)
    {
        if (!$openId || !$mobile) {
            return false;
        }

        //通过微信openid获取
        $userModel = new User();
        $userInfo  = $userModel->getInfoByOpenId($openId);
        if (!$userInfo) {
            return false;
        }
        $uid = $userInfo['uid'];

        //判断是不是已经注册过众筹用户了
        $res = $this->table($this->_userTrowdTable)->where(['uid' => $uid])->find();
        if ($res) {
            return $userInfo;
        } else {
            $data = [
                'uid'         => $uid,
                'add_time'    => time(),
                'update_time' => time(),
            ];
            $res = $this->table($this->_userTrowdTable)->insert($data);

            if (!$res) {
                return false;
            }

            //更新用户的手机号码
            $res = $userModel->updateMobile($uid, $mobile, $isChecked = true);
            if ($res) {
                return $userInfo;
            } else {
                return false;
            }
        }
    }

    /**
     * 用户众筹用户数据
     * @author dwer
     * @date   2017-09-06
     *
     * @param  int $uid 用户ID
     * @param  bool $getExt 是否获取姓名等信息
     * @return array
     */
    public function getUser($uid, $getExt = true)
    {
        if (!$uid) {
            return [];
        }

        $userInfo = $this->table($this->_userTrowdTable)->where(['uid' => $uid])->find();
        if (!$userInfo) {
            return [];
        }

        if ($getExt) {
            //获取openid等信息
            $userModel = new User();
            $tmpInfo   = $userModel->getInfo($uid, 'wx_openid, name');
            if ($tmpInfo) {
                $userInfo['name']      = $tmpInfo['name'];
                $userInfo['wx_openid'] = $tmpInfo['wx_openid'];
            } else {
                $userInfo['name']      = '';
                $userInfo['wx_openid'] = '';
            }
        }

        return $userInfo;
    }

    /**
     * 添加众筹产品
     * @author dwer
     * @date   2017-09-01
     *
     * @param  string $name 产品名称
     * @param  int $price 价格
     * @param  int $stock 库存
     * @param  float $ratio 年化收益率
     * @param  string $desc 具体描述
     * @param  string $realProduct 对应真实产品ID
     * @param  bool $isOnline 是否上架
     * @param  string $onlineTime 定时上架时间 - 2017-09-10 22:00:00 到小时
     * @param  string $offlineTime 定时下架时间 - 2017-09-20 23:00:00 到小时
     * @param  int $type 产品类型 1=小咪按摩椅
     */
    public function addProduct($name, $price, $stock, $ratio, $desc, $realProduct, $isOnline = true, $onlineTime = '', $offlineTime = '', $type = 1)
    {
        $name        = strval($name);
        $price       = intval($price);
        $stock       = strval($stock);
        $realProduct = strval($realProduct);
        $ratio       = floatval($ratio);
        $onlineTime  = strtotime($onlineTime) ? strtotime($onlineTime) : false;
        $offlineTime = strtotime($offlineTime) ? strtotime($offlineTime) : false;
        $desc        = strval($desc) ? strval($desc) : '';

        if (!$name || !$realProduct || $price <= 0 || $stock <= 0 || $ratio <= 0) {
            return false;
        }

        $data = [
            'name'         => $name,
            'price'        => $price,
            'stock'        => $stock,
            'left_stock'   => $stock,
            'ratio'        => $ratio,
            'type'         => $type,
            'desc'         => $desc,
            'real_product' => $realProduct,
            'add_time'     => time(),
            'update_time'  => time(),
            'status'       => 0,
        ];

        //上架判断
        if ($isOnline) {
            $data['online_time'] = time();
            $data['status']      = 1;
        } else {
            //判断定时上架时间是不是正常
            if ($onlineTime && $onlineTime > time()) {

            } else {
                $data['online_time'] = time();
                $data['status']      = 1;
            }
        }

        //下架判断
        if ($offlineTime) {
            if ($isOnline) {
                if ($offlineTime <= time()) {
                    $offlineTime = false;
                }
            } else {
                if ($offlineTime <= $onlineTime) {
                    $offlineTime = false;
                }
            }
        }

        $cpId = $this->table($this->_productTable)->insert($data, false, true);
        if (!$cpId) {
            return false;
        }

        $taskData = [
            'cp_id'       => $cpId,
            'update_time' => time(),
        ];
        if ($onlineTime) {
            $taskData['auto_online_time'] = $onlineTime;
        }
        if ($offlineTime) {
            $taskData['auto_offline_time'] = $offlineTime;
        }

        $res = $this->table($this->_taskTable)->insert($taskData);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加产品的每日收益
     * @author dwer
     * @date   2017-09-07
     *
     * @param  int $cpId 产品ID
     * @param  int $type 类型 - 冗余
     * @param  int $day 日期 - 20170907
     * @param  int $realIncome - 对应产品真实的收益 - 单位分
     * @param  int $calcIncome - 多还少补后的收益 - 单位分
     * @param  int $realProduct 原产品ID - 冗余
     */
    public function addProductIncome($cpId, $type, $day, $realIncome, $calcIncome, $realProduct)
    {
        if (!$cpId || !$day) {
            return false;
        }

        $where = [
            'cp_id' => $cpId,
            'type'  => $type,
            'day'   => $day,
        ];
        $tmp = $this->table($this->_productIncomeTable)->where($where)->find();
        if ($tmp) {
            $data = [
                'real_income'  => $realIncome,
                'calc_income'  => $calcIncome,
                'update_time'  => time(),
                'real_product' => $realProduct,
            ];
            $res = $this->table($this->_productIncomeTable)->where($where)->update($data);
        } else {
            $data = [
                'cp_id'        => $cpId,
                'type'         => $type,
                'day'          => $day,
                'real_income'  => $realIncome,
                'calc_income'  => $calcIncome,
                'update_time'  => time(),
                'real_product' => $realProduct,
            ];
            $res = $this->table($this->_productIncomeTable)->insert($data);
        }

        return $res ? true : false;
    }

    /**
     * 修改众筹产品
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId 产品ID
     * @param array $newData 修改需要的内容
     *          {
     *              'name' => '',
     *              'price' => '',
     *              'stock' => '',
     *              'desc' => '',
     *              'isOnline' => '',
     *              'onlineTime' => '',
     *              'offlineTime' => '',
     *          }
     * @return
     */
    public function editProduct($cpId, $newData = [])
    {
        if (!$cpId || !$newData) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        //需要修改的数据
        $editData = [];
        $taskData = [];

        if (isset($newData['name']) && $newData['name']) {
            $editData['name'] = strval($newData['name']);
        }

        if (isset($newData['real_product']) && $newData['real_product']) {
            $editData['real_product'] = strval($newData['real_product']);
        }

        if (isset($newData['price']) && $newData['price']) {
            $editData['price'] = intval($newData['price']);
        }

        if (isset($newData['stock']) && $newData['stock'] && ($newData['stock'] >= $info['used_num'])) {
            $editData['stock']      = intval($newData['stock']);
            $editData['left_stock'] = intval($editData['stock'] - $info['used_num']);
        }

        if (isset($newData['desc']) && $newData['desc']) {
            $editData['desc'] = strval($newData['desc']);
        }

        $onlineTime  = false;
        $offlineTime = false;

        //如果不是上架状态，需要做处理
        if ($info['status'] == 0) {
            if (isset($newData['isOnline']) && $newData['isOnline']) {
                $editData['status']      = 1;
                $editData['online_time'] = time();
            }

            if (isset($newData['onlineTime']) && $newData['onlineTime']) {
                $onlineTime = strtotime($newData['onlineTime']);
                if ($onlineTime && $onlineTime > time()) {
                    //定时上架
                    $taskData['auto_online_time'] = $onlineTime;
                }
            }
        }

        if (isset($newData['offlineTime']) && $newData['offlineTime']) {
            $offlineTime = strtotime($newData['offlineTime']);
            if (!$offlineTime || $offlineTime <= time()) {
                $offlineTime = false;
            } else {
                $taskData['auto_offline_time'] = $offlineTime;
            }
        }

        if (!$editData) {
            return false;
        }

        //修改产品基础数据
        $editData['update_time'] = time();

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_productTable)->where($where)->update($editData);
        if (!$res) {
            return false;
        }

        //修改产品扩展数据
        if ($taskData) {
            $taskData['update_time'] = time();

            $where = ['cp_id' => $cpId];
            $res   = $this->table($this->_taskTable)->where($where)->update($taskData);
            if (!$res) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * 修改众筹产品
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId 产品ID
     * @return bool
     *
     */public function delProduct($cpId)
    {
        if (!$cpId) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        //已经有用户认筹了,就不能删除
        if ($info['used_num'] > 0) {
            return false;
        }

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_productTable)->where($where)->delete();
        if ($res === false) {
            return false;
        }

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_taskTable)->where($where)->delete();
        if ($res === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 修改众筹产品年化收益率配置
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId
     * @param  float $newRatio
     * @return bool
     */
    public function adjustRatioSetting($cpId, $newRatio)
    {
        $newRatio = floatval($newRatio);

        if (!$cpId || $newRatio <= 0) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        if ($newRatio == $info['ratio']) {
            return false;
        }

        //凌晨的时候才去具体修改年化收益率
        $autoTime = strtotime(date('Y-m-d 23:59:59'));

        $where    = ['cp_id' => $cpId];
        $taskData = [
            'auto_ratio_time' => $autoTime,
            'next_ratio'      => $newRatio,
            'update_time'     => time(),
        ];

        $res = $this->table($this->_taskTable)->where($where)->update($taskData);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 众筹产品上架
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId
     * @return bool
     */
    public function onlineProduct($cpId)
    {
        if (!$cpId) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        if ($info['status'] == 1) {
            return true;
        }

        //修改产品基础数据
        $data = [
            'update_time' => time(),
            'status'      => 1,
            'online_time' => time(),
        ];

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_productTable)->where($where)->update($data);
        if (!$res) {
            return false;
        }

        //将定时上架时间清除
        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_taskTable)->where($where)->update(['auto_online_time' => 0, 'update_time' => time()]);
        if (!$res) {
            return false;
        }

        return true;
    }
    /**
     * 众筹产品下架
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId
     * @return bool
     */
    public function offlineProduct($cpId)
    {
        if (!$cpId) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        //修改产品基础数据
        $data = [
            'update_time'  => time(),
            'status'       => 2,
            'offline_time' => time(),
        ];

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_productTable)->where($where)->update($data);
        if (!$res) {
            return false;
        }

        //将定时下架时间清除
        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_taskTable)->where($where)->update(['auto_offline_time' => 0, 'update_time' => time()]);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * 调整众筹产品年化收益率
     * @author dwer
     * @date   2017-09-02
     *
     * @param int $cpId
     * @param  float $newRatio
     * @return bool
     */
    public function adjustRatio($cpId, $newRatio)
    {
        $newRatio = floatval($newRatio);

        if (!$cpId || $newRatio <= 0) {
            return false;
        }

        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return false;
        }

        if ($newRatio == $info['ratio']) {
            return false;
        }

        //修改产品基础数据
        $data = [
            'update_time' => time(),
            'ratio'       => $newRatio,
        ];

        $where = ['cp_id' => $cpId];
        $res   = $this->table($this->_productTable)->where($where)->update($data);
        if (!$res) {
            return false;
        }

        $where    = ['cp_id' => $cpId];
        $taskData = [
            'auto_ratio_time' => 0,
            'update_time'     => time(),
        ];

        $res = $this->table($this->_taskTable)->where($where)->update($taskData);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取需要执行的任务
     * @author dwer
     * @date   2017-09-02
     *
     * @param  int  $nowTime 当前时间
     * @return array
     */
    public function getTask($nowTime = null)
    {
        $nowTime = $nowTime ? $nowTime : time();

        $where = [
            'auto_online_time|auto_offline_time|auto_ratio_time' => ['between', [1, $nowTime]],
        ];

        $res = $this->table($this->_taskTable)->where($where)->select();
        return $res ? $res : [];
    }

    /**
     * 获取订单信息
     * @author dwer
     * @date   2017-09-02
     *
     * @param  int  $orderId 订单ID
     * @param  string  $field 字段
     * @return array
     */
    public function getOrder($orderId, $field = '*')
    {
        if (!$orderId) {
            return [];
        }

        $table = "{$this->_crowdOrderTable}";
        $where = ['order_id' => $orderId];
        $info  = $this->table($table)->where($where)->find();
        return $info ? $info : [];
    }

    /**
     * 通过订单号获取订单信息
     * @author dwer
     * @date   2017-09-02
     *
     * @param  int  $orderId 订单ID
     * @param  string  $field 字段
     * @return array
     */
    public function getOrderByNo($orderNo, $field = '*')
    {
        if (!$orderNo) {
            return [];
        }

        $table = "{$this->_crowdOrderTable}";
        $where = ['order_no' => $orderNo];
        $info  = $this->table($table)->where($where)->find();
        return $info ? $info : [];
    }

    /**
     * 购买众筹产品
     * @author dwer
     * @date   2017-09-02
     *
     * @param  int  $uid 购买用户
     * @param  int  $cpId 产品ID
     * @param  int  $num 购买数量
     * @param  string  $orderNo 统一订单号
     * @param  int $orderSeq 第几笔订单序号 - 用于多笔订单统一支付
     * @return array
     */
    public function createOrder($uid, $cpId, $num, $orderNo, $orderSeq = 1)
    {
        $uid      = intval($uid);
        $cpId     = intval($cpId);
        $num      = intval($num);
        $orderNo  = strval($orderNo);
        $orderSeq = intval($orderSeq);

        if (!$uid || !$cpId || $num <= 0 || !$orderNo || !$orderSeq) {
            return [0, '参数错误'];
        }

        //获取用户信息
        $userModel = new User();
        $userInfo  = $userModel->getInfo($uid, 'wx_openid, mobile, name');
        if (!$userInfo) {
            return [0, '用户信息不存在'];
        }
        $mobile = $userInfo['mobile'];

        //获取产品信息
        $info = $this->getProduct($cpId, false);
        if (!$info) {
            return [0, '产品不存在'];
        }
        if ($info['status'] == 0) {
            return [0, '产品已经下架'];
        }

        $leftStock = intval($info['stock']) - intval($info['used_num']);
        if ($leftStock < $num) {
            return [0, '产品库存不足'];
        }
        $productName = $info['name'];
        $price       = intval($info['price']);
        $ratio       = floatval($info['ratio']);
        $totalMoney  = $price * $num;
        $version     = $info['version'];

        if ($price <= 0 || $ratio <= 0) {
            return [0, '产品价格或是年华收益率小于0'];
        }

        //判断支付订单号是否正常
        $where = [
            'order_no'  => $orderNo,
            'order_seq' => $orderSeq,
        ];
        $tmp = $this->table($this->_crowdOrderTable)->where($where)->find();
        if ($tmp) {
            return [0, '订单号重复'];
        }

        $orderData = [
            'uid'           => $uid,
            'order_no'      => $orderNo,
            'order_seq'     => $orderSeq,
            'cp_id'         => $cpId,
            'cp_name'       => $productName,
            'num'           => $num,
            'price'         => $price,
            'total_money'   => $totalMoney,
            'order_capital' => $totalMoney,
            'ratio'         => $ratio,
            'mobile'        => $mobile,
            'pay_status'    => 0,
            'status'        => 0,
            'order_time'    => time(),
        ];

        //开启事务
        $this->startTrans();

        //添加订单
        $orderId = $this->table($this->_crowdOrderTable)->insert($orderData);
        if (!$orderId) {
            $this->rollback();
            return [0, '订单新增失败'];
        }

        $userWhere = ['uid' => $uid];
        $userCrowd = $this->table($this->_userTrowdTable)->where($userWhere)->find();
        if (!$userCrowd) {
            //给用户生产众筹账号
            $data = [
                'uid'         => $uid,
                'add_time'    => time(),
                'update_time' => time(),
            ];
            $res = $this->table($this->_userTrowdTable)->insert($data);
            if (!$res) {
                $this->rollback();
                return [0, '用户众筹信息添加失败'];
            }
        }

        //提交事务
        $this->commit();

        //返回订单信息
        $resData = [
            'order_id'     => $orderId,
            'price'        => $price,
            'total_money'  => $totalMoney,
            'product_name' => $productName,
            'ratio'        => $ratio,
        ];
        return [1, '订单创建成功', $resData];
    }

    /**
     * 支付订单
     * @author dwer
     * @date   2017-09-02
     *
     * @param  string $orderNo 支付订单号
     * @param  string $transactionId 第三方支付系统交易流水号
     * @param  float $totalMoney 总的支付金额
     * @return bool
     */
    public function payOrders($orderNo, $transactionId, $totalMoney)
    {
        $orderNo       = strval($orderNo);
        $transactionId = strval($transactionId);
        $totalMoney    = floatval($totalMoney);
        if (!$orderNo || !$transactionId || $totalMoney <= 0) {
            return [0, '参数错误'];
        }

        //获取这笔支付下面的所有订单
        $where     = ['order_no' => $orderNo];
        $field     = 'order_id, total_money, pay_status, cp_id, num, uid, version';
        $orderList = $this->table($this->_crowdOrderTable)->where($where)->field($field)->select();
        if (!$orderList) {
            return [0, '获取不到订单信息'];
        }

        //取出其中一个订单，如果已经支付了，就不再进行处理
        if ($orderList[0]['pay_status'] == 1) {
            return [1, '订单已经处理', []];
        }

        //支付修改信息返回外层
        $backData = [];

        //开启事务
        $this->startTrans();

        $totalCaptital = 0;

        foreach ($orderList as $orderInfo) {
            $cpId       = $orderInfo['cp_id'];
            $orderId    = $orderInfo['order_id'];
            $num        = $orderInfo['num'];
            $uid        = $orderInfo['uid'];
            $oversion   = $orderInfo['version'];
            $totalMoney = $orderInfo['total_money'];

            //获取产品信息
            $info = $this->getProduct($cpId, false);
            if (!$info) {
                continue;
            }
            $pversion = $info['version'];
            $stock    = intval($info['stock']);
            $usedNum  = intval($info['used_num']);

            //是否认筹成功
            $isSuccess          = true;
            $backData[$orderId] = [
                'status'   => -1,
                'used_num' => -1,
            ];

            //判断库存是不是够
            $leftStock = $stock - $usedNum;
            if ($leftStock < $num) {
                //产品库存不足，将订单设置为退款状态
                $isSuccess = false;
            }

            //修改订单状态
            $where = [
                'order_id' => $orderId,
                'version'  => $oversion,
            ];
            $payData = [
                'pay_status'        => 1,
                'pay_time'          => time(),
                'transaction_id'    => $transactionId,
                'machine_start_num' => $usedNum + 1, //机器开始的编号
                'version'           => ['exp', "version + 1"],
            ];

            if ($isSuccess) {
                //开始计算收益的标识
                $nowDay = intval(date('H'));
                if ($nowDay >= $this->_calcHour) {
                    //每天0:00-15:00之后认筹，第二天计算收益，第三天显示收益，第三天10:00发微信红包发放；
                    $calcDay = date('Ymd', strtotime('+2day'));
                } else {
                    //每天0:00-15:00之前认筹，当天计算收益，第二天显示收益，第二天10:00发微信红包；
                    $calcDay = date('Ymd', strtotime('+1day'));
                }

                $payData['status']   = 1;
                $payData['calc_day'] = $calcDay;
            } else {
                $payData['status'] = 3;
            }
            $backData[$orderId]['status'] = $payData['status'];

            $res = $this->table($this->_crowdOrderTable)->where($where)->update($payData);
            if ($res <= 0) {
                $this->rollback();
                return [0, "订单ID[{$orderId}]修改支付状态失败"];
            }

            if ($isSuccess) {
                //扣除库存
                $where = ['cp_id' => $cpId, 'version' => $pversion];
                $data  = [
                    'used_num'   => ['exp', "used_num + {$num}"],
                    'version'    => ['exp', "version + 1"],
                    'left_stock' => intval($leftStock - $num),
                ];
                $backData[$orderId]['used_num'] = $info['used_num'] + $num;

                $res = $this->table($this->_productTable)->where($where)->update($data);
                if ($res <= 0) {
                    $this->rollback();
                    return [0, '产品库存更新失败'];
                }

                //用户这次总的本金
                $totalCaptital += $totalMoney;
            }
        }

        if ($totalCaptital) {
            //更新用户的众筹余额
            $userInfo = $this->getUser($uid);
            if (!$userInfo) {
                $this->rollback();
                return [0, '获取不到用户众筹信息'];
            }

            $uversion = $userInfo['version'];

            $userWhere = [
                'uid'     => $uid,
                'version' => $uversion,
            ];
            $userWhere['version'] = $uversion;
            $data                 = [
                'capital'     => ['exp', "capital+{$totalCaptital}"],
                'update_time' => time(),
                'version'     => ['exp', "version + 1"],
            ];

            $res = $this->table($this->_userTrowdTable)->where($userWhere)->update($data);

            if ($res <= 0) {
                $this->rollback();
                return [0, '用户本金总额更新失败'];
            }
        }

        //所有订单都已经更新成功了
        $this->commit();
        return [1, '支付处理成功', $backData];
    }

    /**
     * 获取每天需要进行清算的订单
     * @author dwer
     * @date   2017-09-04
     *
     * @param  date $calcDay 清算日期 - 2017-09-04
     * @param  bool $getTotal 是否获取总数，主要是用户数据的批量处理
     * @param  int $page 第几页
     * @param  int $size 条数
     * @return int/array
     */
    public function getCalcList($calcDay, $getTotal = false, $page = 1, $size = 500)
    {
        if (!strtotime($calcDay)) {
            if ($getTotal) {
                return 0;
            } else {
                return [];
            }
        }

        $calcDay = date('Ymd', strtotime($calcDay));
        $where   = [
            'status'   => 1,
            'calc_day' => ['ELT', $calcDay],
        ];

        if ($getTotal) {
            $total = $this->table($this->_crowdOrderTable)->where($where)->count();
            return intval($total);
        } else {
            $pageStr = "{$page},{$size}";
            $field   = 'order_id';
            $list    = $this->table($this->_crowdOrderTable)->where($where)->field($field)->page($pageStr)->select();
            return $list;
        }
    }

    /**
     * 获取每天需要进行产品收益计算的列表
     * @author dwer
     * @date   2017-09-04
     *
     * @param  date $calcDay 清算日期 - 2017-09-04
     * @param  bool $getTotal 是否获取总数，主要是用户数据的批量处理
     * @param  int $page 第几页
     * @param  int $size 条数
     * @return int/array
     */
    public function getIncomeList($calcDay, $getTotal = false, $page = 1, $size = 500)
    {
        if (!strtotime($calcDay)) {
            if ($getTotal) {
                return 0;
            } else {
                return [];
            }
        }

        $calcDay = date('Ymd', strtotime($calcDay));
        $where   = [
            'o.status'   => 1,
            'o.calc_day' => ['ELT', $calcDay],
        ];

        if ($getTotal) {
            $total = $this->table($this->_crowdOrderTable . " o")->where($where)->count('distinct cp_id');
            return intval($total);
        } else {
            $pageStr = "{$page},{$size}";
            $field   = 'distinct o.cp_id, p.real_product, p.type';
            $join    = "p.cp_id = o.cp_id";
            $list    = $this->table($this->_crowdOrderTable . " o")->where($where)->join($this->_productTable . " p", $join, 'left')->field($field)->page($pageStr)->select();

            return $list;
        }
    }

    /**
     * 生成每日收益记录
     * @author dwer
     * @date   2017-09-02
     *
     * @param  int $orderId 订单ID
     * @param  string $calcDay 具体哪天 - 2017-09-04
     * @param  int $dayMinIncome 日最小收益
     * @param  int $dayMaxIncome 日最大收益
     * @return array
     */
    public function genDayIncome($orderId, $calcDay, $dayMinIncome = 1500, $dayMaxIncome = 20000)
    {
        if (!strtotime($calcDay) || !$orderId) {
            return [0, '参数错误'];
        }

        //判断这些订单是不是已经处理过
        $day   = date('Ymd', strtotime($calcDay));
        $where = [
            'day'      => $day,
            'order_id' => $orderId,
        ];

        $res = $this->table($this->_incomeTable)->where($where)->find();
        if ($res) {
            return [0, '记录已经处理'];
        }

        //判断订单是否存在
        $orderInfo = $this->getOrder($orderId, 'uid, cp_id, num, calc_day, order_capital, order_income, order_refund, ratio, status, version');
        if (!$orderInfo || $orderInfo['status'] != 1) {
            //订单不存在或是众筹还没有开始
            return [0, '订单不存在或是众筹还没有开始'];
        }

        //判断处理日期是不是正确的 - 日期不能超过一天
        if ((strtotime($orderInfo['calc_day']) - strtotime($day)) > 24 * 3600) {
            return [0, "结算日期不正确"];
        }

        $orderCapital = $orderInfo['order_capital'];
        $orderIncome  = $orderInfo['order_income'];
        $ratio        = $orderInfo['ratio'];
        $cpId         = $orderInfo['cp_id'];
        $uid          = $orderInfo['uid'];
        $oversion     = $orderInfo['version'];
        $num          = $orderInfo['num'];

        //获取众筹用户数据
        $userInfo = $this->getUser($uid);
        if (!$userInfo) {
            return [0, "获取不到用户信息"];
        }
        $uversion = $userInfo['version'];
        $openId   = $userInfo['wx_openid'];

        //获取昨日的打款金额（订单的昨日产品收益）
        $cpIncome = $this->_getProductIncome($cpId, $day);
        if ($cpIncome === false) {
            return [0, '昨日返还金额获取失败'];
        }

        //获取每日的还款金额 - 15 -> 200 进行多还少补的原则
        $tmpOrderRefund = $cpIncome * $num;
        $orderRefund    = $tmpOrderRefund <= $dayMinIncome ? $dayMinIncome : $tmpOrderRefund;
        $orderRefund    = $orderRefund >= $dayMaxIncome ? $dayMaxIncome : $orderRefund;

        //计算用户的每日收益
        $orderIncome = $this->_calcOrderIncome($orderCapital, $ratio);
        if ($orderIncome === false) {
            return [0, '昨日收益金额计算失败'];
        }

        //因为这个计算的是昨天的数据，所以下一个计算日期是明天
        $nextCalcDay = date('Ymd', (strtotime($calcDay) + 2 * 24 * 3600));

        //本平台交易流水号
        $mchBillno = 'xmhb' . $day . $orderId;

        //开始事务
        $this->startTrans();

        //计算+收益后的本金是不是<=200,如果是的话直接终止众筹
        $tmpCapital = $orderCapital + $orderIncome;
        $stopCrowd  = false;

        if ($tmpCapital <= $dayMaxIncome) {
            $orderRefund     = $tmpCapital;
            $newOrderCapital = 0;

            //众筹结束
            $stopCrowd = true;
        } else {
            //本金重新计算 - 原来本金+收益-每日还款
            $newOrderCapital = $orderCapital + $orderIncome - $orderRefund;
        }

        //添加每日收益记录
        $dayIncomeData = [
            'uid'         => $uid,
            'open_id'     => $openId,
            'order_id'    => $orderId,
            'day'         => $day,
            'mch_billno'  => $mchBillno,
            'day_capital' => $newOrderCapital,
            'day_income'  => $orderIncome,
            'day_refund'  => $orderRefund,
            'status'      => 0,
            'add_time'    => time(),
        ];
        $res = $this->table($this->_incomeTable)->insert($dayIncomeData);
        if (!$res) {
            $this->rollback();
            return [0, '每日收益记录写入失败'];
        }

        //更新订单收益信息
        $where     = ['order_id' => $orderId, 'version' => $oversion];
        $orderData = [
            'order_capital' => $newOrderCapital,
            'order_income'  => ['exp', "order_income + {$orderIncome}"],
            'order_refund'  => ['exp', "order_refund + {$orderRefund}"],
            'version'       => ['exp', "version + 1"],
            'calc_day'      => $nextCalcDay,
        ];

        //众筹结束
        if ($stopCrowd) {
            $orderData['status'] = 2;
        }

        $res = $this->table($this->_crowdOrderTable)->where($where)->update($orderData);
        if ($res <= 0) {
            $this->rollback();
            return [0, '每日收益累加失败'];
        }

        //计算这个用户的当前所有本金
        $where = [
            'uid'        => $uid,
            'pay_status' => 1,
            'status'     => 1,
        ];
        $totalCaptital = $this->table($this->_crowdOrderTable)->where($where)->sum('order_capital');
        if ($totalCaptital === false) {
            $this->rollback();
            return [0, '用户本金计算失败1'];
        }

        //因为最后一次本金都会为0，所以不需要判断
        if (!$stopCrowd) {
            if (!$totalCaptital) {
                $this->rollback();
                return [0, '用户本金计算失败2'];
            }
        }

        //更新用户总收益信息
        $userWhere = ['uid' => $uid, 'version' => $uversion];
        $userData  = [
            'capital'     => $totalCaptital,
            'income'      => ['exp', "income + {$orderIncome}"],
            'refund'      => ['exp', "refund + {$orderRefund}"],
            'version'     => ['exp', "version + 1"],
            'update_time' => time(),
        ];

        $res = $this->table($this->_userTrowdTable)->where($userWhere)->update($userData);
        if ($res <= 0) {
            $this->rollback();
            return [0, '用户总收益数据更新失败'];
        }

        //如果是认筹结算了，需要释放库存
        if ($stopCrowd) {
            $cpWhere = ['cp_id' => $cpId];
            $cpData  = [
                'used_num'   => ['exp', "used_num - {$num}"],
                'left_stock' => ['exp', "left_stock + {$num}"],
            ];

            $res = $this->table($this->_productTable)->where($cpWhere)->update($cpData);
            if ($res <= 0) {
                $this->rollback();
                return [0, '产品库存释放失败'];
            }
        }

        //提交事务
        $this->commit();

        return [1, '每日收益生成成功'];
    }

    /**
     * 获取需要打红包的列表
     * @author dwer
     * @date   2017-09-02
     *
     * @param  date $calcDay 清算日期 - 2017-09-04
     * @param  bool $getTotal 是否获取总数，主要是用户数据的批量处理
     * @param  int $page 第几页
     * @param  int $size 条数
     * @return int/array
     *
     * @return array
     */
    public function getRedList($calcDay, $getTotal = false, $page = 1, $size = 500)
    {
        if (!strtotime($calcDay)) {
            if ($getTotal) {
                return 0;
            } else {
                return [];
            }
        }

        $calcDay = date('Ymd', strtotime($calcDay));
        $where   = [
            'income.status' => 0,
            'income.day'    => $calcDay,
        ];

        if ($getTotal) {
            $total = $this->table($this->_incomeTable . " income")->where($where)->count();
            return intval($total);
        } else {
            $pageStr = "{$page},{$size}";
            $join    = "income.order_id = or.order_id";
            $field   = 'income.id, income.uid, income.open_id, income.day, income.mch_billno, income.day_refund, income.order_id, or.mobile, or.cp_name';
            $list    = $this->table($this->_incomeTable . " income")->join($this->_crowdOrderTable . " or", $join, 'LEFT')->where($where)->field($field)->page($pageStr)->select();
            return $list;
        }
    }

    /**
     * 获取需要查询红包状态的列表
     * @author dwer
     * @date   2017-09-25
     *
     * @param  date $calcDay
     * @return array
     */
    public function getRedQueryList($calcDay)
    {
        if (!$calcDay || !strtotime($calcDay)) {
            return [];
        }

        $field   = 'mch_billno, id';
        $endTime = strtotime($calcDay . ' 00:00:00');
        $where   = [
            'add_time'       => ['ELT', $endTime],
            'status'         => 1,
            'receive_status' => 0,
        ];
        $orderStr = "id desc";
        $list     = $this->table($this->_incomeTable)->where($where)->field($field)->order($orderStr)->page('1, 500')->select();
        return $list ? $list : [];
    }

    /**
     * 获取处于退款红包的列表
     * @author dwer
     * @date   2017-09-25
     *
     * @param  date $calcDay
     * @return array
     */
    public function getRedRefundList()
    {
        $field   = 'id';
        $where   = [
            'status'         => 1,
            'receive_status' => 2,
        ];
        $orderStr = "id desc";
        $list     = $this->table($this->_incomeTable)->where($where)->field($field)->order($orderStr)->page('1, 500')->select();
        return $list ? $list : [];
    }

    /**
     * 打红包成功
     * @author dwer
     * @date   2017-09-07
     *
     * @param  string $mchBillno 本系统的红包交易流水号
     * @param  string $sendListid 红包订单的微信单号
     * @param  bool/int $payTime 支付成功的时间
     * @return bool
     */
    public function sendRedSucc($mchBillno, $sendListid, $payTime = false)
    {
        if (!$mchBillno || !$sendListid) {
            return false;
        }

        $where   = ['mch_billno' => $mchBillno];
        $payTime = $payTime ? $payTime : time();
        $data    = [
            'send_listid'    => $sendListid,
            'pay_time'       => $payTime,
            'status'         => 1,
            'receive_status' => 0,
        ];

        $res = $this->table($this->_incomeTable)->where($where)->update($data);
        return $res ? true : fasle;
    }

    /**
     * 更新红包领取的状态
     * @author dwer
     * @date   2017-09-07
     *
     * @param  string $mchBillno 本系统的红包交易流水号
     * @param  string $receiveStatus RECEIVED=已经领取 REFUND=已经退款
     * @return bool
     */
    public function updateRedStatus($mchBillno, $receiveStatus = 'RECEIVED')
    {
        $statusArr = ['RECEIVED' => 1, 'REFUND' => 2];

        if (!$mchBillno || !array_key_exists($receiveStatus, $statusArr)) {
            return false;
        }

        $where = ['mch_billno' => $mchBillno];
        $data  = [
            'receive_status' => $statusArr[$receiveStatus],
        ];

        $res = $this->table($this->_incomeTable)->where($where)->update($data);
        return $res ? true : fasle;
    }

    /**
     * 直接通过ID获取打款情况
     * @author dwer
     * @date   2017-10-09
     *
     * @param  int $incomeId
     * @return array
     */
    public function getRedInfo($incomeId)
    {
        if (!$incomeId) {
            return [];
        }

        $where = ['id' => $incomeId];
        $join  = "income.order_id = or.order_id";
        $field = 'income.id, income.uid, income.open_id, income.day, income.mch_billno, income.day_refund, income.order_id, income.receive_status, or.mobile, or.cp_name';
        $tmp   = $this->table($this->_incomeTable . " income")->join($this->_crowdOrderTable . " or", $join, 'LEFT')->where($where)->field($field)->find();

        return $tmp ? $tmp : [];
    }

    /**
     * 更新红包的本系统流水号
     * @author dwer
     * @date   2017-10-09
     *
     * @param  int $incomeId
     * @param  string $newMchBillno
     * @return bool
     */
    public function udpateMchBillno($incomeId, $newMchBillno)
    {
        if (!$incomeId || !$newMchBillno) {
            return false;
        }

        $where = ['id' => $incomeId];
        $data  = ['mch_billno' => $newMchBillno];
        $res   = $this->table($this->_incomeTable)->where($where)->update($data);
        return $res ? true : false;
    }

    /**
     * 获取日收益等信息
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $uid
     * @param  int $day
     * @return array
     */
    public function getDayIncome($uid, $day)
    {
        if (!$day) {
            return false;
        }

        $where = [
            'uid' => $uid,
            'day' => $day,
        ];

        $tmp = $this->table($this->_incomeTable)->where($where)->field('id')->find();
        if (!$tmp) {
            return false;
        }

        $field = 'sum(day_capital) capital, sum(day_income) as income, sum(day_refund) as refund';
        $res   = $this->table($this->_incomeTable)->where($where)->field($field)->find();

        return $res;
    }

    /**
     * 获取用户的收益列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $uid 用户ID
     * @param  int $page
     * @param  int $size
     * @return
     */
    public function getUserIncomeList($uid, $page = 1, $size = 20)
    {
        $where = [
            'uid' => $uid,
        ];
        $field    = 'day, sum(day_capital) capital, sum(day_income) as income, sum(day_refund) as refund';
        $pageStr  = "{$page},{$size}";
        $orderStr = "day desc";
        $groupStr = "day";

        $total = $this->table($this->_incomeTable)->where($where)->group($groupStr)->field($field)->count();
        $list  = [];

        if ($total > 0) {
            $list = $this->table($this->_incomeTable)->where($where)->group($groupStr)->field($field)->order($orderStr)->page($pageStr)->select();
        }

        return ['total' => $total, 'list' => $list];
    }

    /**
     * 获取用户的认筹列表
     * @author dwer
     * @date   2017-09-14
     *
     * @param  int $uid 用户ID
     * @param  string $status  认筹状态 init = 下单没有支付，runing=认筹中，finish=认筹结束
     * @param  int $page
     * @param  int $size
     * @return [type]
     */
    public function getUserProduct($uid, $status = 'runing', $page = 1, $size = 20)
    {
        $where = [
            'uid' => $uid,
        ];

        $statusArr = [
            'init'   => 0,
            'runing' => 1,
            'finish' => 2,
        ];

        if (isset($statusArr[$status])) {
            $where['or.status'] = $statusArr[$status];
        }

        $field    = 'cp_name,or.pay_time,or.num,or.price,or.ratio,or.status,or.machine_start_num,p.desc';
        $pageStr  = "{$page},{$size}";
        $orderStr = "pay_time desc";
        $join     = "or.cp_id=p.cp_id";

        $total = $this->table($this->_crowdOrderTable . " or")->where($where)->count();
        $list  = [];

        if ($total > 0) {
            $list = $this->table($this->_crowdOrderTable . " or")->where($where)->field($field)->join($this->_productTable . " p", $join, 'left')->page($pageStr)->select();
        }

        return ['total' => $total, 'list' => $list];
    }

    /**
     * 获取产品的每日收益
     * @author dwer
     * @date   2017-09-04
     *
     * @param  int $cpId 产品ID
     * @param  int $day 具体哪天
     * @return bool/int
     */
    private function _getProductIncome($cpId, $day)
    {
        if (!$cpId || !$day) {
            return false;
        }

        $where = [
            'cp_id' => $cpId,
            'day'   => $day,
        ];
        $res = $this->table($this->_productIncomeTable)->where($where)->find();
        if (!$res || !isset($res['calc_income'])) {
            return false;
        }

        return intval($res['calc_income']);
    }

    /**
     * 计算产品的收益
     * @author dwer
     * @date   2017-09-04
     *
     * @param  int $orderCapital 昨日本金
     * @param  float $ratio 万分收益率
     * @return int
     */
    private function _calcOrderIncome($orderCapital, $ratio)
    {
        return round($orderCapital * $ratio / 100 / 360, 2);
    }


    /**
     * 月统计报表
     * @author dwer
     * @date   2017-09-04
     *
     * @param  int $startDay 开始日期
     * @param  int $endDay 结束日期
     * @return int
     */
    public function getMonthStatistics($startDate, $endDate)
    {
        $where['co.pay_status'] = 1;

        if ($startDate && $endDate) {
            $startDay = str_replace('-', '', $startDate);
            $endDay   = str_replace('-', '', $endDate);
        }

        $where = " day BETWEEN {$startDay} AND {$endDay} ";
        $sql = "SELECT u.mobile, co.cp_name, ci.rest_capital, co.total_money, ci.total_income, ci.total_refund, cpi.product_income 
                FROM {$this->_crowdOrderTable} co JOIN
                (SELECT order_id, MIN(day_capital) rest_capital, SUM(day_income) total_income, SUM(day_refund) total_refund 
                FROM {$this->_incomeTable} WHERE " . $where . " GROUP BY order_id) ci
                ON co.order_id = ci.order_id 
                JOIN (SELECT cp_id, SUM(calc_income) product_income FROM {$this->_productIncomeTable} where " . $where . " GROUP BY cp_id)  cpi
                ON cpi.cp_id = co.cp_id INNER JOIN `user` u ON u.uid = co.uid";

        $data =  Db::query($sql);
        return $data;
    }

}

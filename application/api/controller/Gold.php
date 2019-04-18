<?php
namespace app\api\controller;

use app\api\model\Pay;
use app\common\model\ChangeGoldLog;
use app\common\model\Channel;
use app\common\model\ChannelGroup;
use app\common\model\ChannelPlatform;
use app\common\model\ChannelStatistics;
use app\common\model\ChannelType;
use app\common\model\NotifyService;
use app\common\model\RechargeOrder;
use app\common\model\StatisticsReport;
use app\common\model\UserBase;
use app\common\model\UserPlayer;
use app\common\model\WithdrawOrder;
use think\Db;
use think\Validate;

/**
 * 现金
 */
class Gold extends Common
{
    /**
     * 提现
     */
    public function tx()
    {
        $uid = think_decrypt(input('token'));

        !$uid && $this->result('', 1, 'token已失效');

        $channel = input('channel/d', 0); // 提现方式 1：支付宝 2 ：微信 4: 银行
        $amount  = input('amount', 0);

        $data            = [];
        $data['channel'] = $channel;
        $data['amount']  = $amount;

        $rule = [
            'channel|提现方式' => 'between:1,4',
            'amount|金额'    => '>:0|<:1000000',
        ];

        $validate = new Validate($rule);
        $result   = $validate->check($data);

        !$result && $this->result('', 1, $validate->getError());

        $info = UserBase::where('uid', $uid)->master()->field('alipay_account,alipay_name,tel,bank_account,cardholder_name')->find();

        // 判断是否绑定手机号
        !$info['tel'] && $this->result('', 2, '请先注册手机号');

        if ($channel == 1) {
            // 判断是否有支付宝
            !$info['alipay_account'] && $this->result('', 3, '请先绑定支付宝');

            $extInfo = ['account' => $info->alipay_account, 'name' => $info->alipay_name];
            $percent = config('ali_withdraw_percent'); // 获取提现税收
        } else if ($channel == 4) {
            // 判断是否有银行账号

            !$info['bank_account'] && $this->result('', 4, '请先绑定银行');

            $percent = config('bank_withdraw_percent'); // 获取提现税收
            $extInfo = ['account' => $info->bank_account, 'name' => $info->cardholder_name];
        } else {
            $this->result('', 1, '暂时不支持该方式提现');
        }

        $player_info = UserPlayer::where('uid', $uid)->master()->field('gold,type,bankgold')->find();

        //余额不足
        $player_info['gold'] - $amount < 0 && $this->result('', 1, '余额不足');

        // 先判断是否在游戏中
        $res = NotifyService::checkUserInGame($uid);

        if ($res == false) {
            $this->result('', 1, '服务器繁忙请稍后再尝试');
        }
        $msg = json_decode($res, true);

        // 1 游戏中 -1 没有登录 0 大厅
        if ($msg['Body']['CheckUserInGame']['InGame'] == 1) {
            $this->result('', 1, '游戏中禁止操作金币');
        }

        // 金币变动通知游服
        $msgArr = [
            $uid => $player_info['gold'] - $amount,
        ];
        //判断新老用户
        $time = strtotime(date('Y-m-d'));
        if (UserBase::where(['uid' => $uid])->value('create_time') > $time) {
            $newold = 1;
        } else {
            $newold = 2;
        }

        // 启动事务
        Db::startTrans();

        $tax_amount = bcmul($amount, $percent, 2);

        $order = [
            'uid'         => $uid,
            'amount'      => bcsub($amount, $tax_amount, 2),
            'gold'        => $amount,
            'channel'     => $channel,
            'percent'     => $percent,
            'ext_info'    => json_encode($extInfo),
            'user_type'   => 0,
            'newold_user' => $newold,
        ];

        $order = new WithdrawOrder($order);
        if (!$order->save()) {
            Db::rollback();
            $this->result('', 1, '提现失败');
        }
        UserPlayer::where('uid', $uid)->update(['gold' => Db::raw('gold-' . $amount)]);

        $goldLog = new ChangeGoldLog([
            'type'        => ChangeGoldLog::APP_TX,
            'uid'         => $uid,
            'change_gold' => $amount,
            'end_gold'    => $msgArr[$uid],
            'user_type'   => $player_info['type'],
            'rel_id'      => $order->id,
        ]);
        $goldLog->save();
        // 传给游服加上银行金币
        $endGold      = $msgArr[$uid];
        $msgArr[$uid] = $msgArr[$uid] + $player_info['bankgold'];
        $res          = NotifyService::upUserCard(1102, $msgArr);
        if (!$res) {
            Db::rollback();
            $this->result('', 1, '兑换失败');
        }
        //未打款的金额  //支付宝或者银行兑换笔数
        if ($channel == 1) {
            $being['ali_exchange_num'] = Db::raw('ali_exchange_num+1');
        } else if ($channel == 4) {
            $being['bank_exchange_num'] = Db::raw('bank_exchange_num+1');
        }
        $being['being_exchange_amount'] = Db::raw('being_exchange_amount+' . $amount);
        $being['being_exchange_num']    = Db::raw('being_exchange_num+1');
        StatisticsReport::where(['end_time' => 0])->update($being);
        //报表统计
        $reslut = StatisticsReport::userWithdraw($uid, $order->amount, $order->gold, $order->gold * $order->percent, 2);
        //用户统计
        $updatePlayer = [
            'exchange_num'       => Db::raw('exchange_num+1'),
            'exchange_pump'      => Db::raw('exchange_pump+' . ($order->gold * $order->percent)),
            'exchange_gold'      => Db::raw('exchange_gold+' . $order->gold),
            'exchange_amount'    => Db::raw('exchange_amount+' . $order->amount),
            'last_exchange_time' => time(),
        ];
        UserPlayer::where('uid', $uid)->update($updatePlayer);
        if ($reslut['newold_player']) {
            WithdrawOrder::where('id', $order->id)->update(['newold_player' => 1]);
        }
        // 提交事务
        Db::commit();
        $this->result(['gold' => $endGold], 0, '恭喜您已成功兑换' . $amount . '金币！请耐心等待3-5分钟，您的' . $order->channel . '将收到对应金额！');

    }

    /**
     * 充值
     * @return array 平台token
     */
    public function cz()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            return ['', 1, 'token已失效'];
        }
        $id     = input('id', 0); // 支付商品组
        $amount = input('amount', 0);
        // $mode            = input('mode', 0); // 返回客户端回调地址
        $data           = [];
        $data['amount'] = $amount;

        $rule = [
            'amount|金额' => '>:0|<:1000000',
        ];

        $validate = new Validate($rule);
        $result   = $validate->check($data);
        if (!$result) {
            $this->result('', 1, $validate->getError());
        }

        // 判断商品组是否存在
        $channel_group = ChannelGroup::where(['id' => $id, 'status' => 1, 'type' => 1])->field('is_quota')->find();
        !$channel_group && $this->result('', 1, '该支付通道已经被关闭，请使用其他支付方式');

        // 获取分组下面的类别
        $type_ids = ChannelType::where(['group_id' => $id, 'status' => 1])->column('id');
        !$type_ids && $this->result('', 1, '该支付通道已经被关闭，请使用其他支付方式');
        // 获取商品类型下面的通道
        //print_r($type_ids);die();
        $where = [
            'type_id'    => ['in', $type_ids],
            'status'     => 1,
            'weight'     => ['<>', 0],
            'min_amount' => ['<', $amount],
            'max_amount' => ['>', $amount],
        ];
        //print_r($where);die();

        $list = Channel::where($where)->where('day_limit_quota-day_already_quota > ' . $amount)->column('type_id,platform_id,weight,percent', 'id');
        !$list && $this->result('', 1, '该支付通道已经被关闭，请使用其他支付方式');

        //总权重
        $weight = 0;
        $pays   = [];
        foreach ($list as $val) {
            $weight += $val['weight'];
            for ($i = 0; $i < $val['weight']; $i++) {
                $pays[] = $val['id'];
            }
        }

        // 确定哪一个支付通道
        $channel    = $list[$pays[rand(0, $weight - 1)]];
        $channel_id = $channel['id']; // 通道ID

        $agent_id = UserBase::where('uid', $uid)->value('agent_uid');

        /** 生成第三方支付订单号 **/
        $out_trade_no = substr(md5(uniqid() . microtime()), 0, 28);

        $order = [
            'uid'          => $uid,
            'amount'       => $amount,
            'gold'         => $amount * config('recharge_ratio'),
            'channel_id'   => $channel_id,
            'status'       => 0,
            'agent_id'     => $agent_id ? $agent_id : 0,
            'out_trade_no' => $out_trade_no,
            'percent'      => $channel['percent'],
        ];
        StatisticsReport::where('end_time', 0)->update(['recharge_num' => Db::raw('recharge_num+1')]);

        $channelday = [
            'recharge_amount' => Db::raw('recharge_amount+' . $amount),
            'recharge_num'    => Db::raw('recharge_num+1'),
        ];

        ChannelStatistics::where(['end_time' => 0, 'channel_id' => $channel_id])->update($channelday);

        $rechargeOrder = new RechargeOrder($order);
        if (!$rechargeOrder->save()) {
            $this->result('', 1, '充值失败');
        }

        // 判断是否是众宝支付，平台
        if ($channel['platform_id'] == ChannelPlatform::ZB) {
            Pay::createZonBaoOrder($out_trade_no, $amount, $channel['platform_id'], $channel['type_id']);
        } else {
            $this->result('', 1, '该支付平台已经被关闭，请使用其他支付方式');
        }
    }

    /**
     * 获取币种详情
     */
    public function get_gold_info()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }
        $info = UserPlayer::where('uid', $uid)->field('bankgold')->find();
        $data = [
            'bankgold' => $info['bankgold'],
        ];

        $this->result($data, 0, 'success');
    }

    /**
     * 充值历史记录
     */
    public function get_cz_history()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }
        $page   = input('page', 1);
        $length = input('length', 10);
        $data   = RechargeOrder::where('uid', $uid)
            ->field('id,amount,channel,status,create_time')
            ->order('id DESC')
            ->page($page, $length)
            ->select();

        $this->result($data, 0, 'success');
    }

    /**
     * 提现历史记录
     */
    public function get_tx_history()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }
        $page   = input('page', 1);
        $length = input('length', 10);
        $data   = WithdrawOrder::where('uid', $uid)
            ->field('id,amount,channel,status,create_time')
            ->order('id DESC')
            ->page($page, $length)
            ->select();

        $this->result($data, 0, 'success');
    }

    /**
     * 银行存款
     */
    public function bank_store()
    {
        $this->bankGoldOperation(1);
    }

    /**
     * 银行取款
     */
    public function bank_fetch()
    {
        $this->bankGoldOperation(0);
    }

    /**
     * 金币领取提示
     */
    public function gold_collection_tips()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $this->result('', 1, 'token已失效');
        }
        $gold = RechargeOrder::unclaimedGold($uid);
        $this->result(['gold' => $gold], 0);
    }

    /**
     * 金币领取
     */
    public function gold_collection()
    {
        $uid = think_decrypt(input('token'));
        if (!$uid) {
            $uid = input('uid', ''); // 国庆期间暂时这么处理
            //$this->result('', 1, 'token已失效');
        }
        // 已支付待领取的金币
        $where = [
            'uid'        => $uid,
            'is_receive' => 0,
            'is_add'     => 1,
            'status'     => ['in', [1, 2]],
        ];

        $list = RechargeOrder::where($where)->master()->field('gold,id,source')->select();

        if (!$list) {
            $this->result('', 1, '暂时没有可以领取的金币');
        }

        // 先判断是否在游戏中
        $res = NotifyService::checkUserInGame($uid);

        if ($res == false) {
            $this->result('', 1, '服务器繁忙请稍后再尝试');
        }
        $msg = json_decode($res, true);

        // 1 游戏中 -1 没有登录 0 大厅
        if ($msg['Body']['CheckUserInGame']['InGame'] == 1) {
            $this->result('', 1, '游戏中禁止操作金币');
        }

        $info          = UserPlayer::where('uid', $uid)->master()->field('gold,type,bankgold')->find();
        $gold          = 0;
        $orderIdArr    = [];
        $changeGoldLog = []; // 金币变动记录
        foreach ($list as $val) {
            $gold += $val['gold'];
            $orderIdArr[]    = $val['id'];
            $changeGoldLog[] = [
                'type'        => $val->getdata('source') == RechargeOrder::KF ? ChangeGoldLog::KF_CZ : ChangeGoldLog::APP_CZ,
                'uid'         => $uid,
                'change_gold' => $val['gold'],
                'end_gold'    => $info['gold'] + $gold,
                'user_type'   => $info['type'],
                'rel_id'      => $val['id'],
            ];
        }

        // 启动事务
        Db::startTrans();

        $goldLog = new ChangeGoldLog;
        if (!$goldLog->saveAll($changeGoldLog)) {
            Db::rollback();
            return ['', -1];
        }
        $res  = RechargeOrder::where('id', 'in', $orderIdArr)->update(['is_receive' => 1]);
        $res1 = UserPlayer::where('uid', $uid)->where('gold', $info['gold'])->update(['gold' => Db::raw('gold+' . $gold)]);

        if (!$res || !$res1) {
            // 有一个不成功就回滚
            Db::rollback();
            return ['', -1];
        }
        // 传给游服加上银行金币
        $endGold = $info['gold'] + $gold;
        $msgArr  = [
            $uid => $endGold + $info['bankgold'],
        ];

        $res = NotifyService::upUserCard(1102, $msgArr);
        if (!$res) {
            Db::rollback();
            $this->result('', 1, '领取失败');
        }
        // 提交事务
        Db::commit();
        $this->result(['gold' => $endGold], 0, '领取成功');
    }

    /**
     * 银行操作
     */
    private function bankGoldOperation($type)
    {
        $uid = think_decrypt(input('token'));
        !$uid && $this->result('', 1, 'token已失效');

        $text   = $type == 1 ? '存款' : '取款';
        $amount = input('amount/d', 0);
        if ($amount <= 0 || $amount > 1000000) {
            $this->result('', 1, $text . '金额有误');
        }

        // 日志记录类型
        $logType = $type == 1 ? ChangeGoldLog::BANK_STORE : ChangeGoldLog::BANK_FETCH;

        $info = UserPlayer::where('uid', $uid)->master()->field('gold,bankgold,type')->find();

        if ($type == 1) {
            // 存款判断
            if ($info['gold'] - $amount < 0) {
                $this->result('', 1, '当前金币不足');
            }
            $where = [
                'gold'     => Db::raw('gold-' . $amount),
                'bankgold' => Db::raw('bankgold+' . $amount),
            ];
            // 金币变动通知游服
            $msgArr = [
                $uid => $info['gold'] - $amount,
            ];

            // 银行变动通知游服
            $bankMsgArr = [
                $uid => $info['bankgold'] + $amount,
            ];

        } else {
            // 取款判断
            if ($info['bankgold'] - $amount < 0) {
                $this->result('', 1, '银行存款不足');
            }

            $where = [
                'gold'     => Db::raw('gold+' . $amount),
                'bankgold' => Db::raw('bankgold-' . $amount),
            ];
            $msgArr = [
                $uid => $info['gold'] + $amount,
            ];
            $bankMsgArr = [
                $uid => $info['bankgold'] - $amount,
            ];
        }

        // 先判断是否在游戏中
        $res = NotifyService::checkUserInGame($uid);

        if ($res == false) {
            $this->result('', 1, '服务器繁忙请稍后再尝试');
        }
        $msg = json_decode($res, true);

        // 1 游戏中 -1 没有登录 0 大厅
        if ($msg['Body']['CheckUserInGame']['InGame'] == 1) {
            $this->result('', 1, '游戏中禁止操作金币');
        }

        // 启动事务
        Db::startTrans();
        $res = UserPlayer::where('uid', $uid)->where('gold', $info['gold'])->update($where);

        !$res && $this->result('', 1, $text . '失败');

        $goldLog = new ChangeGoldLog([
            'type'        => $logType,
            'uid'         => $uid,
            'change_gold' => $amount,
            'end_gold'    => $msgArr[$uid],
            'user_type'   => $info['type'],
        ]);
        if (!$goldLog->save()) {
            Db::rollback();
            $this->result('', 1, $text . '失败');
        }

        // 银行变动通知游服
        $res = NotifyService::upUserCard(1103, $bankMsgArr);
        if (!$res) {
            $this->result('', 1, $text . '失败');
        }
        // 提交事务
        Db::commit();
        $this->result(['gold' => $msgArr[$uid], 'bankgold' => $bankMsgArr[$uid]], 0, $text . '成功');

    }

    /**
     * 是否拉起网页
     */
    public function isOpenBrowser($uid)
    {

        $data = [
            'type' => $type,
        ];
        $this->result($data, 0);
    }

}

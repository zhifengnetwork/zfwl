<?php
namespace mgcore\service;

use Overtrue\Wechat\Exception;
use Overtrue\Wechat\Notice;
use \think\Config;
use \think\Db;

/**
 * 设备通知服务,TODO封装不同的云平台
 */
class NoticeService
{
    public static $_is_init = false;

    private static function _init()
    {
        if (self::$_is_init) {
            return;
        }
        self::$_is_init = true;

        // 加载wechat微信处理类库
        require VENDOR_PATH . 'wechat-2/autoload.php';
    }

    public static function send($event, $machine)
    {
        self::_init();

        switch ($event) {
            case 1:
                self::notice_updown($machine);
                break;

            default:
                # code...
                break;
        }
    }

    public static function add_machine_log($machine_id, $event)
    {
        $data = ['machine_id' => $machine_id, 'event' => $event, 'create_time' => time(), 'time' => time()];
        Db::table('machine_log')->insert($data);
    }

    public static function machine_updown($machine, $agent_ids = null)
    {
        self::_init();

        $event = 1;

        if (is_array($machine)) {
            !$agent_ids && ($agent_ids = array_column($machine->toArray(), 'agent_id'));
        } else {
            !$agent_ids && ($agent_ids = [$machine->agent_id]);
            $machine = [$machine];
        }

        if (!$agent_ids) {
            return;
        }

        $anc = Db::table('agent_notice_config')
            ->where(['agent_id' => ['IN', $agent_ids], 'event' => 11])
            ->select();

        if (!$anc) {
            return;
        }

        $time = time();
        $i    = 0;
        foreach ($machine as $m) {
            $flag = false;
            if ($m->new_state == 3) {
                $flag        = true;
                $title       = $m->location . ' - ' . $m->name . ' 发生异常';
                $type        = '设备失去连接';
                $title_color = '#CC0000';
                self::add_machine_log($m->machine_id, 11); // 11-异常   1-正常
            } elseif ($m->new_state == 0) {
                $flag        = true;
                $title       = $m->location . ' - ' . $m->name . ' 恢复正常';
                $type        = '设备恢复正常';
                $title_color = '#078610';
                self::add_machine_log($m->machine_id, 1); // 11-异常   1-正常
            }
            if ($flag) {
                foreach ($anc as $v) {
                    if (++$i > 1) {
                        usleep(100000);
                    }

                    self::_wx_notice_device($v['wx_openid'], $title, $type, $time, '', $title_color);
                }
            }
        }
    }

    /**
     * 每日汇总数据提醒
     * @author dwer
     * @date   2017-07-13
     *
     * @param int $agentId 代理商ID
     * @param int $openId 接收用户openId
     * @param string $agentName 代理商名称
     * @param int $placeNum 场所数量
     * @param string $content 具体内容
     * @param date $warnTime 提醒时间
     * @param string $remark 备注信息
     * @return
     */
    public static function yesterdaystatistics($agentId, $openId, $agentName, $placeNum, $content, $warnTime, $remark = '')
    {
        //初始化
        self::_init();

        //暂时将备注去掉
        $remark = '';

        //暂时只对这两个账户发送提醒
        // $tmpArr = ['ozpIYwrbC4lTBiUxQaiaACNtHP0U', 'ozpIYwlP57Te1TQ0bpONcH8bGG6o'];
        // if(!in_array($openId, $tmpArr)) {
        //     return false;
        // }

        //定义域名
        Config::set('url_domain_root', 'miyixia.cn');
        $domain = 'emc';

        $appId     = Config::get('biz_wx_config.appid');
        $secret    = Config::get('biz_wx_config.appsecret');
        $noticeLib = new Notice($appId, $secret);

        $template = Config::get('biz_wx_tmplmsg.merchant_statistics');
        $day      = date('Y-m-d', strtotime('yesterday'));
        $url      = url('admin/statistics/agent_income', ['agent' => $agentId, 'day' => $day], true, $domain);

        $color = '#eabc51';
        $data  = array(
            'first'    => ["", '#eabc51'],
            'keyword1' => [$agentName, '#41515a'], //商户名称
            'keyword2' => [$warnTime, '#41515a'], //提醒时间
            'keyword3' => [$placeNum, '#41515a'], //门店数量
            'keyword4' => [$content, '#41515a'], //汇总数据
            'remark'   => [$remark, '#8c988d'], //备注
        );

        try {
            $messageId = $noticeLib->to($openId)->template($template)->data($data)->url($url)->color($color)->send();
            return $messageId;
        } catch (\Exception $e) {
            pft_log('wechat/exception', json_encode([$data, $e->getMessage()]));
            return false;
        }
    }

    /**
     * 小米科技的微信告警信息
     * @param  string $time      时间 - 2017-10-23 10:22
     * @param  string $msg       告警信息
     * @param  array  $openIdArr 需要接收消息的openid - 没有传的话使用配置数据
     * @return mix
     */
    public static function warningMsg($time, $msg, $openIdArr = [])
    {
        //初始化
        self::_init();

        $appId     = Config::get('biz_wx_config.appid');
        $secret    = Config::get('biz_wx_config.appsecret');
        $noticeLib = new Notice($appId, $secret);
        $template  = Config::get('biz_wx_tmplmsg.machine_fault');

        $color = '#eabc51';
        $data  = array(
            'first'    => ["", '#eabc51'],
            'keyword1' => [$time, '#41515a'], //商户名称
            'keyword2' => [$msg, '#41515a'], //提醒时间
        );

        if (!$openIdArr) {
            $openIdArr = Config::get('warning_openid_arr');
        }

        if ($openIdArr) {
            foreach ($openIdArr as $openId) {
                try {
                    $messageId = $noticeLib->to($openId)->template($template)->data($data)->color($color)->send();
                } catch (\Exception $e) {
                    pft_log('wechat/warningMsg', json_encode([$data, $e->getMessage()]));
                }
            }
        }

        return true;
    }

    private static function _wx_notice_device($openid, $title = '设备异常', $type = '设备失去连接', $time, $remark = '', $title_color)
    {
        // 发送设备异常提醒模板消息
        try {
            $appId  = Config::get('wx_config.appid');
            $secret = Config::get('wx_config.appsecret');
            $notice = new Notice($appId, $secret);

            $template = Config::get('wx_tmplmsg.machine_fault');
            $url      = url('admin/machine/index', '', true, true);
            $color    = '#FF0000';
            $data     = array(
                'first'    => [$title . "\n", $title_color],
                'keyword1' => [date('Y-m-d H:i:s', $time), '#014D79'],
                'keyword2' => [$type, '#014D79'], //名称
                // 'remark'   => [$remark ? "\n".$remark : '', '#014D79'],
            );

            $messageId = $notice->to($openid)->template($template)->data($data)->url($url)->color($color)->send();
        } catch (Exception $e) {
        } catch (\Exception $e) {
        }
    }

    private static function _wx_notice_paid($openid, $title = '您有一位客户付款成功！', $name = '零钱微SPA', $amount, $time, $remark = '')
    {
        // 发送客户付款成功模板消息
        try {
            $appId  = Config::get('wx_config.appid');
            $secret = Config::get('wx_config.appsecret');
            $notice = new Notice($appId, $secret);

            $template = Config::get('wx_tmplmsg.pay_success');
            $url      = url('admin/order/index', '', true, true);
            $color    = '#FF0000';
            $data     = array(
                'first'    => [$title . "\n", '#078610'],
                'keyword1' => [$name, '#014D79'], //名称
                'keyword2' => ['￥' . $amount, '#014D79'], //金额
                'keyword3' => [date('Y-m-d H:i:s', $time), '#014D79'],
                'remark'   => [$remark ? "\n" . $remark : '', '#014D79'],
            );

            $messageId = $notice->to($openid)->template($template)->data($data)->url($url)->color($color)->send();
        } catch (Exception $e) {
        } catch (\Exception $e) {
        }
    }
}

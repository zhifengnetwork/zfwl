<?php
/**
 * 订单相关的服务层封装
 */
namespace mgcore\service;

use Overtrue\Wechat\Payment\Business;
use Overtrue\Wechat\Payment\Refund;
use \app\index\service\SaleUser as SaleUserService;
use \mgcore\model\Coupon as CouponModel;
use \mgcore\model\Order as OrderModel;
use \think\Config;

class OrderService
{
    /**
     * 订单退款
     * @param  int $orderId 订单ID
     * @param  int $loginId 操作ID
     * @param  int $isProd 是否生产环境
     * @return array
     */
    public function refund($orderId, $loginId, $isProd = true)
    {
        if (!$orderId) {
            return [0, '参数错误'];
        }

        $orderModel = new OrderModel();
        $orderInfo  = $orderModel->getOrderInfo($orderId, 'order_no, amount, refunded, time_paid, sale_user_profit, cst_id');
        if (!$orderInfo) {
            return [0, '订单错误'];
        }

        $orderNo    = $orderInfo['order_no'];
        $refundFee  = $orderInfo['amount'];
        $totalFee   = $orderInfo['amount'];
        $isRefund   = $orderInfo['refunded'];
        $paidTime   = $orderInfo['time_paid'];
        $refundNo   = $orderNo . '_b'; //退款流水号
        $cstId      = $orderInfo['cst_id']; //优惠券使用ID
        $saleProfit = $orderInfo['sale_user_profit']; //咪小二分润金额

        if ($isRefund == 1) {
            return [1, '已经退款'];
        }

        // 如果订单前两个月的不允许退款
        if (date('m') - date('m', $paidTime) > 1) {
            return [0, '前两个月的订单不能退款'];
        }

        // 如果生成月小于当前月，并且该月在10号
        if ((date('m', $paidTime) < date('m')) && (date('d') > 9)) {
            return [0, '10号之后不能对上个月订单进行退款'];
        }

        if ($isProd) {
            //真实退款
            $res = $this->_wxRefund($refundNo, $totalFee, $refundFee, $orderNo);
        } else {
            //模拟退款
            $res = [
                'type'      => 'success',
                'msg'       => '模拟退款成功',
                'refund_id' => time(),
            ];
        }

        $type   = $res['type'];
        $errMsg = $res['msg'];

        if ($type == 'success') {
            //退款成功
            $outRefundNo = $res['refund_id'];
            $tmp         = $orderModel->addRefundLog($orderNo, $status = 1, $errMsg, $loginId, $refundFee, $totalFee, $refundNo, $outRefundNo);

            //更新订单状态
            $tmp = $orderModel->updateRefundStatus($orderNo, $refundFee);

            //优惠券模型
            $couponModel = new CouponModel();

            if ($cstId) {
                //如果有优惠券，释放优惠券
                $couponRes = $couponModel->realse($cstId);

                //如果有咪小二分润，返回分润
                $saleRes = -1;
                if ($saleProfit) {
                    $saleRes = SaleUserService::refund($orderNo, $cstId);
                }

                //统一记录日志
                pft_log('order_refund', json_encode([$orderNo, $cstId, $couponRes, $saleRes]));
            }

            if ($tmp) {
                return [1, '退款成功'];
            } else {
                return [3, '退款失败'];
            }
        } else if ($type == 'timeout') {
            //超时
            $orderModel->addRefundLog($orderNo, 2, $errMsg, $loginId, $refundFee, $totalFee, $refundNo);
            return [2, '退款超时'];
        } else {
            //退款失败
            $orderModel->addRefundLog($orderNo, 3, $errMsg, $loginId, $refundFee, $totalFee, $refundNo);
            return [3, $errMsg];
        }
    }

    /**
     * 微信退款交互
     * @param $outRefundNo
     * @param $totalFee
     * @param $refundFee
     * @param $outTradeNo
     * @return array
     */
    static function _wxRefund($outRefundNo, $totalFee, $refundFee, $outTradeNo)
    {
        try {
            include_once VENDOR_PATH . 'wechat-2/autoload.php';
            // 获取实例化参数
            $wxConfigArr   = Config::get('th_wx_config');
            $appId         = $wxConfigArr['appid'];
            $appSecret     = $wxConfigArr['appsecret'];
            $mchId         = $wxConfigArr['mch_id'];
            $mchKey        = $wxConfigArr['mch_key'];
            $apiclientCert = $wxConfigArr['client_cert'];
            $apiclientKey  = $wxConfigArr['client_key'];

            $business = new Business($appId, $appSecret, $mchId, $mchKey);
            $business->setClientCert($apiclientCert);
            $business->setClientKey($apiclientKey);

            $refund = new Refund($business);

            $refund->out_refund_no = $outRefundNo; //退单单号
            $refund->total_fee     = intval($totalFee); //订单金额
            $refund->refund_fee    = intval($refundFee); //退款金额
            $refund->out_trade_no  = $outTradeNo; //原商户订单号
            $refundRes             = $refund->getResponse();

            if ($refundRes['result_code'] == 'SUCCESS') {
                //退款成功
                $res = ['type' => 'success', 'msg' => '退款成功', 'refund_id' => $refundRes['refund_id']];
            } else {
                if ($refundRes['err_code'] == 'SYSTEMERROR') {
                    //超时
                    $res = ['type' => 'timeout', 'msg' => '超时'];
                } else {
                    //退款失败
                    $res = ['type' => 'error', 'msg' => $refundRes['err_code_des']];
                }
            }
        } catch (\Exception $e) {
            $res = ['type' => 'error', 'msg' => $e->getMessage()];
        }

        return $res;
    }
}

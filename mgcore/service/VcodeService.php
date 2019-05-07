<?php
/**
 * 短信验证码发送、验证相关接口
 *
 * @author dwer.cn
 * @date 2017-09-14
 *
 */
namespace mgcore\service;

use mgcore\service\SmsService;
use think\Cache;
use \think\Env;

class VcodeService
{

    /**
     * 发送短信验证码
     *
     * @author wengbin
     * @date   2017-04-19
     *
     * @param  interge    $mobile   手机号
     * @param  string     $tpl      模板配置文件的key
     * @param  string     $identify 本次发送业务的标识
     * @param  integer    $length   验证码长度
     * @param  boolean    $mixed    false : 纯数字 | true : 字母数字混合
     * @param  integer    $expire   过期时间
     * @param  integer    $interval 发送间隔秒数 0=默认不限制
     *
     * @return array
     */
    public static function sendVcode($mobile, $tpl, $identify, $length = 6, $mixed = false, $expire = 300, $interval = 0, $from = '众筹')
    {
        if (!is_mobile_number($mobile)) {
            $res = [
                'code' => 204,
                'msg'  => '手机号格式错误',
            ];
            return $res;
        } else {
            //验证码发送间隔判断
            $interval = intval($interval);
            if ($interval > 0) {
                $vcodeInfo = self::getVcodeInfo($mobile, $identify);
                $lastTime  = ($vcodeInfo && isset($vcodeInfo['time'])) ? $vcodeInfo['time'] : 0;
                $passTime  = time() - $lastTime;

                //间隔时间太短了
                if ($passTime < $interval) {
                    $res = [
                        'code' => 205,
                        'msg'  => "操作太频繁",
                    ];
                    return $res;
                }
            }

            //生成验证码
            $digit  = '0123456789';
            $letter = 'abcdefghijklmnopqrstuvwxyz';
            $string = $mixed ? $digit . $letter : $digit;
            $code   = substr(str_shuffle($string), 0, $length);

            $params = [
                $from . '用户', $code, '5分钟',
            ];

            $appEnv = Env::get('app_env');
            if ($appEnv && in_array($appEnv, ['dev', 'test'])) {
                $code   = '666666';
                $result = ['status' => 'success', 'msg' => '发送成功'];
            } else {
                $result = SmsService::send($mobile, $tpl, $params);
            }

            if ($result['status'] != 'success') {
                $res = [
                    'code' => 204,
                    'msg'  => $result['msg'],
                ];
            } else {
                $res = [
                    'code' => 200,
                    'msg'  => '发送成功',
                ];
                //缓存验证码信息
                self::_saveVcodeData($mobile, $identify, $code, $expire);
            }

            return $res;
        }
    }

    /**
     * 验证码校验
     *
     * @author wengbin
     * @date   2017-04-19
     *
     * @param  interge    $mobile   手机号
     * @param  string     $code     验证码
     * @param  string     $identify 服务标识
     *
     * @return array
     */
    public static function verifyVcode($mobile, $code, $identify)
    {

        $vcodeInfo = self::getVcodeInfo($mobile, $identify);

        if (!$vcodeInfo) {
            $return = [
                'code' => 404,
                'msg'  => '验证码信息不存在或者已过期',
            ];
        } else {
            if ($vcodeInfo['code'] != $code) {
                $return = [
                    'code' => 204,
                    'msg'  => '验证码错误',
                ];
            } else {
                $return = [
                    'code' => 200,
                    'msg'  => '验证成功',
                ];
            }

            //移除验证码信息
            self::removeVcodeInfo($mobile, $identify);

        }

        return $return;
    }

    /**
     * 获取验证码信息
     *
     * @author wengbin
     * @date   2017-04-19
     *
     * @param  integer    $mobile   手机号
     * @param  string     $identify 服务标识
     *
     * @return $data | false
     */
    public static function getVcodeInfo($mobile, $identify)
    {
        $key   = self::_createCacheKey($mobile, $identify);
        $cache = Cache::get($key);

        if ($cache) {
            if(is_array($cache)) {
                //Cache类会自动将json数据转换回来
                return $cache;
            } else {
                return json_decode($cache, true);
            }
            
        } else {
            return false;
        }
    }

    /**
     * 保存验证码数据
     *
     * @author wengbin
     * @date   2017-04-19
     *
     * @param  integer      $mobile     手机号
     * @param  string       $identify   服务标识
     * @param  string       $code       验证码
     * @param  integer      $expire     缓存时间
     */
    private static function _saveVcodeData($mobile, $identify, $code, $expire)
    {

        $key = self::_createCacheKey($mobile, $identify);

        $data = [
            'code' => $code,
            'time' => time(),
        ];
        $data = json_encode($data);

        Cache::set($key, $data, $expire);
    }

    /**
     * 生成短信验证码缓存key
     *
     * @author wengbin
     * @date   2017-04-19
     *
     * @param  integer      $mobile   手机号
     * @param  string       $identify 服务标识
     *
     * @return string
     */
    private static function _createCacheKey($mobile, $identify)
    {
        return "vcode:{$mobile}:{$identify}";
    }

    /**
     * 移除验证码信息
     *
     * @author wengbin
     * @date   2017-04-20
     *
     * @param   int       $mobile   手机号
     * @param  string     $identify 服务标识
     *
     */
    public static function removeVcodeInfo($mobile, $identify)
    {
        $key = self::_createCacheKey($mobile, $identify);
        return Cache::rm($key);
    }

}

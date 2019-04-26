<?php
namespace app\admin\controller;

use think\Db;

/**
 * 首页
 */
class Index extends Common
{
    public function index()
    { 
        // $url  = 'https://openapi.alipay.com/gateway.do?app_id=2019041663926129&version=1.0&format=json&sign_type=RSA2&method=alipayaop.fund.trans.toaccount.transfer&timestamp=2019-04-25+00%3A35%3A12&auth_token=&alipay_sdk=alipayaop-sdk-php-20180705&terminal_type=&terminal_info=&prod_code=&notify_url=&charset=UTF-8&app_auth_token=&sign=2fzAvDER9dmvoe2LRS4ZPGlJyo6yDbAC%2FKiQG%2FZl0eyzcvwR2b99loTMHPcPREmP%2FGVSvWyXOWBIoCnC2NwykM1BWJ68YqGxWCnBBOynhmoH4TtQBk1QCquSv4rr4Rxumv88nE44Qz1YGd2zRAPhL0FMzxphH7kjaI4wT0%2BXqWCqe3Eh6jdsK0NAsva%2Bol4%2BtIxNLhm30Lf3uayWzgfG3m2jm2CEAgY8fwwByzcPXUy4RHwUHertOoBJqPRehmLR18o664EJylpKrLpe4ibJV7VVdarkcJUt7uMk0es8AOCGb505E55I8DU%2FbslTs1sD%2BllieqIXqJIz4aoeLu15kw%3D%3D';
        // $data = 'biz_content=%7B%22out_biz_no%22%3A%22%271556124427%27%22%2C%22payee_type%22%3A%22ALIPAY_LOGONID%22%2C%22payee_account%22%3A%22%2713226785330%27%22%2C%22amount%22%3A%22%270.1%27%22%2C%22payer_show_name%22%3A%22%E5%A2%A8%E5%AE%B6%E4%BA%92%E5%A8%B1%22%2C%22payee_real_name%22%3A%22%27%E6%B2%99%E7%AE%B1%E7%8E%AF%E5%A2%83%27%22%2C%22remark%22%3A%22%27%E6%B2%99%E7%AE%B1%E7%8E%AF%E5%A2%83%27%22%7D';
        // curl_post_query($url,$data);
        $this->assign('meta_title', '首页');
        return $this->fetch();
    }

}

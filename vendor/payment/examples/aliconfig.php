<?php
/**
 * @author: helei
 * @createTime: 2016-07-15 17:19
 * @description:
 */

// 一下配置均为本人的沙箱环境，贡献出来，大家测试

// 个人沙箱帐号：
/*
 * 商家账号   naacvg9185@sandbox.com
 * appId     2016073100130857
 */

/*
 * !!!作为一个良心人，别乱改测试账号资料
 * 买家账号    aaqlmq0729@sandbox.com
 * 登录密码    111111
 * 支付密码    111111
 */

return [
    'use_sandbox'               => false,// 是否使用沙盒模式

    'app_id'                    => '2019050264367537',
    'sign_type'                 => 'RSA2',// RSA  RSA2

    // ！！！注意：如果是文件方式，文件中只保留字符串，不要留下 -----BEGIN PUBLIC KEY----- 这种标记
    // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
    'ali_public_key'            => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjaw+zIeq9IU07mw62+q1xVHGxrUpyGPWchp6oJQIoKx+odn8mAvi8yZvA/idj9cjVJ9Uzv+0isaOSJoI7p19ER9wbDvmvtXDo+bWfPGNRnZTyxzrfRD9PVNvxAyVw+rnCfbG9VhV3mYll0edlCRXJJYJNhf/9jQnTBxmMpZRa0SdH2IxdcDgkf7eFJUzTZudR9oW1zvFcZjV+GVQ8vAenYTNHzWsv21I9o1ErvP0OOb2UGx+DpEW+MEjbYFXHoyqaoUnbGo2HpCkx9LliAehSgrrPKSsHukpQj9A4VRfES5sNQM5nD2ygF4hOFWsxG8E7EXNzCerxTRHjgCsRfZ43QIDAQAB',

    // ！！！注意：如果是文件方式，文件中只保留字符串，不要留下 -----BEGIN RSA PRIVATE KEY----- 这种标记
    // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
    'rsa_private_key'           => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCNrD7Mh6r0hTTubDrb6rXFUcbGtSnIY9ZyGnqglAigrH6h2fyYC+LzJm8D+J2P1yNUn1TO/7SKxo5ImgjunX0RH3BsO+a+1cOj5tZ88Y1GdlPLHOt9EP09U2/EDJXD6ucJ9sb1WFXeZiWXR52UJFcklgk2F//2NCdMHGYyllFrRJ0fYjF1wOCR/t4UlTNNm51H2hbXO8VxmNX4ZVDy8B6dhM0fNay/bUj2jUSu8/Q45vZQbH4OkRb4wSNtgVcejKpqhSdsajYekKTH0uWIB6FKCus8pKwe6SlCP0DhVF8RLmw1AzmcPbKAXiE4VazEbwTsRc3MJ6vFNEeOAKxF9njdAgMBAAECggEAVhak0oReTdfkIj2CRsCJVC4tK/JKQYrpdMzCV3GdDIXFLXTZGUufzUE9lJwuoomI3pMzZdXcT7f4HgX8B4OLzCvelOaRgMVE7QQIskPWJUsh//rC3mzEdc+NywQavcKwQk3C+LOE+m/3x8Ws66hpi8HgNw6+a02l04ouT+8n6pYM1f+4Vy7Fb4LdDEkzKDkvtxj+czXDp/1gnRExvqpMkK9iwFfmvO4rO8Ubw+URjIX6vuIZytbnZqT1jpJNfmvigWPplOdRiyVk5YVtBKNAQYSlk8y6Tiftq9VuJEmRuSs3ohoxS9h128Fbze2/oYDtwLGZKXh2D6GivE2Xz9CGAQKBgQDezHVMJdNjIdO0iUh8oW0li9NOYkl1Bsk4ICKPiKWTEx5OT/qbvO/VZskUaHauWgwhMN12wkM6UI0tBonO/ZOON7Fh/5bQCC5QKHtN//EHz932sKyO/qHgCiWzib30jSZ5XtBx8ClPiKfDIu+UaZXlgCLgoi0fxcfyvWQXL7mDmQKBgQCiyOtdF1Alt0oKSE1v5qARmZ4ldb4FWWwaVQJgocEP11wwIQhq3y4ZgHBrDJP35ec7Hyrlr1i/+hjVd3nEtEzt1F4Jj0aNXHwlch+fsfKR1eN5EcP6zmS6idj/239w+lTGN0eIylvBW/J811E7VF1lWgnesYcmOXxCG+XJdJpp5QKBgDj1xqtAJGn8tPY7/tc2IgRuWgh5IlST9o+tz4gopEQUqDPXSLfWNu61B4V7K5Rpmx5FMulwwuU+wMkZGdRcigPbAzONt43Z+ZUutE99tq6LmzC9fHBWcyYnEfpzpafHCmYPMnVetAEMa+98mAm2cMcq2j/Z1nWACB1sBBHVdrVJAoGBAIkNUiPRQfhPJfYcQ54n9LJ8vIpbZD3KuNo+oj7LUOlOb15SIW0hNAXifkOSlm3LUXAUYKB6jeUr4oavDYVQK8i82OOBjmvr5tX8DKX+QvUHuHmxPGhIJsRq1Jktq1FqYb90wTRo8vGLwU/cVJb4A54WPWMR4nCLS5O5OzDujCcFAoGAARLKIuYqRdyemWv2LaHCvVd+r9T8uyzRJK/udX8hb6mfScCyQeLsMj6oavlWioTGVDZb1zHjd8Uo64buGyXsBqzmgQXPtt3w3Vgs+WvfpUCzSCsPalhSsCaPKRvxYtoxZ/HepwegX77aT3sT6sERIEksl9wyClV5Q3mqP5JYI8k=',

    'limit_pay'                 => [
        //'balance',// 余额
        //'moneyFund',// 余额宝
        //'debitCardExpress',// 	借记卡快捷
        //'creditCard',//信用卡
        //'creditCardExpress',// 信用卡快捷
        //'creditCardCartoon',//信用卡卡通
        //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
    ],// 用户不可用指定渠道支付当有多个渠道时用“,”分隔

    // 与业务相关参数
    'notify_url'                => 'https://helei112g.github.io/v1/notify/ali',
    'return_url'                => 'https://helei112g.github.io/',

    'return_raw'                => false,// 在处理回调时，是否直接返回原始数据，默认为 true
];

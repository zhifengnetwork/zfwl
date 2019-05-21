<?php
return [
    //商品分类
    'good'      => [
        'id'    => 20000,
        'title' => '手机商城',
        'sort'  => 2,
        'url'   => 'index/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-apple',
        'child' => [
            [
                'id'    => 20100,
                'title' => '商城设置',
                'sort'  => 1,
                'icon'  => 'fa-th-large',
                'url'   => 'index/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20101,
                        'title' => '店铺装修',
                        'sort'  => 1,
                        'url'   => 'index/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20102,
                        'title' => '店铺编辑',
                        'sort'  => 1,
                        'url'   => 'index/page_edit',
                        'hide'  => 0,
                     ],
                     [
                        'id'    => 20103,
                        'title' => '店铺新增',
                        'sort'  => 1,
                        'url'   => 'index/page_add',
                        'hide'  => 0,
                     ],
                    [
                        'id'    => 20104,
                        'title' => '基本设置',
                        'sort'  => 1,
                        'url'   => 'site/index',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20200,
                'title' => '支付交易设置',
                'sort'  => 2,
                'icon'  => 'fa-th-large',
                'url'   => 'index/pay_set',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20201,
                        'title' => '微信支付',
                        'sort'  => 1,
                        'url'   => 'index/pay_wechat',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20202,
                        'title' => '支付宝支付',
                        'sort'  => 1,
                        'url'   => 'index/pay_alipay',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20203,
                        'title' => '支付交易设置',
                        'sort'  => 1,
                        'url'   => 'index/pay_py',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20300,
                'title' => '消息提醒模板',
                'sort'  => 3,
                'icon'  => 'fa-th-large',
                'url'   => 'index/notice',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20301,
                        'title' => '商城提醒',
                        'sort'  => 1,
                        'url'   => 'index/notice',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20400,
                'title' => '系统操作日志',
                'sort'  => 4,
                'icon'  => 'fa-th-large',
                'url'   => 'index/notice',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20401,
                        'title' => '系统操作日志',
                        'sort'  => 1,
                        'url'   => 'log/index',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
    ],
       //商品模块
       'goods'      => [
        'id'    => 30000,
        'title' => '商品模块',
        'sort'  => 2,
        'url'   => 'goods/index',
        'icon'  => 'fa-th-large',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-briefcase',
        'child' => [
            [
                'id'    => 30100,
                'title' => '商品管理',
                'sort'  => 1,
                'url'   => 'goods/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30101,
                        'title' => '商品列表',
                        'sort'  => 1,
                        'url'   => 'goods/index',
                        'hide'  => 1,
                        'child' => [
                            
                        ],
                    ],
                    [
                        'id'    => 30102,
                        'title' => '添加商品',
                        'sort'  => 2,
                        'url'   => 'goods/add',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30103,
                        'title' => '修改商品',
                        'sort'  => 3,
                        'url'   => 'goods/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30104,
                        'title' => '配送方式',
                        'sort'  => 4,
                        'url'   => 'goods/goods_delivery_list',
                        'hide'  => 1,
                        'icon'  => 'fa-th-large',
                        'child' => [
                            
                        ],
                    ],
                    [
                        'id'    => 30105,
                        'title' => '添加配送方式',
                        'sort'  => 0,
                        'url'   => 'goods/goods_delivery_add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30106,
                        'title' => '修改配送方式',
                        'sort'  => 0,
                        'url'   => 'goods/goods_delivery_edit',
                        'hide'  => 0,
                    ],
                ],
            ],
            [
                'id'    => 30300,
                'title' => '商品分类',
                'sort'  => 0,
                'url'   => 'category/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30301,
                        'title' => '分类列表',
                        'sort'  => 1,
                        'url'   => 'category/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30302,
                        'title' => '添加分类',
                        'sort'  => 2,
                        'url'   => 'category/add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30303,
                        'title' => '修改分类',
                        'sort'  => 3,
                        'url'   => 'category/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30304,
                        'title' => '分类层级设置',
                        'sort'  => 2,
                        'url'   => 'category/category_set',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 30400,
                'title' => '虚拟商品',
                'sort'  => 4,
                'url'   => 'goods/virtual_goods_list',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30401,
                        'title' => '虚拟物品模版列表',
                        'sort'  => 1,
                        'url'   => 'goods/virtual_goods_list',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30402,
                        'title' => '添加虚拟物品模版',
                        'sort'  => 2,
                        'url'   => 'goods/virtual_goods_add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30403,
                        'title' => '虚拟分类列表',
                        'sort'  => 3,
                        'url'   => 'goods/virtual_category_list',
                        'hide'  => 1,
                        'child' => [
                            
                        ],
                    ],
                    [
                        'id'    => 30404,
                        'title' => '添加虚拟分类',
                        'sort'  => 1,
                        'url'   => 'goods/virtual_category_add',
                        'hide'  => 0,
                    ],
                ],
            ],
            [
                'id'    => 30500,
                'title' => '优惠券管理',
                'sort'  => 5,
                'url'   => 'coupon/coupon_list',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30501,
                        'title' => '优惠券列表',
                        'sort'  => 1,
                        'url'   => 'coupon/coupon_list',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30502,
                        'title' => '添加优惠券',
                        'sort'  => 2,
                        'url'   => 'coupon/coupon_add',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30503,
                        'title' => '修改优惠券',
                        'sort'  => 1,
                        'url'   => 'coupon/coupon_edit',
                        'hide'  => 0,
                    ],
                ],
            ],
        ],
    ],

    //订单管理
    'oreder'      => [
        'id'    => 40000,
        'title' => '订单管理',
        'sort'  => 2,
        'url'   => 'order/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-edit',
        'child' => [
            [
                'id'    => 40100,
                'title' => '销售订单',
                'sort'  => 1,
                'url'   => 'order/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 40101,
                        'title' => '全部订单',
                        'sort'  => 1,
                        'url'   => 'order/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40102,
                        'title' => '发货单列表',
                        'sort'  => 1,
                        'url'   => 'order/edit',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40103,
                        'title' => '发货编辑',
                        'sort'  => 1,
                        'url'   => 'order/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 40104,
                        'title' => '发货编辑',
                        'sort'  => 1,
                        'url'   => 'order/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 40105,
                        'title' => '退换货列表',
                        'sort'  => 1,
                        'url'   => 'order/order_refund',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40106,
                        'title' => '退换货详情',
                        'sort'  => 1,
                        'url'   => 'order/refund_edit',
                        'hide'  => 0,
                    ],
                ],

            ],
            [
                'id'    => 40300,
                'title' => '快递助手',
                'sort'  => 3,
                'url'   => 'order/senduser',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 40301,
                        'title' => '发货人信息管理',
                        'sort'  => 1,
                        'url'   => 'order/senduser',
                        'hide'  => 1,
                      
                    ],
                    [
                        'id'    => 40302,
                        'title' => '发货人信息新增',
                        'sort'  => 1,
                        'url'   => 'order/senduseradd',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 40303,
                        'title' => '发货人信息编辑',
                        'sort'  => 1,
                        'url'   => 'order/senduseredit',
                        'hide'  => 0,
                    ],

                    [
                        'id'    => 40304,
                        'title' => '订单打印',
                        'sort'  => 1,
                        'url'   => 'order/doprint',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40305,
                        'title' => '模板管理',
                        'sort'  => 1,
                        'url'   => 'order/express',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40306,
                        'title' => '打印设置',
                        'sort'  => 1,
                        'url'   => 'order/printset',
                        'hide'  => 1,
                    ],
                ],

            ],
        ],
    ],

      //数据分析
      'baobiao'      => [
        'id'    => 60000,
        'title' => '财务管理',
        'sort'  => 2,
        'url'   => 'finance/balance_logs',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-file',
        'child' => [
            [
                'id'    => 60100,
                'title' => '财务管理',
                'sort'  => 1,
                'url'   => 'finance/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 60101,
                        'title' => '余额记录',
                        'sort'  => 1,
                        'url'   => 'finance/balance_logs',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60102,
                        'title' => '积分记录',
                        'sort'  => 1,
                        'url'   => 'finance/integral_logs',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60103,
                        'title' => '余额充值',
                        'sort'  => 1,
                        'url'   => 'finance/balance_Recharge',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 60104,
                        'title' => '积分充值',
                        'sort'  => 1,
                        'url'   => 'finance/integral_Recharge',
                        'hide'  => 0,
                    ],

                ],

            ],
            [
                'id'    => 60200,
                'title' => '余额提现',
                'sort'  => 2,
                'url'   => 'finance/withdrawal_list',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 60201,
                        'title' => '余额提现列表',
                        'sort'  => 1,
                        'url'   => 'finance/withdrawal_list',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60204,
                        'title' => '余额提现设置',
                        'sort'  => 1,
                        'url'   => 'finance/withdrawalset',
                        'hide'  => 1,
                    ],
                   
                ],
            ],
           
         ],
     ],



    
    
    //配置管理
    'pz_config' => [
        'id'    => 50000,
        'title' => '配置管理',
        'sort'  => 8,
        'url'   => 'config/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-link',
        'child' => [
            [
                'id'    => 50100,
                'title' => '首页轮播图',
                'sort'  => 1,
                'url'   => 'advertisement/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 50101,
                        'title' => '首页轮播图',
                        'sort'  => 1,
                        'url'   => 'advertisement/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 50201,
                        'title' => '轮播图编辑',
                        'sort'  => 1,
                        'url'   => 'advertisement/edit',
                        'hide'  => 0,
                    ],
                ],
            ],
        ],
    ],



    //系统设置
    'sys_config'      => [
        'id'    => 210000,
        'title' => '系统设置',
        'sort'  => 21,
        'url'   => 'mguser/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-cog',
        'child' => [
            [
                'id'    => 210100,
                'title' => '管理员',
                'sort'  => 1,
                'url'   => 'mguser/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                   
                    [
                        'id'    => 210101,
                        'title' => '编辑',
                        'sort'  => 1,
                        'url'   => 'mguser/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210102,
                        'title' => '用户授权',
                        'sort'  => 2,
                        'url'   => '',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210103,
                        'title' => '修改密码',
                        'sort'  => 3,
                        'url'   => 'mguser/update_pwsd',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210104,
                        'title' => '管理人员',
                        'sort'  => 1,
                        'url'   => 'mguser/index',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 210200,
                'title' => '权限分组',
                'sort'  => 2,
                'url'   => 'auths/auth_group',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                  
                    [
                        'id'    => 210201,
                        'title' => '编辑分组',
                        'sort'  => 1,
                        'url'   => 'auths/edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210202,
                        'title' => '分组授权',
                        'sort'  => 2,
                        'url'   => 'auths/manage_auths',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210203,
                        'title' => '授权用户',
                        'sort'  => 3,
                        'url'   => 'auths/auth_user',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 210204,
                        'title' => '权限分组',
                        'sort'  => 1,
                        'url'   => 'auths/auth_group',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 210300,
                'title' => '系统菜单',
                'sort'  => 3,
                'url'   => 'menu/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 210301,
                        'title' => '菜单列表',
                        'sort'  => 1,
                        'url'   => 'menu/index',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 210400,
                'title' => '微信管理',
                'sort'  => 1,
                'url'   => 'wxfans/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                   
                    [
                        'id'    => 210401,
                        'title' => '粉丝列表',
                        'sort'  => 1,
                        'url'   => 'wxfans/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210402,
                        'title' => '微信菜单',
                        'sort'  => 2,
                        'url'   => 'wxmenu/index',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
     ],

     //分销管理
    'distribution' => [
        'id'    => 70000,
        'title' => '分销管理',
        'sort'  => 9,
        'url'   => 'distribution/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-fullscreen',
        'child' => [
            [
                'id'    => 70100,
                'title' => '分销中心',
                'sort'  => 1,
                'url'   => 'distribution/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 70101,
                        'title' => '分销中心入口',
                        'sort'  => 1,
                        'url'   => 'distribution/distribution_center',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 70201,
                        'title' => '分销设置',
                        'sort'  => 2,
                        'url'   => 'distribution/distribution_set',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 70301,
                        'title' => '分销商',
                        'sort'  => 3,
                        'url'   => 'distribution/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 70401,
                        'title' => '分销商等级',
                        'sort'  => 4,
                        'url'   => 'distribution/distribution_grade',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 70501,
                        'title' => '分销关系',
                        'sort'  => 5,
                        'url'   => 'distribution/distribution_relations',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 70601,
                        'title' => '通知设置',
                        'sort'  => 6,
                        'url'   => 'distribution/distribution_notify',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
    ],


    'statistics' => [
        'id'    => 80000,
        'title' => '统计中心',
        'sort'  => 9,
        'url'   => 'statistics/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-wrench',
        'child' => [
            [
                'id'    => 80100,
                'title' => '会员分析',
                'sort'  => 1,
                'url'   => '',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 80101,
                        'title' => '会员消费排行',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80102,
                        'title' => '会员增长趋势',
                        'sort'  => 2,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80103,
                        'title' => '分销商增长趋势统计',
                        'sort'  => 3,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80104,
                        'title' => '会员积分统计',
                        'sort'  => 4,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80105,
                        'title' => '会员余额统计',
                        'sort'  => 5,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80106,
                        'title' => '会员现金消费统计',
                        'sort'  => 6,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80107,
                        'title' => '用户分析',
                        'sort'  => 6,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 80200,
                'title' => '销售分析',
                'sort'  => 1,
                'url'   => '',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 80201,
                        'title' => '销售统计',
                        'sort'  => 1,
                        'url'   => 'statistics/sales',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80203,
                        'title' => '销售指标',
                        'sort'  => 2,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80204,
                        'title' => '订单统计',
                        'sort'  => 3,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 80300,
                'title' => '商品分析',
                'sort'  => 1,
                'url'   => '',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 80301,
                        'title' => '商品销售明细',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80302,
                        'title' => '商品销售排行',
                        'sort'  => 2,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80303,
                        'title' => '商品销售转化率',
                        'sort'  => 3,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
    ],

    'user' => [
        'id'    => 90000,
        'title' => '我的会员',
        'sort'  => 9,
        'url'   => 'member/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-user',
        'child' => [
            [
                'id'    => 90100,
                'title' => '会员设置',
                'sort'  => 1,
                'url'   => 'member/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 90101,
                        'title' => '会员管理',
                        'sort'  => 1,
                        'url'   => 'member/index',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 90102,
                        'title' => '删除会员',
                        'sort'  => 1,
                        'url'   => 'member/delete',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90103,
                        'title' => '会员详情',
                        'sort'  => 1,
                        'url'   => 'member/info',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90104,
                        'title' => '黑名单设置',
                        'sort'  => 1,
                        'url'   => 'member/black',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90105,
                        'title' => '会员等级',
                        'sort'  => 2,
                        'url'   => 'member/level',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 90106,
                        'title' => '等级编辑',
                        'sort'  => 3,
                        'url'   => 'member/level_edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90107,
                        'title' => '等级新增',
                        'sort'  => 4,
                        'url'   => 'member/level_add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90108,
                        'title' => '会员分组',
                        'sort'  => 4,
                        'url'   => 'member/group',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 90109,
                        'title' => '分组编辑',
                        'sort'  => 4,
                        'url'   => 'member/group_edit',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90110,
                        'title' => '分组新增',
                        'sort'  => 4,
                        'url'   => 'member/group_add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 90111,
                        'title' => '会员设置',
                        'sort'  => 4,
                        'url'   => 'member/set',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 90112,
                        'title' => '会员详情',
                        'sort'  => 4,
                        'url'   => 'member/member_edit',
                        'hide'  => 0,
                    ],
                ],
            ],
        ],
    ],

];

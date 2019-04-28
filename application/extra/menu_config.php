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
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20102,
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
                'url'   => '',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20201,
                        'title' => '支付方式',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20202,
                        'title' => '支付参数',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 20203,
                        'title' => '支付交易设置',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20300,
                'title' => '消息提醒模板',
                'sort'  => 3,
                'icon'  => 'fa-th-large',
                'url'   => '',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20301,
                        'title' => '商城提醒',
                        'sort'  => 1,
                        'url'   => '',
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
                        'title' => '添加商品',
                        'sort'  => 1,
                        'url'   => 'goods/add',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30102,
                        'title' => '商品列表',
                        'sort'  => 1,
                        'url'   => 'goods/index',
                        'hide'  => 1,
                    ],
                ],
            ],

            [
                'id'    => 30200,
                'title' => '商品规格管理',
                'sort'  => 1,
                'url'   => 'goods/goods_type_list',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30202,
                        'title' => '商品规格列表',
                        'sort'  => 1,
                        'url'   => 'goods/goods_type_list',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 30203,
                        'title' => '添加商品类型',
                        'sort'  => 3,
                        'url'   => 'goods/goods_type_add',
                        'hide'  => 1,
                    ],
                    
                ],
            ],
            [
                'id'    => 30300,
                'title' => '配送方式',
                'sort'  => 1,
                'url'   => 'goods/goods_delivery_list',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 30301,
                        'title' => '新增配送',
                        'sort'  => 1,
                        'url'   => 'goods/goods_delivery_add',
                        'hide'  => 1,
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
                        'id'    => 40106,
                        'title' => '订单详情',
                        'sort'  => 1,
                        'url'   => 'order/edit',
                        'hide'  => 0,
                    ],
                   
                ],

            ],
            [
                'id'    => 40200,
                'title' => '退款订单',
                'sort'  => 2,
                'url'   => '',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 40201,
                        'title' => '退款订单',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],

            ],
            [
                'id'    => 40300,
                'title' => '快递助手',
                'sort'  => 3,
                'url'   => '',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 40301,
                        'title' => '发货人信息管理',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40302,
                        'title' => '单个打印',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40303,
                        'title' => '批量打印',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40304,
                        'title' => '快递单模板管理',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40305,
                        'title' => '发货单模板管理',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40306,
                        'title' => '商品简称',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40307,
                        'title' => '打印设置',
                        'sort'  => 1,
                        'url'   => '',
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
        'url'   => 'total/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-file',
        'child' => [
            [
                'id'    => 60100,
                'title' => '财务管理',
                'sort'  => 1,
                'url'   => '',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 60101,
                        'title' => '余额记录',
                        'sort'  => 1,
                        'url'   => 'total/balance_logs',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60102,
                        'title' => '积分记录',
                        'sort'  => 1,
                        'url'   => 'total/integral_logs',
                        'hide'  => 1,
                    ],
                ],

            ],
            [
                'id'    => 60200,
                'title' => '余额体现',
                'sort'  => 2,
                'url'   => 'total/finance',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 60201,
                        'title' => '待审核',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60202,
                        'title' => '通过审批',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60203,
                        'title' => '不通过审批',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 60204,
                        'title' => '余额提现设置',
                        'sort'  => 1,
                        'url'   => '',
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
            [
                'id'    => 50200,
                'title' => '配送方式',
                'sort'  => 2,
                'url'   => 'delivery/index',
                'icon'  => 'fa-th-large',
                'hide'  => 1,
            ],
            [
                'id'    => 50300,
                'title' => '客服设置',
                'sort'  => 3,
                'url'   => 'config/get_config',
                'icon'  => 'fa-th-large',
                'hide'  => 1,
            ],
           
        ],
    ],



    //系统设置
    'sys_config'      => [
        'id'    => 210000,
        'title' => '系统设置',
        'sort'  => 21,
        'url'   => 'auths/auth_group',
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
                'title' => '权限管理',
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
                        'title' => '权限管理',
                        'sort'  => 1,
                        'url'   => 'auths/auth_group',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 210300,
                'title' => '菜单',
                'sort'  => 3,
                'url'   => 'menu/index',
                'hide'  => 1,
                'icon'  => 'fa-th-large',
                'child' => [
                    [
                        'id'    => 210301,
                        'title' => '更新菜单',
                        'sort'  => 1,
                        'url'   => 'menu/import_menu',
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
                        'url'   => '',
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
                    [
                        'id'    => 80205,
                        'title' => '流量入口统计',
                        'sort'  => 4,
                        'url'   => 'distribution/distribution_grade',
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
                    [
                        'id'    => 80304,
                        'title' => '入驻商家销售排行',
                        'sort'  => 4,
                        'url'   => 'distribution/distribution_grade',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 80305,
                        'title' => '入驻商家流量排行',
                        'sort'  => 4,
                        'url'   => 'distribution/distribution_grade',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
    ],

];

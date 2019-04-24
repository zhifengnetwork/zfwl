<?php
return [
    //商品分类
    'good'      => [
        'id'    => 20000,
        'title' => '测试标签',
        'sort'  => 2,
        'url'   => 'index/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-duplicate',
        'child' => [
            [
                'id'    => 20100,
                'title' => '测试1',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20101,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
                        'hide'  => 1,
                    ],
                ],
            ],

            [
                'id'    => 20200,
                'title' => '测试2',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20201,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20300,
                'title' => '测试三',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20301,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20400,
                'title' => '测试4',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20401,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 20500,
                'title' => '测试5',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 20501,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
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
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-briefcase',
        'child' => [
            [
                'id'    => 30100,
                'title' => '商品管理',
                'sort'  => 1,
                'url'   => 'goods/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 30101,
                        'title' => '添加商品',
                        'sort'  => 1,
                        'url'   => 'goods/add',
                        'hide'  => 0,
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
                'child' => [
                    [
                        'id'    => 30201,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
                        'hide'  => 0,
                    ],
                    [
                        'id'    => 30202,
                        'title' => '商品规格列表',
                        'sort'  => 1,
                        'url'   => 'goods/goods_type_list',
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
                'child' => [
                    
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
                'url'   => 'order/index2',
                'hide'  => 1,
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
                        'title' => '待付款',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40103,
                        'title' => '待发货',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40104,
                        'title' => '待收货',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40105,
                        'title' => '已完成',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                ],

            ],
            [
                'id'    => 40200,
                'title' => '退款订单',
                'sort'  => 2,
                'url'   => '',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 40201,
                        'title' => '退款申请',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40202,
                        'title' => '已退款',
                        'sort'  => 1,
                        'url'   => '',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 40203,
                        'title' => '已关闭',
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
        'title' => '数据管理',
        'sort'  => 2,
        'url'   => 'total/index',
        'hide'  => 1,
        'icon'  => 'glyphicon glyphicon-file',
        'child' => [
            [
                'id'    => 60100,
                'title' => '业务数据',
                'sort'  => 1,
                'url'   => 'total/business',
                'hide'  => 1,
                'child' => [
                    
                ],

            ],
            [
                'id'    => 60200,
                'title' => '财务数据',
                'sort'  => 1,
                'url'   => 'total/finance',
                'hide'  => 1,
                'child' => [

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
        'icon'  => 'glyphicon glyphicon-wrench',
        'child' => [
            [
                'id'    => 50100,
                'title' => '首页轮播图',
                'sort'  => 1,
                'url'   => 'advertisement/index',
                'hide'  => 1,
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
                'hide'  => 1,
            ],
            [
                'id'    => 50300,
                'title' => '客服设置',
                'sort'  => 3,
                'url'   => 'config/get_config',
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
            [
                'id'    => 210400,
                'title' => '网站设置',
                'sort'  => 4,
                'url'   => 'site/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 210401,
                        'title' => '网站设置',
                        'sort'  => 2,
                        'url'   => 'site/index',
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
        'icon'  => 'glyphicon glyphicon-wrench',
        'child' => [
            [
                'id'    => 70100,
                'title' => '分销中心',
                'sort'  => 1,
                'url'   => 'distribution/index',
                'hide'  => 1,
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

];

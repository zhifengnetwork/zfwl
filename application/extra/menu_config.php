<?php
return [
    //首页
    'index'           => [
        'id'    => 10000,
        'title' => '首页',
        'sort'  => 1,
        'url'   => 'index/index',
        'hide'  => 1,
        'icon'  => 'fa-th-large',
    ],

    //商品分类
    'good'      => [
        'id'    => 20000,
        'title' => '商品分类',
        'sort'  => 2,
        'url'   => 'user/index',
        'hide'  => 1,
        'icon'  => 'fa-user',
        'child' => [
            [
                'id'    => 20100,
                'title' => '分类管理',
                'sort'  => 1,
                'url'   => 'category/index',
                'hide'  => 1,
                
            ],
            [
                'id'    => 20200,
                'title' => '分类层级设置',
                'sort'  => 1,
                'url'   => 'category/category_set',
                'hide'  => 1,
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
        'icon'  => 'fa-user',
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
                ],
            ],
            [
                'id'    => 30200,
                'title' => '商品规格管理',
                'sort'  => 1,
                'url'   => 'goods/sku_index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 30201,
                        'title' => '添加规格',
                        'sort'  => 1,
                        'url'   => 'goods/goods_sku_add',
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
        'icon'  => 'fa-user',
        'child' => [
            [
                'id'    => 40100,
                'title' => '全部订单',
                'sort'  => 1,
                'url'   => 'order/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 40101,
                        'title' => '订单详情',
                        'sort'  => 1,
                        'url'   => 'order/edit',
                        'hide'  => 1,
                    ],
                   
                ],

            ],
            [
                'id'    => 40200,
                'title' => '退货退款',
                'sort'  => 1,
                'url'   => 'order_refund/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 40201,
                        'title' => '退款详情',
                        'sort'  => 1,
                        'url'   => 'order_refund/edit',
                        'hide'  => 1,
                    ],
                   
                ],
            ],
            [
                'id'    => 40300,
                'title' => '评价列表',
                'sort'  => 1,
                'url'   => 'comment/index',
                'hide'  => 1,
            ],
            [
                'id'    => 40400,
                'title' => '打印设置',
                'sort'  => 1,
                'url'   => 'machine/index',
                'hide'  => 1,
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
        'icon'  => 'fa-gear',
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
        'url'   => 'menu/index',
        'hide'  => 1,
        'icon'  => 'fa-cogs',
        'child' => [
            [
                'id'    => 210100,
                'title' => '用户',
                'sort'  => 1,
                'url'   => 'mguser/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 210101,
                        'title' => '编辑',
                        'sort'  => 1,
                        'url'   => 'mguser/edit',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210102,
                        'title' => '用户授权',
                        'sort'  => 2,
                        'url'   => 'mguser/set_authgroup',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210103,
                        'title' => '修改密码',
                        'sort'  => 3,
                        'url'   => 'mguser/update_pwsd',
                        'hide'  => 0,
                    ],
                ],
            ],
            [
                'id'    => 210400,
                'title' => '管理员',
                'sort'  => 1,
                'url'   => 'mguser/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 210401,
                        'title' => '编辑',
                        'sort'  => 1,
                        'url'   => 'mguser/edit',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210402,
                        'title' => '用户授权',
                        'sort'  => 2,
                        'url'   => 'mguser/set_authgroup',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210403,
                        'title' => '修改密码',
                        'sort'  => 3,
                        'url'   => 'mguser/update_pwsd',
                        'hide'  => 0,
                    ],
                ],
            ],
            [
                'id'    => 210200,
                'title' => '权限',
                'sort'  => 2,
                'url'   => 'auths/auth_group',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 210201,
                        'title' => '编辑分组',
                        'sort'  => 1,
                        'url'   => 'auths/edit',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210202,
                        'title' => '分组授权',
                        'sort'  => 2,
                        'url'   => 'auths/manage_auths',
                        'hide'  => 1,
                    ],
                    [
                        'id'    => 210203,
                        'title' => '授权用户',
                        'sort'  => 3,
                        'url'   => 'auths/auth_user',
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
                    [
                        'id'    => 210302,
                        'title' => '初始化数据库',
                        'sort'  => 2,
                        'url'   => 'database/cleanDatabase',
                        'hide'  => 1,
                    ],
                ],
            ],
            [
                'id'    => 210500,
                'title' => '网站设置',
                'sort'  => 4,
                'url'   => 'site/index',
                'hide'  => 1,
                'child' => [
                    [
                        'id'    => 210501,
                        'title' => '网站设置',
                        'sort'  => 2,
                        'url'   => 'site/index',
                        'hide'  => 1,
                    ],
                ],
            ],
        ],
    ],
];

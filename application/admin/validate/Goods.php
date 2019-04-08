<?php
// +----------------------------------------------------------------------
// | Minishop [ Easy to handle for Micro businesses]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.qasl.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: tangtanglove <dai_hang_love@126.com> <http://www.ixiaoquan.com>
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'goods_name'     => 'require',
        'cat_id1'        => 'require',
    ];

    protected $message = [
        'goods_name.require '    => '商品名称必须填写',
        'cat_id1.require '       => '分类必须选择',
    ];

    protected $scene = [
        'add'     => ['goods_name','cat_id1'],
        'edit'    => ['goods_name','cat_id1'],
    ];
}

<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\GoodsPromFactory;
use app\common\model\GoodsCategory;
use think\AjaxPage;
use think\Page;
use think\Db;

class Goods extends ApiBase
{

   /**
    * 商品分类接口
    */
    public function categoryList()
    {
        
        $list = Db::name('category')->where('is_show',1)->order('sort DESC,cat_id ASC')->select();
        $list  = getTree1($list);
        foreach($list as $key=>$value){
            $list[$key]['goods'] = Db::table('goods')->alias('g')
                                ->join('goods_attr ga','FIND_IN_SET(ga.attr_id,g.goods_attr)','LEFT')
                                ->where('cat_id1',$value['cat_id'])
                                ->group('g.goods_id')
                                ->field('goods_id,img,price,original_price,GROUP_CONCAT(ga.attr_name) attr_name,ga.attr_id comment')
                                ->select();
            
        }

        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$list]);
    }


    public function Products()
    {
        $cat_id = I('get.cat_id/d');
        // dump($cat_id);exit;

        // $data = Db::name('goods')->where('cat_id',$cat_id)->select();
        $data = Db::name('goods')->where('cat_id',$cat_id)->field('goods_id,goods_name,original_img')->select();
        //  dump($data);exit;

        foreach($data as $k => $v){
            $data[$k]['original_img'] = SITE_URL.$v['original_img'];
        }

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
    * 商品列表
    */
    public function goodsList(){
        
        $keywords = input('keywords');
        $cat_id = input('cat_id');
        
        $where = [];

        if ($keywords) $where['g.goods_name'] = array('like',"%{$keywords}%");
        if ($cat_id){
            $cat_id1 = Db::table('category')->where('cat_id',$cat_id)->value('pid');
            if($pid){
                $where['g.cat_id2'] = $cat_id;
            }else{
                $where['g.cat_id1'] = $cat_id;
            }
        }
        
        $order['g.goods_id'] = 'desc';
        $goodsM = model("Goods");
        // 分页列表
        $goodsRes = $goodsM->goodsList($where, $order);
        $goodsRes = $goodsRes->toArray();
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$goodsRes]);
        

    }


    //获取商品sku字符串
    public function get_sku_str($sku_id)
    {
        $sku_attr = Db::name('goods_sku')->where('sku_id', $sku_id)->value('sku_attr');
        $sku_attr = json_decode($sku_attr, true);
        $key_arr = [];
        $val_arr = [];
        $key_arr = array_merge($key_arr, array_keys($sku_attr));
        $val_arr = array_merge($val_arr, array_values($sku_attr));

        $key_arr = array_unique($key_arr);
        $val_arr = array_unique($val_arr);

        $key_str = implode(',', $key_arr);
        $val_str = implode(',', $val_arr);

        //求出所有规格值
        $spec_res = Db::name('goods_spec')->where('spec_id', 'in', $key_str)->column('spec_name', 'spec_id');
        //求出所有属性值
        $attr_res = Db::name('goods_spec_val')->where('val_id', 'in', $val_str)->column('val_name', 'val_id');

        foreach ($sku_attr as $k => $v) {
            $sku_attr[$spec_res[$k]] = $attr_res[$v];
            unset($sku_attr[$k]);
        }

        $sku_attr = json_encode($sku_attr, JSON_UNESCAPED_UNICODE);
        $sku_attr = str_replace(array('{', '"', '}'), array('', '', ''), $sku_attr);

        return $sku_attr;
    }





    /**
     * +---------------------------------
     * 首页点击[看相似]根据分类id跳转至商品列表页
     * +---------------------------------
    */
    // public function goodsList()
    // {
    //     $filter_param = array();            // 筛选数组
    //     $id = I('id');                      // 当前分类id
    //     $brand_id = I('brand_id/d', 0);     // 品牌
    //     $spec = I('spec', 0);               // 规格
    //     $attr = I('attr', '');              // 属性
    //     $sort = I('sort', 'sort');          // 排序
    //     $sort_asc = I('sort_asc', 'desc');  // 排序
    //     $price = I('price', '');            // 价钱
    //     $start_price = trim(I('start_price', '0'));         // 输入框价钱
    //     $end_price   = trim(I('end_price', '0'));             // 输入框价钱
    //     if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱

    //     //如果分类是数字
    //     if(is_numeric($id)){
    //         $filter_param['id'] = $id; //加入筛选条件中
    //     }else{
           
    //         //如果不是字母
    //         if($id == 'DISTRIBUT'){
    //             $con['sign_free_receive'] = 1;
    //         }
    //         if($id == 'AGENT'){
    //             $con['sign_free_receive'] = 2;
    //         }
    //     }
           
    //     $brand_id && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
    //     $spec && ($filter_param['spec'] = $spec);             //加入筛选条件中
    //     $attr && ($filter_param['attr'] = $attr);             //加入筛选条件中
    //     $price && ($filter_param['price'] = $price);          //加入筛选条件中

    //     $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
    //     // 分类菜单显示
    //     $goodsCate = M('GoodsCategory')->where("id", $id)->find();  // 当前分类
    //     //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
    //     $cateArr = $goodsLogic->get_goods_cate($goodsCate);

    //     // 筛选 品牌 规格 属性 价格
    //     $cat_id_arr = getCatGrandson($id);
    //     $goods_where = ['is_on_sale' => 1, 'exchange_integral' => 0, 'cat_id' => ['in', $cat_id_arr]];
    //     $filter_goods_id = Db::name('goods')->where($goods_where)->cache(true)->getField("goods_id", true);

    //     // 过滤筛选的结果集里面找商品
    //     if ($brand_id || $price)// 品牌或者价格
    //     {
    //         $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
    //         $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1);    // 获取多个筛选条件的结果 的交集
    //     }
    //     if ($spec) // 规格
    //     {
    //         $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec);                 // 根据 规格 查找当所有商品id
    //         $filter_goods_id = array_intersect($filter_goods_id, $goods_id_2);  // 获取多个筛选条件的结果 的交集
    //     }
    //     if ($attr)  // 属性
    //     {

    //         $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr);                 // 根据 规格 查找当所有商品id
    //         $filter_goods_id = array_intersect($filter_goods_id, $goods_id_3);  // 获取多个筛选条件的结果 的交集
    //     }

    //     //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
    //     $sel = I('sel');
    //     if ($sel) {
    //         $goods_id_4 = $goodsLogic->getFilterSelected($sel, $cat_id_arr);
    //         $filter_goods_id = array_intersect($filter_goods_id, $goods_id_4);
    //     }

    //     $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'goodsList');                      // 获取显示的筛选菜单
    //     $filter_price = $goodsLogic->get_filter_price($filter_goods_id, $filter_param, 'goodsList');  // 筛选的价格期间
    //     $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id, $filter_param, 'goodsList');  // 获取指定分类下的筛选品牌
    //     $filter_spec = $goodsLogic->get_filter_spec($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的筛选规格
    //     $filter_attr = $goodsLogic->get_filter_attr($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的筛选属性

    //     $count = count($filter_goods_id);
    //     $page = new Page($count, C('PAGESIZE'));
    //     if ($count > 0) {
    //         $sort_asc = $sort_asc == 'asc' ? 'desc' : 'asc'; // 防注入
    //         $sort_arr = ['sales_sum','shop_price','is_new','comment_count','sort'];
    //         if(!in_array($sort,$sort_arr)) $sort='sort';    // 防注入

    //         $goods_list = M('goods')->where("goods_id", "in", implode(',', $filter_goods_id))
    //         ->field('goods_id,seller_id,cat_id,extend_cat_id,goods_sn,goods_name,store_count,comment_count,weight,shop_price,goods_remark,original_img')
    //         ->where($con)
    //         ->order([$sort => $sort_asc])->limit($page->firstRow . ',' . $page->listRows)
    //         ->select();
    //         $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
    //         if ($filter_goods_id2)
    //             $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
    //     }
    //     $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
    //     C('TOKEN_ON', false);
    //     $data = [
    //         'goods_list' => $goods_list,            // 商品列表
    //         'goods_category' => $goods_category,    // 商品分类
    //         'goods_images' => $goods_images,        // 相册图片
    //         'filter_menu' => $filter_menu,          // 筛选菜单
    //         'filter_spec' => $filter_spec,          // 筛选规格
    //         'filter_attr' => $filter_attr,          // 筛选属性
    //         'filter_brand' => $filter_brand,        // 列表页筛选属性 - 商品品牌
    //         'filter_price' => $filter_price,        // 筛选的价格期间
    //         'goodsCate' => $goodsCate,              // 传入当前分类
    //         'cateArr' => $cateArr,                  // 分类菜单显示
    //         'filter_param' => $filter_param,        // 筛选参数
    //         'cat_id' => $cat_id,                    // 筛选分类id
    //         'page' => $page,                        // 分页
    //         'sort_asc' => $sort_asc == 'asc' ? 'desc' : 'asc'
    //     ];
    //     $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    // }
}

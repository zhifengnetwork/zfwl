<?php
use think\Db;

function jason($data=array(),$msg="ok",$code=1){
    $result=array(  
      'code'=>$code,  
      'msg'=>$msg, 
      'data'=>$data   
    );  
    //输出json  
    echo json_encode($result);  
    exit;  
} 

function getTree($array, $pid =0, $level = 0){
    //声明静态数组,避免递归调用时,多次声明导致数组覆盖
    static $list = [];
    foreach ($array as $key => $value){
        //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
        if ($value['pid'] == $pid){
            //父节点为根节点的节点,级别为0，也就是第一级
            $value['level'] = $level;
            //把数组放到list中
            $list[] = $value;
            //把这个节点从数组中移除,减少后续递归消耗
            unset($array[$key]);
            //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
           getTree($array, $value['cat_id'], $level+1);
        }
    }
    return $list;
}

//树结构
function getTree1($items,$pid ="pid") {
    $map  = [];
    $tree = [];
    foreach ($items as &$it){ $map[$it['cat_id']] = &$it; }  //数据的ID名生成新的引用索引树
    foreach ($items as &$at){
        $parent = &$map[$at[$pid]];
        if($parent) {
            $parent['children'][] = &$at;
        }else{
            $tree[] = &$at;
        }
    }
    return $tree;
}

/**
 * 获取当前用户权限，控制菜单对某个用户是否显示
 */
function get_menu_auth()
{
    //超级管理员，直接返回
    if (UID == IS_ROOT) {
        return 1;
    }
    //获取当前登录用户所在的用户组(可以是多组)
    $groups = Db::table('auth_group_access')->where('mgid', UID)->column('group_id');
    if (!$groups) {
        return 2; //没有任何权限
    }
    //所有权限数组
    $rules_array = [];
    $arr         = [];
    foreach ($groups as $v) {
        $rules = Db::table('auth_group')->where('id', $v)->where('status', 1)->value('rules');
        if ($rules) {
            $arr = explode(',', $rules);
        }
        $rules_array = array_merge($rules_array, $arr);
    }
    //去除重复
    $rules_array = array_unique($rules_array);
    return $rules_array;
}

/**
 * 权限判断，设置菜单对某个用户是否可见
 * @param  [type] $rule_id      [当前菜单id]
 * @param  [type] $rules       [权限id组]
 */
function check_menu_auth($rule_id, $rules)
{
    //权限判断
    if (is_array($rules)) {
        if (!in_array($rule_id, $rules)) {
            return false;
        }
        return true;
    } else {
        if ($rules == 1) {
            //超级管理员，拥有所有权�?
            return true;
        }
        return false;
    }
}

/**
 * 获取牌局的对应图片
 */
function get_play_pic($str, $gid, $flg = 0)
{
    if ($gid == 1) {
        $url = '/static/images/cards/';

    } else if ($gid == 5) {
        $url = '/static/images/mj_cards/';
    }
    if (!$flg) {
        $list = [];
        if ($gid == 1) {
            $arr = $str ? explode(',', $str) : [];
        } else if ($gid == 5) {
            $mjh_cardArr = config('mjh_cardArr');
            $arr         = str_split($str, 2);
            if (count($arr) == 1) {
                $arr = [];
            }

        }
        if ($arr) {
            foreach ($arr as $v) {
                if ($gid == 1) {
                    $list[] = $url . $v . '.png';
                } else if ($gid == 5) {
                    $list[] = isset($mjh_cardArr[$v]) ? $url . $mjh_cardArr[$v] . '.png' : '';
                }
            }
        }
    } else {
        $list = $url . $str . '.png';
    }
    return $list;
}

/**
 * 获取得分符号
 */
function get_score_sign($score = 0)
{
    $str = '';
    if ($score > 0) {
        $str = '+';
    }
    return $str . $score;
}

/**
 * 将秒数转换为时分秒
 */
function get_hms_time($seconds = 0)
{
    if ($seconds <= 0) {
        return '0';
    }

    $str  = '';
    $hour = floor($seconds / 3600);
    $str .= $hour != 0 ? $hour . '小时' : '';
    $minute = floor(($seconds - 3600 * $hour) / 60);
    $str .= $minute != 0 ? $minute . '分' : '';
    $second = floor((($seconds - 3600 * $hour) - 60 * $minute) % 60);
    $str .= $second != 0 ? $second . '秒' : '';
    return $str;
}

/**
 * 获取类型的符号
 */
function get_type_symbol($type)
{
    return (($type > 0 && $type < 200) || ($type > 300 && $type < 400)) ? '+' : '-';
}

/**
 * 导出csv
 */
function export_to_csv($str, $filename, $data_time)
{
    /*表头时间*/
    $s_date   = isset($data_time['start_date']) && $data_time['start_date'] ? date('Ymd', strtotime($data_time['start_date'])) : date('Ymd');
    $e_date   = isset($data_time['end_date']) && $data_time['end_date'] ? date('Ymd', strtotime($data_time['end_date'])) : date('Ymd');
    $time_str = ($s_date == $e_date) ? $s_date : ($s_date . '-' . $e_date);

    $str      = mb_convert_encoding($str, "GBK", "UTF-8");
    $filename = $time_str . $filename . '.csv'; //设置文件�?
    header("Content-type:text/csv;");
    header("Content-Disposition:attachment;filename=" . $filename);
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    echo $str;
    exit;
}

/**
 * 时间处理
 */
function time_condition($startDate, $endDate, $field)
{
    $where     = [];
    $startTime = strtotime($startDate);
    $endTime   = strtotime($endDate) + 86400 - 1;
    if ($startDate && !$endDate) {
        $where[$field] = ['egt', $startTime];
    } elseif (!$startDate && $endDate) {
        $where[$field] = ['elt', $endTime];
    } elseif ($startDate && $endDate) {
        $where[$field][] = ['egt', $startTime];
        $where[$field][] = ['elt', $endTime];
    }
    return $where;
}

/**
 * 时间条件
 */
function time_where($startTime, $endTime, $field)
{
    $where[$field][] = ['egt', $startTime];
    $where[$field][] = ['elt', $endTime];
    return $where;
}

/**
 * 密码验证
 */
function check_second_password()
{
    //验证密码
    $password = input('password', 0);
    $mg_info  = Db::table('mg_user')->field('second_password, salt')->where('mgid', UID)->find();
    if (minishop_md5($password, $mg_info['salt']) !== $mg_info['second_password']) {
        return false;
    }
    return true;
}

/**
 * 颜色显示
 */
function color_show($num)
{
    $str = 'style="color:';
    $str .= $num > 0 ? 'red' : '#1ab394';
    $str .= '"';
    return $str;
}

function formatsize($size)
{
    $prec  = 3;
    $size  = round(abs($size));
    $units = array(0 => " B", 1 => " KB", 2 => " MB", 3 => " GB", 4 => " TB");
    if ($size == 0) {
        return str_repeat(" ", $prec) . "0" . $units[0];
    }
    $unit = min(4, floor(log($size) / log(2) / 10));
    $size = $size * pow(2, -10 * $unit);
    $digi = $prec - 1 - floor(log($size) / log(10));
    $size = round($size * pow(10, $digi)) * pow(10, -$digi);
    return $size . $units[$unit];
}

function detect_encoding($str)
{
    $chars = null;
    $list  = array('GBK', 'UTF-8');
    foreach ($list as $item) {
        $tmp = mb_convert_encoding($str, $item, $item);
        if (md5($tmp) == md5($str)) {
            $chars = $item;
        }
    }
    return strtolower($chars) !== 'Utf-8' ? iconv($chars, strtoupper('Utf-8') . '//IGNORE', $str) : $str;
}

function set_chars()
{
    return 0 == 'gbk' ? 'GB2312' : 'UTF-8';
}

function convert_charset($str)
{
    return $str;
}

//删除文件夹或者目录
function delDirAndFile($path, $delDir = false)
{
    $handle = opendir($path);
    if ($handle) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
            }

        }
        closedir($handle);
        if ($delDir) {
            return rmdir($path);
        }

    } else {
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}

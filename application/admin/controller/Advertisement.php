<?php
namespace app\admin\controller;
use app\common\model\Advertisement as Advertise;
use think\Db;

/**
 * 广告图管理
 */
class Advertisement extends Common
{
    /**
     * 广告图列表
     */
    public function index()
    {
        $list = Advertise::order('sort')->select();
        $this->assign('list', $list);
        $this->assign('meta_title', '广告图列表');
        return $this->fetch();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = input('id', 0);

        if (request()->isPost()) {
            $id    = input('id', 0);
            $title = input('title', '');
            $sort  = input('sort/d', 0);
            $state = input('state/d', 0);
            $data  = [
                'title' => $title,
                'sort'  => $sort,
            ];
            $data['state']  = $state;
            
            !$title && $this->error('标题不能为空');
            !$sort && $this->error('排序不能为空');
            ($sort < 0 || $sort > 10) && $this->error('排序在0和10之间');
            // 图片验证
            $res = Advertise::pictureUpload('fixed_picture', 0);
            if ($res[0] == 1) {
                $this->error($res[0]);
            } else {
                $pictureName                             = $res[1];
                !empty($pictureName) && $data['picture'] = $pictureName;
            }
            if ($id) {
                $Advertise = new Advertise;
                if ($Advertise->save($data, ['id' => $id]) !== false) {
                    $this->success('编辑成功', url('advertisement/index'));
                }
                $this->error('编辑失败');
            }

            $file = request()->file('file');
            !$file && $this->error('图片不能为空');
            $Advertise = new Advertise($data);
            if ($Advertise->save()) {
                $this->success('添加成功', url('advertisement/edit', ['id' => $id]));
            }
            $this->error('添加失败');
        }
        $info = $id ? Advertise::where('id', $id)->find()->getdata() : [];
        $this->assign('info', $info);
        $this->assign('id', $id);
        $this->assign('meta_title', $id ? '编辑广告' : '新增广告');
        return $this->fetch();
    }

    /**
     * 删除
     */
    public function set_status()
    {
        $id = input('id', 0);
        if (Db::table('advertisement')->where('id', $id)->delete()) {
            $this->success('删除成功！');
        }
        $this->error('删除失败！');
    }

    

}

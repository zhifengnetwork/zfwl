<?php
namespace app\api\controller;

use app\api\model\Notice as NoticeModel;

/**
 * 通知信息管理
 */
class Notice extends Common
{
    /**
     * 获取广播信息
     */
    public function get_broadcast()
    {
        $data = NoticeModel::getBroadcast();
        $this->result(...$data);
    }

    /**
     * 获取公告列表
     */
    public function get_notice_list()
    {
        $data = NoticeModel::getNoticeList();
        $this->result(...$data);
    }

    /**
     * 公告阅读标记
     */
    public function notice_read()
    {
        $data = NoticeModel::notice_read();
        $this->result(...$data);
    }

    /**
     * 轮播公告
     */
    public function ad()
    {
        $data = NoticeModel::fixedPicture();
        $this->result(...$data);
    }

    /**
     * 轮播公告
     */
    public function fixedPicture()
    {
        $data = NoticeModel::fixedPicture();
        $this->result(...$data);
    }

}

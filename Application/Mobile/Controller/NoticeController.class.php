<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class NoticeController extends MobileController
{
    // 初始化函数
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $this->display();
    }

    /**
     * 公告详情
     */
    public function show()
    {
        $this->display();
    }
}

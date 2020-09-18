<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class NewsController extends MobileController
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
     * 资讯详情
     */
    public function show()
    {
        $this->wx_share();
        $this->display();
    }
}

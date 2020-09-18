<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class ActivityController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
        /*
        //标题、描述、关键词
        $this->_config_seo();*/
    }
    //新闻资讯
    public function zgrs()
    {
        $this->display();
    }
}

<?php
namespace Admin\Controller;

use Common\Controller\ConfigbaseController;

class WeixinController extends ConfigbaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('Config');
    }
    /**
     * [config 公众号配置]
     */
    public function index()
    {
        if (IS_POST) {
            //上传微信资源图片
            foreach (array('weixin_img','weixin_first_pic','weixin_default_pic') as $val) {
                if (!$_FILES[$val]['name']) {
                    continue;
                }
                $result = $this->_upload($_FILES[$val], 'resource/', array(
                    'maxSize' => 2*1024,//图片最大2M
                    'uploadReplace' => true,
                    'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                ), $val);
                if ($result['error']) {
                    $_POST[$val] = $result['info'][0]['savename'];
                }
            }
        }
        $this->_edit();//调用父类_edit方法
        $this->display();
    }
}

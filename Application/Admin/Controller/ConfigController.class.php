<?php
namespace Admin\Controller;

use Common\Controller\ConfigbaseController;

class ConfigController extends ConfigbaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('Config');
    }
    public function edit()
    {
        if (IS_POST) {
            foreach (array('logo_home','logo_user','logo_other') as $val) {
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
            $site_domain = I('request.site_domain', '', 'trim');
            if ($site_domain && $site_domain != C('qscms_site_domain')) {
                if ($site_domain == C('qscms_wap_domain')) {
                    $this->error('主域名不能与触屏版域名重复！');
                }
                $str = str_replace('http://', '', $site_domain);
                if (C('apply.Subsite')) {
                    $subsites = D('Subsite')->get_subsite_cache();
                    if ($subsites[$str]) {
                        $this->error('主域名不能与('.$subsites[$str]['s_sitename'].')域名重复！');
                    }
                    $subsite_config = D('SubsiteConfig')->get_subsite_config();
                    if ($subsite_config[$str]) {
                        $this->error('主域名不能与分站详情页域名重复！');
                    }
                }
                if (preg_match('/com.cn|net.cn|gov.cn|org.cn$/', $str) === 1) {
                    $domain = array_slice(explode('.', $str), -3, 3);
                } else {
                    $domain = array_slice(explode('.', $str), -2, 2);
                }
                $domain = '.'.implode('.', $domain);
                $config['SESSION_OPTIONS'] = array('domain'=>$domain);
                $config['COOKIE_DOMAIN'] = $domain;
                $this->update_config($config, CONF_PATH.'url.php');
            }
        }
        $this->_edit();
        $this->display();
    }
    public function reg()
    {
        $this->_edit();
        if (false === $text_list = F('text_list')) {
            $text_list = D('Text')->text_cache();
        }
        $this->assign('agreement', $text_list['agreement']);
        $this->display();
    }
    public function map()
    {
        $this->_edit();
        $this->display();
    }
    public function agreement()
    {
        $agreement = M('Text')->where(array('name'=>'agreement'))->find();
        $this->assign('agreement', $agreement);
        $this->display();
    }
    public function config_points()
    {
        $this->_edit();
        $this->display();
    }
    public function other()
    {
        $this->_edit();
        $this->display();
    }
}

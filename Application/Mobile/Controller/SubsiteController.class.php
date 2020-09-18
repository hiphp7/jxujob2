<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class SubsiteController extends MobileController
{
    // 初始化函数
    public function _initialize()
    {
        parent::_initialize();
    }
    public function set()
    {
        $sid = I('request.sid', 0, 'intval');
        $district = D('Subsite')->get_subsite_domain();
        if ($district[$sid]) {
            $domain = 'http://';
            if (C('PLATFORM')=='mobile' && $this->apply['Mobile']) {
                $domain .= $district[$sid]['s_m_domain'] ? $district[$sid]['s_m_domain'] : $district[$sid]['s_domain'].U('mobile/index/index');
            } else {
                $domain .= $district[$sid]['s_domain'];
            }
        } else {
            if (C('PLATFORM')=='mobile' && $this->apply['Mobile']) {
                $domain = C('qscms_wap_domain')?:C('qscms_site_domain').U('mobile/index/index');
            } else {
                $domain = C('qscms_site_domain');
            }
        }
        cookie('_subsite_domain', $domain);
        redirect($domain);
    }
}

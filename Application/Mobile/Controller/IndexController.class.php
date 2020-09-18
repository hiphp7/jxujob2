<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class IndexController extends MobileController
{
    // 初始化函数
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        if ($this->apply['Subsite'] && $district = D('Subsite')->get_subsite_domain()) {
            $ipInfos = GetIpLookup();
            foreach ($district as $key => $val) {
                if ($ipInfos['district'] && ($val['s_districtname'] == $ipInfos['district'] || strpos($val['s_districtname'], $ipInfos['district']))) {
                    $temp         = $val;
                    $district_org = $ipInfos['district'];
                    break;
                }
                if ($ipInfos['city'] && ($val['s_districtname'] == $ipInfos['city'] || strpos($val['s_districtname'], $ipInfos['city']))) {
                    $temp         = $val;
                    $district_org = $ipInfos['city'];
                    break;
                }
                if ($ipInfos['province'] && ($val['s_districtname'] == $ipInfos['province'] || strpos($val['s_districtname'], $ipInfos['province']))) {
                    $temp         = $val;
                    $district_org = $ipInfos['province'];
                    break;
                }
            }
            if (!cookie('_subsite_domain') && C('SUBSITE_VAL.s_id') != $temp['s_id']) {
                unset($district[$temp['s_id']]);
                $this->assign('subsite_org', $temp);
                $this->assign('district_org', $district_org);
                $domain = C('PLATFORM') == 'mobile' && C('SUBSITE_VAL.s_m_domain') ? C('SUBSITE_VAL.s_m_domain') : C('SUBSITE_VAL.s_domain');
                cookie('_subsite_domain', 'http://' . $domain);
            }
            $this->assign('subsite_choose_type', C('qscms_mobile_subsite_choose_type'));
            unset($district[C('SUBSITE_VAL.s_id')]);
            $district_arr = array();
            if (C('qscms_mobile_subsite_choose_type') == 'complex') {
                foreach ($district as $key => $value) {
                    $district_arr[$value['s_ordid']][] = $value;
                }
            } else {
                $district_arr = $district;
            }
            $this->assign('word_arr', range('A', 'Z'));
            $this->assign('district', $district_arr);
        }
        $jobs_count   = M('Jobs')->count('id');
        $resume_count = M('Resume')->count('id');
        $this->assign('jobs_count', $jobs_count + intval(C('qscms_jobs_base')));
        $this->assign('resume_count', $resume_count + intval(C('qscms_resume_base')));
        if (I('type') == 'test') {
            $this->display('indextest');
        } else {
            $this->display();
        }
    }
    public function compatibility()
    {
        $this->display();
    }
    public function app_download()
    {
        $page_seo['title'] = 'APP下载 - ' . C('qscms_site_name');
        $this->assign('page_seo', $page_seo);
        $this->display();
    }
}

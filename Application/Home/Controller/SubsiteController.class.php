<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class SubsiteController extends FrontendController
{
    // 初始化函数
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        if ($district = D('Subsite')->get_subsite_domain()) {
            $ipInfos = GetIpLookup();
            foreach ($district as $key => $val) {
                if (strpos($val['s_districtname'], $ipInfos['district'])) {
                    $temp = $val;
                    $district_org = $ipInfos['district'];
                    break;
                }
                if (strpos($val['s_districtname'], $ipInfos['city'])) {
                    $temp = $val;
                    $district_org = $ipInfos['city'];
                    break;
                }
                if (strpos($val['s_districtname'], $ipInfos['province'])) {
                    $temp = $val;
                    $district_org = $ipInfos['province'];
                    break;
                }
            }
            if (C('SUBSITE_VAL.s_id') != $temp['s_id']) {
                $this->assign('subsite_org', $temp);
                $this->assign('district_org', $district_org);
            }
            unset($district[0]);
            foreach ($district as $key => $val) {
                $district_list[$val['s_ordid']][] = $val;
            }
            $count = count($district_list);
            $mod = (int)($count/2);
            $this->assign('mod_val', $count%2 ? $mod+1 : $mod);
            $this->assign('district', $district);
            $this->assign('district_list', $district_list);
        } else {
            $this->error('请添加分站信息！');
        }
        $this->display();
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

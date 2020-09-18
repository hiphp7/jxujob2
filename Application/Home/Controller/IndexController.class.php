<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class IndexController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * [index 首页]
     */
    public function index()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            redirect(build_mobile_url());
        }
        $isRecommend = 1;
        if ($this->apply['Subsite'] && $district = D('Subsite')->get_subsite_domain()) {
            $ipInfos = GetIpLookup();
            foreach ($district as $key => $val) {
                if ($ipInfos['district'] && ($val['s_districtname'] == $ipInfos['district'] || strpos($val['s_districtname'], $ipInfos['district']))) {
                    $temp = $val;
                    $district_org = $ipInfos['district'];
                    break;
                }
                if ($ipInfos['city'] && ($val['s_districtname'] == $ipInfos['city'] || strpos($val['s_districtname'], $ipInfos['city']))) {
                    $temp = $val;
                    $district_org = $ipInfos['city'];
                    break;
                }
                if ($ipInfos['province'] && ($val['s_districtname'] == $ipInfos['province'] || strpos($val['s_districtname'], $ipInfos['province']))) {
                    $temp = $val;
                    $district_org = $ipInfos['province'];
                    break;
                }
            }
            if (!cookie('_subsite_domain') && C('SUBSITE_VAL.s_id') != $temp['s_id']) {
                unset($district[$temp['s_id']]);
                $isRecommend = 0;
                $this->assign('subsite_org', $temp);
                $this->assign('district_org', $district_org);
                $domain = C('PLATFORM')=='mobile' && C('SUBSITE_VAL.s_m_domain') ? C('SUBSITE_VAL.s_m_domain') : C('SUBSITE_VAL.s_domain');
                cookie('_subsite_domain', 'http://'.$domain);
            }
            unset($district[C('SUBSITE_VAL.s_id')]);
            $this->assign('district', $district);
        }
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('Oauth')->oauth_cache();
        }
        //招聘会
        $res_big_jobfair_where['display'] =1;
        $jobfair1 = M('jobfair')->where($res_big_where)->order('holddate_end desc')->find();
        $this->assign('jobfair1', $jobfair1);
       
        $res_jobfair_where['display'] =1;
        $jobfair2 = M('jobfair')->where($res_jobfair_where)->order('holddate_end desc')->limit(6)->getfield('id,title,display,holddate_start');
        $this->assign('jobfair2', $jobfair2);
        //网站公告

        $res_notice1 = M('notice')->where('type_id=1')->order('addtime desc')->find();
        $res_notice1['content'] =strip_tags(htmlspecialchars_decode($res_notice1['content']));
        $this->assign('notice1', $res_notice1);
        $res_notice2 = M('notice')->where('type_id=1')->order('addtime desc')->limit(6)->getfield('id,addtime,title');
        $this->assign('notice2', $res_notice2);

        //招聘信息
        $res_article1 = M('article')->order('addtime desc')->find();
        $res_article1['content'] =strip_tags(htmlspecialchars_decode($res_article1['content']));
        $this->assign('article1', $res_article1);
        $res_article2 = M('article')->order('addtime desc')->limit(6)->getfield('id,addtime,title');
        $this->assign('article2', $res_article2);

        //招考公告

        $res_zk1 = M('notice')->where('type_id=2')->order('addtime desc')->find();
        $res_zk1['content'] =strip_tags(htmlspecialchars_decode($res_zk1['content']));
        $this->assign('zk1', $res_zk1);
        $res_zk2 = M('notice')->where('type_id=2')->order('addtime desc')->limit(6)->getfield('id,addtime,title');
        $this->assign('zk2', $res_zk2);

        //求职指南

        $res_zn1 = M('article')->where('type_id=6')->order('addtime desc')->find();
        $res_zn1['content'] =strip_tags(htmlspecialchars_decode($res_zn1['content']));
        $this->assign('zn1', $res_zn1);
        $res_zn2 = M('article')->where('type_id=6')->order('addtime desc')->limit(6)->getfield('id,addtime,title');
        $this->assign('zn2', $res_zn2);

        //职场指南

        $res_zc1 = M('article')->where('type_id=5')->order('addtime desc')->find();
        $res_zc1['content'] =strip_tags(htmlspecialchars_decode($res_zc1['content']));
        $this->assign('zc1', $res_zc1);
        $res_zc2 = M('article')->where('type_id=5')->order('addtime desc')->limit(6)->getfield('id,addtime,title');
        $this->assign('zc2', $res_zc2);

        /*
        **6.30首页改版新增数据段
         */
        //校园招聘会
        $n_notice_xczph = M('jobfair')->where('display=1')->order('holddate_start desc')->limit(6)->getField('id,title,addtime,address,holddate_start');
        //专题招聘会
        $n_notice_ztzph = M('subject')->where('is_display=1')->order('holddate_start desc')->limit(6)->getField('id,title,addtime,holddate_start');
        //网站公告
        $n_notice_wzgg = M('notice')->where('type_id=1')->order('addtime desc')->limit(6)->getField('id,title,addtime');

        $this->assign([
                'n_notice_xczph' =>$n_notice_xczph,
                'n_notice_ztzph'=>$n_notice_ztzph,
                'n_notice_wzgg'=>$n_notice_wzgg
                ]);
        //**新闻发布

        $n_news_gwyks = M('article')->where('type_id=18')->order('addtime desc')->limit(6)->getField('id,title,addtime');
        $n_news_jszk = M('article')->where('type_id=19')->order('addtime desc')->limit(6)->getField('id,title,addtime');
        $n_news_sydw = M('article')->where('type_id=26')->order('addtime desc')->limit(6)->getField('id,title,addtime');
        $n_news_gqzk = M('article')->where('type_id=27')->order('addtime desc')->limit(6)->getField('id,title,addtime');
        $n_news_qtzk = M('article')->where('type_id=25')->order('addtime desc')->limit(6)->getField('id,title,addtime');
        $this->assign([
                'n_news_gwyks' =>$n_news_gwyks,
                'n_news_jszk'=>$n_news_jszk,
                'n_news_sydw'=>$n_news_sydw,
                'n_news_gqzk'=>$n_news_gqzk,
                'n_news_qtzk'=>$n_news_qtzk
                ]);
        //**职位分类
        //互联网/计算机类别
        $n_jobs_category_jsj_zb = M('jobs')->where('topclass=74')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        
        $n_jobs_category_jsj_jsjyy = M('jobs')->where('category=75')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jsj_hlw = M('jobs')->where('category=76')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jsj_jsjryj = M('jobs')->where('category=77')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jsj_it = M('jobs')->where('category=78')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jsj_itpz = M('jobs')->where('category=79')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jsj_tx = M('jobs')->where('category=80')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_jsj_zb' =>$n_jobs_category_jsj_zb,
                'n_jobs_category_jsj_jsjyy'=>$n_jobs_category_jsj_jsjyy,
                'n_jobs_category_jsj_hlw'=>$n_jobs_category_jsj_hlw,
                'n_jobs_category_jsj_jsjryj'=>$n_jobs_category_jsj_jsjryj,
                'n_jobs_category_jsj_it'=>$n_jobs_category_jsj_it,
                'n_jobs_category_jsj_itpz'=>$n_jobs_category_jsj_itpz,
                'n_jobs_category_jsj_tx'=>$n_jobs_category_jsj_tx
                ]);
        //建筑|房地产|物业管理
        $n_jobs_category_jz_zb = M('jobs')->where('topclass=96')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jz_jz= M('jobs')->where('category=97')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jz_fdc= M('jobs')->where('category=98')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jz_wygl= M('jobs')->where('category=99')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_jz_zb' =>$n_jobs_category_jz_zb,
                'n_jobs_category_jz_jz'=>$n_jobs_category_jz_jz,
                'n_jobs_category_jz_fdc'=>$n_jobs_category_jz_fdc,
                'n_jobs_category_jz_wygl'=>$n_jobs_category_jz_wygl
                ]);
        //销售|市场|客服|贸易
        $n_jobs_category_xs_zb = M('jobs')->where('topclass=1')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_xs_xsgl= M('jobs')->where('category=2')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        
        $n_jobs_category_xs_xsry= M('jobs')->where('category=3')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
       
        $n_jobs_category_xs_xsxzsw = M('jobs')->where('category=4')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        
        $n_jobs_category_xs_sc= M('jobs')->where('category=5')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        
        $n_jobs_category_xs_kf= M('jobs')->where('category=6')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        
        $n_jobs_category_xs_my= M('jobs')->where('category=7')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_xs_zb' =>$n_jobs_category_xs_zb,
                'n_jobs_category_xs_xsgl'=>$n_jobs_category_xs_xsgl,
                'n_jobs_category_xs_xsry'=>$n_jobs_category_xs_xsry,
                'n_jobs_category_xs_xsxzsw'=>$n_jobs_category_xs_xsxzsw,
                'n_jobs_category_xs_sc'=>$n_jobs_category_xs_sc,
                'n_jobs_category_xs_kf'=>$n_jobs_category_xs_kf,
                'n_jobs_category_xs_my'=>$n_jobs_category_xs_my
                ]);
        //管理|人力资源|行政
        $n_jobs_category_gl_zb = M('jobs')->where('topclass=19')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gl_jygl= M('jobs')->where('category=20')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gl_rlzy= M('jobs')->where('category=21')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gl_xz= M('jobs')->where('category=22')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_gl_zb' =>$n_jobs_category_gl_zb,
                'n_jobs_category_gl_jygl'=>$n_jobs_category_gl_jygl,
                'n_jobs_category_gl_rlzy'=>$n_jobs_category_gl_rlzy,
                'n_jobs_category_gl_xz'=>$n_jobs_category_gl_xz
                ]);
        //财务|金融|保险
        $n_jobs_category_cw_zb = M('jobs')->where('topclass=49')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_cw_cw= M('jobs')->where('category=50')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_cw_zq= M('jobs')->where('category=51')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_cw_bx= M('jobs')->where('category=52')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_cw_zb' =>$n_jobs_category_cw_zb,
                'n_jobs_category_cw_cw'=>$n_jobs_category_cw_cw,
                'n_jobs_category_cw_zq'=>$n_jobs_category_cw_zq,
                'n_jobs_category_cw_bx'=>$n_jobs_category_cw_bx
                ]);
       //生产|质管|技工|制造
        $n_jobs_category_sc_zb = M('jobs')->where('topclass=116')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_sc= M('jobs')->where('category=117')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_zl= M('jobs')->where('category=118')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_qc= M('jobs')->where('category=119')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_jx= M('jobs')->where('category=120')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_jsgr= M('jobs')->where('category=121')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_sc_fz= M('jobs')->where('category=122')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_sc_zb' =>$n_jobs_category_sc_zb,
                'n_jobs_category_sc_sc'=>$n_jobs_category_sc_sc,
                'n_jobs_category_sc_qc'=>$n_jobs_category_sc_qc,
                'n_jobs_category_sc_jx'=>$n_jobs_category_sc_jx,
                'n_jobs_category_sc_jsgr'=>$n_jobs_category_sc_jsgr,
                'n_jobs_category_sc_fz'=>$n_jobs_category_sc_fz
                ]);
        //广告|媒体|艺术|出版
        $n_jobs_category_gg_zb = M('jobs')->where('topclass=169')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gg_gg= M('jobs')->where('category=170')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gg_mt= M('jobs')->where('category=171')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gg_ys= M('jobs')->where('category=172')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_gg_cb= M('jobs')->where('category=173')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_gg_zb' =>$n_jobs_category_gg_zb,
                'n_jobs_category_gg_gg'=>$n_jobs_category_gg_gg,
                'n_jobs_category_gg_mt'=>$n_jobs_category_gg_mt,
                'n_jobs_category_gg_ys'=>$n_jobs_category_gg_ys,
                'n_jobs_category_gg_cb'=>$n_jobs_category_gg_cb
                ]);
        //教育|法律|咨询|翻译
        $n_jobs_category_jy_zb = M('jobs')->where('topclass=203')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jy_jy= M('jobs')->where('category=204')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jy_fl= M('jobs')->where('category=205')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jy_zx= M('jobs')->where('category=206')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_jy_fy= M('jobs')->where('category=207')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_jy_zb' =>$n_jobs_category_jy_zb,
                'n_jobs_category_jy_jy'=>$n_jobs_category_jy_jy,
                'n_jobs_category_jy_fl'=>$n_jobs_category_jy_fl,
                'n_jobs_category_jy_zx'=>$n_jobs_category_jy_zx,
                'n_jobs_category_jy_fy'=>$n_jobs_category_jy_fy
                ]);
        //医疗|制药|环保
        $n_jobs_category_yl_zb = M('jobs')->where('topclass=225')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_yl_yy= M('jobs')->where('category=226')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_yl_zy=M('jobs')->where('category=227')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_yl_hb= M('jobs')->where('category=228')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_yl_zb' =>$n_jobs_category_yl_zb,
                'n_jobs_category_yl_yy'=>$n_jobs_category_yl_yy,
                'n_jobs_category_yl_zy'=>$n_jobs_category_yl_zy,
                'n_jobs_category_yl_hb'=>$n_jobs_category_yl_hb
                ]);
        
        //零售|餐饮|服务
        $n_jobs_category_ls_zb = M('jobs')->where('topclass=241')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_ls_bh= M('jobs')->where('category=242')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_ls_ba=M('jobs')->where('category=243')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_ls_cy= M('jobs')->where('category=244')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_ls_mr= M('jobs')->where('category=245')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_ls_wl= M('jobs')->where('category=246')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_ls_zb' =>$n_jobs_category_ls_zb,
                'n_jobs_category_ls_bh'=>$n_jobs_category_ls_bh,
                'n_jobs_category_ls_ba'=>$n_jobs_category_ls_ba,
                'n_jobs_category_ls_cy'=>$n_jobs_category_ls_cy,
                'n_jobs_category_ls_mr'=>$n_jobs_category_ls_mr,
                'n_jobs_category_ls_wl'=>$n_jobs_category_ls_wl
                ]);
        
        //学生|科研|农业|其他
        $n_jobs_category_xs_zb = M('jobs')->where('topclass=258')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_xs_xs= M('jobs')->where('category=259')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_category_xs_n=M('jobs')->where('category=260')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $n_jobs_categor_xs_qt= M('jobs')->where('category=261')->order('addtime desc')->limit(8)->getField('id,company_id,jobs_name,refreshtime,minwage,maxwage,experience_cn,education_cn,district_cn,companyname');
        $this->assign([
                'n_jobs_category_xs_zb' =>$n_jobs_category_xs_zb,
                'n_jobs_category_xs_xs'=>$n_jobs_category_xs_xs,
                'n_jobs_category_xs_n'=>$n_jobs_category_xs_n,
                'n_jobs_categor_xs_qt'=>$n_jobs_categor_xs_qt
                ]);
        //**职场资讯
        //求职宝典
        $n_zczx_qzbd= M('article')->where('type_id=5')->order('addtime desc')->limit(8)->getField('id,title,addtime,Small_img');
        //职场资讯
        $n_zczx_qzbd = M('article')->where('type_id=4')->order('addtime desc')->limit(8)->getField('id,title,addtime,Small_img');
        $this->assign([
                'n_zczx_qzbd' =>$n_zczx_qzbd,
                'n_zczx_qzbd'=>$n_zczx_qzbd
                ]);

        
        //企业分类
        //机关事业单位
        $com_list_map_1['nature']= array(array('eq',52),array('eq',53), 'or') ;
        $com_list_map_1['nature']= array(array('eq',52),array('eq',53), 'or') ;
        $res_1 = $this->GetWork($com_list_map_1);
        //国有企业
        $com_list_map_2['nature']= 46;
        $res_2 = $this->GetWork($com_list_map_2);
        //上市名企
        $com_list_map_3['nature']= 51;
        $res_3 = $this->GetWork($com_list_map_3);
        $this->assign([
            'res_1' =>$res_1,
            'res_2'=>$res_2,
            'res_3'=>$res_3
            ]);



        $this->assign('verify_userlogin', $this->check_captcha_open(C('qscms_captcha_config.user_login'), 'error_login_count'));
        
        $this->assign('oauth_list', $oauth_list);
        $this->assign('isRecommend', $isRecommend);
        if (I('type')=='test') {
            $this->display('indextest');
        }
        $this->display();
    }



    /**
     * [ajax_user_info ajax获取用户登录信息]
     */
    public function ajax_user_info()
    {
        if (IS_AJAX) {
            !$this->visitor->is_login && $this->ajaxReturn(0, '请登录！');
            $uid = C('visitor.uid');
            if (C('visitor.utype') == 1) {
                $info = M('CompanyProfile')->field('companyname,logo')->where(array('uid'=>$uid))->find();
                $views = M('ViewJobs')->where(array('jobs_uid'=>C('visitor.uid')))->group('uid')->getfield('uid', true);
                $info['views'] = count($views);
                $join = 'join '.C('DB_PREFIX') .'jobs j on j.id='.C('DB_PREFIX').'personal_jobs_apply.jobs_id';
                $info['apply'] = M('PersonalJobsApply')->join($join)->where(array('company_uid'=>$uid,'is_reply'=>array('eq',0)))->count();
            } else {
                $info['realname'] = M('MembersInfo')->where(array('uid'=>$uid))->getfield('realname');
                $info['pid'] = M('Resume')->where(array('uid'=>$uid,'def'=>1))->getfield('id');
                $info['countinterview'] = M('CompanyInterview')->where(array('resume_uid'=>$uid))->count();
                //谁看过我
                $rids = M('Resume')->where(array('uid'=>$uid))->getField('id', true);
                if ($rids) {
                    $info['views'] = M('ViewResume')->where(array('resumeid'=>array('in',$rids)))->count();
                } else {
                    $info['views'] = 0;
                }
            }
            $issign = D('MembersHandsel')->check_members_handsel_day(array('uid'=>$uid,'htype'=>'task_sign'));
            $this->assign('issign', $issign ? 1 : 0);
            $this->assign('info', $info);
            $hour=date('G');
            if ($hour<11) {
                $am_pm = '早上好';
            } else if ($hour<13) {
                $am_pm = '中午好';
            } else if ($hour<17) {
                $am_pm = '下午好';
            } else {
                $am_pm = '晚上好';
            }
            $this->assign('am_pm', $am_pm);
            $data['html'] = $this->fetch('ajax_user_info');
            $this->ajaxReturn(1, '', $data);
        }
    }
    /**
     * [index 首页搜索跳转]
     */
    public function search_location()
    {
        $act = I('post.act', '', 'trim');
        $key = I('post.key', '', 'trim');
        $this->ajaxReturn(1, '', url_rewrite($act, array('key'=>$key)));
    }
    /**
     * 保存到桌面
     */
    public function shortcut()
    {
        $Shortcut = "[InternetShortcut]
		URL=".C('qscms_site_domain').C('qscms_site_dir')."?lnk
		IDList= 
		IconFile=".C('qscms_site_domain').C('qscms_site_dir')."favicon.ico
		IconIndex=100
		[{000214A0-0000-0000-C000-000000000046}]
		Prop3=19,2";
        header("Content-type: application/octet-stream");
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $filename=C('qscms_site_name').'.url';
        $filename = urlencode($filename);
        $filename = str_replace("+", "%20", $filename);
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        exit($Shortcut);
    }


    public function GetWork($com_list_map)
    {
        $company_list = M('CompanyProfile')->where($com_list_map)->order('refreshtime desc')->limit(18)->getfield('id,uid,audit,companyname,addtime,refreshtime,logo,contact,address,district_cn');
        $cids = array();
        foreach ($company_list as $key => $val) {
            $jobs = M('Jobs')->where(array('company_id'=>$val['id']))->select();
            if ($jobs) {
                $company[$key]['companyname'] = $val['companyname'];
                $company[$key]['contact'] = $val['contact'];
                $company[$key]['address'] = $val['address'];
                if ($val['logo']) {
                    $company[$key]['logo']=attach($val['logo'], 'company_logo');
                } else {
                    $company[$key]['logo']=attach('no_logo.png', 'resource');
                }
                $company[$key]['company_url']=url_rewrite('QS_companyshow', array('id'=>$val['id']));
                $company[$key]['company_jobs_url']=url_rewrite('QS_companyjobs', array('id'=>$val['id']));
                $jobs_num = M('Jobs')->where(array('company_id'=>$val['id']))->count();
                $company[$key]['jobs_num'] = $jobs_num;
                $cids[] = $val['id'];
            }
        }
        if ($cids) {
            $list_map['company_id'] = array('in',$cids);
            if (C('qscms_jobs_display')==1) {
                    $list_map['audit'] = 1;
            }
            $jobs_list = M('Jobs')->field('id,jobs_name,minwage,maxwage,company_id,negotiable,district_cn,education_cn,experience_cn')->where($list_map)->order('refreshtime desc')->select();
            if ($this->params['keytype'] == '' || $this->params['keytype'] == 'com') {
                foreach ($jobs_list as $k => $val) {
                    if (count($company[$val['company_id']]['jobs']) >= 3) {
                        continue;
                    }
                    $val['jobs_url'] = url_rewrite('QS_jobsshow', array('id'=>$val['id']));
                    if ($val['negotiable']==0) {
                        if (C('qscms_wage_unit') == 1) {
                            $val['minwage'] = $val['minwage']%1000==0?(($val['minwage']/1000).'K'):(round($val['minwage']/1000, 1).'K');
                            $val['maxwage'] = $val['maxwage']?($val['maxwage']%1000==0?(($val['maxwage']/1000).'K'):(round($val['maxwage']/1000, 1).'K')):0;
                        } elseif (C('qscms_wage_unit') == 2) {
                            if ($val['minwage']>=10000) {
                                if ($val['minwage']%10000==0) {
                                    $val['minwage'] = ($val['minwage']/10000).'万';
                                } else {
                                    $val['minwage'] = round($val['minwage']/10000, 1);
                                    $val['minwage'] = strpos($val['minwage'], '.') ? str_replace('.', '万', $val['minwage']) : $val['minwage'].'万';
                                }
                            } else {
                                if ($val['minwage']%1000==0) {
                                    $val['minwage'] = ($val['minwage']/1000).'千';
                                } else {
                                    $val['minwage'] = round($val['minwage']/1000, 1);
                                    $val['minwage'] = strpos($val['minwage'], '.') ? str_replace('.', '千', $val['minwage']) : $val['minwage'].'千';
                                }
                            }
                            if ($val['maxwage']>=10000) {
                                if ($val['maxwage']%10000==0) {
                                    $val['maxwage'] = ($val['maxwage']/10000).'万';
                                } else {
                                    $val['maxwage'] = round($val['maxwage']/10000, 1);
                                    $val['maxwage'] = strpos($val['maxwage'], '.') ? str_replace('.', '万', $val['maxwage']) : $val['maxwage'].'万';
                                }
                            } elseif ($val['maxwage']) {
                                if ($val['maxwage']%1000==0) {
                                    $val['maxwage'] = ($val['maxwage']/1000).'千';
                                } else {
                                    $val['maxwage'] = round($val['maxwage']/1000, 1);
                                    $val['maxwage'] = strpos($val['maxwage'], '.') ? str_replace('.', '千', $val['maxwage']) : $val['maxwage'].'千';
                                }
                            } else {
                                $val['maxwage'] = 0;
                            }
                        }
                        if ($val['maxwage']==0) {
                            $val['wage_cn'] = '面议';
                        } else {
                            if ($val['minwage']==$val['maxwage']) {
                                $val['wage_cn'] = $val['minwage'].'/月';
                            } else {
                                $val['wage_cn'] = $val['minwage'].'-'.$val['maxwage'].'/月';
                            }
                        }
                    } else {
                        $val['wage_cn'] = '面议';
                    }
                    $r = D('PersonalJobsApply')->where(array('jobs_id'=>$val['id'],'personal_uid'=>C('visitor.uid')))->find();
                    if ($r) {
                        $val['is_apply'] = 1;
                    } else {
                        $val['is_apply'] = 0;
                    }
                    $company[$val['company_id']]['jobs'][] = $val;
                }
            } elseif ($this->params['keytype'] == 'job') {
                foreach ($jobs_list as $k => $val) {
                    $company = M('CompanyProfile')->where(array('id'=>$val['company_id']))->field('id,companyname,logo')->find();


                    $val['companyname'] = $company['companyname'];
                    $val['company_url']=url_rewrite('QS_companyshow', array('id'=>$company['id']));
                    $val['company_jobs_url']=url_rewrite('QS_companyjobs', array('id'=>$company['id']));
                    if ($val['logo']) {
                        $val['logo']=attach($company['logo'], 'company_logo');
                    } else {
                        $val['logo']=attach('no_logo.png', 'resource');
                    }
                    $val['jobs_url'] = url_rewrite('QS_jobsshow', array('id'=>$val['id']));
                    if ($val['negotiable']==0) {
                        if (C('qscms_wage_unit') == 1) {
                            $val['minwage'] = $val['minwage']%1000==0?(($val['minwage']/1000).'K'):(round($val['minwage']/1000, 1).'K');
                            $val['maxwage'] = $val['maxwage']?($val['maxwage']%1000==0?(($val['maxwage']/1000).'K'):(round($val['maxwage']/1000, 1).'K')):0;
                        } elseif (C('qscms_wage_unit') == 2) {
                            if ($val['minwage']>=10000) {
                                if ($val['minwage']%10000==0) {
                                    $val['minwage'] = ($val['minwage']/10000).'万';
                                } else {
                                    $val['minwage'] = round($val['minwage']/10000, 1);
                                    $val['minwage'] = strpos($val['minwage'], '.') ? str_replace('.', '万', $val['minwage']) : $val['minwage'].'万';
                                }
                            } else {
                                if ($val['minwage']%1000==0) {
                                    $val['minwage'] = ($val['minwage']/1000).'千';
                                } else {
                                    $val['minwage'] = round($val['minwage']/1000, 1);
                                    $val['minwage'] = strpos($val['minwage'], '.') ? str_replace('.', '千', $val['minwage']) : $val['minwage'].'千';
                                }
                            }
                            if ($val['maxwage']>=10000) {
                                if ($val['maxwage']%10000==0) {
                                    $val['maxwage'] = ($val['maxwage']/10000).'万';
                                } else {
                                    $val['maxwage'] = round($val['maxwage']/10000, 1);
                                    $val['maxwage'] = strpos($val['maxwage'], '.') ? str_replace('.', '万', $val['maxwage']) : $val['maxwage'].'万';
                                }
                            } elseif ($val['maxwage']) {
                                if ($val['maxwage']%1000==0) {
                                    $val['maxwage'] = ($val['maxwage']/1000).'千';
                                } else {
                                    $val['maxwage'] = round($val['maxwage']/1000, 1);
                                    $val['maxwage'] = strpos($val['maxwage'], '.') ? str_replace('.', '千', $val['maxwage']) : $val['maxwage'].'千';
                                }
                            } else {
                                $val['maxwage'] = 0;
                            }
                        }
                        if ($val['maxwage']==0) {
                            $val['wage_cn'] = '面议';
                        } else {
                            if ($val['minwage']==$val['maxwage']) {
                                $val['wage_cn'] = $val['minwage'].'/月';
                            } else {
                                $val['wage_cn'] = $val['minwage'].'-'.$val['maxwage'].'/月';
                            }
                        }
                    } else {
                        $val['wage_cn'] = '面议';
                    }
                    $r = D('PersonalJobsApply')->where(array('jobs_id'=>$val['id'],'personal_uid'=>C('visitor.uid')))->find();
                    if ($r) {
                        $val['is_apply'] = 1;
                    } else {
                        $val['is_apply'] = 0;
                    }
                    $company['jobs'][] = $val;
                }
            }
        }
        
        $return['list'] = $company;
        return $return;
    }
}

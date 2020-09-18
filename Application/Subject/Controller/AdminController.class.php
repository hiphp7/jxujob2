<?php
namespace Subject\Controller;

use Common\Builder\ZBuilder;
use Common\Controller\ConfigbaseController;

class AdminController extends ConfigbaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('Subject');
    }
    public function index()
    {
        $this->_name = 'Subject';
        $this->order = 'article_order desc, addtime desc';
        parent::index();
    }
    public function nowExport()
    {
        /*
         *导出近期企业
         */
        $com_list_map['addtime']     = array('gt', 1587052800);
        $com_list_map['refreshtime'] = array('gt', 1587052800);
        $com_list_map['_logic']      = 'or';
        $com_list_map1['_complex']   = $com_list_map;
        $company_list                = M('CompanyProfile')->where($com_list_map1)->getfield('id,uid,audit,companyname,addtime,refreshtime,audit,contact,district_cn,address,telephone,landline_tel');
        $cids                        = array();
        foreach ($company_list as $key => $val) {
            $company[$key]['addtime']      = date("Y-m-d", $val['addtime']);
            $company[$key]['refreshtime']  = date("Y-m-d", $val['refreshtime']);
            $company[$key]['companyname']  = $val['companyname'];
            $jobs_num                      = M('zjydata')->where(array('company_id' => $val['uid']))->count();
            $company[$key]['jobs_num']     = $jobs_num;
            $company[$key]['contact']      = $val['contact'];
            $company[$key]['landline_tel'] = $val['landline_tel'];
            $company[$key]['telephone']    = $val['telephone'];
            if ($val['audit'] == 1) {
                $company[$key]['audit'] = '已注册';
            } elseif ($val['audit'] == 0) {
                $company[$key]['audit'] = '未认证';
            } elseif ($val['audit'] == 2) {
                $company[$key]['audit'] = '待审核';
            } else {
                $company[$key]['audit'] = '审未通过';
            }
            $company[$key]['district_cn'] = $val['district_cn'];
            $cids[]                       = $val['id'];
        }
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '成功报名');
        $headArr  = array('注册时间', '刷新时间', '公司名', '发布职位数', '联系人', '座机号', '手机号', '注册状态', '企业所在地');
        $data     = $company;
        getExcel($property, $headArr, $data, $fileName);
    }
    public function nowExport2()
    {
        /*
         *导出近期企业
         */
        $db                  = M('zjydata');
        $map['p.subject_id'] = 27;
        $res                 = $db->join("job_subject_company p on job_zjydata.company_id=p.company_uid")
            ->order('job_zjydata.id asc')
            ->where($map)
            ->field('job_zjydata.id,job_zjydata.qzxqfbsj,job_zjydata.company_id,job_zjydata.zpzw,job_zjydata.zpdw,job_zjydata.xqrs')
            ->select();
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '成功报名');
        $headArr  = array('职位id', '发布时间', '企业uid', '招聘职位', '招聘企业', '需求人数', '企业所在地');
        $data     = $company;
        getExcel($property, $headArr, $res, $fileName);
    }
    public function nowExport3()
    {
        /*
         *导出近期企业
         */
        $db                                    = M('SubjectCompany');
        $map['job_subject_company.subject_id'] = 27;
        $res                                   = $db->join("job_zjydata p on job_subject_company.company_uid=p.company_id")
            ->where($map)
            ->field('p.zpdw,p.shtyxydm,p.company_id')
            ->select();
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '成功报名');
        $headArr  = array('招聘单位', '信用代码', 'uid');
        $data     = $company;
        getExcel($property, $headArr, $res, $fileName);
    }
    public function successExport()
    {
        /*
         *导出已成功企业
         */
        $map['subject_id'] = 27;
        $subject_list      = M('subject_company')->where($map)->order('addtime desc')->select();
        $uids              = array();
        foreach ($subject_list as $key => $value) {
            $uids[] = $value['company_uid'];
        }
        if ($uids) {
            $com_list_map['uid'] = array('in', $uids);
            $company_list        = M('CompanyProfile')->where($com_list_map)->getfield('id,uid,audit,companyname,addtime,refreshtime,audit,contact,district_cn,address,telephone,landline_tel');
        }
        $cids = array();
        foreach ($company_list as $key => $val) {
            $map11['company_uid']          = $val['uid'];
            $map11['subject_id']           = 26;
            $map11['audit']                = 1;
            $addtime                       = M('subject_company')->where($map11)->select();
            $company[$key]['addtime']      = date("Y-m-d", $addtime[0]['addtime']);
            $company[$key]['companyname']  = $val['companyname'];
            $jobs_num                      = M('zjydata')->where(array('company_id' => $val['uid']))->count();
            $company[$key]['jobs_num']     = $jobs_num;
            $company[$key]['contact']      = $val['contact'];
            $company[$key]['landline_tel'] = $val['landline_tel'];
            $company[$key]['telephone']    = $val['telephone'];
            if ($val['audit'] == 1) {
                $company[$key]['audit'] = '已注册';
            } elseif ($val['audit'] == 0) {
                $company[$key]['audit'] = '未认证';
            } elseif ($val['audit'] == 2) {
                $company[$key]['audit'] = '待审核';
            } else {
                $company[$key]['audit'] = '审未通过';
            }
            $company[$key]['district_cn'] = $val['district_cn'];
            $cids[]                       = $val['id'];
        }
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '成功报名');
        $headArr  = array('注册时间', '公司名', '发布职位数', '联系人', '座机号', '手机号', '注册状态', '企业所在地');
        $data     = $company;
        getExcel($property, $headArr, $data, $fileName);
    }
    public function successExport1()
    {
        /*
         *导出已成功企业
         */
        $map['subject_id'] = 27;
        $map['audit']      = 1;
        $subject_list      = M('subject_company')->where($map)->order('addtime asc')->select();
        $uids              = array();
        foreach ($subject_list as $key => $value) {
            $uids[] = $value['company_uid'];
        }
        if ($uids) {
            $com_list_map['uid'] = array('in', $uids);
            $company_list        = M('CompanyProfile')->where($com_list_map)->getfield('id,uid,audit,companyname,addtime,refreshtime,audit,contact,district_cn,address,telephone,landline_tel');
        }
        $cids = array();
        foreach ($company_list as $key => $val) {
            $map11['company_uid']          = $val['uid'];
            $addtime                       = M('subject_company')->where($map11)->select();
            $company[$key]['addtime']      = date("Y-m-d", $addtime[0]['addtime']);
            $company[$key]['companyname']  = $val['companyname'];
            $jobs_num                      = M('zjydata')->where(array('company_id' => $val['uid']))->count();
            $company[$key]['jobs_num']     = $jobs_num;
            $company[$key]['contact']      = $val['contact'];
            $company[$key]['landline_tel'] = $val['landline_tel'];
            $company[$key]['telephone']    = $val['telephone'];
            if ($val['audit'] == 1) {
                $company[$key]['audit'] = '已注册';
            } elseif ($val['audit'] == 0) {
                $company[$key]['audit'] = '未认证';
            } elseif ($val['audit'] == 2) {
                $company[$key]['audit'] = '待审核';
            } else {
                $company[$key]['audit'] = '审未通过';
            }
            $company[$key]['district_cn'] = $val['district_cn'];
            $cids[]                       = $val['id'];
        }
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '成功报名');
        $headArr  = array('注册时间', '公司名', '发布职位数', '联系人', '座机号', '手机号', '注册状态', '企业所在地');
        $data     = $company;
        getExcel($property, $headArr, $data, $fileName);
    }
    public function unsuccessExport()
    {
        /*
         *导出已成功企业
         */
        $map['job_subject_company.subject_id'] = 27;
        $company                               = M('subject_company')->join("job_zjydata z on job_subject_company.company_uid=z.company_id")
            ->where($map)
            ->order('job_subject_company.addtime')
            ->field('z.company_id,z.zpzw,z.zpdw,job_subject_company.addtime,z.xqrs,z.qzxqfbsj')
            ->select();
        $fileName = date("M-d", time());
        $property = array('title' => $fileName . '发布职位了的');
        $headArr  = array('uid', '职位名称', '公司名', '报名时间', '招聘人数');
        $data     = $company;
        getExcel($property, $headArr, $data, $fileName);
    }
    public function add()
    {
        if (IS_POST) {
            $postdata = I();

            $data['is_display'] = 1;

            $data['title']          = $postdata['title'];
            $data['content']        = $postdata['content'];
            $data['small_img']      = $postdata['small_img'];
            $data['addtime']        = $postdata['addtime'];
            $data['customer_img']   = $postdata['customer_img'];
            $data['subject_ad_img'] = $postdata['subject_ad_img'];
            $data['description']    = $postdata['description'];
            $data['htmlcode']       = $postdata['htmlcode'];

            $holddate_arr           = explode(" - ", $postdata['holddate']);
            $data['holddate_start'] = strtotime(trim($holddate_arr[0]));
            $data['holddate_end']   = strtotime(trim($holddate_arr[1]));

            $data['setmeal_id'] = implode(',', $postdata['setmeal_id']);

            $result = M('Subject')->add($data);
            if ($result) {
                ZBuilder::result(1, '操作成功', U('index'));
            } else {
                ZBuilder::result(0, '操作失败');
            }
        }

        $Zbuilder = ZBuilder::make('Form')
            ->setPageTitle('新增')
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '页面标题'],
                ['laydaterange', 'holddate', '举办日期', '', ['laydatetype' => 'date']],
                // ['imageurl', 'small_img', '缩略图'],
                ['imageurl', 'subject_ad_img', '广告位图片', '广告位图片（建议尺寸1920*300）'],
                ['textarea', 'content', '招聘会介绍', '', '', 'autoHeight=true'],

                ['checkbox', 'setmeal_id', '允许报名套餐', '', array_column(D('Setmeal')->order('show_order desc,id')->select(), 'setmeal_name', 'id'), array_column(D('Setmeal')->order('show_order desc,id')->select(), 'id')],
                ['radio', 'is_audit', '仅认证企业报名', '', [0 => '否', 1 => '是'], 0],
                ['number', 'resume_percent', '要求简历完整度', '单位（%）,(0表示不限,个人简历达到设置值才可报名参会)', 0],
                // ['imageurl', 'customer_img', '联系客服'],

                ['ueditor', 'description', '活动详情介绍', '', '', 'autoHeight=true'],
                ['textarea', 'htmlcode', 'html代码', 'js css 代码都可以', '', 'autoHeight=true'],

            ])
            ->fetch();
    }
    public function edit()
    {
        if (IS_POST) {
            $postdata = I();

            $data['is_display'] = 1;

            $data['title']          = $postdata['title'];
            $data['content']        = $postdata['content'];
            $data['small_img']      = $postdata['small_img'];
            $data['addtime']        = $postdata['addtime'];
            $data['customer_img']   = $postdata['customer_img'];
            $data['subject_ad_img'] = $postdata['subject_ad_img'];
            $data['description']    = $postdata['description'];
            $data['htmlcode']       = $postdata['htmlcode'];

            $holddate_arr           = explode(" - ", $postdata['holddate']);
            $data['holddate_start'] = strtotime(trim($holddate_arr[0]));
            $data['holddate_end']   = strtotime(trim($holddate_arr[1]));

            $data['setmeal_id'] = implode(',', $postdata['setmeal_id']);

            $result = M('Subject')->where(['id' => $postdata['id']])->save($data);
            if ($result) {
                ZBuilder::result(1, '操作成功', U('index'));
            } else {
                ZBuilder::result(0, '操作失败');
            }
        }

        $id               = I('id');
        $info             = M('subject')->where(['id' => $id])->find();
        $info['holddate'] = date('Y-m-d', $info['holddate_start']) . ' - ' . date('Y-m-d', $info['holddate_end']);

        $Zbuilder = ZBuilder::make('Form')
            ->setPageTitle('新增')
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'title', '页面标题'],
                ['laydaterange', 'holddate', '举办日期', '', ['laydatetype' => 'date']],
                // ['imageurl', 'small_img', '缩略图'],
                ['imageurl', 'subject_ad_img', '广告位图片', '广告位图片（建议尺寸1920*300）'],
                ['textarea', 'content', '招聘会介绍', '', '', 'autoHeight=true'],

                ['checkbox', 'setmeal_id', '允许报名套餐', '', array_column(D('Setmeal')->order('show_order desc,id')->select(), 'setmeal_name', 'id'), array_column(D('Setmeal')->order('show_order desc,id')->select(), 'id')],
                ['radio', 'is_audit', '仅认证企业报名', '', [0 => '否', 1 => '是'], 0],
                ['number', 'resume_percent', '要求简历完整度', '单位（%）,(0表示不限,个人简历达到设置值才可报名参会)', 0],

                ['ueditor', 'description', '活动详情介绍', '', '', 'autoHeight=true'],
                ['textarea', 'htmlcode', 'html代码', 'js css 代码都可以', '', 'autoHeight=true'],

                // ['imageurl', 'customer_img', '联系客服'],
            ])
            ->setFormData($info)
            ->fetch();
    }
    public function delete()
    {
        $this->_name = 'Subject';
        parent::delete();
    }
    public function subject_personal()
    {
        $db_pre        = C('DB_PREFIX');
        $this_t        = $db_pre . 'subject_personal';
        $subject_id    = I('get.id', 0, 'intval');
        $key_type      = I('request.key_type', 0, 'intval');
        $key           = I('request.key', '', 'trim');
        $subject_title = D('Subject')->where(array('id' => $subject_id))->field('title')->find();
        $this->field   = $this_t . '.*,m.fullname,m.photo_img,m.birthdate,m.sex_cn,m.education_cn,m.experience_cn,m.audit,m.display,m.refreshtime,m.talent,m.complete_percent';
        if ($key && $key_type > 0) {
            switch ($key_type) {
                case 1:
                    $where['m.fullname'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['m.telephone'] = array('like', '%' . $key . '%');
                    break;
            }
        } else {
            if ($s_audit = I('get.s_audit', 0, 'intval')) {
                $where[$this_t . '.s_audit'] = array('eq', $s_audit);
            }
            if ('' != $robot = I('request.robot')) {
                if ($robot) {
                    $where[$this_t . '.robot'] = intval($robot);
                } else {
                    $where[$this_t . '.robot'] = array(array('eq', 0), array('is', null), 'or');
                }
            }
        }
        $where['subject_id'] = $subject_id;
        $join                = 'left join ' . $db_pre . "resume as m on " . $this_t . ".resume_uid=m.uid";
        $count               = D('SubjectPersonal')->where($where)->join($join)->count();
        $pager               = pager($count, 10);
        $page                = $pager->fshow(true);
        $this->assign("page", $page);
        $order_by = 'field(s_audit,2,1,3),addtime desc';
        $result   = D('SubjectPersonal')->where($where)->join($join)->field($this->field)->order($order_by)->limit($pager->firstRow . ',' . $pager->listRows)->select();
        foreach ($result as $key => $value) {
            $row['fullname']         = $value['fullname'];
            $row['photo_img']        = $value['photo_img'];
            $row['birthdate']        = $value['birthdate'];
            $row['sex_cn']           = $value['sex_cn'];
            $row['education_cn']     = $value['education_cn'];
            $row['experience_cn']    = $value['experience_cn'];
            $row['audit']            = $value['audit'];
            $row['display']          = $value['display'];
            $row['addtime']          = $value['addtime'];
            $row['refreshtime']      = $value['refreshtime'];
            $row['complete_percent'] = $value['complete_percent'];
            $row['id']               = $value['id'];
            $row['s_audit']          = $value['s_audit'];
            $row['robot']            = $value['robot'];
            $info[]                  = $row;
        }
        $this->assign('title', $subject_title['title']);
        $this->assign('subject_id', $subject_id);
        $this->assign('info', $info);
        $this->display();
    }
    public function subject_personal_add()
    {
        if (IS_POST) {
            $post_data['resume_uid'] = I('post.uid', 0, 'intval');
            $post_data['subject_id'] = I('post.subject_id', 0, 'intval');
            $post_data['robot']      = 1;
            $post_data['s_audit']    = 1;
            $company                 = D('SubjectPersonal')->where(array('resume_uid' => $post_data['resume_uid'], 'subject_id' => $post_data['subject_id']))->select();
            if ($company) {
                $this->error('此个人已添加过此网络招聘会专题，请重新添加！');
            }
            if (false === $reg = D('SubjectPersonal')->create($post_data)) {
                $this->error(D('SubjectPersonal')->getError());
            } else {
                if (false === $insert_id = D('SubjectPersonal')->add($reg)) {
                    $this->error(D('SubjectPersonal')->getError());
                } else {
                    $this->success('添加成功！');
                }
            }
        } else {
            $subject_title = D('Subject')->where(array('id' => I('get.subject_id', 0, 'intval')))->field('title')->find();
            $this->assign('title', $subject_title['title']);
            $this->assign('subject_id', I('get.subject_id', 0, 'intval'));
            $this->display();
        }
    }
    public function subject_personal_delete()
    {
        $this->_name = 'SubjectPersonal';
        parent::delete();
    }
    public function subject_personal_edit()
    {
        if (IS_POST) {
            $arr['s_audit'] = I('post.s_audit', 0, 'intval');
            $arr['note']    = I('post.note', '', 'trim');
            $id             = I('post.id', 0, 'intval');
            M('SubjectPersonal')->where(array('id' => $id))->save($arr);
            $this->success('保存成功！');
        } else {
            $id = I('get.id', 0, 'intval');
            !$id && $this->error('请选择项目！');
            $info              = M('SubjectPersonal')->where(array('id' => $id))->find();
            $com               = M('Resume')->where(array('uid' => $info['resume_uid']))->field('fullname,audit,telephone')->find();
            $info['fullname']  = $com['fullname'];
            $info['audit']     = $com['audit'];
            $info['telephone'] = $com['telephone'];
            $this->assign('info', $info);
            $this->display();
        }
    }
    public function subject_company()
    {
        $db_pre        = C('DB_PREFIX');
        $this_t        = $db_pre . 'subject_company';
        $subject_id    = I('get.id', 0, 'intval');
        $key_type      = I('request.key_type', 0, 'intval');
        $key           = I('request.key', '', 'trim');
        $subject_title = D('Subject')->where(array('id' => $subject_id))->field('title')->find();
        $this->field   = $this_t . '.*,m.companyname,m.audit,m.setmeal_name,m.telephone,m.contact';
        if ($key && $key_type > 0) {
            switch ($key_type) {
                case 1:
                    $where['m.companyname'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['m.telephone'] = array('like', '%' . $key . '%');
                    break;
            }
        } else {
            if ($s_audit = I('get.s_audit', 0, 'intval')) {
                $where[$this_t . '.s_audit'] = array('eq', $s_audit);
            }
            if ('' != $robot = I('request.robot')) {
                if ($robot) {
                    $where[$this_t . '.robot'] = intval($robot);
                } else {
                    $where[$this_t . '.robot'] = array(array('eq', 0), array('is', null), 'or');
                }
            }
            if ('' != $c_order = I('request.c_order')) {
                if ($c_order) {
                    $where[$this_t . '.c_order'] = intval($c_order);
                } else {
                    $where[$this_t . '.c_order'] = array(array('eq', 0), array('is', null), 'or');
                }
            }
            if ('' != $status = I('request.add_status')) {
                if ($status == 3) {
                    $where[$this_t . '.add_status'] = 1;
                } elseif ($status == 1) {
                    $where[$this_t . '.wx_photo'] = array('neq', '');
                } elseif ($status == 2) {
                    $where[$this_t . '.wx_photo']      = array('neq', '');
                    $tmpsettr                          = strtotime("-7 day");
                    $where[$this_t . '.wx_photo_time'] = array('gt', $tmpsettr);
                } else {
                    $where[$this_t . '.wx_photo'] = array(array('eq', ''), array('is', null), 'or');
                }
            }
            if ($setmeal = I('get.setmeal_id', 0, 'intval')) {
                $where['m.setmeal_id'] = array('eq', $setmeal);
            }
        }
        $where['subject_id'] = $subject_id;
        $join                = 'left join ' . $db_pre . "company_profile as m on " . $this_t . ".company_uid=m.uid";
        $count               = D('SubjectCompany')->where($where)->join($join)->count();
        $pager               = pager($count, 10);
        $page                = $pager->fshow(true);
        $this->assign("page", $page);
        $order_by    = 'field(s_audit,2,1,3), addtime desc';
        $company_uid = D('SubjectCompany')->where($where)->join($join)->field($this->field)->order($order_by)->limit($pager->firstRow . ',' . $pager->listRows)->select();
        $info        = array();
        foreach ($company_uid as $key => $value) {
            $row = $value;
            if ($value['wx_photo_time']) {
                $days       = time() - $value['wx_photo_time'];
                $day        = $days / 86400;
                $row['day'] = ceil($day);
            }
            $info[] = $row;
        }
        $this->assign('title', $subject_title['title']);
        $this->assign('subject_id', $subject_id);
        $this->assign('info', $info);
        $setmeal = D('Setmeal')->get_setmeal_cache();
        $this->assign('setmeal', $setmeal);
        $this->display();
    }
    public function subject_company_add()
    {
        if (IS_POST) {
            $post_data['company_uid'] = I('post.uid', 0, 'intval');
            $post_data['subject_id']  = I('post.subject_id', 0, 'intval');
            $post_data['robot']       = 1;
            $post_data['s_audit']     = 1;
            $company                  = D('SubjectCompany')->where(array('company_uid' => $post_data['company_uid'], 'subject_id' => $post_data['subject_id']))->select();
            if ($company) {
                $this->error('此企业已添加过此网络招聘会专题，请重新添加！');
            }
            if (false === $reg = D('SubjectCompany')->create($post_data)) {
                $this->error(D('SubjectCompany')->getError());
            } else {
                if (false === $insert_id = D('SubjectCompany')->add($reg)) {
                    $this->error(D('SubjectCompany')->getError());
                } else {
                    $this->success('添加成功！');
                }
            }
        } else {
            $this->assign('subject_id', I('get.subject_id', 0, 'intval'));
            $this->display();
        }
    }
    public function subject_company_edit()
    {
        if (IS_POST) {
            $arr['s_audit'] = I('post.s_audit', 0, 'intval');
            $arr['note']    = I('post.note', '', 'trim');
            $id             = I('post.id', 0, 'intval');
            M('SubjectCompany')->where(array('id' => $id))->save($arr);
            $this->success('保存成功！');
        } else {
            $id = I('get.id', 0, 'intval');
            !$id && $this->error('请选择项目！');
            $info                 = M('SubjectCompany')->where(array('id' => $id))->find();
            $com                  = M('CompanyProfile')->where(array('uid' => $info['company_uid']))->field('companyname,audit,setmeal_name,telephone,contact')->find();
            $info['companyname']  = $com['companyname'];
            $info['audit']        = $com['audit'];
            $info['setmeal_name'] = $com['setmeal_name'];
            $info['telephone']    = $com['telephone'];
            $info['contact']      = $com['contact'];
            $this->assign('info', $info);
            $this->display();
        }
    }
    /**
     * 审核参会简历
     */
    public function subject_personal_audit()
    {
        if (IS_AJAX) {
            $ids = I('request.id');
            if (!$ids) {
                $this->ajaxReturn(0, '请选择参会简历！');
            }
            $this->assign('ids', $ids);
            $html = $this->fetch();
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $id = I('request.id');
            if (empty($id)) {
                $this->error('请选择参会简历！');
            }
            $audit = I('request.audit', 1, 'intval');
            if ($n = D('SubjectPersonal')->admin_personal_audit($id, $audit)) {
                $this->success("审核成功！影响行数 {$n}");
            } else {
                $this->error("审核失败！");
            }
        }
    }
    /*
    获取单条 信息
    @$data  字段名=>值
     */
    public function get_user_one($data)
    {
        $user = D('Members')->subsite(true)->where($data)->find();
        return $user;
    }
    // 消息
    public function write_pmsnotice($touid, $toname, $message, $utype)
    {
        $setsqlarr['message']   = trim($message);
        $setsqlarr['msgtouid']  = intval($touid);
        $setsqlarr['msgtoname'] = trim($toname);
        $setsqlarr['replytime'] = time();
        $setsqlarr['utype']     = $utype;
        if (false === $this->create($setsqlarr)) {
            return array('state' => 0, 'error' => $this->getError());
        } else {
            if (false === $insert_id = $this->add()) {
                return array('state' => 0, 'error' => '数据添加失败！');
            }
            M('MembersMsgtip')->where(array('uid' => $setsqlarr['msgtouid'], 'utype' => $utype))->setInc('unread');
        }
        return array('state' => 1, 'id' => $insert_id);
    }
    /**
     * 审核参会企业
     */
    public function subject_audit()
    {
        if (IS_AJAX) {
            $ids = I('request.id');
            if (!$ids) {
                $this->ajaxReturn(0, '请选择参会企业！');
            }
            $this->assign('ids', $ids);
            $html = $this->fetch();
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $id = I('request.id');
            if (empty($id)) {
                $this->error('请选择参会企业！');
            }
            $audit = I('request.audit', 1, 'intval');
            if ($n = D('SubjectCompany')->admin_subject_audit($id, $audit)) {
                $this->success("审核成功！影响行数 {$n}");
            } else {
                $this->error("审核失败！");
            }
        }
    }
    /**
     * 微信直面设置为客服
     */
    public function subject_wx_audit()
    {
        $id = I('request.id');
        if (empty($id)) {
            $this->error('请选择参会企业！');
        }
        $audit = I('request.audit', 1, 'intval');
        if ($n = D('SubjectCompany')->admin_subject_wx_audit($id, $audit)) {
            $this->success("设置成功！影响行数 {$n}");
        } else {
            $this->error("设置失败！");
        }
    }
    /**
     * 直面
     */
    public function subject_wx()
    {
        if (IS_AJAX) {
            $id = I('request.id');
            if (!$id) {
                $this->ajaxReturn(0, '请选择参会企业！');
            }
            $subject = M('SubjectCompany')->where(array('id' => $id))->find();
            $info    = M('CompanyProfile')->where(array('uid' => $subject['company_uid']))->field('companyname')->find();
            $this->assign('info', $info);
            $this->assign('subject', $subject);
            $html = $this->fetch();
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $id = I('request.id');
            if (empty($id)) {
                $this->error('请选择参会企业！');
            }
            $audit                = I('request.audit', 0, 'intval');
            $note                 = I('request.note', '', 'trim');
            $img                  = I('request.wx_photo');
            $arr['add_status']    = $audit;
            $arr['note_wx']       = $note;
            $arr['wx_photo']      = $img;
            $arr['wx_photo_time'] = time();
            D('SubjectCompany')->where(array('id' => $id))->save($arr);
            $this->success("更新成功！");
        }
    }
    /**
     * 审核参会企业
     */
    public function subject_order()
    {
        if (IS_AJAX) {
            $ids                    = I('request.id');
            !is_array($ids) && $ids = array($ids);
            if (!$ids) {
                $this->ajaxReturn(0, '请选择参会企业！');
            }
            $this->assign('ids', $ids);
            $html = $this->fetch();
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $id = I('request.id');
            if (empty($id)) {
                $this->error('请选择参会企业！');
            }
            $order = I('request.c_order', 1, 'intval');
            if ($n = D('SubjectCompany')->admin_subject_order($id, $order)) {
                $this->success("设置成功！影响行数 {$n}");
            } else {
                $this->error("设置失败！");
            }
        }
    }
    public function subject_company_delete()
    {
        $this->_name = 'SubjectCompany';
        parent::delete();
    }
    /**
     * [_before_search 查询条件]
     */
    public function _before_search($data)
    {
        $this->_name = 'Subject';
        $key_type    = I('request.key_type', 0, 'intval');
        $key         = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $data['title'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $data['id'] = intval($key);
                    break;
            }
        } else {
            if ($settr = I('get.settr', 0, 'intval')) {
                $data['addtime'] = array('gt', strtotime("-" . $settr . " day"));
            }
        }
        return $data;
    }
    /**
     * [_before_add 添加专题招聘会]
     */
    public function _before_add()
    {
        if (IS_POST) {
            $_POST['addtime'] = time();
        } else {
            $setmeal_show = D('Setmeal')->order('show_order desc,id')->select();
            $this->assign('setmeal_show', $setmeal_show);
        }
    }
    public function _after_select($info)
    {
        $setmeal_id = explode(',', $info['setmeal_id']);
        foreach ($setmeal_id as $key => $value) {
            $row[$value] = 1;
        }
        $info['setmeal_id'] = $row;
        return $info;
    }
    /**
     * [_before_edit 修改专题信息]
     */
    public function _before_edit()
    {
        $this->_before_add();
    }
    /**
     * [_before_update 加粗是否有值]
     */
    public function _before_update($data)
    {
        $data['tit_b'] = $data['tit_b'] ? 1 : 0;
        return $data;
    }
    public function _after_search()
    {
        $data = $this->get('list');
        foreach ($data as $key => $val) {
            $row                              = $val;
            $total_count                      = M('SubjectCompany')->where(array('subject_id' => $val['id']))->count();
            $wait_audit_count                 = M('SubjectCompany')->where(array('subject_id' => $val['id'], 's_audit' => 2))->count();
            $row['total_count']               = $total_count;
            $row['wait_audit_count']          = $wait_audit_count;
            $total_count_personal             = M('SubjectPersonal')->where(array('subject_id' => $val['id']))->count();
            $wait_audit_count_personal        = M('SubjectPersonal')->where(array('subject_id' => $val['id'], 's_audit' => 2))->count();
            $row['total_count_personal']      = $total_count_personal;
            $row['wait_audit_count_personal'] = $wait_audit_count_personal;
            $setmeal_count                    = M('Setmeal')->count();
            $setmeal_cn                       = M('Setmeal')->getfield('id,setmeal_name');
            $setmeal_id                       = explode(',', $val['setmeal_id']);
            $s_count                          = count($setmeal_id);
            if ($setmeal_count > $s_count) {
                $row['setmeal'] = 1;
                foreach ($setmeal_id as $key => $value) {
                    $title .= $setmeal_cn[$value] . ',';
                }
                $title             = trim($title, ',');
                $row['setmeal_cn'] = $title;
                unset($title);
            } elseif ($setmeal_count == $s_count) {
                $row['setmeal'] = 0;
            }
            $row['setmeal_id'] = $setmeal_id;
            $list[]            = $row;
        }
        $this->assign('list', $list);
    }
    /**
     * [del_img 删除缩略图]
     */
    public function del_img()
    {
        $id        = I('get.id', 0, 'intval');
        $small_img = D('Article')->where(array('id' => $id))->getfield('small_img');
        false === $small_img && $this->error('新闻不存在或已经删除！');
        if ($small_img) {
            $reg = D('Article')->where(array('id' => $id))->setfield('small_img', '');
            if (false !== $reg) {
                @unlink(C('qscms_attach_path') . "images/" . $small_img);
            } else {
                $this->error('缩略图删除失败，请重新操作！');
            }
        }
        $this->success('缩略图删除成功！');
    }
    /**
     * ajax获取职位
     */
    public function ajax_get_resume()
    {
        $type = I('get.type', '', 'trim');
        $key  = I('get.key', '', 'trim');
        switch ($type) {
            case 'get_comname':
                $where = array('fullname' => array('like', '%' . $key . '%'));
                $limit = 30;
                break;
            case 'get_uid':
                $uid   = intval($key);
                $where = array('uid' => array('eq', $uid));
                $limit = 30;
                break;
        }
        $result = D('Resume')->where($where)->limit($limit)->select();
        $info   = array();
        foreach ($result as $key => $value) {
            $value['addtime']     = date("Y-m-d", $value['addtime']);
            $value['refreshtime'] = date("Y-m-d", $value['refreshtime']);
            $value['resume_url']  = url_rewrite('QS_resumeshow', array('id' => $value['id'])) . '&validation=1';
            $info[]               = $value['uid'] . "%%%" . $value['fullname'] . "%%%" . $value['resume_url'] . "%%%" . $value['addtime'] . "%%%" . $value['refreshtime'] . "%%%" . $subsite_id;
        }
        if (!empty($info)) {
            exit(implode('@@@', $info));
        } else {
            exit();
        }
    }
    /**
     * ajax获取职位
     */
    public function ajax_get_jobs()
    {
        $type = I('get.type', '', 'trim');
        $key  = I('get.key', '', 'trim');
        switch ($type) {
            case 'get_comname':
                $where = array('companyname' => array('like', '%' . $key . '%'));
                $limit = 30;
                break;
            case 'get_uid':
                $uid   = intval($key);
                $where = array('uid' => array('eq', $uid));
                $limit = 30;
                break;
        }
        if ($this->apply['Subsite']) {
            $field = D('Jobs')->getDbFields();
            if (in_array('subsite_id', $field) && C('visitor.subsite')) {
                $where['subsite_id'] = array('in', C('visitor.subsite'));
            }
        }
        $result = D('Jobs')->where($where)->limit($limit)->select();
        $info   = array();
        foreach ($result as $key => $value) {
            $value['addtime']     = date("Y-m-d", $value['addtime']);
            $value['deadline']    = $value['deadline'] == 0 ? '无限期' : date("Y-m-d", $value['deadline']);
            $value['refreshtime'] = date("Y-m-d", $value['refreshtime']);
            $value['company_url'] = url_rewrite('QS_companyshow', array('id' => $value['company_id']));
            $value['jobs_url']    = url_rewrite('QS_jobsshow', array('id' => $value['id']));
            $info[]               = $value['uid'] . "%%%" . $value['jobs_name'] . "%%%" . $value['jobs_url'] . "%%%" . $value['companyname'] . "%%%" . $value['company_url'] . "%%%" . $value['addtime'] . "%%%" . $value['deadline'] . "%%%" . $value['refreshtime'] . "%%%" . $subsite_id;
        }
        if (!empty($info)) {
            exit(implode('@@@', $info));
        } else {
            exit();
        }
    }
    public function subject_tpl()
    {
        $this->tpl_dir = APP_PATH . '/Subject/View/';
        $result        = D('Tpl')->where(array('tpl_type' => 3))->select();
        $list          = array();
        foreach ($result as $key => $value) {
            //$value['info']=$this->_get_templates_info($this->tpl_dir.$tpl_file_dir.'/'.$value['tpl_dir']."/info.txt");
            $value['thumb_dir'] = $this->tpl_dir . '/' . $value['tpl_dir'];
            $list[]             = $value;
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function subject_message()
    {
        if (IS_POST) {
            $id                        = I('post.id', 0, 'intval');
            $holddate                  = trim($_POST['holddate_']);
            $holddate_arr              = explode("~", $holddate);
            $holddate_start            = trim($holddate_arr[0]);
            $holddate_end              = trim($holddate_arr[1]);
            $arr['subject_time_start'] = strtotime($holddate_start);
            $arr['subject_time_end']   = strtotime($holddate_end);
            $arr['subject_open']       = I('post.subject_open', 0, 'intval');
            $arr['subject_note']       = I('post.subject_note', '', 'trim');
            $res                       = D('Subject')->where(array('id' => $id))->save($arr);
            if ($res !== false) {
                $this->ajaxReturn(1, '更新成功');
            } else {
                $this->ajaxReturn(0, '更新失败');
            }
        } else {
            $id   = I('get.id', 0, 'intval');
            $info = M('Subject')->where(array('id' => $id))->field('id,title,subject_open,subject_time_start,subject_time_end,subject_note')->find();
            $this->assign('info', $info);
            $total       = M('SubjectMessageLog')->where(array('s_id' => $id))->count();
            $pager       = pager($total, 10);
            $page        = $pager->fshow();
            $this->limit = $pager->firstRow . ',' . $pager->listRows;
            $list        = M('SubjectMessageLog')->where(array('s_id' => $id))->limit($this->limit)->select();
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->display();
        }
    }
    public function subject_message_delete()
    {
        $this->_name = 'SubjectMessageLog';
        parent::delete();
    }
    public function subject_explain()
    {
        $this->display();
    }
    /**
     * 批量添加个人
     */
    public function ajax_export_personal()
    {
        $subject_id = I('get.subject_id', 0, 'intval');
        !$subject_id && $this->ajaxReturn(0, '招聘会ID不能为空');
        $setmeal = D('Setmeal')->get_setmeal_cache();
        $this->assign('subject_id', $subject_id);
        $this->assign('setmeal', $setmeal);
        $this->assign('education_arr', D('Category')->get_category_cache('QS_education'));
        $this->assign('experience_arr', D('Category')->get_category_cache('QS_experience'));
        $html = $this->fetch("ajax_export_personal");
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    public function add_personal_s()
    {
        $where = array();
        if ($settr = I('post.settr', 0, 'intval')) {
            $where['refreshtime'] = array('gt', strtotime("-" . $settr . " day"));
        }
        if ($percent = I('post.percent', 0, 'intval')) {
            $where['complete_percent'] = array('egt', $percent);
        }
        $education                          = I('post.education', 0, 'intval');
        $education && $where['education']   = $education;
        $experience                         = I('post.experience', 0, 'intval');
        $experience && $where['experience'] = $experience;
        $limit                              = 100;
        $count                              = M('Resume')->where($where)->count();
        if ($count == 0) {
            $this->error('没有符合条件的数据');
        }
        $a = 0;
        for ($i = 0; $i < ceil($count / $limit); $i++) {
            //分段查询
            $offset = $i * $limit;
            $list   = M('Resume')->where($where)->order('id desc')->limit($offset . ',' . $limit)->field('uid')->select();
            foreach ($list as $key => $value) {
                $post_data['resume_uid'] = $value['uid'];
                $post_data['subject_id'] = I('post.subject_id', 0, 'intval');
                $post_data['robot']      = 1;
                $post_data['s_audit']    = 1;
                $company                 = D('SubjectPersonal')->where(array('resume_uid' => $post_data['resume_uid'], 'subject_id' => $post_data['subject_id']))->select();
                if ($company) {
                    continue;
                }
                if (false === $reg = D('SubjectPersonal')->create($post_data)) {
                    $this->error(D('SubjectPersonal')->getError());
                } else {
                    if (false === $insert_id = D('SubjectPersonal')->add($reg)) {
                        $this->error(D('SubjectPersonal')->getError());
                    }
                }
                $a++;
            }
        }
        $this->success('成功添加了' . $a++ . '个简历！');
    }
    /**
     * 批量添加企业
     */
    public function ajax_export_company()
    {
        $subject_id = I('get.subject_id', 0, 'intval');
        !$subject_id && $this->ajaxReturn(0, '招聘会ID不能为空');
        $setmeal = D('Setmeal')->get_setmeal_cache();
        $this->assign('subject_id', $subject_id);
        $this->assign('setmeal', $setmeal);
        $html = $this->fetch("ajax_export_company");
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    public function add_company_s()
    {
        if ($settr = I('post.settr', 0, 'intval')) {
            $where['refreshtime'] = array('gt', strtotime("-" . $settr . " day"));
        }
        $setmeal_id = I('post.setmeal_id', 0, 'intval');
        if ($setmeal_id) {
            $where['setmeal_id'] = $setmeal_id;
        }
        $audit = I('post.audit', '', 'trim');
        if ($audit != '') {
            $where['audit'] = $audit;
        }
        $limit = 100;
        $count = M('CompanyProfile')->where($where)->count();
        if ($count == 0) {
            $this->error('没有符合条件的数据');
        }
        $a = 0;
        for ($i = 0; $i < ceil($count / $limit); $i++) {
            //分段查询
            $offset   = $i * $limit;
            $com_list = M('CompanyProfile')->where($where)->order('refreshtime desc')->limit($offset . ',' . $limit)->field('uid')->select();
            foreach ($com_list as $key => $value) {
                $post_data['company_uid'] = $value['uid'];
                $post_data['subject_id']  = I('post.subject_id', 0, 'intval');
                $post_data['robot']       = 1;
                $post_data['s_audit']     = 1;
                $company                  = D('SubjectCompany')->where(array('company_uid' => $post_data['company_uid'], 'subject_id' => $post_data['subject_id']))->select();
                if ($company) {
                    continue;
                }
                if (false === $reg = D('SubjectCompany')->create($post_data)) {
                    $this->error(D('SubjectCompany')->getError());
                } else {
                    if (false === $insert_id = D('SubjectCompany')->add($reg)) {
                        $this->error(D('SubjectCompany')->getError());
                    }
                }
                $a++;
            }
        }
        $this->success('成功添加了' . $a++ . '个企业！');
    }
    public function add_company_zjy()
    {
        echo '导入企业 zjycode 1261';
        die;
        for ($x = 1261; $x <= 1364; $x++) {
            $id                  = $x;
            $code                = M('zjycode')->where(array('id' => $x))->getField('shtyxydm');
            $data['company_uid'] = M('zjycode')->where(array('id' => $x))->getField('company_uid');
            //$data['company_uid']='400935';
            //$code = M('zjydata')->where(array('company_id'=>$data['company_uid']))->getField('shtyxydm');
            if ($code == '' || $code == null) {
                echo 'error';
                die;
            }
            $ajax_url              = 'http://dx.jxjob.net/Precision/company!addJudgment';
            $ajax_data['shtyxydm'] = $code;
            $output                = https_request_array($ajax_url, $ajax_data);
            dump($output);
            $company_mes = M('CompanyProfile')->where(array('uid' => $data['company_uid']))->find();
            if ($output['code'] == 154 || $output['code'] == 155) {
                $http_url              = 'http://dx.jxjob.net/Precision/company!getaddtest';
                $http_data['shtyxydm'] = $code;
                $http_data['dwyhm']    = $company_mes['companyname'];
                $http_data['password'] = '123456';
                $http_data['dwqc']     = $company_mes['companyname'];
                $http_data['dwlxr']    = $company_mes['contact'];
                $http_data['dwlxsj']   = $company_mes['telephone'];
                $http_data['dwlxjh']   = $company_mes['landline_tel'];
                $http_data['sfyx']     = 1;
                $http_data['dwjj']     = $company_mes['contents'];
                $http_data['u']        = '20190524094129302WalHEnHA';
                $http_data['e_mail']   = $company_mes['email'];
                if ($company_mes['nature'] > 0) {
                    if ($company_mes['nature'] == 46) {
                        $http_data['zpdwlbdm'] = 31;
                        $http_data['zpdwlb']   = '国有企业';
                    } elseif ($company_mes['nature'] == 47 || $company_mes['nature'] == 54) {
                        $http_data['zpdwlbdm'] = 39;
                        $http_data['zpdwlb']   = '中小企业';
                    } elseif ($company_mes['nature'] == 48 || $company_mes['nature'] == 49) {
                        $http_data['zpdwlbdm'] = 32;
                        $http_data['zpdwlb']   = '三资企业';
                    } elseif ($company_mes['nature'] == 50 || $company_mes['nature'] == 51) {
                        $http_data['zpdwlbdm'] = 39;
                        $http_data['zpdwlb']   = '中小企业';
                    } elseif ($company_mes['nature'] == 52) {
                        $http_data['zpdwlbdm'] = 32;
                        $http_data['zpdwlb']   = '三资企业';
                    } elseif ($company_mes['nature'] == 53) {
                        $http_data['zpdwlbdm'] = 29;
                        $http_data['zpdwlb']   = '其他事业单位';
                    }
                }
                $output = https_request_array($http_url, $http_data);
                dump($x);
                dump($output);
            }
        }
    }
    public function add_jobs_zjy()
    {
        echo '导入企业 zjydata id编号3280';
        die;
        for ($x = 3280; $x <= 3415; $x++) {
            $map['id'] = $x;
            $data_url  = M('zjydata')->where($map)->find();
            if ($data_url != null) {
                unset($data_url['id']);
                $ajax_url = 'http://dx.jxjob.net/Precision/job!addJobPhp';
                $output   = https_request_array($ajax_url, $data_url);
                echo $map['id'];
                dump($output);
            } else {
            }
        }
    }
}

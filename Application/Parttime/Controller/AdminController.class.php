<?php
namespace Parttime\Controller;

use Common\Controller\ConfigbaseController;

class AdminController extends ConfigbaseController {

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 兼职列表
     */
    public function index() {
        $audit = I('get.audit');
        $settr = I('get.settr', 0, 'intval');
        $addsettr = I('get.addsettr', 0, 'intval');
        $orderby_str = I('get.orderby', 'addtime', 'trim');
        $order_by = 'field(audit,0,1,2),' . $orderby_str . ' DESC, id DESC';
        $key = I('get.key', '', 'trim');
        $key_type = I('get.key_type', 0, 'intval');
        if ($key && $key_type > 0) {
            switch ($key_type) {
                case 1:
                    $where['jobs_name'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['contact'] = array('like', '%' . $key . '%');
                    break;
                case 3:
                    $where['mobile'] = array('eq', $key);
                    break;
                case 4:
                    $where['id'] = array('eq', $key);
                    break;
                /*case 5:
                    $where['uid']=array('eq',$key);break;*/
            }
            unset($_REQUEST['key']);
        } else {
            in_array($audit, array_keys(D('ParttimeJobs')->audit_status), true) && $where['audit'] = $audit;
            if ($settr > 0) {
                $tmpsettr = strtotime("-" . $settr . " day");
                $where['refreshtime'] = array('GT', $tmpsettr);
                $order_by = 'field(audit,0,1,2), refreshtime DESC';
            }
            if ($addsettr > 0) {
                $tmpaddsettr = strtotime("-" . $addsettr . " day");
                $where['addtime'] = array('GT', $tmpaddsettr);
                $order_by = 'field(audit,0,1,2), addtime DESC';
            }
        }
        if (C('apply.Subsite') && '' != $subsite_id = I('request.subsite_id')) {
            $where['subsite_id'] = $subsite_id;
        }
        $this->where = $where;
        $this->order = $order_by;
        $this->_name = 'ParttimeJobs';
        //$this->custom_fun = '_format_jobs_list';
        parent::index();
    }

    /**
     * 删除兼职职位
     */
    public function del_jobs() {
        $ids = I('request.id');
        !$ids && $this->error('请选择兼职');
        if (!is_array($ids)) $ids = array($ids);
        $sql_in = implode(",", $ids);
        if ($n = D('ParttimeJobs')->where(array('id' => array('in', $sql_in)))->delete()) {
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 刷新兼职职位
     */
    public function refresh() {
        $ids = I('request.id');
        !$ids && $this->error('请选择兼职');
        if ($n = D('ParttimeJobs')->refresh_jobs($ids)) {
            $this->success("刷新成功！响应行数 {$n}");
        } else {
            $this->error("刷新失败！");
        }
    }

    /**
     * 审核兼职职位
     */
    public function set_audit() {
        $ids = I('request.id');
        !$ids && $this->ajaxReturn(0, '请选择兼职');
        if (IS_AJAX) {
            $this->assign('ids', $ids);
            $html = $this->fetch('ajax_audit');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $audit = I('post.audit', 0, 'intval');
            //$reason = I('post.reason','','trim');
            $result = D('ParttimeJobs')->audit_jobs($ids, $audit);
            if ($result) {
                D('ParttimeJobs')->refresh_jobs($ids);
                $this->success("设置成功！");
            } else {
                $this->error('设置失败！');
            }
        }
    }

    /**
     * 修改兼职
     */
    public function edit_jobs() {
        if (IS_POST) {
            $post_data = I('post.');
            if ($post_data['long_period'] == 1) {
                $post_data['cycle_starttime'] = $post_data['cycle_endtime'] = 0;
            } else {
                $post_data['long_period'] = 0;
                if ($post_data['cycle_starttime']) {
                    $post_data['cycle_starttime'] = strtotime($post_data['cycle_starttime']);
                }
                if ($post_data['cycle_endtime']) {
                    $post_data['cycle_endtime'] = strtotime($post_data['cycle_endtime']);
                }
            }
            if (false === $reg = D('ParttimeJobs')->create($post_data)) {
                $this->error(D('ParttimeJobs')->getError());
            } else {
                if (false === D('ParttimeJobs')->save($reg)) {
                    $this->error(D('ParttimeJobs')->getError());
                } else {
                    D('ParttimeJobs')->update_search($reg['id']);
                    $this->success('修改成功！');
                }
            }
        } else {
            $id = I('request.id',0,'intval');
            !$id && $this->error('你没有选择兼职！');
            $category_jobs = D('ParttimeCategory')->get_category_cache('QS_category_jobs');
            $category_wage = D('ParttimeCategory')->get_category_cache('QS_wage_type');
            $category_settlement = D('ParttimeCategory')->get_category_cache('QS_settlement');
            $jobs = D('ParttimeJobs')->find($id);
            $jobs['cycle_starttime'] = $jobs['cycle_starttime'] ? date('Y-m-d', $jobs['cycle_starttime']) : '';
            $jobs['cycle_endtime'] = $jobs['cycle_endtime'] ? date('Y-m-d', $jobs['cycle_endtime']) : '';
            $jobs['worktime'] = $jobs['worktime'] ? unserialize($jobs['worktime']) : '';
            $this->assign('new_record', 0);
            $this->assign('info', $jobs);
            $this->assign('category_jobs', $category_jobs);
            $this->assign('category_wage', $category_wage);
            $this->assign('category_settlement', $category_settlement);
            $this->display();
        }
    }

    /**
     * 添加兼职
     */
    public function add_jobs() {
        if (IS_POST) {
            $post_data = I('post.');
            if ($post_data['long_period'] == 1) {
                $post_data['cycle_starttime'] = $post_data['cycle_endtime'] = 0;
            } else {
                $post_data['long_period'] = 0;
                if ($post_data['cycle_starttime']) {
                    $post_data['cycle_starttime'] = strtotime($post_data['cycle_starttime']);
                }
                if ($post_data['cycle_endtime']) {
                    $post_data['cycle_endtime'] = strtotime($post_data['cycle_endtime']);
                }
            }
            $auth = D('Authentication')->where(array('mobile' => $post_data['mobile']))->find();
            if (!$auth) {
                $auth = D('Authentication')->add_auth_info($post_data['mobile']);
            }
            $post_data['uid'] = $auth['id'];
            if (C('SUBSITE_VAL.s_id') > 0) {
                $post_data['subsite_id'] = C('SUBSITE_VAL.s_id');
            }
            if (false === $reg = D('ParttimeJobs')->create($post_data)) {
                $this->error(D('ParttimeJobs')->getError());
            } else {
                if (false === $insert_id = D('ParttimeJobs')->add($reg)) {
                    $this->error(D('ParttimeJobs')->getError());
                } else {
                    D('ParttimeJobs')->update_search($insert_id);
                    $this->success('添加成功！',U('Parttime/Admin/index'));
                }
            }
        } else {
            $category_jobs = D('ParttimeCategory')->get_category_cache('QS_category_jobs');
            $category_wage = D('ParttimeCategory')->get_category_cache('QS_wage_type');
            $category_settlement = D('ParttimeCategory')->get_category_cache('QS_settlement');
            $this->assign('category_jobs', $category_jobs);
            $this->assign('category_wage', $category_wage);
            $this->assign('category_settlement', $category_settlement);
            $this->assign('new_record', 1);
            $this->display('edit_jobs');
        }
    }

    /**
     * 收到的报名
     */
    public function receive() {
        $id = I('get.id', '0', 'intval');
        !$id && $this->error(0, '请选择兼职');
        $job = M('ParttimeJobs')->find($id);
        $this->assign('job', $job);
        $this->assign('pid', $id);
        unset($_REQUEST['id']);
        $this->where = array('pid' => $id);
        $this->_name = 'ParttimeJobsApply';
        parent::index();
    }

    /**
     * 删除收到的报名
     */
    public function del_receive() {
        $pid = I('request.pid', 0, 'intval');
        !$pid && $this->error('兼职id错误');
        $ids = I('request.id');
        !$ids && $this->error('请选择报名信息');
        if (!is_array($ids)) $ids = array($ids);
        $sql_in = implode(",", $ids);
        if ($n = M('ParttimeJobsApply')->where(array('id' => array('in', $sql_in)))->delete()) {
            M('ParttimeJobsApply')->where(array('id' => $pid))->setDec('apply_num', $n);
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 参数配置
     */
    public function config() {
        if (IS_POST) {
            parent::_edit();
        } else {
            $this->display();
        }
    }

    /**
     * 分类列表
     */
    public function category() {
        if (IS_POST) {
            $cid = I('post.save_cid');
            $name = I('post.name');
            $ordid = I('post.ordid');
            foreach ($cid as $key => $value) {
                $data['name'] = trim($name[$key]);
                $data['ordid'] = intval($ordid[$key]);
                D('ParttimeCategory')->where(array('id' => intval($value)))->setField($data);
            }
            $this->success('保存成功！');
        } else {
            $alias = I('request.alias', '', 'trim');
            !$alias && $this->error('参数alias错误！');
            $list = D('ParttimeCategory')->where(array('alias' => $alias))->order('ordid DESC,id')->select();
            $this->assign('alias', $alias);
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 添加分类
     * @param $alias string 分类组别
     */
    public function add_cate($alias) {
        $this->_name = 'ParttimeCategory';
        if (IS_POST) {
            parent::add();
        } else {
            $info['alias'] = $alias;
            $this->assign('info', $info);
            $this->display('edit_cate');
        }
    }

    /**
     * 修改分类
     */
    public function edit_cate() {
        $this->_name = 'ParttimeCategory';
        $this->_tpl = 'edit_cate';
        parent::edit();
    }

    /**
     * 删除分类
     */
    public function del_cate() {
        $this->_name = 'ParttimeCategory';
        parent::delete();
    }
}

?>
<?php
namespace Gworker\Controller;

use Common\Controller\ConfigbaseController;

class AdminController extends ConfigbaseController {

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 普工列表
     */
    public function publish_apply() {
        $status = I('get.status');
        $addsettr = I('get.addsettr', 0, 'intval');
        $order_by = 'status asc,id DESC';
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
            }
            unset($_REQUEST['key']);
        } else {
            in_array($status, array_keys(D('GworkerPublishApply')->status_arr), true) && $where['status'] = $status;
            if ($addsettr > 0) {
                $tmpaddsettr = strtotime("-" . $addsettr . " day");
                $where['addtime'] = array('GT', $tmpaddsettr);
                $order_by = 'status asc, addtime DESC';
            }
        }
        if (C('apply.Subsite') && '' != $subsite_id = I('request.subsite_id')) {
            $where['subsite_id'] = $subsite_id;
        }
        $this->where = $where;
        $this->order = $order_by;
        $this->_name = 'GworkerPublishApply';
        //$this->custom_fun = '_format_jobs_list';
        parent::index();
    }
    /**
     * 删除业务申请
     */
    public function del_publish_apply() {
        $ids = I('request.id');
        !$ids && $this->error('请选择申请');
        if (!is_array($ids)) $ids = array($ids);
        $sql_in = implode(",", $ids);
        if ($n = D('GworkerPublishApply')->where(array('id' => array('in', $sql_in)))->delete()) {
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 处理申请
     */
    public function set_publish_apply_status() {
        $ids = I('request.id');
        !is_array($ids) && $ids = array($ids);
        !$ids && $this->ajaxReturn(0, '请选择申请');
        if (IS_AJAX) {
            $this->assign('ids', $ids);
            $html = $this->fetch('ajax_status');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $status = I('post.status', 0, 'intval');
            $result = D('GworkerPublishApply')->set_status($ids, $status);
            if ($result) {
                $this->success("设置成功！");
            } else {
                $this->error('设置失败！');
            }
        }
    }
    /**
     * 普工列表
     */
    public function index() {
        $display = I('get.display');
        $settr = I('get.settr', 0, 'intval');
        $addsettr = I('get.addsettr', 0, 'intval');
        $orderby_str = I('get.orderby', 'addtime', 'trim');
        $order_by = 'display asc,' . $orderby_str . ' DESC, id DESC';
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
            }
            unset($_REQUEST['key']);
        } else {
            in_array($display, array_keys(D('GworkerJobs')->display_arr), true) && $where['display'] = $display;
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
        $this->_name = 'GworkerJobs';
        //$this->custom_fun = '_format_jobs_list';
        parent::index();
    }
    /**
     * 删除普工职位
     */
    public function del_jobs() {
        $ids = I('request.id');
        !$ids && $this->error('请选择职位');
        if (!is_array($ids)) $ids = array($ids);
        $sql_in = implode(",", $ids);
        if ($n = D('GworkerJobs')->where(array('id' => array('in', $sql_in)))->delete()) {
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 刷新普工职位
     */
    public function refresh() {
        $ids = I('request.id');
        !$ids && $this->error('请选择职位');
        if ($n = D('GworkerJobs')->refresh_jobs($ids)) {
            $this->success("刷新成功！响应行数 {$n}");
        } else {
            $this->error("刷新失败！");
        }
    }

    /**
     * 普工职位显示/隐藏
     */
    public function set_display() {
        $ids = I('request.id');
        !$ids && $this->ajaxReturn(0, '请选择职位');
        if (IS_AJAX) {
            $this->assign('ids', $ids);
            $html = $this->fetch('ajax_display');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        } else {
            $display = I('post.display', 0, 'intval');
            $result = D('GworkerJobs')->display_jobs($ids, $display);
            if ($result) {
                $this->success("设置成功！");
            } else {
                $this->error('设置失败！');
            }
        }
    }

    /**
     * 添加普工
     */
    public function add() {
        $model = D('GworkerJobs');
        if (IS_POST) {
            $post_data = I('post.');
            if (false === $reg = $model->create($post_data)) {
                $this->error($model->getError());
            } else {
                if (false === $model->add($reg)) {
                    $this->error($model->getError());
                } else {
                    $this->success('修改成功！');
                }
            }
        } else {
            $category_wage = D('GworkerCategory')->get_category_cache('QS_wage');
            $category_education = D('GworkerCategory')->get_category_cache('QS_education');
            $category_tag = D('GworkerCategory')->get_category_cache('QS_tag');
            $this->assign('new_record', 1);
            $this->assign('action', 'add');
            $this->assign('action_cn', '添加');
            $this->assign('category_wage', $category_wage);
            $this->assign('category_education', $category_education);
            $this->assign('category_tag', $category_tag);
            $this->display('form');
        }
    }

    /**
     * 修改普工
     */
    public function edit() {
        $model = D('GworkerJobs');
        if (IS_POST) {
            $post_data = I('post.');
            if (false === $reg = $model->create($post_data)) {
                $this->error($model->getError());
            } else {
                if (false === $model->save($reg)) {
                    $this->error($model->getError());
                } else {
                    $this->success('修改成功！');
                }
            }
        } else {
            $id = I('request.id',0,'intval');
            $category_wage = D('GworkerCategory')->get_category_cache('QS_wage');
            $category_education = D('GworkerCategory')->get_category_cache('QS_education');
            $category_tag = D('GworkerCategory')->get_category_cache('QS_tag');
            $info = $model->find($id);
            $storeTag = $info['tag'] ? explode(",", $info['tag']) : array();
            $tagArr = array('id' => array(), 'cn' => array());
            if (!empty($storeTag)) {
                foreach ($storeTag as $key => $value) {
                    $arr = explode("|", $value);
                    $tagArr['id'][] = $arr[0];
                    $tagArr['cn'][] = $arr[1];
                }
            }
            $tagStr = array('id' => '', 'cn' => '');
            if (!empty($tagArr['id']) && !empty($tagArr['cn'])) {
                $tagStr['id'] = implode(",", $tagArr['id']);
                $tagStr['cn'] = implode(",", $tagArr['cn']);
            }
            $this->assign('tagArr', $tagArr);
            $this->assign('tagStr', $tagStr);
            $this->assign('new_record', 0);
            $this->assign('info', $info);
            $this->assign('action', 'edit');
            $this->assign('action_cn', '修改');
            $this->assign('category_wage', $category_wage);
            $this->assign('category_education', $category_education);
            $this->assign('category_tag', $category_tag);
            $this->display('form');
        }
    }
    /**
     * 收到的报名
     */
    public function receive() {
        $id = I('get.id', '0', 'intval');
        !$id && $this->error(0, '请选择职位');
        $job = M('GworkerJobs')->find($id);
        $this->assign('job', $job);
        $this->assign('pid', $id);
        unset($_REQUEST['id']);
        $this->where = array('pid' => $id);
        $this->_name = 'GworkerJobsApply';
        parent::index();
    }

    /**
     * 删除收到的报名
     */
    public function del_receive() {
        $pid = I('request.pid', 0, 'intval');
        !$pid && $this->error('职位id错误');
        $ids = I('request.id');
        !$ids && $this->error('请选择报名信息');
        if (!is_array($ids)) $ids = array($ids);
        $sql_in = implode(",", $ids);
        if ($n = M('GworkerJobsApply')->where(array('id' => array('in', $sql_in)))->delete()) {
            D('GworkerJobs')->where(array('id' => $pid))->setDec('apply_num', $n);
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
                D('GworkerCategory')->where(array('id' => intval($value)))->setField($data);
            }
            $this->success('保存成功！');
        } else {
            $alias = I('request.alias', '', 'trim');
            !$alias && $this->error('参数alias错误！');
            $list = D('GworkerCategory')->where(array('alias' => $alias))->order('ordid DESC,id')->select();
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
        $this->_name = 'GworkerCategory';
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
        $this->_name = 'GworkerCategory';
        $this->_tpl = 'edit_cate';
        parent::edit();
    }

    /**
     * 删除分类
     */
    public function del_cate() {
        $this->_name = 'GworkerCategory';
        parent::delete();
    }
}

?>
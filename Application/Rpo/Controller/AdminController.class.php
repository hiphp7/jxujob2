<?php
namespace Rpo\Controller;
use Common\Controller\BackendController;
class AdminController extends BackendController {
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * RPO申请列表
     */
    public function index() {
        $this->_name = 'Rpo';
        $db_pre = C('DB_PREFIX');
        $table_name = $db_pre . 'rpo';
        $key = I('get.key', '', 'trim');
        $key_type = I('get.key_type', 0, 'intval');
        if ($key && $key_type > 0) {// 关键字搜索
            switch ($key_type) {
                case 1://公司名称
                    $where['c.companyname'] = array('like', '%' . $key . '%');
                    break;
                case 2://用户名
                    $where['m.username'] = $key;
                    break;
                case 3://公司id
                    $where[$table_name . '.com_id'] = $key;
                    break;
                case 4://会员UID
                    $where[$table_name . '.uid'] = $key;
                    break;
            }
        } else { // 顶部筛选
            if ('' != $status = I('get.status')) {
                $where[$table_name . '.status'] = $status;
            }
            if ($job = I('get.job', 0, 'intval')) {
                $where[$table_name . '.job'] = $job;
            }
            if ($stage = I('get.stage', 0, 'intval')) {
                $where[$table_name . '.stage'] = $stage;
            }
            if ($settr = I('get.settr', 0, 'intval')) {
                $tmpsettr = strtotime("-" . $settr . " day");
                $where[$table_name . '.add_time'] = array('gt', $tmpsettr);
            }
        }
        $this->where = $where;
        $this->order = $table_name . '.id DESC';
        $this->field = $table_name . '.*,m.username,c.companyname';
        $joinsql[0] = $db_pre . 'members as m on ' . $table_name . '.uid=m.uid';
        $joinsql[1] = $db_pre . 'company_profile as c on ' . $table_name . '.uid=c.uid';
        $this->join = $joinsql;
        $this->cate_status = D('Category')->get_category_cache('QS_rpo_status');// 服务状态
        $map = array('type' => 'job', 'display' => 1);
        $this->cate_job = M('RpoCategory')->where($map)->order('sort DESC,cid')->getField('cid,title');// 岗位类别
        $map['type'] = 'stage';
        $this->cate_stage = M('RpoCategory')->where($map)->order('sort DESC,cid')->getField('cid,title');// 服务类型
        parent::index();
    }

    /**
     * 删除申请记录
     */
    public function del() {
        $id = I('request.id');
        !$id && $this->error('请选择要删除的记录！');
        if ($n = D('Rpo')->del($id)) {
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 更新服务状态
     */
    public function set_status() {
        $id = I('request.id');
        !$id && $this->error('请选择申请记录！');
        $status = I('post.status',0,'intval');
        $reason = I('post.reason','','trim');
        $result = D('Rpo')->set_status($id,$status,$reason);
        if($result){
            $this->success("设置成功！");
        }else{
            $this->error('设置失败！');
        }
    }

    /**
     * 添加联系记录
     */
    public function add_follow(){
        $data = I('post.');
        !$data['rpo_id'] && $this->error('参数错误！');
        $rpo = D('Rpo')->get_detail(array('id' => $data['rpo_id']));
        $data['com_id'] = $rpo['com_id'];
        $data['com_uid'] = $rpo['uid'];
        $data['admin_uid'] = C('visitor.id');
        if(false !== D('RpoFollow')->add_follow($data)){
            $this->success('添加成功！');
        } else {
            $err = D('RpoFollow')->getError();
            $this->error($err ? $err : '添加失败！');
        }
    }

    /**
     * 查看详情
     */
    public function ajax_detail() {
        $id = I('request.id', 0, 'intval');
        !$id && $this->ajaxReturn(0, '参数错误！');
        $info = D('Rpo')->get_detail(array('id' => $id));// 申请服务详情
        $follow = D('RpoFollow')->get_follow_list(array('rpo_id'=>$id));// 联系记录
        $this->assign('info', $info);
        $this->assign('list', $follow);
        $html = $this->fetch('ajax_detail');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }

    /**
     * RPO相关分类列表
     */
    public function cate() {
        if (IS_POST) {
            parent::edit();
        } else {
            $type = I('request.type', 0, 'trim');
            !$type && $this->ajaxReturn(0, '参数错误！');
            $list = D('RpoCategory')->where(array('type' => $type))->order('sort desc,cid')->select();
            $this->assign('type', $type);
            $this->assign('list', $list);
        }
        $this->display('cate');
    }

    /**
     * 添加分类
     * @param $type string 分类组别
     */
    public function add_cate($type) {
        $this->_name = 'RpoCategory';
        if (IS_POST) {
            parent::add();
        } else {
            $info['display'] = 1;
            $info['type'] = $type;
            $this->assign('info', $info);
            $this->display('edit_cate');
        }
    }

    /**
     * 添加分类成功后，上传图标
     * @param $id int 分类id
     * @param $data
     */
    protected function _after_insert($id, $data) {
        $folder = QSCMS_DATA_PATH . 'upload/rpo/';
        if ($_FILES['img']['name']) {
            if (file_exists($folder . $id . '.png')) {
                unlink($folder . $id . '.png');
            }
            $result = $this->_upload($_FILES['img'], 'rpo/', array(
                'maxSize'       => 128,//图片最大128k
                'uploadReplace' => true,
                'attach_exts'   => 'png'
            ), $id);
            if ($result['error'] == 0) {
                $this->error($result['info']);
            }
        }
    }

    /**
     * 修改分类
     */
    public function edit_cate() {
        $this->_name = 'RpoCategory';
        $this->_tpl = 'edit_cate';
        parent::edit();
    }

    /**
     * 修改分类成功后，修改图标
     * @param $id int 分类id
     * @param $data
     */
    protected function _after_update($id, $data) {
        $this->_after_insert($id, $data);
    }

    /**
     * 批量保存排序
     */
    public function save_sort() {
        $cid = I('post.cid');
        $sort = I('post.sort');
        foreach ($cid as $key => $value) {
            D('RpoCategory')->where(array('cid' => intval($value)))->setField('sort', $sort[$key]);
        }
        $this->success('保存成功！');
    }

    /**
     * 删除分类
     */
    public function del_cate() {
        $this->_name = 'RpoCategory';
        parent::delete();
    }

    /**
     * 添加分类成功后，上传图标
     * @param $id int 分类id
     */
    protected function _after_del($id) {
        $folder = QSCMS_DATA_PATH . 'upload/rpo/';
        if (file_exists($folder . $id . '.png')) {
            unlink($folder . $id . '.png');
        }
    }
}

?>
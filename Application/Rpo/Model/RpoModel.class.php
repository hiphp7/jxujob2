<?php
namespace Rpo\Model;

use Think\Model;

class RpoModel extends Model {

    protected $_validate = array(
        array('com_id', 'require', '公司id不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('job', 'require', '申请服务岗位不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('stage', 'require', '申请服务阶段不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('contact', 'require', '联系人不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('phone', 'require', '联系方式不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('contact', '1,20', '联系人应在1-20个字内！', self::EXISTS_VALIDATE, 'length'),
        array('phone', '1,50', '联系方式应在1-50个字符内！', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('add_time', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * 根据指定条件获取申请记录列表
     * @param array $where 查询条件
     * @param int $pagesize 每页条数
     * @return array
     */
    public function get_apply_list($where, $pagesize = 10) {
        $count = $this->where($where)->count();
        $pager = pager($count, $pagesize);
        $rst['list'] = $this->where($where)->order('id DESC')->limit($pager->firstRow . ',' . $pager->listRows)->select();
        $cates = D('RpoCategory')->getField('cid,title');
        foreach ($rst['list'] as $key => $value) {
            $value['job'] && $rst['list'][$key]['job_cn'] = $cates[$value['job']];
            $value['stage'] && $rst['list'][$key]['stage_cn'] = $cates[$value['stage']];
        }
        $rst['page'] = $pager->fshow();
        return $rst;
    }

    /**
     * 根据指定条件获取单条申请记录
     * @param array $where 查询条件
     * @return array
     */
    public function get_detail($where) {
        $detail = $this->where($where)->find();
        $cates = D('RpoCategory')->getField('cid,title');
        $detail['job'] && $detail['job_cn'] = $cates[$detail['job']];// 岗位类别
        $detail['stage'] && $detail['stage_cn'] = $cates[$detail['stage']];// 服务类型
        $cate_status = D('Category')->get_category_cache('QS_rpo_status');
        $detail['status'] && $detail['status_cn'] = $cate_status[$detail['status']];// 服务状态
        $detail['companyname'] = M('CompanyProfile')->getFieldByUid($detail['uid'], 'companyname');// 公司名称
        $detail['username'] = M('Members')->getFieldByUid($detail['uid'], 'username');// 公司名称
        return $detail;
    }

    /**
     * 删除申请记录
     * @param array $ids 要删除的记录id
     * @return bool
     */
    public function del($ids) {
        if (!is_array($ids)) $ids = array($ids);
        $rst = $this->where(array('id' => array('in', $ids)))->delete();
        if (false === $rst) return false;
        //删除跟进记录
        D('RpoFollow')->where(array('rpo_id' => array('in', $ids)))->delete();
        //记录日志
        admin_write_log("删除id为" . implode(',', $ids) . "的招聘外包记录", C('visitor.username'), 3);
        return $rst;
    }

    /**
     * 更新服务状态
     * @param array $ids 记录id
     * @param int $status 状态id
     * @param string $reason 原因
     * @return bool
     */
    public function set_status($ids, $status, $reason) {
        if (!is_array($ids)) $ids = array($ids);
        $rst = $this->where(array('id' => array('in', $ids)))->setField(array('status' => $status));
        if (false === $rst) return false;
        //记录日志
        $log = 'id为' . implode(',', $ids) . '的RPO记录，服务状态设为' . $status;
        $reason && $log .= '(原因：' . $reason . ')';
        admin_write_log($log, C('visitor.username'), 3);
        return true;
    }

    /**
     * 申请招聘外包服务
     * @param $data
     * @return bool|mixed
     */
    public function apply_rpo($data) {
        $data = $this->create($data);
        if (!$data) { //数据对象创建错误
            return false;
        }
        $pk = $this->getPk();
        /* 添加或更新数据 */
        if (empty($data[$pk])) {
            $res = $this->add();
        } else {
            $res = $this->save();
        }
        return $res;
    }
}

?>
<?php
namespace Rpo\Model;

use Think\Model;

class RpoFollowModel extends Model {

    protected $_validate = array(
        array('com_id', 'require', '公司id不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('com_uid', 'require', '公司uid不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('admin_uid', 'require', '客服uid不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('comment', 'require', '联系内容不能为空！', self::EXISTS_VALIDATE, 'regex'),
        array('comment', '1,255', '联系方式应在1-255个字符内！', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('add_time', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * 根据指定条件的联系记录
     * @param array $where 查询条件
     * @param int $pagesize 每页条数
     * @return array
     */
    public function get_follow_list($where, $pagesize = 10) {
        $list = $this->where($where)->order('id DESC')->select();
        $admins = M('Admin')->getField('id,username');
        $follow = array();
        foreach ($list as $key => $value) {
            $value['admin_uid'] && $value['admin_username'] = $admins[$value['admin_uid']];
            $follow[] = $value;
        }
        return $follow;
    }

    /**
     * 添加联系记录
     * @param $data
     * @return bool|mixed
     */
    public function add_follow($data) {
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
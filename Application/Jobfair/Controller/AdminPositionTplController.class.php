<?php
namespace Jobfair\Controller;
use Common\Controller\BackendController;
class AdminPositionTplController extends BackendController{
	public function _initialize() {
        parent::_initialize();
        $this->_name = 'JobfairPositionTpl';
        $this->_mod = D($this->_name);
        $this->assign('pre',range('A','Z'));
    }
    /**
     * 添加保存
     */
    public function add_tpl(){
        $area = I('post.area');
        $position_start = I('post.position_start');
        $position_end = I('post.position_end');
        //检测展区提交值是否有重复
        if (count($area) != count(array_unique($area))) {   
            $this->error('您提交的展区数据重复，请选择不同展区！');
        } 
        //新增的入库
        $add_sql['title'] = I('post.title','','trim');
        trim($add_sql['title'])=='' && $this->error('请填写展位模板标题！');
        $add_sql['area'] = serialize($area);
        $position = array();
        if (is_array($area) && count($area)>0)
        {
            for ($i =0; $i <count($area);$i++){
                if (!empty($area[$i]))
                {   
                    $area_word = $area[$i];
                    if(!empty($position_start[$i]) && !empty($position_end[$i])){
                        for ($x = $position_start[$i]; $x <= $position_end[$i]; $x++) { 
                            $position[$area_word][] = $area_word.$x;
                        }
                    }
                }
            }
        }
        $add_sql['position'] = serialize($position);
        $add_sql['position_img'] = '';
        $add_sql['position_img'] = serialize($add_sql['position_img']);
        $add_sql['status'] = 1;
        $insert_id = $this->_mod->add($add_sql);
        if($insert_id){
            $this->success('添加展位模板成功！',U('index'));
        }else{
            $this->error('添加展位模板失败！');
        }
    }
    /**
     * 删除模板
     */
    public function delete(){
        $id = I('request.id');
        $r = $this->_mod->tpl_delete($id);
        if($r['state']){
            $this->success($r['msg']);
        }else{
            $this->error($r['msg']);
        }
    }
    public function _after_select($info){
        $info['area'] = unserialize($info['area']);
        $info['position'] = unserialize($info['position']);
        $info['position_img'] = unserialize($info['position_img']);
        return $info;
    }
    /**
     * 添加展位
     */
    public function position_add(){
        $id = I('post.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }

        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $area = I('post.area');
        $position_start = I('post.position_start');
        $position_end = I('post.position_end');
        //检测展区提交值是否有重复
        if (count($area) != count(array_unique($area))) {   
            $this->error('您提交的展区数据重复，请选择不同展区！');
        } 
        //新增的入库
        $edit_sql['area'] = $info['area'];
        $edit_sql['position'] = $info['position'];
        if (is_array($area) && count($area)>0)
        {
            for ($i =0; $i <count($area);$i++){
                if (!empty($area[$i]))
                {   
                    if(!in_array($area[$i],$edit_sql['area'])){
                        $edit_sql['area'][] = $area[$i];
                    }
                    $area_word = $area[$i];
                    if(!empty($position_start[$i]) && !empty($position_end[$i])){
                        for ($x = $position_start[$i]; $x <= $position_end[$i]; $x++) { 
                            if(!in_array($area_word.$x,$edit_sql['position'][$area_word])){
                                $edit_sql['position'][$area_word][] = $area_word.$x;
                            }
                        }
                    }
                }
            }
        }
        $edit_sql['area'] = serialize($edit_sql['area']);
        $edit_sql['position'] = serialize($edit_sql['position']);
        $this->_mod->where(array('id'=>$id))->save($edit_sql);
        $this->success('添加展位成功！');
    }
    /**
     * 删除展位
     */
    public function position_delete(){
        $id = I('get.id',0,'intval');
        $area = I('get.area','','trim');
        $position = I('get.position','','trim');
        if(!$id || !$position || !$area){
            $this->error('参数错误！');
        }
        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $edit_sql['position'] = $info['position'];
        unset($edit_sql['position'][$area][array_search($position,$edit_sql['position'][$area])]);
        $edit_sql['position'] = serialize($edit_sql['position']);
        $this->_mod->where(array('id'=>$id))->save($edit_sql);
        $this->ajaxReturn(1,'删除成功！');
    }
    /**
     * 批量删除展位
     */
    public function position_delete_batch(){
        $id = I('post.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $area = I('post.area','','trim');
        $position_start = I('post.position_start',0,'intval');
        $position_end = I('post.position_end',0,'intval');
        $edit_sql['position'] = $info['position'];
        if($id && $area && $position_start && $position_end){
            for ($i=$position_start; $i <= $position_end; $i++) { 
                if(in_array($area.$i, $edit_sql['position'][$area])){
                    unset($edit_sql['position'][$area][array_search($area.$i,$edit_sql['position'][$area])]);
                }
            }
            $edit_sql['position'] = serialize($edit_sql['position']);
            $this->_mod->where(array('id'=>$id))->save($edit_sql);
            $this->success('删除成功！');
        }else{
            $this->error('参数错误！');
        }
    }
    /**
     * 删除展区
     */
    public function area_delete(){
        $id = I('request.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $edit_sql['area'] = $info['area'];
        $edit_sql['position'] = $info['position'];
        $area = I('request.area','','trim');
        unset($edit_sql['area'][array_search($area,$edit_sql['area'])]);
        unset($edit_sql['position'][$area]);
        $edit_sql['area'] = serialize($edit_sql['area']);
        $edit_sql['position'] = serialize($edit_sql['position']);
        $this->_mod->where(array('id'=>$id))->save($edit_sql);
        $this->success('删除成功！');
    }
    /**
     * 上传展位图
     */
    public function upload_position_img(){
        $id = I('post.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $edit_sql['position_img'] = $info['position_img'];
        if($_FILES['img']['name']){
            $date = date('y/m/d/');
            $result = $this->_upload($_FILES['img'], 'jobfair_tpl/' . $date, array(
                    'maxSize' => 2*1024,//图片最大2M
                    'uploadReplace' => true,
                    'attach_exts' => 'bmp,png,gif,jpeg,jpg'
            ));
            if ($result['error']) {
                $edit_sql['position_img'][] = $date.$result['info'][0]['savename'];
            }
            $edit_sql['position_img'] = serialize($edit_sql['position_img']);
            $this->_mod->where(array('id'=>$id))->save($edit_sql);
            $this->success('上传成功！');
        }else{
            $this->error('请选择图片！');
        }
    }
    /**
     * 删除展位图
     */
    public function position_img_delete(){
        $id = I('get.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = $this->_mod->find($id);
        if(!$info){
            $this->error('参数错误！');
        }
        $info = $this->_after_select($info);
        $edit_sql['position_img'] = $info['position_img'];
        $img = I('get.img','','trim');
        if(isset($edit_sql['position_img'][array_search($img,$edit_sql['position_img'])])){
            $item = $edit_sql['position_img'][array_search($img,$edit_sql['position_img'])];
            unset($edit_sql['position_img'][array_search($img,$edit_sql['position_img'])]);
            @unlink(C('qscms_attach_path')."jobfair_tpl/".$item);
            $edit_sql['position_img'] = serialize($edit_sql['position_img']);
            $this->_mod->where(array('id'=>$id))->save($edit_sql);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
}
?>
<?php
namespace Jobfair\Controller;
use Common\Controller\BackendController;
class AdminPositionController extends BackendController{
	public function _initialize() {
        parent::_initialize();
        $this->_mod = D('Jobfair');
    }
    public function index($jobfair_id){
        $position_arr = M('JobfairPosition')->where(array('jobfair_id'=>$jobfair_id))->order('area_id asc,orderid asc')->select();
        $position = array();
        foreach ($position_arr as $key => $value) {
            $position[$value['area_id']][] = $value;
        }
        $this->assign('jobfair_id',$jobfair_id);
        $this->assign('area',M('JobfairArea')->where(array('jobfair_id'=>$jobfair_id))->order('area asc')->select());
        $this->assign('position',$position);
        $this->assign('position_img',M('JobfairPositionImg')->where(array('jobfair_id'=>$jobfair_id))->select());
        $this->assign('pre',range('A','Z'));
        $this->display('Admin/set_position');
    }
    /**
     * 添加展区
     */
    public function area_add(){
        $jobfair_id = I('post.jobfair_id',0,'intval');
        if(!$jobfair_id){
            $this->error('参数错误！');
        }
        $area = I('post.area');
        $position_start = I('post.position_start');
        $position_end = I('post.position_end');
        //检测展区提交值是否有重复
        if (count($area) != count(array_unique($area))) {   
            $this->error('您提交的展区数据重复，请选择不同展区！');
        } 
        //新增的入库
        $area_arr = M('JobfairArea')->where(array('jobfair_id'=>$jobfair_id))->getField('area,id');
        $position_arr = M('JobfairPosition')->where(array('jobfair_id'=>$jobfair_id))->getField('position,id');
        if (is_array($area) && count($area)>0)
        {
            for ($i =0; $i <count($area);$i++){
                if (!empty($area[$i]))
                {   
                    //如果展区不存在
                    if(!isset($area_arr[$area[$i]])){
                        $area_sqlarr['jobfair_id'] = intval($jobfair_id);
                        $area_sqlarr['area'] = trim($area[$i]);
                        $insertid = M('JobfairArea')->add($area_sqlarr);
                    }else{
                        //展区存在，则不新增展区数据
                        $insertid = $area_arr[$area[$i]];
                    }
                    $area_word = $area[$i];
                    if(!empty($position_start[$i]) && !empty($position_end[$i])){
                        for ($x = $position_start[$i]; $x <= $position_end[$i]; $x++) { 
                            if(isset($position_arr[$area_word.$x])){
                                continue;
                            }
                            $position_sqlarr['jobfair_id'] = intval($jobfair_id);
                            $position_sqlarr['area_id'] = $insertid;
                            $position_sqlarr['position'] = $area_word.$x;
                            $position_sqlarr['orderid'] = $x;
                            M('JobfairPosition')->add($position_sqlarr);
                        }
                    }
                }
            }
        }
        $this->success('添加展区成功！');
    }
    /**
     * 开启/暂停预定
     */
    public function position_pause($id,$pause=1){
        if($pause==1){
            M('JobfairPosition')->where(array('id'=>$id))->setField('status',3);
            $status = 3;
        }else{
            M('JobfairPosition')->where(array('id'=>$id))->setField('status',0);
            $status = 0;
        }
        $this->ajaxReturn(1,'操作成功！',$status);
    }
    /**
     * 删除展位
     */
    public function position_delete($id){
        $r = M('JobfairPosition')->where(array('id'=>$id))->delete();
        if($r){
            $this->ajaxReturn(1,'操作成功！');
        }else{
            $this->ajaxReturn(0,'操作失败！');
        }
    }
    /**
     * 删除展区
     */
    public function area_delete(){
        $area = I('post.area',0,'intval');
        $jobfair_id = I('post.jobfair_id',0,'intval');
        $r = M('JobfairArea')->where(array('id'=>$area))->delete();
        if($r){
            $position_arr = M('JobfairPosition')->where(array('jobfair_id'=>$jobfair_id,'area_id'=>$area))->select();
            foreach ($position_arr as $key => $value) {
                M('JobfairExhibitors')->where(array('jobfair_id'=>$jobfair_id,'position_id'=>$value['id']))->delete();
            }
            M('JobfairPosition')->where(array('jobfair_id'=>$jobfair_id,'area_id'=>$area))->delete();
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    /**
     * 批量删除展位
     */
    public function position_delete_batch(){
        $jobfair_id = I('post.jobfair_id',0,'intval');
        if(!$jobfair_id){
            $this->error('参数错误！');
        }
        $area = I('post.area','','trim');
        $position_start = I('post.position_start',0,'intval');
        $position_end = I('post.position_end',0,'intval');
        if($jobfair_id && $area && $position_start && $position_end){
            for ($i=$position_start; $i <= $position_end; $i++) { 
                $position = M('JobfairPosition')->where(array('jobfair_id'=>$jobfair_id,'position'=>$area.$i))->find();
                M('JobfairExhibitors')->where(array('jobfair_id'=>$jobfair_id,'position_id'=>$position['id']))->delete();
                M('JobfairPosition')->where(array('id'=>$position['id']))->delete();
            }
            $this->success('删除成功！');
        }else{
            $this->error('参数错误！');
        }
    }
    /**
     * 上传展位图
     */
    public function upload_position_img(){
        $this->_name = 'JobfairPositionImg';
        $jobfair_id = I('post.jobfair_id',0,'intval');
        if(!$jobfair_id){
            $this->error('参数错误！');
        }
        if($_FILES['img']['name']){
            $date = date('y/m/d/');
            $result = $this->_upload($_FILES['img'], 'jobfair/' . $date, array(
                    'maxSize' => 2*1024,//图片最大2M
                    'uploadReplace' => true,
                    'attach_exts' => 'bmp,png,gif,jpeg,jpg'
            ));
            if ($result['error']) {
                $_POST['img'] = $date.$result['info'][0]['savename'];
            }
            parent::add();
        }else{
            $this->error('请选择图片！');
        }
    }
    /**
     * 删除展位图
     */
    public function position_img_delete(){
        $id = I('get.id',0,'intval');
        $model = M('JobfairPositionImg')->where(array('id'=>$id))->find();
        if($model && $model['img']){
            @unlink(C('qscms_attach_path')."jobfair/".$model['img']);
            M('JobfairPositionImg')->where(array('id'=>$id))->delete();
            $this->success('删除成功！');
        }else{
            $this->error('参数错误！');
        }
    }
}
?>
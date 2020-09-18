<?php
namespace Jobfair\Controller;
use Common\Controller\BackendController;
use Common\ORG\qiniu;
class AdminController extends BackendController{
	public function _initialize() {
        parent::_initialize();
        $this->_mod = D('Jobfair');
    }
    /**
     * 招聘会列表
     */
    public function index(){
        $this->_name = 'Jobfair';
        $key = I('get.key','','trim');
        $key_type = I('get.key_type',0,'intval');
        if ($key && $key_type>0)
        {
            switch($key_type){
                case 1:
                    $where['title']=array('like','%'.$key.'%');break;
            }
        }
        else
        {
            if($status=I('get.status',0,'intval')){
                if($status == 1){
                    $where['holddate_start'] = array('gt',time());
                }else{
                    $where['holddate_start'] = array('lt',time());
                }
            }
            if ($settr=I('get.settr',0,'intval'))
            {
                $tmpsettr=strtotime("-".$settr." day");
                $where['addtime']=array('gt',$tmpsettr);
            }
        }
        $this->where = $where;
        $this->order = 'ordid desc,id desc';
        $this->assign('time',time());
        parent::index();
    }
    /**
     * 添加招聘会
     */
    public function jobfair_add(){
        $this->_name = 'Jobfair';
        if(IS_POST){
            if($_FILES['thumb']['name']){
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if(C('qscms_qiniu_open')==1){
                    $qiniu = new qiniu(array(
                        'maxSize'=>2*1024,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES,'thumb');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['thumb'], 'jobfair/' . $date, array(
                            'maxSize' => 2*1024,//图片最大2M
                            'uploadReplace' => true,
                            'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                    ));
                    if ($result['error']) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $date.$result['info'][0]['savename'];
                        $config_params['info'] = '';
                    }
                }
                if($config_params['upload_ok']){
                    $_POST['thumb'] = $config_params['url'];
                }
            }
            if($_FILES['intro_img']['name']){
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if(C('qscms_qiniu_open')==1){
                    $qiniu = new qiniu(array(
                        'maxSize'=>2*1024,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES,'intro_img');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['intro_img'], 'jobfair/' . $date, array(
                            'maxSize' => 2*1024,//图片最大2M
                            'uploadReplace' => true,
                            'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                    ));
                    if ($result['error']) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $date.$result['info'][0]['savename'];
                        $config_params['info'] = '';
                    }
                }
                if($config_params['upload_ok']){
                    $_POST['intro_img'] = $config_params['url'];
                }
            }
            $_POST['holddate_start'] = strtotime($_POST['holddate_start']);
            $_POST['holddate_end'] = strtotime($_POST['holddate_end']);
            $_POST['predetermined_start'] = strtotime($_POST['predetermined_start']);
            $_POST['predetermined_end'] = strtotime($_POST['predetermined_end']);
            $_POST['predetermined_web'] = isset($_POST['predetermined_web'])?$_POST['predetermined_web']:0;
            $_POST['predetermined_tel'] = isset($_POST['predetermined_tel'])?$_POST['predetermined_tel']:0;
        }else{
            $tpl_arr = D('JobfairPositionTpl')->where(array('status'=>1))->select();
            $this->assign('tpl_arr',$tpl_arr);
        }
        parent::add();
    }
    public function _after_insert($id,$data){
        if(intval($data['tpl_id'])>0){
            $info = D('JobfairPositionTpl')->where(array('status'=>1,'id'=>$data['tpl_id']))->find();
            $area = unserialize($info['area']);
            $position = unserialize($info['position']);
            $position_img = unserialize($info['position_img']);
            $area_insert_id = array();
            foreach ($area as $key => $value) {
                $area_sqlarr['jobfair_id'] = $id;
                $area_sqlarr['area'] = $value;
                $area_insert_id[$value] = M('JobfairArea')->add($area_sqlarr);
            }
            if(!empty($area_insert_id)){
                foreach ($position as $key => $value) {
                    foreach ($value as $k => $v) {
                        $position_sqlarr['jobfair_id'] = $id;
                        $position_sqlarr['area_id'] = $area_insert_id[$key];
                        $position_sqlarr['position'] = $v;
                        $position_sqlarr['orderid'] = ltrim($v,$key);
                        M('JobfairPosition')->add($position_sqlarr);
                    }
                }
            }
            foreach ($position_img as $key => $value) {
                $dir = C('qscms_attach_path')."jobfair_tpl/".$value;
                if(file_exists($dir)){
                    $date = date('y/m/d/');
                    $extension = end(explode('.',$dir));
                    $new_dir = $date.uniqid().'.'.$extension;
                    copy($dir,C('qscms_attach_path')."jobfair/".$new_dir);
                    $img_sqlarr['jobfair_id'] = $id;
                    $img_sqlarr['img'] = $new_dir;
                    M('JobfairPositionImg')->add($img_sqlarr);
                }
            }
        }
    }
    /**
     * 修改招聘会
     */
    public function jobfair_edit($id){
        $this->_name = 'Jobfair';
        if(IS_POST){
            if($_FILES['thumb']['name']){
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if(C('qscms_qiniu_open')==1){
                    $qiniu = new qiniu(array(
                        'maxSize'=>2*1024,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES,'thumb');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['thumb'], 'jobfair/' . $date, array(
                            'maxSize' => 2*1024,//图片最大2M
                            'uploadReplace' => true,
                            'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                    ));
                    if ($result['error']) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $date.$result['info'][0]['savename'];
                        $config_params['info'] = '';
                    }
                }
                if($config_params['upload_ok']){
                    $_POST['thumb'] = $config_params['url'];
                }
            }
            if($_FILES['intro_img']['name']){
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if(C('qscms_qiniu_open')==1){
                    $qiniu = new qiniu(array(
                        'maxSize'=>2*1024,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES,'intro_img');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['intro_img'], 'jobfair/' . $date, array(
                            'maxSize' => 2*1024,//图片最大2M
                            'uploadReplace' => true,
                            'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                    ));
                    if ($result['error']) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $date.$result['info'][0]['savename'];
                        $config_params['info'] = '';
                    }
                }
                if($config_params['upload_ok']){
                    $_POST['intro_img'] = $config_params['url'];
                }
            }
            $_POST['holddate_start'] = strtotime($_POST['holddate_start']);
            $_POST['holddate_end'] = strtotime($_POST['holddate_end']);
            $_POST['predetermined_start'] = strtotime($_POST['predetermined_start']);
            $_POST['predetermined_end'] = strtotime($_POST['predetermined_end']);
            $_POST['predetermined_web'] = isset($_POST['predetermined_web'])?$_POST['predetermined_web']:0;
            $_POST['predetermined_tel'] = isset($_POST['predetermined_tel'])?$_POST['predetermined_tel']:0;
        }
        parent::edit();
    }
    /**
     * 删除招聘会
     */
    public function jobfair_delete(){
        $id = I('request.id');
        if(!$id){
            $this->error('请选择招聘会！');
        }
        $r = $this->_mod->admin_jobfair_delete($id);
        if($r['state']==1){
            $this->success($r['msg']);
        }else{
            $this->error($r['msg']);
        }
    }
    /**
     * 参会企业
     */
    public function exhibitors_list(){
        $map = array();
        $recommend=I('get.recommend',0,'intval');
        $jobfair_id=I('get.jobfair_id',0,'intval');
        $etype=I('get.etype',0,'intval');
        $audit=I('get.audit',0,'intval');
        $settr=I('get.settr',0,'intval');
        $key = I('get.key','','trim');
        $key_type = I('get.key_type',0,'intval');
        $order_by = 'field(audit,2) desc, id desc';
        
        if ($key && $key_type>0)
        {
            switch($key_type){
                case 1:
                    $map['companyname']=array('like','%'.$key.'%');break;
                case 2:
                    $map['jobfair_title']=array('like','%'.$key.'%');break;
            }
        }
        else
        {
            $recommend>0 && $map['recommend']=array('eq',$recommend-1);
            $jobfair_id>0 && $map['jobfair_id']=array('eq',$jobfair_id);
            $audit>0 && $map['audit']=array('eq',$audit);
            $etype>0 && $map['etype']=array('eq',$etype);
            if ($settr>0)
            {
                $tmpsettr=strtotime("-".$settr." day");
                $map['eaddtime']=array('gt',$tmpsettr);
            }
        }
        
        parent::_list(M('JobfairExhibitors'),$map,$order_by,"*",'','',10);
        $count = M('JobfairExhibitors')->where(array('audit'=>2))->count('id');
        $this->assign('count',$count);
        $this->assign('jobfair_list',D('Jobfair')->select());
        $this->assign('time',time());
        $this->display();
    }
    /**
     * 删除参会企业
     */
    public function exhibitors_delete(){
        $id=I('request.id');
        if (empty($id)) $this->error('请选择项目！');
        if ($n=D('Jobfair')->admin_exhibitors_delete($id))
        {
            $this->success("删除成功！影响行数 {$n}");
        }else{
            $this->error("删除失败！");
        }
    }
    /**
     * 审核参会企业
     */
    public function exhibitors_audit(){
        $id=I('request.id');
        if (empty($id)) $this->error('请选择项目！');
        $audit = I('request.audit',1,'intval');
        if ($n=D('Jobfair')->admin_exhibitors_audit($id,$audit))
        {
            $this->success("审核成功！影响行数 {$n}");
        }else{
            $this->error("审核失败！");
        }
    }
    /**
     * 推荐参会企业
     */
    public function exhibitors_recommend(){
        $id=I('request.id');
        if (empty($id)) $this->error('请选择项目！');
        $recommend = I('request.recommend',1,'intval');
        $n=D('JobfairExhibitors')->where(array('id'=>array('in',$id)))->setField('recommend',$recommend);
        if ($n)
        {
            $this->success("操作成功！影响行数 {$n}");
        }else{
            $this->error("操作失败！");
        }
    }
    /**
     * 新增参会企业
     */
    public function exhibitors_add(){
        if(IS_POST){
            $comid = I('post.comid',0,'intval');
            if ($comid==0)
            {
                $this->error('请选择企业！');
            }
            else
            {
                $info = D('CompanyProfile')->where(array('id'=>$comid))->find();
                if (empty($info))
                {
                    $this->error('企业不存在！');
                }
                $setsqlarr['uid']=$info['uid'];
                $setsqlarr['companyname']=$info['companyname'];
                $setsqlarr['company_id']=$info['id'];
                $setsqlarr['company_addtime']=$info['addtime'];
            }
            $setsqlarr['recommend'] = I('post.recommend',0,'intval');
            $setsqlarr['position_id'] = I('post.position_id',0,'intval');
            $setsqlarr['audit'] = I('post.audit',1,'intval');
            $setsqlarr['etype'] = I('post.etype',1,'intval');
            $setsqlarr['eaddtime']=time();
            $setsqlarr['note'] = I('post.note','','trim');
            //查看选择的招聘会是否存在
            $setsqlarr['jobfair_id'] = I('post.jobfair_id',0,'intval');
            $jobfair = D('Jobfair')->where(array('id'=>$setsqlarr['jobfair_id']))->find();
            if (empty($jobfair))
            {
            $this->error('招聘会不存在');
            }
            $setsqlarr['jobfair_title']=$jobfair['title'];
            $setsqlarr['jobfair_addtime']=$jobfair['addtime'];
            $position = D('JobfairPosition')->where(array('id'=>$setsqlarr['position_id']))->find();
            if (empty($position))
            {
                $this->error('展位不存在');
            }
            $setsqlarr['position']=$position['position'];
            //判断该企业是否已经预订过该招聘会
            $is_join = D('JobfairExhibitors')->where(array('companyname'=>array('eq',$setsqlarr['companyname']),'jobfair_id'=>array('eq',$setsqlarr['jobfair_id'])))->find();
            if(!empty($is_join))
            {
                $this->error('该企业已经预订过此招聘会');
            }
            if($position['status']>0){
                $this->error('此展位不可预定');
            }
            $r = D('JobfairExhibitors')->add($setsqlarr);
            if ($r)
            {
                $position_save['status'] = $setsqlarr['audit'];
                $position_save['company_id'] = $setsqlarr['company_id'];
                $position_save['company_uid'] = $info['uid'];
                $position_save['company_name'] = $info['companyname'];
                M('JobfairPosition')->where(array('id'=>$setsqlarr['position_id']))->save($position_save);
                $this->success('添加成功！'); 
            }
            else
            {
                $this->error('添加失败！'); 
            }   
        }else{
            $this->assign('jobfair',D('Jobfair')->admin_get_jobfair_audit());
            $this->display();
        }
    }
    /**
     * 修改参会企业
     */
    public function exhibitors_edit(){
        $id = I('request.id',0,'intval');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = D('JobfairExhibitors')->where(array('id'=>$id))->find();
        if(IS_POST){
            $setsqlarr['companyname'] = I('post.companyname','','trim')?I('post.companyname','','trim'):$this->error('您没有填写企业名称！');
            $setsqlarr['audit']=I('post.audit',1,'intval');
            $setsqlarr['etype']=I('post.etype',1,'intval');
            $setsqlarr['note']=I('post.note','','trim');
            $setsqlarr['recommend']=I('post.recommend',0,'intval');
            D('JobfairExhibitors')->where(array('id'=>$id))->save($setsqlarr);
            D('JobfairPosition')->where(array('id'=>$info['position_id']))->setField('status',$setsqlarr['audit']);
            $this->success('保存成功！');
        }else{
            $info['company_url']=url_rewrite('QS_companyshow',array('id'=>$info['company_id']));
            $this->assign('info',$info);
            $this->assign('position',M('JobfairPosition')->where(array('jobfair_id'=>$info['jobfair_id']))->select());
            $this->assign('jobfair',D('Jobfair')->admin_get_jobfair_audit());
            $this->display();
        }
    }
    /**
     * ajax获取企业
     */
    public function ajax_get_company(){
        $key = I('get.key','','trim');
        $result = D('CompanyProfile')->where(array('companyname'=>array('like','%'.$key.'%')))->limit(30)->select();
        $info = array();
        foreach ($result as $key => $value) {
            if (empty($value['companyname']))
            {
            continue;
            }
            $value['addtime']=date("Y-m-d",$value['addtime']);
            $value['company_url']=url_rewrite('QS_companyshow',array('id'=>$value['id']));
            $info[]=$value['id']."%%%".$value['companyname']."%%%".$value['company_url']."%%%".$value['addtime'];
        }
        if (!empty($info))
        {
        exit(implode('@@@',$info));
        }
    }
    /**
     * ajax获取展位
     */
    public function ajax_get_position(){
        $jobfair_id = I('get.jobfair_id',0,'intval');
        $result = D('JobfairPosition')->where(array('status'=>0,'jobfair_id'=>array('eq',$jobfair_id)))->select();
        $html='';
        foreach ($result as $key => $value) {
            $html .= '<option value="'.$value['id'].'">'.$value['position'].'</option>';
        }
        exit($html);
    }
    /**
     * 精彩回顾
     */
    public function retrospect($jobfair_id){
        $this->assign('retrospect',M('JobfairRetrospect')->where(array('jobfair_id'=>$jobfair_id))->select());
        $this->assign('jobfair_id',$jobfair_id);
        $this->display();
    }
    /**
     * 上传精彩回顾图
     */
    public function upload_retrospect(){
        $this->_name = 'JobfairRetrospect';
        $jobfair_id = I('post.jobfair_id',0,'intval');
        if(!$jobfair_id){
            $this->error('参数错误！');
        }
        $_POST['jobfair_id'] = $jobfair_id;
        if($_FILES['img']['name']){
            $config_params = array(
                'upload_ok'=>false,
                'url'=>'',
                'info'=>''
            );
            //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
            if(C('qscms_qiniu_open')==1){
                $qiniu = new qiniu(array(
                    'maxSize'=>2*1024,
                    'exts'=>'bmp,png,gif,jpeg,jpg'
                ));
                $img_url = $qiniu->upload($_FILES,'img');
                if($img_url){
                    $config_params['upload_ok'] = true;
                    $config_params['url'] = $img_url;
                    $config_params['info'] = '';
                }else{
                    $config_params['info'] = $qiniu->getError();
                }
            }else{
                $date = date('y/m/d/');
                $result = $this->_upload($_FILES['img'], 'jobfair/' . $date, array(
                        'maxSize' => 2*1024,//图片最大2M
                        'uploadReplace' => true,
                        'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                ));
                if ($result['error']) {
                    $config_params['upload_ok'] = true;
                    $config_params['url'] = $date.$result['info'][0]['savename'];
                    $config_params['info'] = '';
                }
            }
            if($config_params['upload_ok']){
                $_POST['img'] = $config_params['url'];
            }
            parent::add();
        }else{
            $this->error('请选择图片！');
        }
    }
    /**
     * 删除精彩回顾图
     */
    public function retrospect_delete($id){
        $model = M('JobfairRetrospect')->where(array('id'=>$id))->find();
        if($model && $model['img']){
            @unlink(C('qscms_attach_path')."jobfair/".$model['img']);
            if(C('qscms_qiniu_open')==1){
                $qiniu = new qiniu;
                $qiniu->delete($model['img']);
            }
            M('JobfairRetrospect')->where(array('id'=>$id))->delete();
            $this->success('删除成功！');
        }else{
            $this->error('参数错误！');
        }
    }
}
?>
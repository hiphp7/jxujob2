<?php
namespace Analyze\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
        $this->_mod = D('MembersRoute');
    }
    public function index(){
        $key = I('get.key','','trim');
        $key_type = I('get.key_type',0,'intval');
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['username']=array('like','%'.$key.'%');
                    break;
                case 2:
                    $where['uid']=array('eq',$key);
                    break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where['addtime']=array('gt',strtotime("-".$settr." day"));
            }
        }
        $this->where = $where;
        $this->_name = 'MembersRouteGroup';
        $this->_tpl = 'analyze_list_user';
        parent::index();
    }
    protected function _format_list($list){
    	foreach ($list as $key => $value) {
    		$list[$key]['during'] = sub_day($value['page_during']+$value['addtime'],$value['addtime']);
    	}
    	return $list;
    }
    public function config(){
    	parent::edit();
    }
    public function analyze_list_per(){
        $this->index();
    }
    public function analyze_list_com(){
        $this->index();
    }
    public function analyze_detail_per(){
        $this->_analyze_detail();
    }
    public function analyze_detail_com(){
        $this->_analyze_detail();
    }
    public function analyze_detail(){
        $this->_analyze_detail();
    }
    protected function _analyze_detail(){
        $gid = I('get.gid',0,'intval');
        $list = D('MembersRoute')->where(array('gid'=>$gid))->select();
        $this->assign('list',$list);
        $this->display('analyze_detail');
    }
    public function delete_group(){
        $id = I('request.id');
        if(!$id){
            $this->error('请选择要删除的记录！');
        }
        $id_arr = is_array($id)?$id:explode(",", $id);
        M('MembersRoute')->where(array('gid'=>array('in',$id_arr)))->delete();
        M('MembersRouteGroup')->where(array('id'=>array('in',$id_arr)))->delete();
        $this->success('删除成功！');
    }
}
?>
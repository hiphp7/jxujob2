<?php
namespace Mall\Controller;
use Common\Controller\BackendController;
use Common\ORG\qiniu;
class AdminController extends BackendController{
	public function _initialize() {
        parent::_initialize();
    }
    /**
     * 商品列表
     */
    public function index(){
        $model = D('MallGoods');
        $map = array();
        $perpage=I('get.perpage',10,'intval');
        $category=I('get.category',0,'intval');
        $predetermined_status=I('get.predetermined_status',0,'intval');
        $settr=I('get.settr',0,'intval');
        $key = I('get.key','','trim');
        $key_type = I('get.key_type',0,'intval');
        $order_by = 'id desc';
        
        if ($key && $key_type>0)
        {
            switch($key_type){
                case 1:
                    $map['goods_title']=array('like','%'.$key.'%');break;
            }
        }
        else
        {
            $category>0 && $map['category'] = array('eq',$category);
            if ($settr>0)
            {
                $tmpsettr=strtotime("-".$settr." day");
                $map['addtime']=array('gt',$tmpsettr);
            }
        }
        
        parent::_list($model,$map,$order_by,"*",'','',$perpage);
        $this->assign('category',D('MallCategory')->get_mall_cache());
        $this->display();
    }
    /**
     * 添加商品
     */
    public function goods_add(){
        $this->_name = 'MallGoods';
        if(IS_POST){
            $scategory_arr=explode(",", I("post.scategory"));
            $_POST["category"]=$scategory_arr[0];
            $_POST["scategory"]=$scategory_arr[1];
            $_POST["category_cn"]=$scategory_arr[2];
            if($_FILES['goods_img']['name']){
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
                    $img_url = $qiniu->upload($_FILES,'goods_img');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['goods_img'], 'mall/' . $date, array(
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
                    $_POST['goods_img'] = $config_params['url'];
                }
            }
        }else{
            $cat = M('MallCategory')->select();
            $category = array();
            foreach ($cat as $key => $value) {
                $category[$value['parentid']][] = $value;
            }
            $this->assign('category',$category);
        }
        parent::add();
    }
    /**
     * 修改商品
     */
    public function goods_edit(){
        $this->_name = 'MallGoods';
        if(IS_POST){
            $scategory_arr=explode(",", I("post.scategory"));
            $_POST["category"]=$scategory_arr[0];
            $_POST["scategory"]=$scategory_arr[1];
            $_POST["category_cn"]=$scategory_arr[2];
            if($_FILES['goods_img']['name']){
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
                    $img_url = $qiniu->upload($_FILES,'goods_img');
                    if($img_url){
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    }else{
                        $config_params['info'] = $qiniu->getError();
                    }
                }else{
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['goods_img'], 'mall/' . $date, array(
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
                    $_POST['goods_img'] = $config_params['url'];
                }
            }
        }else{
            $cat = M('MallCategory')->select();
            $category = array();
            foreach ($cat as $key => $value) {
                $category[$value['parentid']][] = $value;
            }
            $this->assign('category',$category);
        }
        parent::edit();
    }
    /**
     * 删除商品
     */
    public function goods_delete(){
        $id = I('request.id','','trim');
        if(!$id){
            $this->error('请选择商品！');
        }
        $r = D('MallGoods')->goods_delete($id);
        if($r['state']==1){
            $this->success('成功删除'.$r['num'].'条商品信息！');
        }else{
            $this->error('删除失败！');
        }
    }
    /**
     * 商品分类列表
     */
    public function category(){
        $pid=I('get.pid',0,'intval');
        $map['parentid'] = array('eq',$pid);
        if(IS_AJAX){
            $this->ajaxReturn(1,'分类获取成功',D('MallCategory')->where($map)->order('category_order desc,id')->select());
        }else{
            $this->assign('category',D('MallCategory')->where($map)->order('category_order desc,id')->select());
            $this->display();
        }
    }
    /**
     * 添加商品分类
     */
    public function category_add(){
        if(IS_POST){
            $categoryname = I('post.categoryname');
            $category_order = I('post.category_order');
            $parentid = I('post.parentid');
            //新增的入库
            $num = 0;
            if (is_array($categoryname) && count($categoryname)>0)
            {
                for ($i =0; $i <count($categoryname);$i++){
                    if (!empty($categoryname[$i]))
                    {   
                        $setsqlarr['categoryname']=trim($categoryname[$i]);
                        $setsqlarr['category_order']=intval($category_order[$i]);
                        $setsqlarr['parentid']=intval($parentid[$i]);  
                        $r = D('MallCategory')->add($setsqlarr);
                        $r && $num++;
                    }

                }
            }
            $this->success('添加成功！本次添加了'.$num.'个分类');
        }else{
            $this->assign('category',D('MallCategory')->where(array('parentid'=>0))->select());
            $this->display();
        }
    }
    /**
     * 修改商品分类
     */
    public function category_edit(){
        if(IS_POST){
            $setsqlarr['id'] = I('post.id',0,'intval')?I('post.id',0,'intval'):$this->error('参数错误！');
            $setsqlarr['categoryname']=I('post.categoryname','','trim')?I('post.categoryname','','trim'):$this->error('请填写分类名称');
            $setsqlarr['category_order']=I('post.category_order',0,'intval');
            $setsqlarr['parentid']=I('post.parentid',0,'intval')?I('post.parentid',0,'intval'):$this->error('请选择上级分类');
            $r = D('MallCategory')->save($setsqlarr);
            if($r){
                $this->success('保存成功！');
            }else{
                $this->error('保存失败！');
            }
        }else{
            $id = I('get.id',0,'intval');
            if(!$id){
                $this->error('参数错误！');
            }
            $this->assign('category',D('MallCategory')->where(array('parentid'=>0))->select());
            $this->assign('info',D('MallCategory')->where(array('id'=>array('eq',$id)))->find());
            $this->display();
        }
    }
    /**
     * 批量保存
     */
    public function category_all_save(){
        $save_id = I('post.save_id');
        $categoryname = I('post.categoryname');
        $category_order = I('post.category_order');
        $add_pid = I('post.add_parentid');
        $add_categoryname = I('post.add_categoryname');
        $add_category_order = I('post.add_category_order');
        if (is_array($save_id) && count($save_id)>0)
        {
            foreach($save_id as $k=>$v)
            {
                $setsqlarr['categoryname']=trim($categoryname[$k]);
                $setsqlarr['category_order']=intval($category_order[$k]);
                D('MallCategory')->where(array('id'=>array('eq',intval($save_id[$k]))))->save($setsqlarr);
            }
        }
        //新增的入库
        if (is_array($add_pid) && count($add_pid)>0)
        {
            for ($i =0; $i <count($add_pid);$i++){
                if (!empty($add_categoryname[$i]))
                {   
                    $setsqlarr['categoryname']=trim($add_categoryname[$i]);
                    $setsqlarr['category_order']=intval($add_category_order[$i]);
                    $setsqlarr['parentid']=intval($add_pid[$i]);   
                    D('MallCategory')->add($setsqlarr);
                }
            }
        }
        $this->success('保存成功！');
    }
    /**
     * 删除商品分类
     */
    public function category_delete(){
        $id = I('request.id','','trim');
        if(!$id){
            $this->error('请选择商品分类！');
        }
        $r = D('MallCategory')->category_delete($id);
        if($r['state']==1){
            $this->success('成功删除'.$r['num'].'条商品分类！');
        }else{
            $this->error('删除失败！');
        }
    }
    /**
     * 订单管理
     */
    public function order_list(){
        $this->_name = 'MallOrder';
        $key = I('get.key','','trim');
        $key_type = I('get.key_type',0,'intval');
        if ($key && $key_type>0)
        {
            switch($key_type){
                case 1:
                    $where['goods_title']=array('like','%'.$key.'%');break;
            }
        }
        else
        {
            if ($settr=I('get.settr',0,'intval'))
            {
                $tmpsettr=strtotime("-".$settr." day");
                $where['addtime']=array('gt',$tmpsettr);
            }
        }
        $this->where = $where;
        $this->order = 'status asc, id desc';
        $this->assign('count',parent::_pending('MallOrder',array('status'=>1)));
        parent::index();
    }
    /**
     * 审核订单
     */
    public function order_set(){
        $id = I('request.id');
        if(!$id){
            $this->error('请选择订单！');
        }
        $state = I('request.status',0,'intval');
        if(!$state){
            $this->error('参数错误！');
        }
        $num = D('MallOrder')->order_set($id,$state);
        if($num>0)
        {
            $this->success("操作成功!");
        }
        else
        {
            $this->error("操作失败！");
        }
    }
    /**
     * 删除订单
     */
    public function order_delete(){
        $id = I('request.id');
        if(!$id){
            $this->error('请选择订单！');
        }
        $num = D('MallOrder')->order_delete($id);
        if($num>0)
        {
            $this->success("删除成功!");
        }
        else
        {
            $this->error("删除失败！");
        }
    }
    /**
     * 查看订单
     */
    public function order_show(){
        $id = I('request.id');
        if(!$id){
            $this->error('参数错误！');
        }
        $info = D('MallOrder')->where(array('id'=>$id))->find();
        !$info && $this->error('参数错误！');
        $this->assign('info',$info);
        $this->display();
    }
    /**
     * 热门关键字
     */
    public function hotword(){
        $this->_name = 'MallHotword';
        parent::index();
    }
    /**
     * 添加热门关键字
     */
    public function hotword_add(){
        if(IS_POST){
            $w_word = I('post.w_word');
            $w_hot = I('post.w_hot');
            //新增的入库
            $num = 0;
            if (is_array($w_word) && count($w_word)>0)
            {
                for ($i =0; $i <count($w_word);$i++){
                    if (!empty($w_word[$i]))
                    {   
                        $setsqlarr['w_word']=trim($w_word[$i]);
                        $setsqlarr['w_hot']=intval($w_hot[$i]);
                        $r = D('MallHotword')->add($setsqlarr);
                        $r && $num++;
                    }

                }
            }
            $this->success('添加成功！本次添加了'.$num.'个关键词');
        }else{
            $this->_name = 'MallHotword';
            parent::add();
        }
    }
    /**
     * 修改热门关键字
     */
    public function hotword_edit(){
        $this->_name = 'MallHotword';
        parent::edit();
    }
    /**
     * 删除热门关键字
     */
    public function hotword_delete(){
        $this->_name = 'MallHotword';
        parent::delete();
    }
}
?>
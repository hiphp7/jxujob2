<?php
namespace Admin\Controller;

use Common\Controller\BackendController;
use Common\ORG\qiniu;

class ArticleController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('ArticleCategory');
    }
    /**
     * [_before_index 资讯列表]
     */
    public function _before_index()
    {
        $article_category = $this->_mod->get_article_category_cache('all');
        if (false === $article_property = F('article_property')) {
            $article_property = D('ArticleProperty')->article_property_cache();
        }
        $this->assign('article_property', $article_property);
        $this->assign('article_category', $article_category);
        $this->list_relation = true;
        $this->assign('parentid', I('get.parentid', 0, 'intval'));
        $this->order = 'article_order desc, addtime desc';
    }
    /**
     * [_before_search 查询条件]
     */
    public function _before_search($data)
    {
        $key_type = I('request.key_type', 0, 'intval');
        $key = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $data['title'] = array('like','%'.$key.'%');
                    break;
                case 2:
                    $data['id'] = intval($key);
                    break;
            }
        }
        return $data;
    }
    /**
     * [_before_add 添加资讯]
     */
    public function _before_add()
    {
        $article_category = $this->_mod->get_article_category_cache('all');
        if (IS_POST) {
            $type_id = I('request.type_id', 0, 'intval');
            if ($article_category[$type_id]) {
                $_POST['parentid'] = $type_id;
                $_POST['type_id'] = $type_id;
            } else {
                $_POST['parentid'] = $this->_mod->where(array('id'=>$type_id))->getfield('parentid');
            }
            if ($addtime = I('request.addtime', '', 'trim')) {
                if (date('Y-m-d') == $addtime) {
                    $_POST['addtime'] = time();
                } else {
                    $_POST['addtime'] = strtotime($addtime);
                }
            } else {
                $_POST['addtime'] = time();
            }
            if (!$_FILES['Small_img']['name']) {
                return false;
            }
            $config_params = array(
                'upload_ok'=>false,
                'url'=>'',
                'info'=>''
            );
            //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
            if (C('qscms_qiniu_open')==1) {
                $qiniu = new qiniu(array(
                    'maxSize'=>2*1024,
                    'exts'=>'bmp,png,gif,jpeg,jpg'
                ));
                $img_url = $qiniu->upload($_FILES, 'Small_img');
                if ($img_url) {
                    $config_params['upload_ok'] = true;
                    $config_params['url'] = $img_url;
                    $config_params['info'] = '';
                } else {
                    $config_params['info'] = $qiniu->getError();
                }
            } else {
                $date = date('y/m/d/');
                $result = $this->_upload($_FILES['Small_img'], 'images/' . $date, array(
                        'maxSize' => 2*1024,//图片最大2M
                        'uploadReplace' => true,
                        'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                ));
                if ($result['error']) {
                    $config_params['upload_ok'] = true;
                    $config_params['url'] = $date.$result['info'][0]['savename'];
                    $config_params['info'] = '';
                } else {
                    $config_params['info'] = $result['info'];
                }
            }
            if ($config_params['upload_ok']) {
                $_POST['Small_img'] = $config_params['url'];
            } else {
                $this->ajaxReturn(0, $config_params['info']);
            }
        } else {
            if (false === $article_property = F('article_property')) {
                $article_property = D('ArticleProperty')->article_property_cache();
            }
            $this->assign('article_property', $article_property);
            $this->assign('article_category', $article_category);
        }
    }
    /**
     * [_before_edit 修改资讯信息]
     */
    public function _before_edit()
    {
        $this->_before_add();
    }
    /**
     * [_before_update 加粗是否有值]
     */
    public function _before_update($data)
    {
        $data['tit_b'] = $data['tit_b']?1:0;
        return $data;
    }
    /**
     * [del_img 删除缩略图]
     */
    public function del_img()
    {
        $id = I('get.id', 0, 'intval');
        $Small_img = D('Article')->where(array('id'=>$id))->getfield('Small_img');
        false === $Small_img && $this->error('新闻不存在或已经删除！');
        if ($Small_img) {
            $reg = D('Article')->where(array('id'=>$id))->setfield('Small_img', '');
            if (false !== $reg) {
                @unlink(C('qscms_attach_path')."images/".$Small_img);
                if (C('qscms_qiniu_open')==1) {
                    $qiniu = new \Common\ORG\qiniu;
                    $qiniu->delete($Small_img);
                }
            } else {
                $this->error('缩略图删除失败，请重新操作！');
            }
        }
        $this->success('缩略图删除成功！');
    }
    /**
     * [property 资讯属性列表]
     */
    public function property()
    {
        $this->_name='ArticleProperty';
        $this->order = 'category_order desc,id';
        $this->index();
    }
    /**
     * [add_property 添加资讯属性]
     */
    public function add_property()
    {
        $this->_name = 'ArticleProperty';
        $this->add();
    }
    /**
     * [add_property 修改资讯属性]
     */
    public function edit_property()
    {
        $this->_name = 'ArticleProperty';
        $this->edit();
    }
    /**
     * [del_property 删除资讯属性]
     */
    public function del_property()
    {
        $this->_name = 'ArticleProperty';
        $this->_map['admin_set'] = array('neq',1);
        $this->delete();
    }
}

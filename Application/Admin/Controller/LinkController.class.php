<?php
namespace Admin\Controller;

use Common\Controller\BackendController;
use Common\ORG\qiniu;

class LinkController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('Link');
    }
    public function index()
    {
        $this->_name = 'Link';
        $db_pre = C('DB_PREFIX');
        $tablename = $db_pre.'link';
        $key_type = I('request.key_type', 0, 'intval');
        $key = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where[$tablename.'.link_name'] = array('like','%'.$key.'%');
                    break;
                case 2:
                    $where[$tablename.'.link_url'] = array('like','%'.$key.'%');
                    break;
            }
        }
        $this->order = 'show_order desc,link_id';
        $this->where = $where;
        $this->join = $db_pre.'link_category AS c ON '.$tablename.'.alias=c.c_alias';
        $category = D('LinkCategory')->select();
        $this->assign('category', $category);
        parent::index();
    }
    public function _before_add()
    {
        if (IS_POST) {
            if ($_FILES['logo']['name']) {
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if (C('qscms_qiniu_open')==1) {
                    $qiniu = new qiniu(array(
                        'maxSize'=>100,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES, 'logo');
                    if ($img_url) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    } else {
                        $config_params['info'] = $qiniu->getError();
                    }
                } else {
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['logo'], 'link_logo/' . $date, array(
                            'maxSize' => 100,//图片最大100K
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
                    $_POST['link_logo'] = $config_params['url'];
                } else {
                    $this->ajaxReturn(0, $config_params['info']);
                }
            }
        } else {
            $category = D('LinkCategory')->select();
            $this->assign('category', $category);
        }
    }

    public function _before_edit()
    {
        if (IS_POST) {
            if ($_FILES['logo']['name']) {
                $config_params = array(
                    'upload_ok'=>false,
                    'url'=>'',
                    'info'=>''
                );
                //如果开启七牛云，执行七牛云接口，否则执行系统内置程序
                if (C('qscms_qiniu_open')==1) {
                    $qiniu = new qiniu(array(
                        'maxSize'=>100,
                        'exts'=>'bmp,png,gif,jpeg,jpg'
                    ));
                    $img_url = $qiniu->upload($_FILES, 'logo');
                    if ($img_url) {
                        $config_params['upload_ok'] = true;
                        $config_params['url'] = $img_url;
                        $config_params['info'] = '';
                    } else {
                        $config_params['info'] = $qiniu->getError();
                    }
                } else {
                    $date = date('y/m/d/');
                    $result = $this->_upload($_FILES['logo'], 'link_logo/' . $date, array(
                            'maxSize' => 100,//图片最大100K
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
                    $_POST['link_logo'] = $config_params['url'];
                } else {
                    $this->ajaxReturn(0, $config_params['info']);
                }
            }
        } else {
            $category = D('LinkCategory')->select();
            $this->assign('category', $category);
        }
    }
    public function _before_del($data)
    {
        if (C('qscms_qiniu_open')==1) {
            $qiniu = new qiniu;
        }
        foreach ($data as $key => $value) {
            @unlink(C('qscms_attach_path')."link_logo/".$value['link_logo']);
            if (C('qscms_qiniu_open')==1) {
                $qiniu->delete($value['link_logo']);
            }
        }
    }

    public function category()
    {
        $this->_name = 'LinkCategory';
        parent::index();
    }
    
    public function category_add()
    {
        $this->_name = 'LinkCategory';
        parent::add();
    }
    
    public function category_edit()
    {
        $this->_name = 'LinkCategory';
        parent::edit();
    }
    
    public function category_del()
    {
        $this->_name = 'LinkCategory';
        $this->_map['c_sys'] = array('neq',1);
        parent::delete();
    }
}

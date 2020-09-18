<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class SetmealController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function edit()
    {
        if (IS_POST) {
            if ($_FILES['setmeal_img']['name']) {
                $result = $this->_upload($_FILES['setmeal_img'], 'setmeal_img/', array(
                    'maxSize' => 128,//图片最大128k
                    'uploadReplace' => true,
                    'attach_exts' => 'png'
                ), $_POST['id']);
                if ($result['error']==0) {
                    $this->error($result['info']);
                }
            }
        }
        parent::edit();
    }
    public function _after_insert($id, $data)
    {
        $folder = QSCMS_DATA_PATH.'upload/setmeal_img/';
        if ($_FILES['setmeal_img']['name']) {
            $result = $this->_upload($_FILES['setmeal_img'], 'setmeal_img/', array(
                'maxSize' => 128,//图片最大128k
                'uploadReplace' => true,
                'attach_exts' => 'png'
            ), $id);
            if ($result['error']==0) {
                $this->error($result['info']);
            }
        }
        if (!file_exists($folder.$id.'.png')) {
            copy($folder.'2.png', $folder.$id.'.png');
        }
    }
}

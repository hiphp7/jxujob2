<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class OtherController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function zbuilderupload()
    {
        $maxSize                   = 2097152 * 10;
        $rootPath                  = C('qscms_attach_path') . 'images' . '/';
        $upload                    = new \Common\ORG\UploadFile(); // 实例化上传类
        $upload->maxSize           = $maxSize; // 设置附件上传大小
        $upload->uploadReplace     = true; //存在同名文件是否是覆盖
        $upload->allowExts         = array('png', 'gif', 'bmp', 'jpg', 'jpeg'); // 设置附件上传类型
        $upload->rootPath          = $rootPath; // 设置附件上传根目录
        $upload->savePath          = $rootPath; // 设置附件上传（子）目录
        $upload->thumbPrefix       = ''; //缩略图的文件前缀，默认为thumb_
        $upload->thumbSuffix       = '_thumb'; //缩略图的文件后缀，默认为空
        $upload->thumbExt          = ''; //指定缩略图的扩展名
        $upload->thumbRemoveOrigin = false; //生成缩略图后是否删除原图

        // 上传文件
        $upload->savePath .= date('y/m/d/');
        $info = $upload->uploadOne($_FILES['file']);

        if ($info) {
            $data = [
                'class' => 'success',
                'code'  => 1,
                'id'    => null,
                'info'  => '上传成功',
                'path'  => '/' . $info[0]['savepath'] . $info[0]['savename'],
            ];
            echo json_encode($data);
            die;
        }
    }
}

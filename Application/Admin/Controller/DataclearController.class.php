<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class DataclearController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        if (IS_POST) {
            $num = 0;
            $type = I('post.type');
            $settr = I('post.settr', 360, 'intval');
            $cuttime = strtotime('-'.$settr.' day');
            foreach ($type as $key => $value) {
                $arr = explode("|", $value);
                $model = $arr[0];
                $time_param = $arr[1];
                $map[$time_param] = array('lt',$cuttime);
                $r = D($model)->where($map)->delete();
                $r && $num += $r;
                unset($map);
            }
            if ($num>0) {
                $this->success('删除数据成功，共删除'.$num.'条数据！');
                exit;
            } else {
                $this->error('没有可删除的数据！');
            }
        }
        $this->display();
    }
}

<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class JobfairController extends MobileController
{
    // 初始化函数
    public function _initialize()
    {
        parent::_initialize();
        if (I('get.code', '', 'trim')) {
            $reg = $this->get_weixin_openid(I('get.code', '', 'trim'));
            $reg && $this->redirect('members/apilogin_binding');
        }
    }

    /**
     * 招聘会首页
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 招聘会详情
     */
    public function show()
    {
		$faire_id = I('id');
		if(!$faire_id){
			$this->error('参数非法');
		}
        if (C('visitor.utype') == 1) {
			//人才推荐
			//查询企业所发布的职位职位类型
			$job_cate = M('jobs')->where(array('uid'=>$this->visitor->info['uid']))->field('topclass,category,subclass')->select();
			$cate = [];
			if($job_cate){
				foreach($job_cate as $k=>$v){
					$cate[$k] = implode('.',$v);
				}
			}
			$this->assign('cate',implode(',',$cate));
            $this->assign('show_booth', 1);
        } else {
			//查询用户最新简历中的理想职位类型
			$cate = M('resume')->where(array('uid'=>$this->visitor->info['uid'],'display'=>1))->order('addtime desc')->getField('intention_jobs_id');
            $this->assign('job_cate', $cate);
			$this->assign('show_booth', 0);
        }

        $signin = M('jobfairsignin')->where(['uid' => $this->visitor->info['uid'],'utype'=>C('visitor.utype'), 'jobfair_id' => I('id')])->find();
		$info = [];
		if($signin){
			$info = M('jobfair')->where(['id' => I('id')])->find();
		}
		//当前招聘会签到企业或求职者
		$hold_time_info = M('jobfair')->where(array('id'=>$faire_id))->field('holddate_start,holddate_end')->find();
		$type = C('visitor.utype')==1?2:1;
		$wh = array('jobfair_id'=>$faire_id,'utype'=>$type);
		if($hold_time_info){
		    isset($hold_time_info['holddate_start']) && $wh['createtime'][]=array('egt',$hold_time_info['holddate_start']);
		    isset($hold_time_info['holddate_end']) && $wh['createtime'][]=array('elt',$hold_time_info['holddate_end']);
		}
		$ids = M('jobfairsignin')->where($wh)->order('createtime desc')->getField('uid',true);
        $this->assign([
            'signin' => $signin,
			'ids'=>$ids,
			'info'=>$info
        ]);
        $this->wx_share();
        $this->display();
    }

    /**
     * 参会企业
     */
    public function comlist()
    {
        $this->wx_share();
        $this->display();
    }
    /**
     * 展位预定
     * $booth_status  0不可预定 1可预订 2预订成功 3审核中
     */
    public function reserve()
    {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->_404('参数错误！');
        }
        if (!C('visitor')) {
            IS_AJAX && $this->ajaxReturn(0, L('login_please'), '', 1);
            //非ajax的跳转页面
            $this->redirect('members/login');
        }
        $area = D('Jobfair/JobfairArea')->where(array('jobfair_id' => $id))->order('area asc')->select();
        foreach ($area as $key => $value) {
            $position[$value['id']] = D('Jobfair/JobfairPosition')->where(array('jobfair_id' => $id, 'area_id' => $value['id'], 'status' => 0))->order('orderid asc')->select();
        }
        foreach ($position as $key => $value) {
            if (empty($value)) {
                unset($position[$key]);
            }
        }
        $booth_status = 0;
        if (C('visitor.utype') == 1) {
            $booth_info = D('Jobfair/JobfairExhibitors')->where(array('jobfair_id' => $id, 'uid' => C('visitor.uid')))->find();
            if ($booth_info) {
                if ($booth_info['audit'] == 1) {
                    $booth_status = 2;
                } else {
                    $booth_status = 3;
                }
            } else {
                $booth_status = 1;
            }
        }
        $img          = M('JobfairPositionImg')->where(array('jobfair_id' => $id))->select();
        $position_img = array();
        foreach ($img as $key => $value) {
            $arr['src']     = attach($value['img'], 'jobfair');
            $arr['w']       = 750;
            $arr['h']       = 400;
            $position_img[] = $arr;
        }
        $has_img = !empty($position_img) ? 1 : 0;
        $this->assign('has_img', $has_img);
        $this->assign('position_img', json_encode($position_img));
        $this->assign('booth_info', $booth_info);
        $this->assign('booth_status', $booth_status);
        $this->assign('area', $area);
        $this->assign('position', $position);
        $this->wx_share();
        $this->display();
    }
    /**
     * 展位预定保存
     */
    public function reserve_save()
    {
        $jobfair_id  = I('post.jobfair_id', 0, 'intval');
        $position_id = I('post.position_id', 0, 'intval');
        if (!$jobfair_id) {
            $this->ajaxReturn(0, '参数错误！');
        }
        if (!$position_id) {
            $this->ajaxReturn(0, '请选择展位！');
        }

        $r = D('Jobfair/Jobfair')->jobfair_booth(C('visitor'), $jobfair_id, $position_id);
        $this->ajaxReturn($r['state'], $r['msg']);
    }

    /**
     * 招聘会签到
     * @return [type] [description]
     */
    public function signin()
    {
        $jobfairid = I('jobfairid');
        $utype     = I('utype');

        $jobfairinfo = M('jobfair')->where(['id' => $jobfairid])->find();

        // 验证账号
        if ($utype != $this->visitor->info['utype']) {
            if ($utype == 1) {
                $this->ajaxReturn(0, '请登录/注册企业账号签到');
            }
            if ($utype == 2) {
                $this->ajaxReturn(0, '请登录/注册求职者账号签到');
            }
        }
        // 验证时间
        if ($jobfairinfo['holddate_start'] > time() || $jobfairinfo['holddate_end'] < time()) {
            $this->ajaxReturn(0, '签到错误，不在签到时间范围');
        }

        // 是否已经签到
        $isset = M('jobfairsignin')->where(['uid' => $this->visitor->info['uid'], 'jobfair_id' => $jobfairid, 'utype' => $utype])->find();

        if ($isset) {
            $this->ajaxReturn(0, '您已经签到过了');
        }

        $insertdata = [
            'uid'        => $this->visitor->info['uid'],
            'jobfair_id' => $jobfairid,
            'utype'      => $utype,
            'createtime' => time(),
        ];
        $result = M('jobfairsignin')->add($insertdata);

        if ($result) {
            $this->ajaxReturn(1, '签到成功', ['url' => U('mobile/jobfair/show', ['id' => $jobfairid])]);
        }
    }
}

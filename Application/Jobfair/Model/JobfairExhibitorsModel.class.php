<?php
namespace Jobfair\Model;
use Think\Model;
class JobfairExhibitorsModel extends Model{
	protected $_validate = array(
		array('uid,company_id,company_addtime,eaddtime,jobfair_id,jobfair_addtime','identicalNull','',0,'callback'),
		array('uid,company_id,company_addtime,eaddtime,jobfair_id,jobfair_addtime','identicalEnum','',0,'callback'),
		array('companyname,jobfair_title','identicalLength_200','',0,'callback'),
	);
	protected $_auto = array ( 
		array('audit',0),//预定状态
		array('etypr',1),//预定方式
	);
	/**
	 * 验证猎头职位表字段合法性
	 * 验证指定字段长度
	 * @param array $data 被验证字段
	 * 
	 * @return Boolean/string 验证结果(true:合法,false:不合法,字符串：不合法提示语)
	 */
	protected function identicalLength_200($data){
		foreach($data as $key=>$val){
			if(mb_strlen($val)>=200) return 'jobfair_exhibitors_length_error_'.$key;
		}
		return true;
	}
	/*
		添加参会企业
	*/
	public function add_jobfair_exhibitors($data)
	{
		if(false === $this->create($data))
		{
			return array('state'=>0,'error'=>$this->getError());
		}
		else
		{
			if($data['id'])
			{
				if(false === $num = $this->save())
				{
					return array('state'=>0,'error'=>'更新数据失败！');
				}
			}
			else
			{
				if(false === $insert_id = $this->add())
				{
					return array('state'=>0,'error'=>'数据添加失败！');
				}
			}
			return array('state'=>1,'id'=>$insert_id,'num'=>$num);
		}
	}
	/*
		获取会员预定的展会记录
		@$user 用户信息
	*/
	public function get_jobfair_exhibitors($user,$pagesize=10)
	{
		$where['uid']=$user['uid'];
		$db_pre = C('DB_PREFIX');
		$this_t = $db_pre.'jobfair_exhibitors';
		$join = 'left join '.$db_pre .'jobfair j on j.id='.$this_t.'.jobfair_id';
		if($count = $this->where($where)->join($join)->count()){
			$pager =  pager($count,$pagesize);
			$rst['count'] = $count;
			$rst['list'] = $this->where($where)->join($join)->field($this_t.'.*,j.title,j.address,j.holddate_start,j.holddate_end,j.addtime')->order('id desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
			$rst['page'] = $pager->fshow();
		}
		return $rst;
	}
	/*
		获取指定招聘会的参会企业
		@$jobfair_id 招聘会id
	*/
	public function get_jobfair_exhibitors_list($jobfair_id)
	{
		$where['jobfair_id']=$jobfair_id;
		$where['audit']=1;
		$result = $this->where($where)->select();
		return $result;
	}
}
?>
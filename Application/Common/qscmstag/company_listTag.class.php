<?php
/**
 * 企业列表
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class company_listTag {
    protected $params = array();
    protected $map = array();
    protected $order;
    protected $limit;
    protected $string;
    function __construct($options) {
        $array = array(
            '列表名'            =>  'listname',
            '显示数目'          =>  'row',
            '开始位置'          =>  'start',
            '企业名长度'        =>  'companynamelen',
            '描述长度'          =>  'brieflylen',
            '填补字符'          =>  'dot',
            '行业'              =>  'trade',
            '地区分类'          =>  'citycategory',
            '地区大类'          =>  'district',
            '地区中类'          =>  'sdistrict',
            '地区小类'          =>  'tdistrict',
            '企业性质'          =>  'nature',
            '企业规模'          =>  'scale',
            '关键字'            =>  'key',
            '排序'              =>  'displayorder',
            '分页显示'          =>  'paged',
            '公司页面'          =>  'companyshow',
            '去除id'            =>  'except_id',
			'签到'				=>  'sign',
			'招聘会id'			=>  'fair_id',
            '列表页'            =>  'listpage'
        );
        foreach ($options as $key => $value) {
            $this->params[$array[$key]] = $value;
        }
        $this->params['listname']=isset($this->params['listname'])?$this->params['listname']:"list";
        $this->limit = isset($this->params['row'])?intval($this->params['row']):10;
        $this->limit>20 && $this->limit=20;
        $this->params['start']=isset($this->params['start'])?intval($this->params['start']):0;
        $this->params['companynamelen']=isset($this->params['companynamelen'])?intval($this->params['companynamelen']):15;
        $this->params['dot']=isset($this->params['dot'])?$this->params['dot']:'';
        $this->params['companyshow']=isset($this->params['companyshow'])?$this->params['companyshow']:'QS_companyshow';
        $this->params['listpage']=isset($this->params['listpage'])?$this->params['listpage']:'QS_companylist';
        $displayorder = isset($this->params['displayorder'])?explode(':',$this->params['displayorder']):array('refreshtime','desc');
        $this->order = $displayorder[0].' '.$displayorder[1].',id desc';
        $this->_where_trade();
        $this->params['except_id'] && $this->map['id'] = array('neq',intval($this->params['except_id']));
        if(isset($this->params['nature']) && intval($this->params['nature'])>0){
            $this->map['nature'] = array('eq',intval($this->params['nature']));
        }
        if(isset($this->params['scale']) && intval($this->params['scale'])>0){
            $this->map['scale'] = array('eq',intval($this->params['scale']));
        }
        if(isset($this->params['key']) && trim($this->params['key'])<>''){
            $this->map['companyname'] = array('like','%'.trim($this->params['key']).'%');
        }
		if($this->params['sign'] && $this->params['fair_id']){
		    $faire_id = $this->params['fair_id'];
		    $hold_time_info = M('jobfair')->where(array('id'=>$faire_id))->field('holddate_start,holddate_end')->find();
		    $wh = array('jobfair_id'=>$faire_id,'utype'=>1);
		    if($hold_time_info){
		        isset($hold_time_info['holddate_start']) && $wh['createtime'][]=array('egt',$hold_time_info['holddate_start']);
		        isset($hold_time_info['holddate_end']) && $wh['createtime'][]=array('elt',$hold_time_info['holddate_end']);
		    }
		    $ids = M('jobfairsignin')->where($wh)->order('createtime desc')->getField('uid',true);
		    $this->map['uid'] = array('in',$ids);
		}
        $this->string = $this->_where_citycategory();
        if($this->string){
            $this->map['_string'] = $this->string;
        }
        if(C('SUBSITE_VAL.s_id') > 0) $this->map['subsite_id'] = C('SUBSITE_VAL.s_id');
    }
    public function run(){
        if($this->params['paged']){
            $count = M('CompanyProfile')->where($this->map);
            $total = $count->count();
            $pager = pager($total, $this->limit);
            $pager->showname = $this->params['listpage'];
            $page = $pager->fshow();
            $this->params['start']>0 && $pager->firstRow = $this->params['start'];
            $this->limit = $pager->firstRow.','.$pager->listRows;
        }else{
            $this->limit = $this->params['start'].','.$this->limit;
            $total = 0;
            $page = '';
        }
		
        
        $result = M('CompanyProfile')->where($this->map)->order($this->order)->limit($this->limit)->select();
        $list = array();
        foreach ($result as $key => $value) {
            $row = $value;
            $row['companyname_']=$row['companyname'];
            $row['companyname']=cut_str($row['companyname'],$this->params['companynamelen'],0,$this->params['dot']);
            $row['url'] = url_rewrite($this->params['companyshow'],array('id'=>$row['id']));
            $row['contents']=str_replace('&nbsp;','',$row['contents']);
            $row['briefly_']=strip_tags($row['contents']);
            $row['briefly']=strip_tags($row['briefly_']);
            if ($this->params['brieflylen']>0)
            {
            $row['briefly']=cut_str(strip_tags($row['contents']),$this->params['brieflylen'],0,$this->params['dot']);
            }
            if ($row['logo'])
            {
                $row['logo']=attach($row['logo'],'company_logo');
            }
            else
            {
                $row['logo']=attach('no_logo.png','resource');
            }
            $count_map['uid'] = $row['uid'];
            if(C('qscms_jobs_display')==1){
                $count_map['audit'] = 1;
            }
            $row['jobs_count'] = D('Jobs')->where($count_map)->count();
            $list[] = $row;
        }
        $return['page'] = $page;
        $return['total'] = $total;
        $return['list'] = $list;
        return $return;
    }

    protected function _where_trade(){
        $data = $this->params['trade'];
        if($data){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $sqlin = implode(',',array_slice($arr,0,10));
                if (fieldRegex($sqlin,'in')){
                    $this->map['trade'] = array('in',$sqlin);
                }
            }else{
                $this->map['trade'] = array('eq',intval($data));
            }
        }
    }
    protected function _where_citycategory(){
        $data = $this->params['citycategory'];
        if($data){
            $arr = explode(',',$data);
            foreach(array_slice($arr,0,10) as $v) {
                $a = explode('.',$v);
                $d = 'district='.intval($a[0]);
                if(intval($a[1])>0){
                    $d .= ' OR sdistrict';
                }
                if (intval($a[2])===0){
                    if(intval($a[1])==0){
                        $d.= ' OR district='.intval($a[0]);
                    }else{
                        $d.= ' OR sdistrict='.intval($a[1]).' OR (district='.intval($a[0]).' AND sdistrict=0)';
                    }
                }else{
                    $d.= " OR tdistrict=".intval($a[2]).' OR (district='.intval($a[0]).' AND sdistrict=0) OR (district='.intval($a[0]).' AND sdistrict='.intval($a[1]).' AND tdistrict=0)';
                }
            }
            return $d ? '('.ltrim($d,' OR').')' : false;
        }
    }
}
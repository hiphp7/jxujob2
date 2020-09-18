<?php
/**
 * 合并加载JS和CSS文件
 *
 * @author brivio
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class company_jobs_listTag {
    protected $mod;                                                             //索引表
    protected $params           = array();                                      //全部传值
    protected $where            = array();                                      //查询条件
    protected $order;                                                           //排序
    protected $join;
    protected $firstRow         = 0;                                            //分页开始位置
    protected $spage_size       = 10;                                           //单页条数
    protected $default_order    = 'refreshtime %s';                             //默认排序字段
    protected $order_array      = array(//排序枚举列表
        'rtime'                 =>    'refreshtime %s',
        'stickrtime'            =>    'stime %s,refreshtime %s',
        'hot'                   =>    'click %s',
        'scale'                 =>    'scale %s,refreshtime %s',
        'wage'                  =>    'minwage %s,refreshtime %s',
        'null'                  =>    ''
    );
    protected $enum             =   array(
        '显示数目'              =>  'spage_size',
        '开始位置'              =>  'firstRow',
        '企业名长度'            =>  'companynamelen',
        '填补字符'              =>  'dot',
        '地区大类'              =>  'district',
        '地区小类'              =>  'sdistrict',
        '紧急招聘'              =>  'emergency',
        '日期范围'              =>  'settr',
        '推荐'                  =>  'recommend',
        '名企'                  =>  'setmeal',
        '职位名长度'            =>  'jobslen',
        '显示职位'              =>  'jobsrow',
        '职位页面'              =>  'jobsshow',
        '职位分类'              =>  'jobcategory',
        '行业'                  =>  'trade',
        '排序'                  =>  'displayorder',
        '分页显示'              =>  'page',
        '公司页面'              =>  'companyshow',
        '列表页'                =>  'listpage',
        '统计职位'              =>  'countjobs',
        '职位数量'              =>  'jobs_num'
    );
    public function __construct($options) {
        foreach ($options as $key => $val) {
            $this->params[$this->enum[$key]] = $val;
        }
        $map = array();
        if(C('SUBSITE_VAL.s_id') > 0 && !$this->params['citycategory']){
            $this->params['citycategory'] = C('SUBSITE_VAL.s_district');
        }
        foreach(array(1=>'citycategory',2=>'jobcategory') as $v) {
            $name = '_where_'.$v;
            if(false !== $w = $this->$name(trim($this->params[$v])))  is_string($w) ? $map[] = $w : $map = array_merge($map,$w);
        }
        if($map) $this->where['_string'] = implode(' AND ',$map);
        if($settr = intval($this->params['settr'])) $this->where['refreshtime'] = array('gt',strtotime("-".$settr."day"));
        foreach (array('recommend','emergency','trade') as $val) {
            if($d =  intval($this->params[$val])) $this->where[$val] = $d;
        }
        if(C('qscms_jobs_display')==1){
            $this->where['audit'] = 1;
        }
        $this->params['setmeal'] && $this->where['setmeal_id'] = array('gt',1);
        $sort = trim($this->params['displayorder']);
        $sort = explode('.',$sort);
        if(!$sort[0]) $sort[0] = 'rtime';
        if(!$order = $this->order_array[$sort[0]]) $order = $this->default_order;
        if($sort[1]=='desc'){
            $sort[1]="desc";
        }elseif($sort[1]=="asc"){
            $sort[1]="asc";
        }else{
            $sort[1]="desc";
        }
        $this->order = str_replace('%s',$sort[1],$order);
        isset($this->params['firstRow']) && $this->firstRow = intval($this->params['firstRow']);
        isset($this->params['spage_size']) && $this->spage_size = intval($this->params['spage_size']);
        $this->params['dot'] = isset($this->params['dot']) ? trim($this->params['dot']) : '...';
        $this->params['companyshow'] = isset($this->params['companyshow']) ? $this->params['companyshow'] : "QS_companyshow";
        $this->params['jobsshow'] = isset($this->params['jobsshow']) ? $this->params['jobsshow'] : "QS_jobsshow";
        $this->params['listpage'] = isset($this->params['listpage']) ? $this->params['listpage'] : "QS_companyjobs";
    }
    public function run(){
        $model = M('Jobs');
        if($this->params['page']){
            if($result['total'] = $model->where($this->where)->order($this->order)->count('distinct company_id')){
                $pager = pager($result['total'],$this->spage_size);
                $company_info = $model->where($this->where)->order($this->order)->limit($pager->firstRow . ',' .$this->spage_size)->group('company_id')->getfield('distinct company_id,count(id) as jobs_num');
                $pager->path = $this->params['listpage'];
                $pager->showname = $this->params['listpage'];
                $result['page'] = $pager->fshow();
                $result['page_params'] = $pager->get_page_params();
            }else{
                $result['page'] = '';
            }
        }else{
            $company_info = $model->where($this->where)->order($this->order)->limit($this->firstRow . ',' .$this->spage_size)->group('company_id')->getfield('distinct company_id,count(id) as jobs_num');
            $result['page'] = '';
            $result['total'] = 0;
        }
        if($company_info){
            $cids = array_keys($company_info);
            $field_famous = C('apply.Sincerity')?',famous':'';
            $company = M('CompanyProfile')->where(array('id'=>array('in',$cids)))->order('refreshtime desc')->getfield('id,uid,audit,companyname,nature_cn,district_cn,addtime,refreshtime,logo'.$field_famous);
            $cids = array();
            foreach ($company as $key => $val) {
                $company[$key]['jobs_num'] = $company_info[$key];
                $this->params['companynamelen'] && $company[$key]['companyname'] = cut_str($val['companyname'],$this->params['companynamelen'],0,$this->params['dot']);
                $company[$key]['company_url']=url_rewrite($this->params['companyshow'],array('id'=>$val['id']));
                $company[$key]['company_jobs_url']=url_rewrite('QS_companyjobs',array('id'=>$val['id']));
                $company[$key]['refreshtime_cn']=daterange(time(),$val['refreshtime'],'m-d',"#FF3300");
                $cids[] = $val['id'];
            }
            if($cids){
                $jobs_map['company_id'] = array('in',$cids);
                if(C('qscms_jobs_display')==1){
                    $jobs_map['audit'] = 1;
                }
                $jobs = $model->where($jobs_map)->select();
                if(C('apply.Subsite')){
                    if(false === $subsite_district = F('subsite_district')) $subsite_district = D('Subsite')->subsite_district_cache();
                }
                foreach ($jobs as $key => $val) {
                    if($this->params['jobs_num'] && count($company[$val['company_id']]['jobs']) >= $this->params['jobs_num']) continue;
                    $val['jobs_name'] = $this->params['jobslen']?cut_str($val['jobs_name'],$this->params['jobslen'],0,$this->params['dot']):$val['jobs_name'];
                    C('apply.Subsite') && $subsite_id = $subsite_district[$val['tdistrict']]?:($subsite_district[$val['sdistrict']]?:$subsite_district[$val['district']]);
                    $val['jobs_url'] = url_rewrite($this->params['jobsshow'],array('id'=>$val['id']),$subsite_id);
                    if (!empty($val['highlight'])){
                        $val['jobs_name']="<span style=\"color:{$val['highlight']}\">{$val['jobs_name']}</span>";
                    }
                    if($val['negotiable']==0){
                        if(C('qscms_wage_unit') == 1){
                            $val['minwage'] = $val['minwage']%1000==0?(($val['minwage']/1000).'K'):(round($val['minwage']/1000,1).'K');
                            $val['maxwage'] = $val['maxwage']?($val['maxwage']%1000==0?(($val['maxwage']/1000).'K'):(round($val['maxwage']/1000,1).'K')):0;
                        }elseif(C('qscms_wage_unit') == 2){
                            if($val['minwage']>=10000){
                                if($val['minwage']%10000==0){
                                   $val['minwage'] = ($val['minwage']/10000).'万';
                                }else{
                                    $val['minwage'] = round($val['minwage']/10000,1);
                                    $val['minwage'] = strpos($val['minwage'],'.') ? str_replace('.','万',$val['minwage']) : $val['minwage'].'万';
                                }
                            }else{
                                if($val['minwage']%1000==0){
                                    $val['minwage'] = ($val['minwage']/1000).'千';
                                }else{
                                    $val['minwage'] = round($val['minwage']/1000,1);
                                    $val['minwage'] = strpos($val['minwage'],'.') ? str_replace('.','千',$val['minwage']) : $val['minwage'].'千';
                                }
                            }
                            if($val['maxwage']>=10000){
                                if($val['maxwage']%10000==0){
                                   $val['maxwage'] = ($val['maxwage']/10000).'万';
                                }else{
                                    $val['maxwage'] = round($val['maxwage']/10000,1);
                                    $val['maxwage'] = strpos($val['maxwage'],'.') ? str_replace('.','万',$val['maxwage']) : $val['maxwage'].'万';
                                }
                            }elseif($val['maxwage']){
                                if($val['maxwage']%1000==0){
                                   $val['maxwage'] = ($val['maxwage']/1000).'千';
                                }else{
                                    $val['maxwage'] = round($val['maxwage']/1000,1);
                                    $val['maxwage'] = strpos($val['maxwage'],'.') ? str_replace('.','千',$val['maxwage']) : $val['maxwage'].'千';
                                }
                            }else{
                                $val['maxwage'] = 0;
                            }
                        }
                        if($val['maxwage']==0){
                            $val['wage_cn'] = '面议';
                        }else{
                            if($val['minwage']==$val['maxwage']){
                                $val['wage_cn'] = $val['minwage'].'/月';
                            }else{
                                $val['wage_cn'] = $val['minwage'].'-'.$val['maxwage'].'/月';
                            }
                        }
                    }else{
                        $val['wage_cn'] = '面议';
                    }
                    $company[$val['company_id']]['setmeal_id'] = $val['setmeal_id'];
                    $company[$val['company_id']]['jobs'][] = $val;
                }
            }
        }
        $result['list'] = $company;
        return $result;
    }
    protected function _where_citycategory($data){
        if($data){
            if (strpos($data,".")){
                $arr = explode(',',$data);
                foreach(array_slice($arr,0,10) as $v) {
                    if(C('SUBSITE_VAL.s_id') > 0){
                        $reg = '/^\.'.str_replace(',','|',C('SUBSITE_VAL.s_district')).'\./';
                        if(preg_match($reg,'.'.$v.'.') !== 1) continue;
                    }
                    $a = explode('.',$v);
                    if (intval($a[1])===0){
                        $d.= " OR district=".intval($a[0]);
                    }else{
                        $d.= " OR sdistrict=".intval($a[1]);
                    }
                }
                return $d ? '('.ltrim($d,' OR').')' : false;
            }else{
                if(APP_SPELL && !fieldRegex($data,'in')){
                    if(false === $result = F('city_cate_list')) $result = D('CategoryDistrict')->city_cate_cache();
                    $arr=explode(",",$data);
                    foreach ($arr as $key => $val) {
                        $arr[$key] = $result['spell'][$val]['id'];
                    }
                }else{
                    if(fieldRegex($data,'in')){
                        $arr=explode(",",$data);
                    }
                }
                if($arr){
                    if(false === $city_cate = F('city_search_cate')) $city_cate = D('CategoryDistrict')->city_search_cache();
                    foreach ($arr as $key => $val) {
                        if(C('SUBSITE_VAL.s_id') > 0){
                            $reg = '/^\_'.str_replace(',','|',C('SUBSITE_VAL.s_district')).'\_/';
                            if(preg_match($reg,'_'.$val.'_') !== 1) continue;
                        }
                        $t = explode('_',$city_cate[$val]);
                        if($t[2]){
                            $tdistrict[] = $t[2];
                        }elseif($t[1]){
                            $sdistrict[] = $t[1];
                        }else{
                            $district[] = $t[0];
                        }
                    }
                    if($district){
                        $district = array_unique($district);
                        $district = array_slice($district,0,20);
                        $sqlin=implode(",",$district);
                        $d[] = "district IN ({$sqlin})";
                    }
                    if($sdistrict){
                        $sdistrict = array_unique($sdistrict);
                        $sdistrict = array_slice($sdistrict,0,20);
                        $sqlin=implode(",",$sdistrict);
                        $d[] = "sdistrict IN ({$sqlin})";
                    }
                    if($tdistrict){
                        $tdistrict = array_unique($tdistrict);
                        $tdistrict = array_slice($tdistrict,0,20);
                        $sqlin=implode(",",$tdistrict);
                        $d[] = "tdistrict IN ({$sqlin})";
                    }
                }
                return $d ? $d : false;
            }
        }else{
            if (!empty($this->params['district'])){
                if (strpos($this->params['district'],",")){
                    $or=$orsql="";
                    $arr=explode(",",$this->params['district']);
                    $arr=array_unique($arr);
                    $arr = array_slice($arr,0,20);
                    $sqlin=implode(",",$arr);
                    if (fieldRegex($sqlin,'in')){
                        $d[] = 'district IN ({$sqlin})';
                    }
                }else{
                    $d[] = 'district = '.intval($this->params['district']);
                }
            }
            if (!empty($this->params['sdistrict'])){
                if (strpos($this->params['sdistrict'],",")){
                    $or=$orsql="";
                    $arr=explode(",",$this->params['sdistrict']);
                    $arr=array_unique($arr);
                    $arr = array_slice($arr,0,20);
                    $sqlin=implode(",",$arr);
                    if (fieldRegex($sqlin,'in')){
                        $d[] = 'sdistrict IN ({$sqlin})';
                    }
                }else{
                    $d[] = 'sdistrict = '.intval($this->params['sdistrict']);
                }
            }
            if (!empty($this->params['tdistrict'])){
                if (strpos($this->params['tdistrict'],",")){
                    $or=$orsql="";
                    $arr=explode(",",$this->params['tdistrict']);
                    $arr=array_unique($arr);
                    $arr = array_slice($arr,0,20);
                    $sqlin=implode(",",$arr);
                    if (fieldRegex($sqlin,'in')){
                        $d[] = 'tdistrict IN ({$sqlin})';
                    }
                }else{
                    $d[] = 'tdistrict = '.intval($this->params['tdistrict']);
                }
            }
            return $d ? $d : false;
        }
    }
    protected function _where_jobcategory($data){
        if($data){
            if (strpos($data,".")){
                $arr = explode(',',$data);
                foreach(array_slice($arr,0,10) as $v) {
                    $a = explode('.',$v);
                    if (intval($a[2])===0){
                        $d.= " OR category =".intval($a[1]);
                    }else{
                        $d.= " OR subclass =".intval($a[2]);
                    }
                }
                return $d ? '('.ltrim($d,' OR').')' : false;
            }else{
                if(APP_SPELL && !fieldRegex($data,'in')){
                    if(false === $result = F('jobs_cate_list')) $result = D('CategoryJobs')->jobs_cate_cache();
                    $arr=explode(",",$data);
                    foreach ($arr as $key => $val) {
                        $arr[$key] = $result['spell'][$val]['id'];
                    }
                }else{
                    if(fieldRegex($data,'in')){
                        $arr=explode(",",$data);
                    }
                }
                if($arr){
                    if(false === $jobs_cate = F('jobs_search_cate')) $jobs_cate = D('CategoryJobs')->jobs_search_cache();
                    foreach ($arr as $key => $val) {
                        $t = explode('_',$jobs_cate[$val]);
                        if($t[2]){
                            $subclass[] = $t[2];
                        }else{
                            $category[] = $t[1];
                        }
                    }
                    if($category){
                        $category = array_unique($category);
                        $category = array_slice($category,0,20);
                        $sqlin=implode(",",$category);
                        $d[] = "category IN ({$sqlin})";
                    }
                    if($subclass){
                        $subclass = array_unique($subclass);
                        $subclass = array_slice($subclass,0,20);
                        $sqlin=implode(",",$subclass);
                        $d[] = "subclass IN ({$sqlin})";
                    }
                }
                return $d ? $d : false;
            }
        }else{
            if (!empty($this->params['category'])){
                if (strpos($this->params['category'],",")){
                    $or=$orsql="";
                    $arr=explode(",",$this->params['category']);
                    $arr=array_unique($arr);
                    $arr = array_slice($arr,0,20);
                    $sqlin=implode(",",$arr);
                    if (fieldRegex($sqlin,'in')){
                        $d[] = 'category IN ({$sqlin})';
                    }
                }else{
                    $d[] = 'category = '.intval($this->params['category']);
                }
            }
            if (!empty($this->params['subclass'])){
                if (strpos($this->params['subclass'],",")){
                    $or=$orsql="";
                    $arr=explode(",",$this->params['subclass']);
                    $arr=array_unique($arr);
                    $arr = array_slice($arr,0,20);
                    $sqlin=implode(",",$arr);
                    if (fieldRegex($sqlin,'in')){
                        $d[] = 'subclass IN ({$sqlin})';
                    }
                }else{
                    $d[] = 'subclass = '.intval($this->params['subclass']);
                }
            }
            return $d ? $d : false;
        }
    }
}
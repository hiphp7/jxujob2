<?php
/**
 * 合并加载JS和CSS文件
 *
 * @author brivio
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class jobs_listTag {
    protected $range_select     = 0;   
    protected $mod;                                                             //索引表
    protected $params           = array();                                      //全部传值
    protected $where            = array();                                      //查询条件
    protected $join;
    protected $order;                                                           //排序
    protected $firstRow         = 0;                                            //分页开始位置
    protected $spage_size       = 10;                                           //单页条数
    protected $default_order    = 'stime desc';      //默认排序字段
    protected $order_array      = array(//排序枚举列表
        'rtime'                 =>    'refreshtime %s',
        'stickrtime'            =>    'stime %s,refreshtime %s',
        'hot'                   =>    'click %s',
        'scale'                 =>    'scale %s,refreshtime %s',
        'wage'                  =>    'minwage %s,refreshtime %s',
        'score'                 =>    '`score` %s',
        'null'                  =>    ''
    );
    protected $enum             = array(
        '搜索类型'              =>  'search_type',
        '搜索内容'              =>  'search_cont',
        '保证金'                =>  'famous',
        '名企招聘'              =>  'setmeal',
        '显示数目'              =>  'spage_size',
        '开始位置'              =>  'firstRow',
        '职位名长度'            =>  'jobslen',
        '企业名长度'            =>  'companynamelen',
        '描述长度'              =>  'brieflylen',
        '填补字符'              =>  'dot',
        '应届生职位'            =>  'graduate',
        '职位分类'              =>  'jobcategory',
        '职位大类'              =>  'category',
        '职位小类'              =>  'subclass',
        '地区分类'              =>  'citycategory',
        '地区大类'              =>  'district',
        '地区中类'              =>  'sdistrict',
        '地区小类'              =>  'tdistrict',
        '道路'                  =>  'street',
        '写字楼'                =>  'officebuilding',
        '标签'                  =>  'tag',
        '行业'                  =>  'trade',
        '学历'                  =>  'education',
        '工作经验'              =>  'experience',
        '工资'                  =>  'wage',
        '最低工资'              =>  'minwage',
        '最高工资'              =>  'maxwage',
        '性别'                  =>  'sex',
        '职位性质'              =>  'nature',
        '公司规模'              =>  'scale',
        '紧急招聘'              =>  'emergency',
        '推荐'                  =>  'recommend',
        '营业执照'              =>  'license',
        '过滤已投递'            =>  'deliver',
        '关键字'                =>  'key',
        '关键字类型'            =>  'keytype',
        '日期范围'              =>  'settr',
        '排序'                  =>  'displayorder',
        '分页显示'              =>  'page',
        '会员uid'               =>  'uid',
        '公司页面'              =>  'companyshow',
        '职位页面'              =>  'jobsshow',
        '列表页'                =>  'listpage',
        '合并'                  =>  'mode',
        '公司列表名'            =>  'comlistname',
        '公司职位页面'          =>  'companyjobs',
        '单个公司显示职位数'    =>  'companyjobs_row',
        '浏览过的职位'          =>  'view_jobs',
        '风格模板'              =>  'tpl_compnay',
        '去除id'                =>  'except_id',
        '经度'                  =>  'lng',
        '纬度'                  =>  'lat',
        '半径'                  =>  'wa',
        '搜索范围'              =>  'range',
        '左下经度'              =>  'ldLng',
        '左下纬度'              =>  'ldLat',
        '右上经度'              =>  'ruLng',
        '右上纬度'              =>  'ruLat',
        '联系方式'              =>  'show_contact',
		'推荐用户id'				=>  'user_ids'
    );
    public function __construct($options) {
        foreach ($options as $key => $val) {
            $this->params[$this->enum[$key]] = $val;
        }
        $this->field = 'id';
        $map = array();
        if(!$this->params['uid'] && C('SUBSITE_VAL.s_id') > 0 && !$this->params['citycategory']){
            $this->params['citycategory'] = C('SUBSITE_VAL.s_district');
        }
        foreach(array(1=>'citycategory',2=>'jobcategory',3=>'trade',4=>'nature') as $k => $v) {//省市,职位,行业
            $name = '_where_'.$v;
            if(false !== $w = $this->$name(trim($this->params[$v])))  is_string($w) ? $map[] = $w : $map = array_merge($map,$w);
        }
        if($education = intval($this->params['education'])){
            $category = D('Category')->get_category_cache();
            $w = '';
            foreach ($category['QS_education'] as $key => $val) {
                if($key <= $education) $w[] = 'education='.$key;
            }
            if($w){
                $map[] = '('.implode(' OR ',$w).')';
            }
        }
        if($experience = intval($this->params['experience'])){
            $map[] = '('.implode(' OR ',array('experience='.$experience,'experience=0')).')';
        }
        if($sex = intval($this->params['sex'])){
            $map[] = '('.implode(' OR ',array('sex='.$sex,'sex=0')).')';
        }
        if($map) $this->where['_string'] = implode(' AND ',$map);
        if($tag = trim($this->params['tag'])){
            if(fieldRegex($tag,'in')){
                // 这里卡飞了，先注释一下 访问地址：http://www.jxujob.com/index.php?m=&c=Jobs&a=jobs_list&key=%E4%BC%9A%E8%AE%A1&sort=rtime&trade=21&jobtag=275&experience=77
                // $sql = M('JobsTag')->field('pid')->where(array('tag'=>array('in',$tag)))->Distinct(true)->buildSql();
                // $this->join = ' right join '.$sql.' as t ON t.pid=j.id';
            }
        }
        if(intval($this->params['range'])){
            $this->params['wa'] = intval($this->params['range'])*1000;
            $baidu_api_result = $this->_get_baidumap_api();
            $baidu_api_jsoninfo = json_decode($baidu_api_result, true);
            if($baidu_api_jsoninfo['status']==0){
                $this->range_select = 1;
                $this->params['lng'] = $baidu_api_jsoninfo['content']['point']['x'];
                $this->params['lat'] = $baidu_api_jsoninfo['content']['point']['y'];
                $this->field = "id,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$this->params['lat']."*PI()/180-map_y*PI()/180)/2),2)+COS(".$this->params['lat']."*PI()/180)*COS(map_y*PI()/180)*POW(SIN((".$this->params['lng']."*PI()/180-map_x*PI()/180)/2),2)))*1000) AS map_range";
            }
        }
        if($this->params['lng'] && $this->params['lat']){
            if($this->params['ldLng'] && $this->params['ldLng'] && $this->params['ldLng'] && $this->params['ldLng']){
                $this->where['map_x'] = array('between',array($this->params['ldLng'],$this->params['ruLng']));
                $this->where['map_y'] = array('between',array($this->params['ldLat'],$this->params['ruLat']));
            }else{
                !$this->params['wa'] && $this->params['wa'] = 1000;
                $squares = square_point($this->params['lng'],$this->params['lat'],$this->params['wa']/1000);
                $this->where['map_x'] = array('between',array($squares['lt']['lng'],$squares['rb']['lng']));
                $this->where['map_y'] = array('between',array($squares['rb']['lat'],$squares['lt']['lat']));
            }
        }
        if($this->params['wage']){
            if(false === $category = F('category_wage_list')) $category = D('Category')->category_wage();
            $wage = $category[$this->params['wage']];
            if(preg_match_all('(\d+)',$wage,$reg)){
                $reg = $reg[0];
                $reg[0] && $this->where['minwage'] = array('egt',intval($reg[0]));
                $reg[1] && $this->where['maxwage'] = array('elt',intval($reg[1]));
            }
        }else{
            $this->params['minwage'] && $this->where['minwage'] = array('egt',intval($this->params['minwage']));
            $this->params['maxwage'] && $this->where['maxwage'] = array('elt',intval($this->params['maxwage']));
        }
        if($this->params['license']){
            $this->where['license'] = 1;
        }
        if(C('qscms_jobs_display')==1){
            $this->where['audit'] = 1;
        }
        switch($this->params['search_cont']){
            case 'setmeal':
                $this->where['setmeal_id'] = array('gt',1);
                break;
            case 'famous':
                $this->where['famous'] = 1;
                break;
            case 'allowance':
                $this->where['allowance_id'] = array('gt',0);
                break;
        }
        $this->params['uid'] && $this->where['uid'] = array('eq',intval($this->params['uid']));
        $this->params['except_id'] && $this->where['id'] = array('neq',intval($this->params['except_id']));
		$this->params['user_ids'] && is_array($this->params['user_ids']) && $this->where['uid'] = array('in',$this->params['user_ids']);//推荐的签到企业职位
        $this->has_apply = array();
        $this->has_favor = array();
        if(C('visitor.utype') == 2){
            $jids = M('PersonalJobsApply')->where(array('personal_uid'=>C('visitor.uid')))->getfield('jobs_id',true);
            if($this->params['deliver'] && $jids){
                $this->where['id'] = array('not in',$jids);
            }else{
                $this->has_apply = $jids;
            }
            $this->has_favor = M('PersonalFavorites')->where(array('personal_uid'=>C('visitor.uid')))->getfield('jobs_id',true);
        }
        if($settr = intval($this->params['settr'])) $this->where['addtime'] = array('gt',strtotime("-".$settr."day"));
        $array = array('graduate','uid','emergency','recommend','scale','street','officebuilding');
        foreach ($array as $val) {
            if($d =  intval($this->params[$val])) $this->where[$val] = $d;
        }
        if($this->range_select==1){
            $this->order = 'map_range asc,refreshtime desc';
        }
        elseif($sort = trim($this->params['displayorder']))
        {
            $sort = explode('.',$sort);
            if(!$sort[0]) $sort[0] = C('qscms_fulltext_orderby') && $this->params['key'] ? 'score' : 'stickrtime';
            if($sort[0] == 'score' && (!C('qscms_fulltext_orderby') || !$this->params['key'])){
                $sort[0] = 'stickrtime';
                $_GET['sort'] = '';
            }
            if(!$order = $this->order_array[$sort[0]]) $order = $this->default_order;
            if($sort[1]=='desc'){
                $sort[1]="desc";
            }elseif($sort[1]=="asc"){
                $sort[1]="asc";
            }else{
                $sort[1]="desc";
            }
            $this->order = str_replace('%s',$sort[1],$order);
        }
        else
        {
            $this->order = $this->default_order;
        }
        
        $this->mod = 'jobs_search';

        if(!empty($this->params['key'])){
            $this->params['key'] = urldecode(urldecode($this->params['key']));
            $key = trim($this->params['key']);
            if(!$this->params['search_type']){
                if(C('qscms_jobsearch_key_first_choice') == 1){
                    $this->params['search_type'] = 'jobs';
                }elseif(C('qscms_jobsearch_type') != 0){
                    $this->params['search_type'] = 'full';
                }
            }
            switch($this->params['search_type']){
                case 'jobs':
                    $this->where['jobs_name'] = array('like','%'.$key.'%');
                    $this->mod = 'jobs_search';
                    break;
                case 'company':
                    $this->where['companyname'] = array('like','%'.$key.'%');
                    $this->mod = 'jobs_search';
                    break;
                case 'full':
                    $key = get_tags($key);
                    if(C('qscms_match_type') && $sort[0] == 'score'){
                        $this->where['key'] = array('match_with',$key);
                    }else{
                       $this->where['key'] = array('match_mode',$key);
                    }
                    if($sort[0] == 'score'){
                        $this->field = 'id,MATCH (`key`) AGAINST ("'.implode(' ',$key).'") as score';
                    }
                    $this->mod = 'jobs_search_key';
                    break;
                case 'jobs_commpany':
                    $this->where['jobs_name|companyname'] = array('like','%'.$key.'%');
                    $this->mod = 'jobs_search';
                    break;
                default:
                    $this->where['jobs_name'] = array('like','%'.$key.'%');
                    $this->mod = 'jobs_search';
                    break;
            }
            D('Hotword')->set_inc_batch($key);
            if(C('apply.Recommend')){
                $class = new \Recommend\Controller\IndexController();
                $class->set_jobs($this->params['key']);
            }
        }
        isset($this->params['firstRow']) && $this->firstRow = intval($this->params['firstRow']);
        if(isset($this->params['spage_size'])){
            $this->spage_size = $this->params['spage_size'] == '-1' ? 0 : intval($this->params['spage_size']);
        }
        $this->params['dot'] = isset($this->params['dot']) ? trim($this->params['dot']) : '...';
        $this->params['listpage'] = isset($this->params['listpage']) ? $this->params['listpage'] : "QS_jobslist";
        $this->params['jobsshow'] = isset($this->params['jobsshow']) ? $this->params['jobsshow'] : 'QS_jobsshow';
        $this->params['companyshow'] = isset($this->params['companyshow']) ? $this->params['companyshow'] : 'QS_companyshow';
        $this->params['companyjobs'] = isset($this->params['companyjobs']) ? $this->params['companyjobs'] : 'QS_companyjobs';
    }
    public function run(){
        $db_pre = C('DB_PREFIX');
        $model = new \Think\Model;
        if($this->params['page']){
            if($this->params['view_jobs']){
                $result['total'] = count(cookie('view_jobs_log'));
            }else{
                if($result['total'] = $model->Table($db_pre.$this->mod.' j')->where($this->where)->join($this->join)->count('id')){
                    if (C('qscms_jobs_list_max') > 0){
                        $result['total'] > intval(C('qscms_jobs_list_max')) && $result['total']=intval(C('qscms_jobs_list_max'));
                    }
                    $pager = pager($result['total'],$this->spage_size);
                    //$start = abs($pager->firstRow - 1) * $this->spage_size;
                    if($this->params['lat'] && $this->params['lng']){
                        $sql = $model->Table($db_pre.$this->mod)->where($this->where)->join($this->join)->select(false);
                        $field = "id,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$this->params['lat']."*PI()/180-map_y*PI()/180)/2),2)+COS(".$this->params['lat']."*PI()/180)*COS(map_y*PI()/180)*POW(SIN((".$this->params['lng']."*PI()/180-map_x*PI()/180)/2),2)))*1000) AS map_range";
                        $jobs_list = $model->query('SELECT '.$field.' FROM('.$sql.') as j ORDER BY map_range asc LIMIT '.$pager->firstRow . ',' .$this->spage_size);
                    }else{
                        $jobs_list = $model->Table($db_pre.$this->mod.' j')->field($this->field)->where($this->where)->join($this->join)->order($this->order)->limit($pager->firstRow . ',' .$this->spage_size)->select();
                    }
                    $pager->path = $this->params['listpage'];
                    $pager->showname = $this->params['listpage'];
                    $result['page'] = $pager->fshow();
                    $result['page_params'] = $pager->get_page_params();
                }else{
                    $result['page'] = '';
                }
            }
        }else{
            $limit = $this->spage_size ? $this->firstRow . ',' .$this->spage_size : '';
            $jobs_list = $model->Table($db_pre.$this->mod.' j')->field($this->field)->where($this->where)->join($this->join)->order($this->order)->limit($limit)->select();
            $result['page'] = '';
            $result['total'] = 0;
        }
        if($this->params['view_jobs']){
            $jobs = cookie('view_jobs_log');
        }else{
            foreach ($jobs_list as $key => $val) {
                $val['id'] && $jobs[] = $val['id'];
            }
        }
        if($jobs){
            if($this->params['show_contact']==1){
                $contact_info = D('JobsContact')->where(array('pid'=>array('in',$jobs)))->select();
                foreach ($contact_info as $k => $v) {
                    $contact_arr[$v['pid']] = $v['telephone']?$v['telephone']:trim($v['landline_tel'],'-');
                    if($v['telephone']){
                        $tel[$v['pid']] = $v['telephone'];
                    }else{
                        $tel[$v['pid']] = explode('-',$v['landline_tel']);
                        unset($tel[$v['pid']][2]);
                        $tel[$v['pid']] = implode('-', $tel[$v['pid']]);
                    }
                }
            }else{
                $contact_arr = array();
            }
            $jids = implode(',',$jobs);
            $jobs = M('Jobs')->field()->where(array('id'=>array('in',$jids)))->order('field(id,'.$jids.')')->limit($this->spage_size)->select();
            foreach ($jobs as $key => $val) {
                $cids[] = $val['company_id'];
            }
            $cids && $company_list = M('CompanyProfile')->where(array('id'=>array('in',$cids)))->limit(count($cids))->getfield('id,logo');
            if(C('apply.Subsite')){
                if(false === $subsite_district = F('subsite_district')) $subsite_district = D('Subsite')->subsite_district_cache();
            }
            foreach ($jobs as $key => $val) {
                $val['jobs_name_'] = $val['jobs_name'];
                $val['refreshtime_cn'] = daterange(time(),$val['refreshtime'],'Y-m-d',"#FF3300");
                $this->params['jobslen'] && $val['jobs_name']=cut_str($val['jobs_name'],$this->params['jobslen'],0,$this->params['dot']);
                if ($this->params['brieflylen']>0){
                    $val['briefly']=cut_str(strip_tags($val['contents']),$this->params['brieflylen'],0,$this->params['dot']);
                }else{
                    $val['briefly']=strip_tags($val['contents']);
                }
                $val['amount']=$val['amount']=="0"?'若干':$val['amount'];
                $val['briefly_']=strip_tags($val['contents']);
                $val['companyname_']=$val['companyname'];
                $this->params['companynamelen'] && $val['companyname']=cut_str($val['companyname'],$this->params['companynamelen'],0,$this->params['dot']);
                C('apply.Subsite') && $subsite_id = $subsite_district[$val['tdistrict']]?:($subsite_district[$val['sdistrict']]?:$subsite_district[$val['district']]);
                $val['jobs_url']=url_rewrite($this->params['jobsshow'],array('id'=>$val['id'],'style'=>$this->params['tpl_compnay']),$subsite_id);
                $val['company_url']=url_rewrite($this->params['companyshow'],array('id'=>$val['company_id']));
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
                $age = explode('-',$val['age']);
                if(!$age[0] && !$age[1]){
                    $val['age_cn'] = '不限';
                }else{
                    $age[0] && $val['age_cn'] = $age[0].'岁以上';
                    $age[1] && $val['age_cn'] = $age[1].'岁以下';
                }
                if ($val['tag_cn']){
                    $val['tag_cn']=explode(',',$val['tag_cn']);
                }else{
                    $val['tag_cn']=array();
                }
                $val['logo'] = $company_list[$val['company_id']];
                if ($val['logo'])
                {
                    $val['logo']=attach($val['logo'],'company_logo');
                }
                else
                {
                    $val['logo']=attach('no_logo.png','resource');
                }
                if($val['experience_cn']=='不限'){
                    $val['experience_cn'] = '经验不限';
                }
                if($val['education_cn']=='不限'){
                    $val['education_cn'] = '学历不限';
                }
                if(C('apply.Allowance') && $val['allowance_id']>0){
                    $val['allowance_info'] = D('Allowance/AllowanceInfo')->find($val['allowance_id']);
                    $val['allowance_info']['type_cn'] = D('Allowance/AllowanceInfo')->get_alias_cn($val['allowance_info']['type_alias']);
                }else{
                    $val['allowance_info'] = array();
                    $val['allowance_id'] = 0;
                }
                if($this->params['lat'] && $this->params['lng']){
                    $val['map_range'] = $this->_get_distance($this->params['lat'],$this->params['lng'],$val['map_y'],$val['map_x']);
                }
                //合并公司 显示模式
                if($this->params['mode']==1){
                    //统计单个公司符合条件职位数
                    $match_map['company_id'] = $val['company_id'];
                    if(C('qscms_jobs_display')==1){
                        $match_map['audit'] = 1;
                    }
                    $val['count'] = M('Jobs')->where($match_map)->count('id');
                    $val['count_url']= $val['company_url'];
                    $list[$val['company_id']][] = $val;
                }else{//职位列表 显示模式
                    $list[] = $val;
                }
            }
            $result['list'] = $list;
            $result['contact_arr'] = $contact_arr;
            $result['has_apply'] = $this->has_apply;
            $result['has_favor'] = $this->has_favor;
            if(C('apply.Allowance')){
                $result['allowance_count'] = D('Jobs')->where(array('allowance_id'=>array('gt',0)))->count();
            }
            $result['tel'] = $tel;
        }else{
            $result['list'] = '';
        }
        return $result;
    }
    /**
     * 访问百度api获取当前坐标
     */
    protected function _get_baidumap_api(){
        $api = 'http://api.map.baidu.com/location/ip';
        $data['ip'] = get_client_ip();
        if($data['ip']=='127.0.0.1' || !C('qscms_map_ak')){
            return false;
        }else{
            $data['ak'] = C('qscms_map_ak');
            $data['coor'] = 'bd09ll';
            $result = https_request($api,$data);
            return $result;
        }
    }
    /**
     * 计算两坐标点之间的距离
     * 返回友好的距离长度
     *
     * @param   $lat1     decimal   纬度
     * @param   $lng1     decimal   经度
     * @param   $lat2     decimal   纬度
     * @param   $lng2     decimal   经度
     *
     * @return  decimal   距离
     */
    protected function _get_distance($lat1, $lng1, $lat2, $lng2, $type = false){
        $PI = '3.1415926535898';
        $radLat1 = $lat1 * ($PI / 180);
        $radLat2 = $lat2 * ($PI / 180);
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * ($PI / 180)) - ($lng2 * ($PI / 180));
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
        $s = $s * 6378;
        if(!$type){
            $s = $s > 1 ? round($s,1).'km' : round($s*1000).'m';
        }else{
            $s = round($s,1);
        }
        return $s;
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
                    if (intval($a[2])===0){
                        $d.= " OR sdistrict=".intval($a[1]);
                    }else if(intval($a[1])===0){
                        $d.= " OR district=".intval($a[0]);
                    }else{
                        $d.= " OR tdistrict=".intval($a[2]);
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
                            $reg = strpos(C('SUBSITE_VAL.s_district'),',') ? '/^\_'.str_replace(',','|',C('SUBSITE_VAL.s_district')).'\_/' : '_'.C('SUBSITE_VAL.s_district').'_';
                            if(preg_match($reg,'_'.$city_cate[$val].'_') !== 1) continue;
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
//                    if (intval($a[2])===0){
//                        $d.= " OR category =".intval($a[1]);
//                    }else{
//                        $d.= " OR subclass =".intval($a[2]);
//                    }
                    if(intval($a[0])){
                        $d.= " OR topclass =".intval($a[0]);
                    }
                    if(intval($a[1])){
                        $d.= " OR category =".intval($a[1]);
                    }
                    if(intval($a[2])){
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
    protected function _where_trade($data){
        if(!empty($data)){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $sqlin = implode(',',array_slice($arr,0,10));
                if (fieldRegex($sqlin,'in')){
                    return "trade IN ({$sqlin})";
                }
            }else{
                return 'trade = '.intval($data);
            }
        }
        return false;
    }
    protected function _where_nature($data){
        if($data){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $sqlin = implode(',',array_slice($arr,0,10));
                if (fieldRegex($sqlin,'in')){
                    return "nature IN ({$sqlin})";
                }
            }else{
                return 'nature = '.intval($data);
            }
        }
        return false;
    }
}
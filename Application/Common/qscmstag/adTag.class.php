<?php
/**
 * 合并加载JS和CSS文件
 *
 * @author brivio
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class adTag {
    protected $enum             = array(
        '广告位名称'            =>   'tpl',
        '广告数量'            =>   'num',
        '职位数量'              =>   'jobs',
        '广告位终端'            =>   'type',
        '招聘会id'              =>'fare_id',
        '签到'                =>'sign'
    );
    public function __construct($options) {
        foreach ($options as $key => $val) {
            $this->params[$this->enum[$key]] = $val;
        }
        !isset($this->params['type']) && $this->params['type'] = 'Home';
    }
    public function run(){
        if(!in_array($this->params['type'],array('Home','Mobile','Apk'))) return false;
        if(C('SUBSITE_VAL')) $cache = '_'.C('SUBSITE_VAL.s_id');
        if(false === $adcate = F('ad_cate_list_'.C('qscms_template_dir').$cache)){
            $adcate = D('AdCategory')->ad_cate_cache(C('qscms_template_dir'),C('SUBSITE_VAL.s_id'));
        }
        $board_info = $adcate[$this->params['tpl']];
        if(!$board_info) return false;
        $time_now = time();
        //创建数组变量，并赋值，此处作用为，根据广告的起始时间与结束时间来判断是否显示广告内容，下面查询语句用
        //$map['org'] = $type;
        $map['alias'] = $board_info['alias'];
        $map['starttime'] = array('elt', $time_now);
        $map['deadline'] = array(array('egt',$time_now),array('eq',0), 'or');
        $map['is_display'] = 1;
        if(C('SUBSITE_VAL')) $map['subsite_id'] = C('SUBSITE_VAL.s_id');
        !isset($this->params['num']) && $this->params['num'] = $board_info['ad_num'];
        //url广告链接地址
        //content广告内容（图片、文字、flash动画）
        
        $ad_list = M('Ad')->where($map)->field('id,type_id,title,content,url,text_color,uid')->order('show_order desc')->limit($this->params['num'])->select();
        if($this->params['type'] == 'Apk') return $ad_list;
        foreach ($ad_list as $key => $val) {
            $val['uid'] && $uids[] = $val['uid'];
        }
        $uids && $company = M('CompanyProfile')->where(array('uid'=>array('in',$uids)))->limit(count($uids))->getfield('uid,id,companyname,logo');
        foreach ($company as $key=>$val) { 
            $company[$key]['briefly']=cut_str(strip_tags($val['contents']),30,0,'...');
            $company[$key]['company_url']=url_rewrite('QS_companyshow',array('id'=>$val['id']));
        }
        if(C('apply.Subsite')){
            if(false === $subsite_district = F('subsite_district')) $subsite_district = D('Subsite')->subsite_district_cache();
        }
        foreach ($ad_list as $key=>$val) {
            $ad_list[$key]['company'] = $company[$val['uid']];
            if($this->params['jobs'] && $company[$val['uid']]){
                $list_map['company_id'] = $company[$val['uid']]['id'];
                if(C('qscms_jobs_display')==1){
                    $list_map['audit'] = 1;
                }
                $jobs_list = M('Jobs')->field('id,jobs_name,minwage,maxwage,district_cn')->where($list_map)->order('refreshtime desc')->select();
                $jobs_list = array_slice($jobs_list,0,$this->params['jobs']);
                foreach ($jobs_list as $k => $v) {
                    $v['minwage'] = $v['minwage']%1000==0?($v['minwage']/1000):round($v['minwage']/1000,1);
                    $v['maxwage'] = $v['maxwage']?($v['maxwage']%1000==0?($v['maxwage']/1000):round($v['maxwage']/1000,1)):0;
                    $jobs_list[$k]['jobs_url'] = url_rewrite('QS_jobsshow',array('id'=>$v['id']));
                    if($v['maxwage']==0){
                        $jobs_list[$k]['wage_cn'] = '面议';
                    }else{
                        if($v['minwage']==$v['maxwage']){
                            $jobs_list[$k]['wage_cn'] = $v['minwage'].'K/月';
                        }else{
                            $jobs_list[$k]['wage_cn'] = $v['minwage'].'K-'.$v['maxwage'].'K/月';
                        }
                    }
                    if(C('apply.Subsite')){
                        $subsite_id = $subsite_district[$v['tdistrict']]?:($subsite_district[$v['sdistrict']]?:$subsite_district[$v['district']]);
                        $jobs_list[$k]['jobs_url'] = url_rewrite('QS_jobsshow',array('id'=>$v['id']),$subsite_id);
                    }
                }
                $ad_list[$key]['company']['jobs_count'] = count($jobs_list);
                $ad_list[$key]['company']['jobs'] = $jobs_list;
            }
            $this->_get_html($ad_list[$key],$board_info,$company[$val['uid']]);
        }
        return array('board'=>$board_info,'list'=>$ad_list);
    }
    //_get_html方法用于根据不同的广告类型生成对应的模板文件代码
    //广告类型(1:文字,2:图片,3:代码,4:flash,5:视频)
    private function _get_html(&$ad, $board_info,$company) {
        $html = $ad['desc'];
        $size_html = '';
        $board_info['width'] && $size_html .= 'width="'.$board_info['width'].'"';
        $board_info['height'] && $size_html .= ' height="'.$board_info['height'].'"';
        if($ad['url']){
            $href = $ad['url'];
        }elseif($company['id']){
            $href = url_rewrite('QS_companyshow',array('id'=>$company['id']));
        }
        !$href && $href = 'javascript:;';
        switch($ad['type_id']){
            case 1:
                $ad['text_color'] && $style="color:{$ad['text_color']};";
                $board_info['width'] && $style.="width:{$board_info['width']};";
                $board_info['height'] && $style.="height:{$board_info['height']}";
                $html  = '<a title="'.$ad['title'].'" href="'.$href.'" target="_blank" style="'.$style.'">'.$ad['content'].'</a>';
                break;
            case 2://生成广告为图片类型的代码
                $html  = '<a title="'.$ad['title'].'" href="'.$href.'" target="_blank">';
                $html .= '<img alt="'.$ad['title'].'" src="'.attach($ad['content'],'ads').'" '.$size_html.'>';
                $html .= '</a>';
                break;
            case 3:
                $html = $ad['content'];
                break;
            case 4://生成广告为flash类型的代码
                $html  = '<a title="'.$ad['title'].'" href="'.$href.'" target="_blank">';
                $html .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '.$size_html.' codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">';
                $html .= '<param name="movie" value="'.attach($ad['content'],'ads').'" />';
                $html .= '<param name="quality" value="autohigh" />';
                $html .= '<param name="wmode" value="opaque" />';
                $html .= '<embed src="'.attach($ad['content'],'ads').'" quality="autohigh" wmode="opaque" name="flashad" swliveconnect="TRUE" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" '.$size_html.'></embed>';
                $html .= '</object>';
                $html .= '</a>';
                break;
            case 5://生成视频广告
                if(preg_match("/^.+\.(flv|f4v)$/i",$ad['content'])){
                    $html  = '<a title="'.$ad['title'].'" href="'.$href.'" target="_blank">';
                    $html .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" '.$size_html.'>';
                    $html .= '<param name="movie" value="__ROOT__/static/flvplayer.swf"/>';
                    $html .= '<param name="quality" value="high"/>';
                    $html .= '<param name="allowFullScreen" value="true" />';
                    $html .= '<param name="FlashVars" value="vcastr_file='.attach($ad['content'],'ads').'&IsAutoPlay=1&IsContinue=1" />';
                    $html .= '<embed src="__ROOT__/static/flvplayer.swf" autostart=true loop=true allowFullScreen="true" FlashVars="vcastr_file='.attach($ad['content'],'ads').'&IsAutoPlay=1&IsContinue=1" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" '.$size_html.'></embed>';
                    $html .= '</object>';
                    $html .= '</a>';
                }else{
                    //通用代码
                    $ad['content'] = preg_replace('/((width)[=]?[:]?[\"]?[0-9]+[\"]?)/i','width="'.$board_info['width'].'"',$ad['content']);//宽
                    $ad['content'] = preg_replace('/((height)[=]?[:]?[\'"]?[0-9]+[\'"]?)/i','height="'.$board_info['height'].'"', $ad['content']);//高
                    $html  = '<a title="'.$ad['title'].'" href="'.$href.'" target="_blank">'.$ad['content'].'</a>';
                }
                break;
        }
        $ad['html'] = $html;
        $ad['href'] = $href;
    }
}
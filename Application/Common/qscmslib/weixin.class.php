<?php
/**
 * 微信相关
 *
 * @author andery
 */
namespace Common\qscmslib;
class weixin {
    private static $qrcode_type = array(
        'login'=>array('s'=>1,'g'=>10000000),
        'register'=>array('s'=>10000001,'g'=>20000000),
        'bind'=>array('s'=>20000001,'g'=>30000000)
    );
    /*
        pwd_hash
        获取access_token
    */
    public function get_access_token($length=6,$reset=false){
        $access_token=S('access_token');
        if($access_token && !$reset) return $access_token;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".C('qscms_weixin_appid')."&secret=".C('qscms_weixin_appsecret');
		//$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx28a4b7342d3e0bc8&secret=115db799457b551eaca1b4d3a26a8c16";
      
		
		$result = self::https_request($url);
        $jsoninfo = json_decode($result, true);
        $access_token = $jsoninfo["access_token"];
        //更新数据
        S('access_token',$access_token,7200);
        return $access_token;
    }
    /**
     * [get_weixin_json_menu 读取微信菜单]
     */
    public function get_weixin_json_menu(){
        $arr = array();
        $weixin_menu=M('WeixinMenu');
        $menu_arr = $weixin_menu->where(array('parentid'=>0,'status'=>1))->order('menu_order desc,id asc')->select();
        foreach ($menu_arr as $key => $value) {
            $sub_menu = $weixin_menu->where(array('parentid'=>$value['id'],'status'=>1))->order('menu_order desc,id asc')->select();
            if(!empty($sub_menu)){
                $arr[$key]['name'] = urlencode($value['title']);
                foreach ($sub_menu as $sub_key => $sub_value) {
                    $arr[$key]['sub_button'][$sub_key]['type'] = $sub_value['type'];
                    $arr[$key]['sub_button'][$sub_key]['name'] = urlencode($sub_value['title']);
                    if($sub_value['type']=="click"){
                        $arr[$key]['sub_button'][$sub_key]['key'] = $sub_value['key'];
                    }else{
                        $sub_value['url'] = str_replace('|appid|',C('qscms_weixin_appid'),$sub_value['url']);
                        $sub_value['url'] = htmlspecialchars_decode($sub_value['url'],ENT_QUOTES);
                        $arr[$key]['sub_button'][$sub_key]['url'] = $sub_value['url'];
                        $weixin_menu->where(array('id'=>$sub_value['id']))->setfield('url',$sub_value['url']);
                    }
                }
            }else{
                $arr[$key]['type'] = $value['type'];
                $arr[$key]['name'] = urlencode($value['title']);
                if($value['type']=="click"){
                    $arr[$key]['key'] = $value['key'];
                }else{
                    $value['url'] = str_replace('|appid|',C('qscms_weixin_appid'),$value['url']);
                    $value['url'] = htmlspecialchars_decode($value['url'],ENT_QUOTES);
                    $arr[$key]['url'] = $value['url'];
                    $weixin_menu->where(array('id'=>$value['id']))->setfield('url',$value['url']);
                }
            }
        }
        $menu['button'] = $arr;
        return urldecode(json_encode($menu));
    }
    /**
     * [menu_sync 微信菜单同步]
     */
    public function menu_sync(){
        if(!C('qscms_weixin_appid')) return '请配置微信公众号参数！';
        $access_token = self::get_access_token();
	
        $jsonmenu = self::get_weixin_json_menu();
		
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $result = self::https_request($url, $jsonmenu);
        $result_arr = json_decode($result,true);
        if($result_arr['errcode'] == 0 && $result_arr['errmsg']=='ok'){
            return true;
        }else{
			
            return $result_arr['errmsg'].'(错误代码：'.$result_arr['errcode'].')';
        }
    }
    /**
     * [send_msg 微信发送信息]
     */
    public function send_msg($openid,$content){
        if(!$openid) return '微信openid不能为空！';
        if(!$content) return '内容不能为空！';
        $access_token = self::get_access_token();
        $data_arr = array(
            "touser"=>$openid,
            "msgtype"=>"text",
            "text"=>array(
                "content"=>urlencode($content)
            )
        );
        $data = urldecode(json_encode($data_arr));
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$access_token;
        $result = self::https_request($url, $data);
        $result_arr = json_decode($result,true);
        if($result_arr['errcode'] == 0 && $result_arr['errmsg']=='ok'){
            return true;
        }else{
            return $result_arr['errmsg'].'(错误代码：'.$result_arr['errcode'].')';
        }
    }
    /**
     * [send_queue_msg 批量发送微信消息]
     */
    public function send_queue_msg($utype,$content){
        if(!$utype) return '请正确选择用户类型！';
        if(!$content) return '内容不能为空！';
        $db_pre = C('DB_PREFIX');
        $m_t = $db_pre.'members';
        if($user_list = M('Members')->field($m_t.'.uid,username,utype,b.keyid as weixin_openid')->where(array('utype'=>$utype,'b.type'=>'weixin'))->join($db_pre.'members_bind as b ON b.uid='.$m_t.'.uid')->select()){
            $access_token = self::get_access_token();
            $msg_mod = D('WeixinMsgList');
            foreach ($user_list as $val) {
                $result = self::send_msg($val['weixin_openid'],$content);
                if($result){
                    $val['content'] = $content;
                    if(false === $msg_mod->create($val)) return $msg_mod->getError();
                    if(false === $msg_mod->add()) return '微信消息记录失败！';
                }
            }
            return true;
        }else{
            return '暂时还没有满足条件的用户！';
        }
    }
    /**
     * [qrcode_img 生成微信二维码]
     */
    public function qrcode_img($type,$width=120,$height=120){
        $access_token = self::get_access_token();
        srand((double)microtime() * 1000000);
        $range = self::$qrcode_type[$type];
        $scene_id = rand($range['s'], $range['g']);
        session($type.'_scene_id',$scene_id);
        F('/weixin/'.($scene_id%10).'/'.$scene_id,0);
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
        $post_data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
        $result = self::https_request($url, $post_data);
        $result_arr = json_decode($result,true);
        if($result_arr['errcode'] == 40001){
            self::get_access_token(6,true);
            self::qrcode_img($type,$width,$height);
        }else{
            $ticket = urlencode($result_arr["ticket"]);
            $html = '<img width='.$width.' height='.$height.' src="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket.'">';
            return $html;
        }
    }
    /**
     * [bind 绑定微信]
     */
    public function bind($openid,$user){
        if(!$openid) return array('state'=>0,'tip'=>'openid不能为空！');
        $bind_user = M('MembersBind')->where(array('type' => 'weixin', 'keyid' => $openid))->find();
        if ($bind_user['uid'] && $bind_user['uid'] != $user['uid']) return array('state'=>0,'tip'=>'此帐号已经绑定过本站！');
        $access_token = self::get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $result = self::https_request($url);
        $result = preg_replace("/(\\\ue[0-9a-f]{3})/ie","addslashes('\\1')",$result);
        $userinfo = json_decode($result,true);
        $user_bind_info['keyid'] = $userinfo['openid'];
        $user_bind_info['keyname'] = emoji_unicode($userinfo['nickname']);
        $userinfo = serialize($user_bind_info);
        $id = M('MembersBind')->add(array('uid' => $user['uid'],'type' => 'weixin','keyid' => $openid,'info' => $userinfo,'bindingtime'=>time()));
        if($id){
            // 绑定微信 获得积分
            $taskid = $user['utype']==1?33:15;
            D('TaskLog')->do_task($user,$taskid);
            $rule = D('Task')->get_task_cache($user['utype'],$taskid);
            $user_points=D('MembersPoints')->get_user_points($user['uid']);
            $operator="+";
            
            $scene_id = session('bind_scene_id');
            session('bind_scene_id',null);
            F('/weixin/'.($scene_id%10).'/'.$scene_id,null);
            return array('state'=>1,'tip'=>'微信绑定成功！');
        }else{
            return array('state'=>0,'tip'=>'微信绑定失败！');
        }
        
    }
    /**
     * [get_user_info 获取微信用户信息]
     */
    public function get_user_info($openid){
        if(!$openid) return array('state'=>0,'tip'=>'openid不能为空！');
        $access_token = self::get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $result = self::https_request($url);
        $userinfo = json_decode($result,true);
        $user = array('type'=>'weixin','openid'=>$userinfo['openid'],'username'=>$userinfo['nickname'],'keyavatar_big'=>$userinfo['headimgurl']);
        cookie('members_bind_info', $user);
        return array('state'=>1,'tip'=>'用户信息获取成功！','data'=>$user);
    }
    /**
     * [reg 微信注册]
     */
    public function reg($openid){
        if(!$openid) return array('state'=>0,'tip'=>'openid不能为空！');
        $access_token = self::get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $result = self::https_request($url);
        if(!$result) return array('state'=>0,'tip'=>'用户信息获取失败！');
        $userinfo = json_decode($result,true);
        $userinfo['keyname'] = emoji_unicode($userinfo['nickname']);
        return array('state'=>1,'data'=>$userinfo);
    }
    public function https_request($url,$data = null){
        if(function_exists('curl_init')){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
			//var_dump($output);
			//die;
            curl_close($curl);
            return $output;
        }else{
            return false;
        }
    }
    /**
     * 构建模板消息
     */
    public function build_tpl_msg($data){
        $access_token = self::get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $result = self::https_request($url, $data);
        return json_decode($result,true);
    }
    /**
     * 错误
     */
    public function getError(){
		
        return self::_error;
    }
}
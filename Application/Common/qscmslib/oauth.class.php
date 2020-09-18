<?php
/**
 * 第三方登陆
 *
 * @author andery
 */
namespace Common\qscmslib;
class oauth {
    private $_type = '';
    private $_setting = array();
    private $_error = '';
    public function __construct($name) {
        $this->_type = $name ? $name : C('qscms_oauth_default');
        //加载登陆接口配置
        if(false === $oauth_list = F('oauth_list')){
            $oauth_list = D('Oauth')->oauth_cache();
        }
        $this->_setting = unserialize($oauth_list[$this->_type]['config']);
        //导入接口文件
        include_once QSCMSLIB_PATH . 'oauth/' . $this->_type . '/' . $this->_type . '.php';
        $om_class = $this->_type . '_oauth';
        $this->_om = new $om_class($this->_setting);
    }
    /**
     * 跳转到授权页面
     */
    public function authorize() {
        redirect($this->_om->getAuthorizeURL());
    }
    /**
     * 登陆回调
     */
    public function callbackLogin($request_args) {
        $user = $this->_om->getUserInfo($request_args);
        if(!$user){
            $this->_error = '用户信息获取失败！';
            return false;
        }
        $bind_user = $this->_checkBind($this->_type, $user['keyid']);
        if ($bind_user) {
            //已经绑定过则更新绑定信息 自动登陆
            $this->_updateBindInfo($user);
            //登陆
            $visitor = $this->_oauth_visitor();
            if(false === $visitor->login($bind_user['uid'])){
                $this->_error = $visitor->getError();
                return false;
            }
            $urls = array('1'=>'company/index','2'=>'personal/index');
            $url = $request_args['state'] == 'mobile' && !C('qscms_wap_domain') ? 'mobile/'.$urls[$visitor->info['utype']] : $urls[$visitor->info['utype']];
        } else {
            //处理用户名
            if(M('Members')->where(array('username' => $user['keyname']))->getfield('uid')){
                $user['username'] = $user['keyname'] . '_' . mt_rand(99, 9999);
            }else{
                $user['username'] = $user['keyname'];
            }
            if($user['keyavatar_big']) {
                //下载原始头像到本地临时储存  用日期文件夹分类  方便清理
                $avatar_temp_root = C('qscms_attach_path') . 'avatar/temp/';
                $file_name = md5($user['keyid']) . '.jpg';
                if(!is_dir($avatar_temp_root)) mkdir($avatar_temp_root);
                $image_content = \Common\ORG\Http::fsockopenDownload($user['keyavatar_big']);
                file_put_contents($avatar_temp_root . $file_name, $image_content);
                $user['temp_avatar'] = $file_name;
            }
            $user['type'] = $this->_type;
            $user['bind_info']['keyname'] = $user['keyname'];
            $user['bind_info'] = serialize($user['bind_info']);
            //把第三方的数据存到COOKIE
            cookie('members_bind_info', $user);
            $url = $request_args['state'] == 'mobile' && !C('qscms_wap_domain') ? 'mobile/members/apilogin_binding' : 'members/apilogin_binding';
        }
        return U($url);
    }
    /**
     * 绑定回调
     */
    public function callbackBind($request_args) {
        $visitor = $this->_oauth_visitor();
        if(!$visitor->is_login) return U('members/login');
        $user_info = $visitor->info;
        $user = $this->_om->getUserInfo($request_args);
        $bind_user = $this->_checkBind($this->_type, $user['keyid']);
        if ($bind_user['uid']) {
            $this->_error = '此帐号已经绑定过本站！';
            return false;
        }
        $user['uid'] = $user_info['uid'];
        $user['bind_info']['keyname'] = $user['keyname'];
        $user['utype'] = $user_info['utype'];
        if(false === $this->bindUser($user)){
            $this->_error = '帐号绑定失败，请重新操作！';
            return false;
        }
        if($request_args['state'] == 'mobile'){
            $urls = array('1'=>'company/com_binding','2'=>'personal/per_binding');
            $url = !C('qscms_wap_domain') ? 'mobile/'.$urls[C('visitor.utype')] : $urls[C('visitor.utype')];
        }else{
            $urls = array('1'=>'company/user_security','2'=>'personal/user_safety');
            $url = $urls[C('visitor.utype')];
        }
        return U($url);
    }
    /**
     * 更新绑定信息
     */
    private function _updateBindInfo($user) {
        return M('MembersBind')->where(array('type' => $this->_type,'keyid' => $user['keyid']))->setfield('info',serialize($user['bind_info']));
    }
    /**
     * 绑定帐号
     */
    public function bindUser($user) {
        $bind_user = array(
            'uid'           => $user['uid'],
            'type'          => $this->_type,
            'keyid'         => $user['keyid'],
            'info'          => !is_array($user['bind_info'])?$user['bind_info']:serialize($user['bind_info']),
            'bindingtime'   => time()
        );
        if(false !== $reg = M('MembersBind')->add($bind_user)){
            $taskid = $user['utype']==1?24:8;
            switch($this->_type){
                case 'qq':
                    $taskid = $user['utype']==1?25:9;
                    break;
                case 'sina':
                    $taskid = $user['utype']==1?24:8;
                    break;
                case 'taobao':
                    $taskid = $user['utype']==1?26:10;
                    break;
                default:
                    $taskid = $user['utype']==1?25:9;
                    break;
            }
            D('TaskLog')->do_task($user,$taskid);
        }
        return $reg;
    }
    /**
     * 检测用户是否已经绑定过本站
     */
    public function _checkBind($type, $key_id) {
        return M('MembersBind')->where(array('type' => $type, 'keyid' => $key_id))->find();
    }
    /**
     * 访问者
     */
    private function _oauth_visitor() {
        return new \Common\qscmslib\user_visitor();
    }
    /**
     * 返回需要的参数
     */
    public function NeedRequest() {
        return $this->_om->NeedRequest();
    }
    /**
     * 错误
     */
    public function getError(){
        return $this->_error;
    }
}
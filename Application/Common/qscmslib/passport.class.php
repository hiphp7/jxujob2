<?php
/**
 * 用户基类
 *
 * @author andery
 */
namespace Common\qscmslib;
class passport
{
    private $_error = 0;
    private $_us = null;
    private $_status = false;
    public $_user;
    public function __construct($name) {
        !$name && $name = C('apply.Ucenter') && C('qscms_uc_open') ? 'ucenter' : 'default';
        $name = $name == 'ucenter' && C('apply.Ucenter') && C('qscms_uc_open') ? 'ucenter' : 'default';
        include_once QSCMSLIB_PATH . 'passport/' . $name . '.php';
        $class = $name . '_passport';
        $this->_us  = new $class();
    }
    public function uc($name) {
        !$name && $name = C('apply.Ucenter') && C('qscms_uc_open') ? 'ucenter' : 'default';
        $name = $name == 'ucenter' && C('apply.Ucenter') && C('qscms_uc_open') ? 'ucenter' : 'default';
        include_once QSCMSLIB_PATH . 'passport/' . $name . '.php';
        $class = $name . '_passport';
        $this->_us  = new $class();
        return $this;
    }
    /**
     * 新注册用户名生成
     */
    protected function _uname($data){
        if($data['reg_type']==1){
            $data['username']= strtolower(C('qscms_reg_prefix').$data['mobile']);
            $exist_num = D('Members')->where(array('username'=>array('like',$data['username'].'%')))->count();
            if($exist_num>0){
                $data['username'] .= $exist_num;
            }
            // 手机注册 手机验证状态
            $data['mobile_audit']=1;
        }elseif($data['reg_type']==2){
            $email_str = str_replace('@', '', $data['email']);
            $email_str = str_replace('.', '', $email_str);
            $data['username']= strtolower(C('qscms_email_reg_prefix').$email_str);
            // 邮箱注册 邮箱验证状态
            if(C('qscms_check_reg_email')=="1") $data['email_audit']=1;
        }else{
            $data['username']=strtolower(C('qscms_third_reg_prefix').D('Members')->randstr());
        }
        return $data;
    }
    /**
     * [_password 微信注册用户密码生成]
     */
    public function _password($username){
        if(C('qscms_reg_password_tpye') == 1){
            $password = $username;
        }elseif(C('qscms_reg_password_tpye') == 2){
            $password = D('Members')->randstr();
        }else{
            $password = C('qscms_reg_weixin_password');
        }
        return $password;
    }
    /**
     * 注册新用户
     */
    public function register($data) {
        !$data['username'] && $data = $this->_uname($data);
        !$data['password'] && $data['password'] = $this->_password($data['username']);
        if (!$add_data = $this->_us->register($data)) {
            $this->_error = $this->_us->get_error();
            return false;
        }
        //添加到本地
        $reg = $this->_local_add($add_data);
        cookie('members_uc_info',NULL);
        if(false === $reg && $this->_error == L('members_unique_error_email')){
            $user = M('Members')->where(array('email'=>$add_data['email']))->find();
            if($user['status'] != 0 && $user['utype'] != 2) return false;
            $time=time();
            $key=substr(md5($user['email'].$time),8,16);
            $str = encrypt(http_build_query(array('e'=>$user['email'],'k'=>$key,'t'=>$time,'p'=>$add_data['password'],'n'=>$add_data['username'])));
            $email_str.="您好,请在24小时内点击以下链接完成注册：<br>";
            $url = C('qscms_site_domain').U('members/activate',array('key'=>$str));
            $email_str.="<a href='".$url."' target='_blank'>".$url."</a><br>";
            $email_str.="如果链接无法点击,请复制粘贴到浏览器访问！<br>";
            $email_str.="本邮件由系统发出,请勿回复！<br>";
            $email_str.="如有任何疑问请联系网站官方：".C('qscms_top_tel')."";
            $email_data = array('sendto_email'=>$user['email'],'subject'=>C('qscms_site_name')." - 会员注册",'body'=>$email_str);
            if(true !== $tip = D('Mailconfig')->send_mail($email_data)) $this->_error = $tip;
            $this->_status = $user;
        }
        return $reg;
    }

    /**
     * 修改用户资料
     * $force  是否强制修改
     */
    public function edit($uid,$data,$old_password='',$force = false) {
        if (!$edit_data = $this->_us->edit($uid,$data,$old_password,$force)) {
            $this->_error = $this->_us->get_error();
            return false;
        }
        //本地修改
        return $this->_local_edit($uid,$edit_data);
    }

    /**
     * 删除用户
     */
    public function delete($uid) {
        if (!$this->_us->delete($uid)) {
            $this->_error = $this->_us->get_error();
            return false;
        }
        return $this->_local_delete($uid);
    }
    /**
     * 获取用户信息
     */
    public function get($flag, $is_name = false) {
        return $this->_us->get($flag, $is_name = false);
    }
    /**
     * 登陆验证
     */
    public function auth($username,$password) {
        $this->_user = $this->_us->auth($username,$password,$regist);
        if (!$this->_user) {
            $this->_error = $this->_us->get_error();
            return false;
        }
        /*if (is_array($uid)) {
            $uid = $this->_local_sync($uid);
        }*/
        return $this->_user['uid'];
    }
    /**
     * 同步登陆
     */
    public function synlogin($uid) {
        return $this->_us->synlogin($uid);
    }
    /**
     * 同步退出
     */
    public function synlogout() {
        return $this->_us->synlogout();
    }
    /**
     * 检测用户邮箱唯一
     */
    public function check_email($email) {
        return $this->_us->check_email($email);
    }
    /**
     * 检测手机唯一
     */
    public function check_mobile($mobile) {
        return $this->_us->check_mobile($mobile);
    }
    /**
     * 检测用户名唯一
     */
    public function check_username($username) {
        return $this->_us->check_username($username);
    }
    /**
     * 本地用户添加
     */
    private function _local_add($add_data) {
        $user_mod = D('Members');
        if (false !== $user_mod->create($add_data)){
            $user_mod->password= $user_mod->make_md5_pwd($add_data['password'],$user_mod->pwd_hash);
            $user_mod->invitation_code = $user_mod->randstr(8,true);
            C('SUBSITE_VAL') && $add_data['subsite_id'] = $user_mod->subsite_id = C('SUBSITE_VAL.s_id');
            if (!$uid = $user_mod->add()) {
                $this->_error = $user_mod->getError();
                return false;
            } else {
                //写入会员日志
                write_members_log(array('uid'=>$uid,'utype'=>$add_data['utype'],'username'=>$add_data['username']),1000);
                $add_data['uid'] = $uid;
                return $add_data;
            }
        }
        $this->_error = $user_mod->getError();
        return false;
    }
    /**
     * 本地用户编辑
     */
    private function _local_edit($uid, $data) {
        $user_mod = D('Members');
        $data['uid'] = $uid;
        if(false !== $user_mod->create($data)){
            if (isset($data['password'])){
                $user_mod->password = $user_mod->make_md5_pwd($data['password'],$data['pwd_hash']);
            }
            if (false !== $user_mod->where(array('uid'=>$uid))->save()) {
                return true;
            }
            $this->_error = $user_mod->getError();
            return false;
        }
        $this->_error = $user_mod->getError();
        return false;
    }

    /**
     * 本地用户删除
     */
    private function _local_delete($uid) {
        $user_mod = D('Members');
        return $user_mod->delete($uid);
    }

    private function _local_get($flag, $is_name = false) {
        if ($is_name) {
            $map = array('username' => $flag);
        } else {
            $map = array('uid' => intval($flag));
        }
        return M('Members')->where($map)->find();
    }

    /**
     * 本地用户同步
     */
    private function _local_sync($user_info) {
        $local_info = $this->_local_get($user_info['username'], true);
        if (empty($local_info)) {
            $local_info['uid'] = $this->_local_add($user_info); //新增本地用户
        } else {
            $this->_local_edit($local_info['uid'], $user_info); //更新本地用户
        }
        return $local_info['uid'];
    }
    public function get_error() {
        return $this->_error;
    }

    public function get_status(){
        return $this->_status;
    }
}
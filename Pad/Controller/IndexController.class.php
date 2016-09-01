<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Controller;

use Common\Controller\BaseController;
use Alipay;
use Common\Common\WechatCallbackapiTest;

class IndexController extends BaseController {

  public function index() {//define

      init_openid();
//      $po = M('user')->where("username = '".$_SESSION['openid']."'")->find();
//      if($po['front_admin'] == 0){
//        echo '<h2>系统正在维护，请稍后再访问！</h2>';
//        return;
//      }
    if(I('request.vip') == 'yes'){
      $this->redirect('Product/selectCompany');
    }

//    $company_discount = 1;
//    if(I('request.code') != ''){
//      $disObj = M('company_discount')->where(array(id=>I('request.code')))->find();
//      $company_discount = $disObj['yh'];
//    }

    if(I('request.address') == 'jt'){
      $school = M('school')->where('id=7')->find();
      $_SESSION['school'] = $school;
      $this->redirect('Product/index/vipcode'.'/'.I('request.vipcode'));
    }else{
      if(I('request.reset') == 'true' || $_SESSION['openid'] == null){
        $schoolList = M('school')->where('state = 1 and delete_flag = 0 and type != 99')->select();
        $this->assign('schools', $schoolList);
        $this->assign('ccc', I('request.vipcode'));
        $this->display();
      }else{
        $user = M('user')->where('username = \''.$_SESSION['openid'].'\'')->find();
        if($user==null||$user['last_school']==null){
          $schoolList = M('school')->where('state = 1 and delete_flag = 0 and type != 99')->select();
          if(count($schoolList) == 1){
            $school = $schoolList[0];
            $_SESSION['school'] = $school;
            $this->redirect('Product/index?vipcode='.I('request.vipcode'));
          }else{
            $this->assign('schools', $schoolList);
            $this->assign('ccc', I('request.vipcode'));
            $this->display();
          }
        }else {
          $_SESSION['current_user'] = $user;
          $school = M('school')->where('id='.$user['last_school'])->find();
          $_SESSION['school'] = $school;
          $this->redirect('Product/index?vipcode='.I('request.vipcode'));
        }
      }
    }
  }


  public function checkVipCode(){
      $code = I('request.code');
      $obj = M('company_discount')->where(array('yh_code'=>$code,'delete_flag'=>0))->find();
      $array = array();
      if($obj){
        $array['state'] = 'success';
        $array['discount_id'] = $obj['id'];

      }else{
        $array['state'] = 'error';
      }
      $this->ajaxReturn(json_encode($array));
  }

  public function checkuser(){
    $this->redirect('Index/index?guess=true');
  }

  public function checkreg(){
    $id = I('request.id');
    $where = 'id = '. $id;
    $school = M('school')->where($where)->find();
    $_SESSION['school'] = $school;

    $user = D('User')->where(array('username' => $_SESSION['openid']))->find();

    if($user == null) {
      $_SESSION['validateNum'] = null;
      $this->assign("guess",'true');
      $this->display('Order/reg');
    }else{
      $_SESSION['current_user'] = $user;
      $this->redirect('GuessPrice/index');
    }

  }

  public function signup() {
    $this->display();
  }

  public function findpwd() {
    $this->display();
  }

  public function findpwdnext(){
    $_SESSION['validate_username'] = I('post.username');
    $_SESSION['validate_mobile'] = I('post.mobile');
    $this->display();
  }

  public function editPwd(){
    $username = $_SESSION['validate_username'];
    $phone = $_SESSION['validate_mobile'];
    $result = M('User')->where("username='".$username."' and mobile='".$phone."' and role_id in (2,3) and delete_flag = 0")->select();
    if(count($result) == 1){
      $data['id'] = $result[0]['id'];
      $data['password'] = I('post.password');
      $saveResult = M('User')->save($data);
      if ($saveResult) {
        $this->success("修改成功", U('Index/index'), 3);
      } else {
        $this->success("修改失败", U('Index/findpwd'), 3);
      }
    }else{
      $this->success("用户不存在", U('Index/findpwd'), 3);
    }
    $this->display();
  }



  public function sendSMS(){
    $phone = I('post.mobile');
    $count = M('user')->where(array(mobile=>$phone,delete_flag=>0))->count();
    if($count == 0){
      $validateNum = sendSMS(I('post.mobile'));
      $_SESSION['validateNum'] = $validateNum;
//      $validateNum = true;
      $result = array(status=>$validateNum,msg=>'操作失败');
      $this->ajaxReturn(json_encode($result));
    }else{
      $result = array(status=>false,msg=>'手机已经绑定');
      $this->ajaxReturn(json_encode($result));
    }
    //$validateNum = true;
  }

  public function delSMS(){
    $validateNum = generate_code();
    $_SESSION['validateNum'] = null;
    $this->ajaxReturn($validateNum);
  }

  public function validateSMS(){
    $sms = I('post.sms');
    if($sms == $_SESSION['validateNum']){
      $this->ajaxReturn(true);
    }else{
      $this->ajaxReturn(false);
     }
  }

  public function getUserHas(){
    $username = I('post.username');
    $phone = I('post.phone');
    $result = M('User')->where("username='".$username."' and mobile='".$phone."' and role_id in (2,3) and delete_flag = 0")->count();
    $this->ajaxReturn($result);
  }

  public function getPhoneHas(){
    $phone = I('post.mobile');
    $result = M('User')->where("mobile='".$phone."' and delete_flag = 0")->count();
    $this->ajaxReturn($result);
  }

  public function addUser(){
    $result = weixin_exec(USERINFO);

    if($result['errcode']!= null){
      throw new \Exception("微信获取用户信息失败");
    }

    $m = M('user');

//    $data['username'] = $_SESSION['openid'];
//
//    $data['password'] = $_SESSION['openid'];

    $data['username'] = $_SESSION['openid'];

    $data['real_name'] = urlencode($result['nickname']);

    $data['password'] = $_SESSION['openid'];

    $data['mobile'] = I('request.phone');

    if($data['mobile'] == null || $data['mobile'] == ''){
      throw new \Exception("由于网络原因无法获取手机号，请重新注册！");
    }

    $data['status'] = 0;

    $data['role_id'] = 2;

    $data['oper_time'] = date('Y-m-d H:i:s',time());

    $count = M('user')->where(array(username=>$_SESSION['openid'],status=>0,delete_flag=>0))->count();
    if($count == 0){
      $m->data($data)->add();
    }

    $login['username'] = $_SESSION['openid'];
    $login['status&delete_flag'] = array('0', '0', '_multi'=>true);
    $result = D('User')->logon($login);
    $_SESSION['current_user'] = $result;
    $addressList = M('school_address')->where(array('school'=>$_SESSION['school']['id']))->select();
    $this->assign('address',$addressList);
    $this->assign('addressCount',count($addressList));

    $pay = M('PayType')->where(array('state' => 0))->order('id')->select();
    $this->assign('pay', $pay);

    $count= D('Order')->where(array('purchaser' => $_SESSION['openid'],'delete_flag' => 0))->count();
    if($count == 0)
      $_SESSION['discount'] = 0;
    else
      $_SESSION['discount'] = 0;
    $this->redirect('Order/index');
  }

  public function logoff() {
    if($_SESSION['current_user']['role_id'] == 4){
        $_SESSION['current_user'] = NULL;
        $this->success('注销成功',__APP__);
    }else{
        $_SESSION['current_user'] = NULL;
        $_SESSION['supplier'] = NULL;
        $this->success('注销成功');
    }
  }

  public function doLogin() {
    $login = I('post.login');
    $login['status&delete_flag'] = array('0', '0', '_multi'=>true);
    $result = D('User')->logon($login);
    if ($result) {
      $_SESSION['current_user'] = $result;
      if($result['role_id'] == 1 ||$result['role_id'] == 5 ){
        if($result['role_id'] == 5){
          $supplierObj = M('supplier')->where('id='.$result['supplier_id'])->find();
          $_SESSION['supplier'] = $supplierObj;
        }
        $this->success('登录成功',__APP__.'/Admin');
      }else if($result['role_id'] == 4 ){
        $this->success('登录成功',__APP__.'/Pad/Delivery');
      }else if($result['role_id'] == 2 ||$result['role_id'] == 3 ){
        $this->success('登录成功');
      }else{
        $this->error('登录失败,该用户名不能登录');
      }
    } else {
      $this->error('登录失败,用户名密码不存在');
    }
  }

  public function contact(){
    $this->display("contact");
  }

  public function about(){
    $this->display("about");
  }

  public function partner(){
    $this->display("partner");
  }

  public function service(){
    $this->display("service");
  }
  
  public function errorPage(){
    $this->display("errorPage");
  }

  public function wechatPage(){
    $this->display("wechatPage");
  }

}

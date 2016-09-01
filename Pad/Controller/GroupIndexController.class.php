<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Controller;

use Common\Common\Wechat\base\Jssdk;
use Common\Controller\BaseController;

class GroupIndexController extends BaseController {

  public function _initialize(){
    $this->wx = new Jssdk(C('APPID'),C('SECRET'));
  }

  public function checkGuanZhu(){
    $userinfo = weixin_exec(USERINFO);
    if($userinfo['subscribe'] === 0){
      return false;
    }else{
      return true;
    }
  }

  public function index() {

    $info = weixin_getAuthInfo(3,true);
    $user = getUserInfo($info->access_token,$info->openid);
    $_SESSION['group_user_info'] = $user;
    $_SESSION['openid'] = $user['openid'];
    if(!$this::checkGuanZhu()){
      echo '<script>window.location.href="http://mp.weixin.qq.com/s?__biz=MzIyMTEzMzkxNQ==&mid=502519806&idx=1&sn=65607ee07c7538f91daf53b8ac1d2cb4&scene=0&previewkey=76alSKftjhcWuwvuMO0JmcNS9bJajjJKzz%2F0By7ITJA%3D#wechat_redirect"</script>';
      return;
    }
    $this->display();
  }

  public function item(){
    $id = $_REQUEST['id'];
    $obj = M('group')->where(array(id=>$id))->find();
    $this->assign('item',$obj);
    $this->assign('id',$id);
    $this->display();
  }

  public function address(){
    $id = $_REQUEST['id'];
    $this->assign('id',$id);
    $addressList = M('school_address')->where(array('school'=>10))->select();
    $this->assign('address',$addressList);
    $this->display();
  }

  private function saveAddress($array){
    $m = M('group_buy_address');
    $obj = $m->where(array('session'=>$array['sessionid']))->find();
    if($obj){
      $m->data(array(
         id=>$obj['id']
        ,sessionid=>$array['sessionid']
        ,name=>$array['name']
        ,phone=>$array['phone']
        ,address=>$array['address']
      ))->save();
    }else{
      $m->data(array(
         sessionid=>$array['sessionid']
        ,name=>$array['name']
        ,phone=>$array['phone']
        ,address=>$array['address']
      ))->add();
    }
  }

  private function checkUser($sessionid){
    $m = M('group_buy_user');
    $obj = $m->where(array(sessionid=>$sessionid))->find();
    if($obj)
      return false;
    else
      return true;
  }

  public function linkGroup(){
    //获取用户信息
    $info = weixin_getAuthInfo(3,true);
    $user = getUserInfo($info->access_token,$info->openid);

    $_SESSION['group_user_info'] = $user;

    $id = $_REQUEST['id'];
    $group_buy = M('group_buy')->where(array(id=>$id))->find();
    $obj = M('group')->where(array(id=>$group_buy['group_id']))->find();
    $group_buy_user = M('group_buy_user')->where(array(buy_id=>$group_buy['id'],leader=>0))->select();
    $this->assign('item',$obj);
    $this->assign('wx', $this->wx->GetSignPackage());
    $this->assign('buy_user_count',count($group_buy_user));
    $this->assign('unbuy_user_count',($obj['person_number'] - count($group_buy_user) - 1));
    $this->assign('group_buy',$group_buy);
    $this->assign('group_buy_user',$group_buy_user);

    $this->display();
  }

  public function showJoin(){

    $user = $_SESSION['group_user_info'];

    $hasUser = M('group_buy_user')->where(array(buy_id=>$_REQUEST['bid'],sessionid=>$user['openid']))->find();
    $buy = M('group_buy')->where(array(id=>$_REQUEST['bid'],flag=>0))->find();
    $group = M('group')->where(array(id=>$buy['group_id']))->find();

    if($hasUser){

    }else{
      $buy = M('group_buy')->where(array(
          id=>$_REQUEST['bid']
      ,flag=>0
      ))->find();
      if($buy){
        $group = M('group')->where(array(id=>$buy['group_id']))->find();
        //保存购买人信息
        $buy_user = M('group_buy_user');
        $buy_user_data = array(
            buy_id => $_REQUEST['bid']
        ,sessionid => $user['openid']
        ,name=>$_REQUEST['name']
        ,phone=>$_REQUEST['phone']
        ,address=>$_REQUEST['address']
        ,headimgurl=>$user['headimgurl']
        ,leader=>0
        );

        $buy_user->data($buy_user_data)->add();

        $this::saveAddress(array(sessionid=>$user['openid'],name=>$_REQUEST['name'],phone=>$_REQUEST['phone'],address=>$_REQUEST['address']));

        $param = array(
            name=>$group['name'],
            number=>'1份',
            expDate=>date('Y-m-d H:i:s'),
            remark=>'您已经成功参加了**发起的团购',
        );


        $successParam = array(
            name=>$group['name'],
            number=>'1份',
            expDate=>date('Y-m-d H:i:s'),
            remark=>'您参加的团购已经成功开团,请去选择门店提货',
        );
        //oW5r-vxP-UHzb7ffWvf7hbzuADQQ
        weixin_exec(SENDTEMPLATE,sendYDMsg($param,$_SESSION['group_user_info']['openid']));

        //判断是否组团成功,通知全员
        $hasUser = M('group_buy_user')->where(array(buy_id=>$_REQUEST['bid']))->select();
        if(count($hasUser) == $group['person_number']){
          foreach($hasUser as $key => $val){
            weixin_exec(SENDTEMPLATE,sendYDMsg($successParam,$val['sessionid']));
          }
        }

      }else{
        echo '团购信息不存在';
      }
    }


    //跳转至个人信息页面
    $obj = M('group')->where(array(id=>$group['id']))->find();
    $group_buy_user = M('group_buy_user')->where(array(buy_id=>$_REQUEST['bid'],leader=>0))->select();
    $this->assign('item',$obj);
    $this->assign('wx', $this->wx->GetSignPackage());
    $this->assign('buy_user_count',count($group_buy_user));
    $this->assign('unbuy_user_count',($obj['person_number'] - count($group_buy_user) - 1));
    $this->assign('group_buy',$buy);
    $this->assign('group_buy_user',$group_buy_user);



    $this->display();
  }

  public function joinGroup(){
    $user = $_SESSION['group_user_info'];
    $buy = M('group_buy')->where(array(id=>$_REQUEST['id'],flag=>0))->find();
    if(!$buy){
//      $group = M('group')->where(array(id=>$buy['group_id']))->find();
//    }else{
      echo '不存在';
    }
    $this->assign('groupBuy',$buy);
    $addressList = M('school_address')->where(array('school'=>10))->select();
    $this->assign('address',$addressList);
    $this->display();
//    $buy = M('group_buy')->where(array(
//      id=>$id
//      ,flag=>0
//    ))->find();
//    if($buy){
//      $group = M('group')->where(array(id=>$buy['group_id']))->find();
//      //保存购买人信息
//      $buy_user = M('group_buy_user');
//      $buy_user_data = array(
//       buy_id => $id
//      ,sessionid => $user['openid']
//      ,name=>$_REQUEST['name']
//      ,phone=>$_REQUEST['phone']
//      ,address=>$_REQUEST['address']
//      ,headimgurl=>$_SESSION['group_user_info']['headimgurl']
//      ,leader=>0
//      );
//
//      $buy_user->data($buy_user_data)->add();
//      dump($buy_user);
//      return;
//
//      $this::saveAddress(array(sessionid=>$user['openid'],name=>$_REQUEST['name'],phone=>$_REQUEST['phone'],address=>$_REQUEST['address']));
//
//      $param = array(
//          name=>$group['name'],
//          number=>'1份',
//          expDate=>date('Y-m-d H:i:s'),
//          remark=>'您已经成功参加了**发起的团购',
//      );
//      //oW5r-vxP-UHzb7ffWvf7hbzuADQQ
//      weixin_exec(SENDTEMPLATE,sendYDMsg($param,$_SESSION['group_user_info']['openid']));
//      //send Message;
//
//
//      //判断是否组团成功,通知全员
//    }else{
//      echo 'error';
//    }
//
//    return;
  }

  public function success(){
    $group = M('group')->where(array(id=>$_REQUEST['id']))->find();

    $m = M('group_buy');
    $m->startTrans();
    $group_buy = array(
       group_id=>$_REQUEST['id']
      ,create_time=>date('Y-m-d H:i:s')
      ,flag=>0
      ,leader=>$_SESSION['group_user_info']['nickname']
      ,headimgurl=>$_SESSION['group_user_info']['headimgurl']
    );
    $group_by_id = $m->data($group_buy)->add();

    $m->commit();
    //保存购买人信息
    $buy_user = M('group_buy_user');
    $buy_user_data = array(
         buy_id => $group_by_id
        ,sessionid => $_SESSION['group_user_info']['openid']
        ,name=>$_SESSION['group_user_info']['nickname']
        ,phone=>$_REQUEST['phone']
        ,address=>$_REQUEST['address']
        ,headimgurl=>$_SESSION['group_user_info']['headimgurl']
        ,leader=>1
    );

    $buy_user->data($buy_user_data)->add();

    $this::saveAddress(array(sessionid=>$_SESSION['group_user_info']['openid'],name=>$_REQUEST['name'],phone=>$_REQUEST['phone'],address=>$_REQUEST['address']));

    $param = array(
        name=>$group['name'],
        number=>'1份',
        expDate=>date('Y-m-d H:i:s'),
        remark=>'您发起的团购已经成功,请联系小伙伴一起购买吧',
    );
    //oW5r-vxP-UHzb7ffWvf7hbzuADQQ
    weixin_exec(SENDTEMPLATE,sendYDMsg($param,$_SESSION['group_user_info']['openid']));

    $id = $_REQUEST['id'];
    $type = $_REQUEST['type'];
    $obj = M('group')->where(array(id=>$id))->find();
    $group_buy = M('group_buy')->where(array(id=>$group_by_id))->find();
    $group_buy_user = M('group_buy_user')->where(array(buy_id=>$group_buy['id'],leader=>0))->select();
    $this->assign('buy_user_count',count($group_buy_user));
    $this->assign('unbuy_user_count',($obj['person_number'] - count($group_buy_user)));
    $this->assign('item',$obj);
    $this->assign('group_buy',$group_buy);
    $this->assign('group_buy_user',$group_buy_user);
    $this->assign('wx', $this->wx->GetSignPackage());
    $this->assign('type',$type);

    $this->display();
  }

  public function getItem(){
    $itemsPer = $_REQUEST['itemsPer'];
    $lastIndex = $_REQUEST['lastIndex'];


    $m = M();
    $m->table('group')->field('*');

    $m2 = clone($m);
    $arr = $m2->limit(($lastIndex+1).','.$itemsPer)->select();

    $this->ajaxReturn(json_encode($arr));
  }

}

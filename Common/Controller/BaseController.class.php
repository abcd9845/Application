<?php

namespace Common\Controller;

use Think\Controller;

class BaseController extends Controller {

  protected $options = array();
  protected $session = array();
  protected $pageInfos = array();
  protected $accessInfos = array();
//  private $system_setting = array();


  public function _initialize() {
//    if($_SESSION['openid'] == null && I('request.openid') == null){
//      if(!(
//             (CONTROLLER_NAME == 'Index' && ACTION_NAME == 'index')
//          || (CONTROLLER_NAME == 'Index' && ACTION_NAME == 'wechatPage')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'lists')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'orderDetailForAliPay')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'orderDetail')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'callback')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'notify')
////          || (CONTROLLER_NAME == 'Order' && ACTION_NAME == 'notify')
//      )){
//        $this->redirect('Index/wechatPage');
//      }
//    }

    $config = S('DB_CONFIG_DATA');
    if (!$config) {
      $config = api('Config/lists');
      S('DB_CONFIG_DATA', $config);
    }
    C($config);
    $this->session = $_SESSION;


//    $m = M('system_setting');
//    $rel = $m->select();
//    $this->system_setting = $rel[0];

    $this::getSignUp();

  }

//  public function getHomePageShowFlag(){
//     return $this->system_setting["homepage_product"];
//  }

  public function getSignUp(){
    //return $this->system_setting["signup"];
//    $_SESSION['signup'] = $this->system_setting["signup"];
  }


  public function _before_display() {
    $this->assign('options', $this->options);
    $this->assign('session', $this->session);
    $this->assign('pageInfos', $this->pageInfos);
    $this->assign('accessInfos', $this->accessInfos);
  }

}

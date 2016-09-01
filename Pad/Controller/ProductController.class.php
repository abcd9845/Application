<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Controller;

use Common\Controller\BaseController;

class ProductController extends BaseController {

  public function basicType($arr) {
    $return_arr = array();
    for ($i = 0; $i < count($arr); $i++) {
      if ($arr[$i]['up_type_id'] == 0) {
        array_push($return_arr, $arr[$i]);
      }
    }
    return $return_arr;
  }

  public function secondType(&$base, $arr) {
    for ($i = 0; $i < count($base); $i++) {
      $child = array();
      for ($j = 0; $j < count($arr); $j++) {
        if ($base[$i]['id'] == $arr[$j]['up_type_id']) {
          array_push($child, $arr[$j]);
        }
      }
      $base[$i]['child'] = $child;
    }
  }

  public function index() {

    if($_GET['id']){
      $where = array(id=>$_GET['id']);
    }else{
      $where = array(id=>$_SESSION['school']['id']);
    }

    if(is_null($_GET['id']) && is_null($_SESSION['school']['id'])){
      $this->redirect('Index/index');
    }

    $school = M('school')->where($where)->find();


    if(I('request.vipcode') != ''){
      $disObj = M('company_discount')->where(array(id=>I('request.vipcode')))->find();

      if($disObj){
        $school['company_discount'] = $disObj['yh'];
      }
    }else{
      $school['company_discount_flag'] = 0;
    }


    $_SESSION['school'] = $school;
    $_SESSION['st'] = $school['type'];


    $typeList = D('Product')->typeList();

    if($school['happy'] == 1){
      $m = M('happy_time');
      $count = $m->where(array(
          "create_date" => array(array('egt', date('Y-m-d') . ' 00:00:00'), array('elt', date('Y-m-d') . ' 23:59:59'))
         ,"user_id" => $_SESSION['openid']
         ,"school_type" => $_SESSION['school']['type']
         ,"school_id" => $_SESSION['school']['id']
      ))->count();


//      dump($_SESSION['school']['id']);
      if($count == 0) {
//        $w['goods.delete_flag'] = 0;
        $w['storage_item.online'] = 0;
        $w['storage.school_id'] = $_SESSION['school']['id'];
        $w['storage_item.ishappy'] = 1;
        $w['storage.count'] = array('gt',0);
        $list = M('storage')
            ->field('
             storage.id
            ,storage_item.start_price
            ,storage_item.end_price
            ')
            ->join('storage_item on storage.id=storage_item.storage_id')
            ->join('basic_goods on basic_goods.id=storage.basic_id')
            ->where($w)
            ->order('RAND() LIMIT '.$_SESSION['school']['show_count'])
            ->select();

        foreach ($list as $k=>$v)
        {
          $list[$k]['happy_price'] = float_rand($v['start_price'],$v['end_price'],2);
        }

        foreach($list as $k=>$v){
          $dataList[] = array(
           'user_id'=>$_SESSION['openid']
          ,'goods_id'=>$v['id']
          ,'price'=>$v['happy_price']
          ,'school_type'=>$_SESSION['school']['type']
          ,'school_id'=>$_SESSION['school']['id']
          ,'create_date'=>date('Y-m-d H:i:s',time()));
        }

        $m->addAll($dataList);
      }

    }

    $this->assign('product_menu', $typeList);
    $this->display();
  }


  public function searchProduct() {
    $get = I('request.type');
    $happy = I('request.happy');
    $isnow = I('request.isnow');
    $goodsList = D('Product')->goodsList($get,$happy,$isnow);

//    $count = getHappyOrderCount();
//
//    if($count == 0){
//      $buy = 1;
//    }else{
//      $buy = 0;
//    }

    if($goodsList == 'happy_none'){
      $this->ajaxReturn("{status:'happy_none'}");
    }else{
      if (count($goodsList) > 0) {
        $this->ajaxReturn('{"status":"success","happy":'.$happy.',"data":'.json_encode($goodsList).'}');
      } else {
        $this->ajaxReturn("{status:'error'}");
      }
    }

  }

  public function selectCompany(){
    $this->assign('company', M('company_discount')->where(array('delete_flag'=>0))->select());
    $this->display();
  }


  public function info() {
    $get = I('get.id');
    $goods = D('Product')->getGoods($get);
    $spec = $goods['spec'];
    $goods['specArr'] = json_decode($spec, true);
    $this->assign('goods', $goods);
    $this->display();
  }

  public function carTotal(){
    $uid = $_SESSION['current_user']['id'];
    $goods_list = D('Car')->getCar($uid);
    return $goods_list['total_price'];
  }

  public function addCar() {
    $car['oper_user'] = $_SESSION['current_user']['id'];
    $car['gid'] = I('post.gid');
    $result = D('Car')->add($car);
    $total = $this::carTotal();
    if ($result) {
      $this->ajaxReturn('{"status":"success","total":'.$total.'}');
    } else {
      $this->ajaxReturn("{status:'error',total:0}");
    }
  }

  public function getCar() {
    $total = $this::carTotal();
    if ($total > 0) {
      $this->ajaxReturn('{"status":"success","total":'.$total.'}');
    } else {
      $this->ajaxReturn("{status:'error',total:0}");
    }
  }

  public function confirm(){
    $order_no = I('request.name');
    $po = M('order')->field('order.hdfk,order.delivery_address,order.expense,order.re_type,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where(array(
            'order.id'=>$order_no
//                ,'state_id' => array('in', array(2,3))
        ))->find();
    if($po['state_id'] == 0){
      $this->assign('result',null);
      $this->assign('msg',"订单已经取消");
    }

    if($po['state_id'] == 1){
      $this->assign('result',null);
      $this->assign('msg',"订单未付款");
    }

    if($po['state_id'] == 4){
      $this->assign('result',null);
      $this->assign('msg',"订单货物已经取走了!");
    }

    if($po['state_id'] == 2 || $po['state_id'] == 3 || $po['state_id'] == 5) {
      $this->assign('result', $po);
    }

    $this->display('pick');
  }

  public function save(){
    $order_no = I('request.hide_name');
    $po = M('order')->where(array(
        'order_no'=>$order_no
    ,'state_id' => array('in', array(2,3,5))
    ))->find();
    if($po == null){
      $this->assign('msg',"订单不存在!");
    }else{
      $re = M('order')->data(array(state_id => 4,confirmPerson => $_SESSION['openid']))->where(array(id => $po['id']))->save();
      if($re >=0 ){
        $this->assign('msg',"取货成功!");
      }else{
        $this->assign('msg',"操作失败!");
      }
    }

    $this->display('pick');


  }


  public function praiseconfirm(){
    $order_no = I('request.name');
    $phone = I('request.phone');
    $po = M('order')->field('order.id,order.re_type,order.delivery_address,order.expense,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where(array(
         'order.id'=>$order_no
        ,'user.mobile' => $phone
        ,'order.create_time'=>array(array('egt',C('PRAISE_START_TIME')),array('lt',C('PRAISE_END_TIME')))
        ))->find();
    if($po==null){
      $this->assign('result',null);
      $this->assign('msg',"订单不存在");
    }else if($po['state_id'] == 1){
      $this->assign('result',null);
      $this->assign('msg',"订单未付款");
    }else{
      $po_praise = M('praise')->where(array(order_id=>$order_no,phone=>$phone))->find();
      if($po_praise == null){
        $this->assign('result', $po);
        $this->assign('msg',"此订单符合此次活动规则");
      }else{
        $this->assign('result',null);
        $this->assign('msg',"此订单已经参加过活动了");
      }
    }

    $this->display('praise');
  }

  public function praisesave(){
    $order_no = I('request.hide_name');
    $phone = I('request.hide_phone');

    $po_praise = M('praise')->where(array(order_id=>$order_no,phone=>$phone))->find();
    if($po_praise != null){
      $this->assign('msg',"请不要重复操作");
    }else{
      $po = M('order')->field('order.id,order.re_type,order.delivery_address,order.expense,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
          ->join('left join user on user.username = order.purchaser')
          ->join('left join school_address on school_address.id = order.address_id')
          ->where(array(
           'order.id'=>$order_no
          ,'user.mobile'=>$phone
          ,'order.create_time'=>array(array('egt',C('PRAISE_START_TIME')),array('lt',C('PRAISE_END_TIME')))
          ,'state_id' => array('in', array(2,3,4))
          ))->find();

      if($po == null){
        $this->assign('msg',"订单不存在!");
      }else{
        $re = M('praise')->add(array(
            order_no=>$po['order_no'],
            order_id=>$po['id'],
            phone=>$phone,
            operator_time=>date('Y-m-d H:i:s',time()),
            operator_id=>$_SESSION['current_user']['username']
        ));
        if($re >=0 ){
          $this->assign('msg',"操作成功!");
        }else{
          $this->assign('msg',"操作失败!");
        }
      }
    }



    $this->display('praise');


  }

  public function cancelOrder(){
    $order_no = I('request.name');
    $po = M('order')->field('order.hdfk,order.delivery_address,order.expense,order.re_type,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where(array(
            'order.id'=>$order_no
        ,'order.state_id'=>5
        ))->find();




    if($po['state_id'] ==  5) {
      $this->assign('result', $po);
    }

    $this->display('cancelOrder');
  }

  public function cancelOrdersave(){
    $order_no = I('request.hide_name');
    $po = M('order')->where(array(
         'order_no'=>$order_no
        ,'state_id' => 5
    ))->find();
    if($po == null){
      $this->assign('msg',"订单不存在!");
    }else{
      $re = M('order')->data(array(state_id => 0,cancelPerson=> $_SESSION['openid']))->where(array(id => $po['id']))->save();
      if($re >=0 ){
        $this->assign('msg',"订单已经取消");
      }else{
        $this->assign('msg',"操作失败!");
      }
    }

    $this->display('cancelOrder');
  }


}

<?php

namespace Pad\Controller;

use Common\Controller\BaseController;

class AlipayController extends BaseController {

  public function submit() {
    if (IS_POST) {
      //页面上通过表单选择在线支付类型，支付宝为alipay 财付通为tenpay
      $paytype = 'alipay';
      $pay = new \Think\Pay($paytype, C('payment.' . $paytype));
      $order_no = 'GS0000001';
      $vo = new \Think\Pay\PayVo();
      $vo->setBody("商品描述")
        ->setFee(0.1) //支付金额
        ->setOrderNo($order_no)
        ->setTitle('绿色蔬菜')
        ->setCallback("Alipay/payback")
        ->setUrl(U("Home/User/order")) //修改
        ->setParam(array('order_no' => 'GS0000001'));
      echo $pay->buildRequestForm($vo);

    }
  }

  public function payback($money, $param) {
    dump($param);
    dump($money);
    if (session("pay_verify") == true) {
      session("pay_verify", null);
      //处理goods1业务订单、改名good1业务订单状态
      //M("Goods1Order")->where(array('order_id' => $param['order_id']))->setInc('haspay', $money);你说的是这个？

      $result = D('Order')->changeState($param['order_no'], 5);
      $this->success('付款成功', U('Order/orderPay?orderno=' . $param['order_no']), 3);
    } else {
      //E("Access Denied");
      $this->error('付款失败');
    }
  }

  public function notify() {
    $apitype = I('get.apitype');
    $pay = new \Think\Pay($apitype, C('payment.' . $apitype));
    if (IS_POST && !empty($_POST)) {
      $notify = $_POST;
    } elseif (IS_GET && !empty($_GET)) {
      $notify = $_GET;
      unset($notify['method']);
      unset($notify['apitype']);
    } else {
      exit('Access Denied');
    }
    //验证
    if ($pay->verifyNotify($notify)) {
      //获取订单信息
      $info = $pay->getInfo();
      if ($info['status']) {
        $result = D('Order')->changeState($info['out_trade_no'], 5);
        if (I('get.method') == "return") {
          //ajax返回
        } else {
          //通知返回
        }
        $this->display("Order/paySuccess");
      } else {
        $this->error("支付失败！");
      }
    } else {
      E("Access Denied");
    }
  }

}

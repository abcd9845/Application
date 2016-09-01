<?php
namespace Admin\Controller;
use Think\Controller;

class CancelOrderController extends Controller {

    public function index(){
        $this->display();
    }

    public function confirm(){
        $order_no = I('request.name');
        $po = M('order')->field('order.hdfk,order.delivery_address,order.expense,order.re_type,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
            ->join('left join user on user.username = order.purchaser')
            ->join('left join school_address on school_address.id = order.address_id')
            ->where(array(
                'order_no'=>$order_no
                ,'state_id'=>5
            ))->find();


        if($po['state_id'] ==  5) {
            $this->assign('result', $po);
        }

        $this->display('index');
    }

    public function save(){
        $order_no = I('request.hide_name');
        $po = M('order')->where(array(
                'order_no'=>$order_no
                ,'state_id' => 5
            ))->find();
        if($po == null){
            $this->assign('msg',"订单不存在!");
        }else{
            $re = M('order')->data(array(state_id => 0,cancelPerson=> $_SESSION['current_user']['username']))->where(array(id => $po['id']))->save();
            if($re >=0 ){
                $this->assign('msg',"订单已取消");
            }else{
                $this->assign('msg',"操作失败!");
            }
        }

        $this->display('index');


    }


}
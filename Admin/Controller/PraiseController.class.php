<?php
namespace Admin\Controller;
use Think\Controller;

class PraiseController extends Controller {

    public function index(){
        $this->display();
    }

    public function confirm(){
        $order_no = I('request.name');
        $phone = I('request.phone');
        $po = M('order')->field('order.re_type,order.delivery_address,order.expense,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
            ->join('left join user on user.username = order.purchaser')
            ->join('left join school_address on school_address.id = order.address_id')
            ->where(array(
                'order_no'=>$order_no
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
            $po_praise = M('praise')->where(array(order_no=>$order_no,phone=>$phone))->find();
            if($po_praise == null){
                $this->assign('result', $po);
                $this->assign('msg',"此订单符合此次活动规则");
            }else{
                $this->assign('result',null);
                $this->assign('msg',"此订单已经参加过活动了");
            }
        }

        $this->display('index');
    }

    public function save(){
        $order_no = I('request.hide_name');
        $phone = I('request.hide_phone');

        $po_praise = M('praise')->where(array(order_no=>$order_no,phone=>$phone))->find();
        if($po_praise != null){
            $this->assign('msg',"请不要重复操作");
        }else{
            $po = M('order')->field('order.id,order.re_type,order.delivery_address,order.expense,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
                ->join('left join user on user.username = order.purchaser')
                ->join('left join school_address on school_address.id = order.address_id')
                ->where(array(
                    'order_no'=>$order_no
                ,'user.mobile'=>$phone
                ,'order.create_time'=>array(array('egt',C('PRAISE_START_TIME')),array('lt',C('PRAISE_END_TIME')))
                ,'state_id' => array('in', array(2,3,4))

                ))->find();

            if($po == null){
                $this->assign('msg',"订单不存在!");
            }else{
                $re = M('praise')->add(array(
                    order_no=>$order_no,
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

        $this->display('index');


    }


}
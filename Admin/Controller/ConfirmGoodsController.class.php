<?php
namespace Admin\Controller;
use Think\Controller;

class ConfirmGoodsController extends Controller {

    public function index(){
//        $goods_type = M('goods_type');
//        $goods_type_result = $goods_type->where(' delete_flag = 0 and up_type_id = 0')->select();
//        $this->assign('goods_type',$goods_type_result);
//        $filter = array();
//        $where = ' goods.delete_flag = 0 ';
//        wrapper_sql_where_lk($where,'goods.name','filter_name',$_REQUEST,$filter);
////        wrapper_sql_where($where,'goods.goods_parent_type','filter_goods_type',$_REQUEST,'string',$filter);
//        $this->assign('filter',$filter);
//        $m = M();
//        $m->table('basic_goods as goods')->field('goods.id,goods.title_pic,goods.name')
////        ->join('left join goods_type on goods.goods_parent_type = goods_type.id')
//        ->where($where)
//        ->order('goods.oper_time desc');
//        $m1 = clone($m);
//        $m2 = clone($m);
//
//        $count = $m1->count();
//        $page = new \Think\Page($count,10);
//        set_page_param($page,'name','filter_name',$_REQUEST);
//        set_page_param($page,'goods_type','filter_goods_type',$_REQUEST);
//        $page->show();
//        $show = $page->show();
//        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();
//        $this->assign('array',$arr);
//        $this->assign('show',$show);
        $this->display();
    }

    public function confirm(){
        $order_no = I('request.name');
        $po = M('order')->field('order.hdfk,order.delivery_address,order.expense,order.re_type,order.state_id,order.pick_no,order.pick_time,user.real_name,order.goods_items,order.total,order.discount,order.order_no,order.create_time,user.mobile,school_address.address')
            ->join('left join user on user.username = order.purchaser')
            ->join('left join school_address on school_address.id = order.address_id')
            ->where(array(
                'order_no'=>$order_no
//                ,'state_id' => array('in', array(2,3))
            ))->find();

        if($po['state_id'] == 0){
            $this->assign('result',null);
            $this->assign('msg',"订单已取消");
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

        $this->display('index');
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
            $re = M('order')->data(array(state_id => 4,confirmPerson=> $_SESSION['current_user']['username']))->where(array(id => $po['id']))->save();
            if($re >=0 ){
                $this->assign('msg',"取货成功!");
            }else{
                $this->assign('msg',"操作失败!");
            }
        }

        $this->display('index');

    }


}
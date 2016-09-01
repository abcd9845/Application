<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Common\CommonTool;
use Common\Controller\BaseController;
class OrderController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $state = M('order_state');
        $state_result = $state->select();
        $this->assign('stateArray',$state_result);

        $filter = array();
        $where = ' order.delete_flag = 0 ';
        wrapper_sql_where_lk($where,'order.order_no','order_no',$_REQUEST,$filter);
//        wrapper_sql_where($where,'address.recipient','recipient',$_REQUEST,'string',$filter);
        wrapper_sql_where($where,'address.phone','phone',$_REQUEST,'string',$filter);
        wrapper_sql_where($where,'order.state_id','state',$_REQUEST,'num',$filter);

        $this->assign('filter',$filter);

        $m = M();
        $m->table('order')->field('order.id,school_address.address,user.mobile,order.order_no,order_state.state,order.remark ')
            ->join('left join order_state on order_state.id = order.state_id')
            ->join('left join school_address on school_address.id = order.address_id')
            ->join('left join user on user.username = order.purchaser')
            ->where($where);
        $m1 = clone($m);
        $m2 = clone($m);
        $count = $m1->count();
        $page = new \Think\Page($count,10);
        set_page_param($page,'order_no','order_no',$_REQUEST);
//        set_page_param($page,'recipient','recipient',$_REQUEST);
        set_page_param($page,'phone','phone',$_REQUEST);
        set_page_param($page,'state','state',$_REQUEST);
        $page->show();
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->order('order.oper_time desc')->select();

        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toView(){
        $m = M();
        $id = $_REQUEST['id'];
        $arr = $m->table('order')->field('order.id,school_address.address,order.express_no,user.mobile,order.order_no,order_state.state,order.remark,order.total,order.goods_items,order.discount')
            ->join('left join user on user.username = order.purchaser')
//            ->join('left join user as expressUser on expressUser.id = order.express')
//            ->join('left join express on express.id = expressUser.express_id')
            ->join('left join order_state on order_state.id = order.state_id')
            ->join('left join school_address on school_address.id = order.address_id')
            ->where('order.id = '.$id)
            ->select();
//        $item_m = M('order_item');
//        $order_no = $_REQUEST['order_no'];
//        $itemArray = $item_m->where('order_no = \''.$order_no.'\'')->select();
        $this->assign('t',$arr[0]);
//        $itemArray[0]['goods_items'] = id2Name($itemArray[0]);
//        $this->assign('array',$itemArray[0]);

//        $sn = getExpress_no($arr[0]['express_no']);
//        $this->assign('orderURL', $sn);

        $this->display();
    }

}
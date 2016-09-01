<?php
namespace Admin\Controller;
use Think\Controller;

class VendorOrderUnGoController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $filters = array(
            order_no=>'',
            phone=>'',
            state_id=>'',
            re_type=>'',
            school_type=>'',
            school=>'',
            address=>'',
            address_child=>'',
            create_time_start=>'',
            create_time_end=>'',
            pick_time_start=>'',
            pick_time_end=>'',
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        if($_SESSION[SESSION_USER]['admin_school'] != ZONGCANG){
            $this->assign('school',M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select());
        }

        $where = array();
        add_where('order.delete_flag',0,$where);
        add_where('order.state_id',array(1,2),$where,'in');
        add_where('order.order_no',$filters['order_no'],$where,'like');
        add_where('user.mobile',$filters['phone'],$where);
        add_where('order.state_id',$filters['state_id'],$where);
        add_where('order.re_type',$filters['re_type'],$where);

        if($filters['create_time_start']!=''&&$filters['create_time_end']!=''){
            add_where('order.create_time',($filters['create_time_start'].'|'.$filters['create_time_end']),$where,'d2d');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('order.create_time',$filters['create_time_start'],$where,'egt');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('order.create_time',$filters['create_time_end'],$where,'elt');
        }

        if($filters['pick_time_start']!=''&&$filters['pick_time_end']!=''){
            add_where('order.pick_time',($filters['pick_time_start'].'|'.$filters['pick_time_end']),$where,'d2d');
        }else if($filters['pick_time_start']!=''&&$filters['pick_time_end']==''){
            add_where('order.pick_time',$filters['create_time_start'],$where,'egt');
        }else if($filters['pick_time_start']!=''&&$filters['create_time_end']==''){
            add_where('order.pick_time',$filters['pick_time_end'],$where,'elt');
        }

        //自提
        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if($filters['re_type'] == 0) {
                add_where('school.type',$filters['school_type'],$where);
                add_where('school.id',$filters['school'],$where);
                add_where('order.address_id',$filters['address'],$where);
            }else if($filters['re_type'] == 1){
                add_where('school.type',$filters['school_type'],$where);
                add_where('school.id',$filters['school'],$where);
                add_where('address.level',$filters['address'],$where);
                add_where('address.id',$filters['address_child'],$where);
            }
        }else{
            if($filters['re_type'] == 0) {
                add_where('school.id',$_SESSION[SESSION_USER]['admin_school'],$where);
                add_where('order.address_id',$filters['address'],$where);
            }else if($filters['re_type'] == 1){
                add_where('school.id',$_SESSION[SESSION_USER]['admin_school'],$where);
                add_where('address.level',$filters['address'],$where);
                add_where('address.id',$filters['address_child'],$where);
            }
        }

            $m = M();
            $m->table('order')->field('order.delivery_time_txt,order.re_type,order.delivery_address,order.pick_time,order.create_time,order.happy_order,order.id,school_address.address,user.mobile,order.order_no,order_state.state ')
                ->join('left join order_state on order_state.id = order.state_id')
                ->join('left join school_address on school_address.id = order.address_id')
                ->join('left join address as address on address.id = order.delivery_address_id')
                ->join('left join user on user.username = order.purchaser')
                ->join('left join school on school.id = order.school_id')
                ->where($where);

            $m1 = clone($m);
            $m2 = clone($m);

            $count = $m1->count();
            $page = new \Think\Page($count,PAGECOUNT);
            add_param($page->parameter,$filters);
            set_page_param($page,'address',$_REQUEST);

            $show = $page->show();
            $arr = $m2->limit($page->firstRow.','.$page->listRows)->order('order.oper_time desc')->select();
            foreach ($arr as $k=>$v)
            {
                $arr[$k]['happy_price'] = float_rand($v['start_price'],$v['end_price'],2);
            }
            $this->assign('array',$arr);
            $this->assign('show',$show);
            $this->display();

    }

    public function changeVal(){
        $m = M('school_');
        $array = $m->where($this->common_selet.' and role_id = 4 and express_id = '.$_REQUEST['expressID'])->find();
        $this->ajaxReturn($array);
    }

    public function change(){
        $log = M('order_log');
        $logData = array();
        $logData['order_no'] = $_REQUEST['no'];
        $logData['state_id'] = 3;
        $logData['oper_user'] = $_SESSION['current_user']['id'];
        $logData['oper_time'] = date('Y-m-d H:i:s',time());


        $m = M('order');
        $m->startTrans();

        $data['id'] = $_REQUEST['id'];
//        $data['express'] = $_REQUEST['express'];
//        $data['delivery_user'] = $_REQUEST['user'];
//        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $data['state_id'] = 3;
        $data['express_no'] = $_REQUEST['express_no'];
        $result1 = $m->save($data);
        $result2 = $log->data($logData)->add();

        if($result1 && $result2){
            $m->commit();
            $this->success('修改成功','index');
        }else{
            $m->rollback();
            $this->error('修改失败','index');
        }

    }

    public function toView(){
        $m = M();
        $id = $_REQUEST['id'];
        $this->assign('id',$id);

        $arr = $m->table('order')->field('order.pick_time,order.expense,order.delivery_time_txt,order.re_type,order.delivery_address,order.id,school_address.address,order.express_no,user.mobile,order.order_no,order_state.state,order.remark,order.total,order.goods_items,order.discount')
            ->join('left join user on user.username = order.purchaser')
            ->join('left join order_state on order_state.id = order.state_id')
            ->join('left join school_address on school_address.id = order.address_id')
            ->where('order.id = '.$id)
            ->select();


//        $item_m = M('order_item');
//        $order_no = $_REQUEST['order_no'];
//        $itemArray = $item_m->where('order_no = \''.$order_no.'\'')->select();

        $express = M('school');
        $expressArray = $express->select();


        $this->assign('t',$arr[0]);
//        $itemArray[0]['goods_items'] = id2Name($itemArray[0]);
//        $this->assign('array',$itemArray[0]);
        $this->assign('expressArray',$expressArray);

        $this->display();
    }

    public function execWhere($origWhere){

    }


}
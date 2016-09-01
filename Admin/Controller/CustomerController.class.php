<?php
namespace Admin\Controller;
use Think\Controller;
class CustomerController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $array = D('Customer')->getCustomerPaging();
        $this->assign('array',$array['list']);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    public function add(){
        $m = M('customer');

        $data['name'] = $_REQUEST['name'];

        $data['age'] = $_REQUEST['age'];

        $data['sex'] = $_REQUEST['sex'];

        $data['phone'] = $_REQUEST['phone'];

        $data['description'] = $_REQUEST['description'];

        //$data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_user'] = 1;

        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $m->data($data)->add();

        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $customer = M('customer')->where("id = ".$id)->find();
        $this->assign('obj',$customer);
        $this->display();
    }

    public function edit(){
        $m = M('customer');

        $data['name'] = $_REQUEST['name'];

        $data['age'] = $_REQUEST['age'];

        $data['sex'] = $_REQUEST['sex'];

        $data['phone'] = $_REQUEST['phone'];

        $data['descrption'] = $_REQUEST['descrption'];

//        $data['oper_user'] = $_SESSION['current_user']['id'];;
        $data['oper_user'] = 1;

        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $data['id'] = $_REQUEST['id'];

        $m->save($data);
        $this->success('修改成功','index');
    }

    public function del(){
        $m = M('user');
        $data['delete_flag'] = 1;
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);
        $this->success('删除成功','index');
    }

    public function getUserCount(){
        $m = M('user');
        $array = $m->where($_REQUEST['field']." = '".$_REQUEST['val']."' and delete_flag = 0")->count();
        $this->ajaxReturn($array);
    }

    public function getUserEditCount(){
        $m = M('user');
        $array = $m->where($_REQUEST['field']." = '".$_REQUEST['val']."' and delete_flag = 0 and id != ".$_REQUEST['id'])->count();
        $this->ajaxReturn($array);
    }



}
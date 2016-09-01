<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $m = M();
        $m->table('user')->field('user.id,user.username,user.real_name,role.name as type,user.oper_time,school.name')
            ->join('left join role on role.id = user.role_id')
            ->join('left join school on school.id = user.admin_school')
            ->where('user.delete_flag = 0 and user.role_id <> 2');
        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,25);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
//        $express_m = M('express');
//        $vip_m = M('vip');
//        $supplier_m = M('supplier');
//        $role_m = M('role');
//        $this->assign('express',$express_m->where($this->common_selet)->select());
//        $this->assign('vip',$vip_m->where($this->common_selet)->select());
//        $this->assign('supplier',$supplier_m->where($this->common_selet)->select());
//        $this->assign('role',$role_m->select());
        $school = M('school')->where(array(delete_flag => 0,state => 1))->select();
        $this->assign('school',$school);
        $this->display();
    }

    public function add(){
        $m = M('user');

        $data['username'] = $_REQUEST['username'];

        $data['password'] = $_REQUEST['password'];

        $data['admin_school'] = urlencode($_REQUEST['admin_school']);

        $data['real_name'] = urlencode($_REQUEST['real_name']);

        $data['mail'] = $_REQUEST['mail'];

//        $data['mobile'] = $_REQUEST['mobile'];

        $data['status'] = $_REQUEST['status']==''? 0:$_REQUEST['status'];

//        $data['role_id'] = I('request.role_id');
        if(I('request.admin_school') == ZONGCANG){
            $data['role_id'] = 1;
        }else{
            $data['role_id'] = 3;
        }

//        switch($data['role_id']){
//            case 1:  case 2:
//                break;
//            case 3:
//                $data['vip_id'] = $_REQUEST['vip_id'];
//                break;
//            case 4:
//                $data['express_id'] = $_REQUEST['express_id'];
//                break;
//            case 5:
//                $data['supplier_id'] = $_REQUEST['supplier_id'];
//                break;
//        }

        $data['oper_user'] = $_SESSION['current_user']['id'];

        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $m->data($data)->add();

        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $m = M('user');
        $school = M('school')->where(array(delete_flag => 0,state => 1))->select();
        $this->assign('school',$school);
//        $express_m = M('express');
//        $vip_m = M('vip');
//        $supplier_m = M('supplier');
//        $role_m = M('role');
//        $this->assign('express',$express_m->where($this->common_selet)->select());
//        $this->assign('vip',$vip_m->where($this->common_selet)->select());
//        $this->assign('supplier',$supplier_m->where($this->common_selet)->select());
//        $this->assign('role',$role_m->select());
        $array = $m->where("id = ".$id)->select();
        $this->assign('obj',$array[0]);
        $this->display();
    }

    public function edit(){
        $m = M('user');

//        $data['username'] = $_REQUEST['username'];

        $data['password'] = $_REQUEST['password'];

        $data['admin_school'] = urlencode($_REQUEST['admin_school']);

        $data['real_name'] = urlencode($_REQUEST['real_name']);

        $data['mail'] = $_REQUEST['mail'];

//        $data['mobile'] = $_REQUEST['mobile'];

        $data['status'] = $_REQUEST['status']==''? 0:$_REQUEST['status'];

//        $data['role_id'] = I('request.role_id');

//        switch($data['role_id']){
//            case 1:  case 2:
//            break;
//            case 3:
//                $data['vip_id'] = $_REQUEST['vip_id'];
//                break;
//            case 4:
//                $data['express_id'] = $_REQUEST['express_id'];
//                break;
//            case 5:
//                $data['supplier_id'] = $_REQUEST['supplier_id'];
//                break;
//        }

        $data['oper_user'] = $_SESSION['current_user']['id'];;

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
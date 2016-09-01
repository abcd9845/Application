<?php
namespace Admin\Controller;
use Think\Controller;
class BranchController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $array = D('school')->getCustomerPaging();
        $this->assign('array',$array['list']);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    public function add(){
        $m = M('school');

        $data['name'] = $_REQUEST['name'];

        $data['type'] = $_REQUEST['type'];

        //99 总店
        //1 分店
        //2 代理
        $data['state'] = $_REQUEST['state'];

        $data['delete_flag'] = 0;

        $m->data($data)->add();

        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $school = M('school')->where("id = ".$id)->find();
        $this->assign('obj',$school);
        $this->display();
    }

    public function edit(){
        $m = M('customer');

        $data['name'] = $_REQUEST['name'];

        $data['type'] = $_REQUEST['type'];

        //99 总店
        //1 分店
        //2 代理
//        $data['state'] = $_REQUEST['state'];

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
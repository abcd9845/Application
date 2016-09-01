<?php
namespace Admin\Controller;
use Think\Controller;
class ExpressController extends Controller {
    public $common_selet = 'delete_flag = 0';
    public function index(){
        $m = M('express');
        $count = $m->where($this->common_selet)->count();
        $page = new \Think\Page($count,25);
        $show = $page->show();
        $arr = $m->where($this->common_selet)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    public function add(){
        $m = M('express');
        $data['express_name'] = $_REQUEST['express_name'];
        $m->data($data)->add();
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $m = M('express');
        $array = $m->where("id = ".$id)->select();
        $this->assign('obj',$array[0]);
        $this->display();
    }

    public function edit(){
        $m = M('Supplier');
        $data['express_name'] = $_REQUEST['express_name'];
        $data['id'] = $_REQUEST['id'];
        $m->save($data);
        $this->success('修改成功','index');
    }

    public function del(){
        $m = M('express');
        $data['delete_flag'] = 1;
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);
        $this->success('删除成功','index');
    }

}
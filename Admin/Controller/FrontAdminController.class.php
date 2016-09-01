<?php
namespace Admin\Controller;
use Think\Controller;

class FrontAdminController extends Controller {

    public function index(){
        $filter = array();
        $where = ' delete_flag = 0 and front_admin=1 and role_id = 2 and status = 0';
        $m = M('user')
        ->where($where);

        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,50);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    public function add(){
        $m = M('user')->where(array(mobile=>I('request.mobile')))->find();
        if($m == null){
            $this->error('用户不存在','toAdd');
        }else{
            $m['front_admin'] = 1;
            M('user')->data($m)->save();
            $this->success('添加成功','index');
        }
    }

    public function del(){
        $m = M('user')->where(array(id=>I('request.id')))->find();
        if($m == null){
            $this->error('用户不存在','index');
        }else{
            $m['front_admin'] = 0;
            M('user')->data($m)->save();
            $this->success('删除成功','index');
        }
    }


}
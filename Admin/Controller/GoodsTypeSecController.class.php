<?php
namespace Admin\Controller;
use Think\Controller;
class GoodsTypeSecController extends Controller {
    public $common_selet = 'delete_flag = 0 and up_type_id <> 0';
    public function index(){
        $m = M();
        $m->table('goods_type as child')->field('child.id,child.type_name,father.type_name as f_name ')
            ->join('left join goods_type as father on child.up_type_id = father.id')
            ->where('child.delete_flag = 0 and child.up_type_id <> 0');
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
        $type = M('goods_type');
        $this->assign('goods_type',$type->where('delete_flag = 0 and up_type_id = 0')->select());
        $this->display();
    }

    public function add(){
        $m = M('goods_type');
        $data['type_name'] = $_REQUEST['type_name'];
        $data['up_type_id'] = $_REQUEST['up_type_id'];;
        $m->data($data)->add();
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $m = M('goods_type');
        $array = $m->where("id = ".$id)->select();
        $this->assign('obj',$array[0]);
        $type = M('goods_type');
        $this->assign('goods_type',$type->where('delete_flag = 0 and up_type_id = 0')->select());
        $this->display();
    }

    public function edit(){
        $m = M('goods_type');
        $data['type_name'] = $_REQUEST['type_name'];
        $data['up_type_id'] = $_REQUEST['up_type_id'];;
        $data['id'] = $_REQUEST['id'];
        $m->save($data);
        $this->success('修改成功','index');
    }

    public function del(){
        $m = M('goods_type');
        $data['delete_flag'] = 1;
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);
        $this->success('删除成功','index');
    }

}
<?php
namespace Admin\Controller;
use Think\Controller;
class GoodsTypeController extends Controller {
    public $common_selet = 'goods_type.delete_flag = 0 and goods_type.up_type_id = 0';
    public function index(){
        $m = M();
        $m->table('goods_type')->field('goods_type.id,goods_type.type_name,school.name,school.type')
            ->join('left join school on school.id= goods_type.school_id')
            ->where($this->common_selet)->order('school.name,goods_type.srt');
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
        $this->display();
    }

    public function add(){
        $m = M('goods_type');
        $data['type_name'] = I('request.type_name');
        $data['school_id'] = I('request.school');
        $data['up_type_id'] = 0;
        $m->data($data)->add();
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $array = M('goods_type')->field('goods_type.id,goods_type.type_name,school.name,school.type,school.id as school_id')
            ->join('left join school on school.id = goods_type.school_id')->where("goods_type.id = ".$id)->find();
        $this->assign('obj',$array);
        $this->display();
    }

    public function edit(){
        $m = M('goods_type');
        $data['type_name'] = I('request.type_name');
        $data['school_id'] = I('request.school');
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

    public function changeSchool(){
        $m = M('school');
        $array = $m->where('state = 1 and type = '.I('request.school_id'))->select();
        $this->ajaxReturn($array);
    }

}
<?php
namespace Admin\Controller;
use Think\Controller;
class DeliveryController extends Controller {
//    private $where = 'school.type = 1';
    public function index(){
        $m = M();
        $m->table('delivery')->field('delivery.id,delivery.name as address,school.name,delivery.expense')
            ->join('left join school on delivery.address = school.id');
//            ->where($this->where);
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
        $type = M('school');
        $this->assign('school',$type->select());
        $this->display();
    }

    public function add(){
        $m = M('delivery');
        $data['address'] = I('request.address');
        $data['name'] = I('request.name');
        $data['expense'] = I('request.expense');
        $m->data($data)->add();
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = I('request.id');
        $this->assign('id',$id);
        $m = M('delivery');
        $array = $m->where("id = ".$id)->select();
        $this->assign('obj',$array[0]);
        $type = M('school');
        $this->assign('school',$type->select());
        $this->display();
    }

    public function edit(){
        $m = M('delivery');
        $data['address'] = I('request.address');
        $data['name'] = I('request.name');
        $data['expense'] = I('request.expense');
        $data['id'] = I('request.id');
        $m->save($data);
        $this->success('修改成功','index');
    }

    public function del(){
        $m = M('delivery');
        $m->where('id in ('.$_REQUEST['id'].')')->delete();
        $this->success('删除成功','index');
    }

}
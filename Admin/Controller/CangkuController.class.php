<?php
namespace Admin\Controller;
use Think\Controller;
class StorageController extends Controller {

    public function index(){
        $m = M();
        $m->table('cangku')->field('cangku.id,cangku.count,basic_goods.name,school.name as school_name')
            ->join('left join school on storage.school_id = school.id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id');
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
        $type = M('basic_goods_type');
        $this->assign('basic_type',$type->where(array('delete_flat'=>0))->select());
        $this->display();
    }

    public function add(){
        $storage['basic_id'] = I('request.goods_name');
        $storage['count'] = I('request.inventory');
        $storage['school_id'] = ZONGCANG;

        $ruku['type'] = RUKU;
        $ruku['school_id'] = ZONGCANG;
        $ruku['goods_id'] = I('request.goods_name');
        $ruku['count'] = I('request.inventory');
        $ruku['oper_user'] = $_SESSION[SESSION_USER]['id'];
        $ruku['oper_time'] = date('Y-m-d H:i:s',time());

        D('Storage')->ruku($storage,$ruku);

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
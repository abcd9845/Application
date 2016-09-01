<?php
namespace Admin\Controller;
use Think\Controller;
class StorageController extends Controller {

    public function index(){
        $this->assign('school',D('school')->getSchoolList());
        $this->assign('basic_type',M('basic_goods_type')->where(array('delete_flag'=>0))->select());

        $filters = array(
            basic_name=>'',
            basic_type=>'',
            school=>''
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $where = array();
        add_where('storage.school_id',$filters['school'],$where);
        add_where('basic_goods.name',$filters['basic_name'],$where,'like');
        add_where('basic_goods_type.id',$filters['basic_type'],$where);

        $m = M();
        $m->table('storage')->field('storage.id,storage.count,basic_goods.name,school.name as school_name,basic_goods_type.basic_type')
            ->join('left join school on storage.school_id = school.id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->join('left join basic_goods_type on basic_goods.goods_basic_type = basic_goods_type.id')
            ->where($where);
        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,25);
        add_param($page->parameter,$filters);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $type = M('basic_goods_type');
        $this->assign('basic_type',$type->where(array('delete_flag'=>0))->select());
        $this->display();
    }

    public function add(){
//        $array = [326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356];
//        for($i=0;$i<count($array);$i++){
//            $storage['basic_id'] = $array[$i];
//            $storage['count'] = 100;
//            $storage['school_id'] = ZONGCANG;
//
//            $ruku['type'] = RUKU;
//            $ruku['school_id'] = ZONGCANG;
//            $ruku['goods_id'] = $array[$i];
//            $ruku['count'] = 100;
//            $ruku['oper_user'] = $_SESSION[SESSION_USER]['id'];
//            $ruku['oper_time'] = date('Y-m-d H:i:s',time());
//
//            D('Storage')->ruku($storage,$ruku);
//        }
        $storage['basic_id'] = I('request.goods_name');
        $storage['count'] = I('request.inventory');
        $storage['school_id'] = ZONGCANG;

        $ruku['type'] = RUKU;
        $ruku['school_id'] = ZONGCANG;
        $ruku['goods_id'] = I('request.goods_name');
        $ruku['count'] = I('request.invxentory');
        $ruku['oper_user'] = $_SESSION[SESSION_USER]['id'];
        $ruku['oper_time'] = date('Y-m-d H:i:s',time());

        D('Storage')->ruku($storage,$ruku);
//
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
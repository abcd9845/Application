<?php
namespace Admin\Controller;
use Think\Controller;
class SchoolController extends Controller {

    public function index(){
        $m = M('school');
        $count = $m->where(array('delete_flag' => 0,type =>array('neq',99)))->count();
        $page = new \Think\Page($count,25);
        $show = $page->show();
        $arr = $m->where(array('delete_flag' => 0,type =>array('neq',99)))->limit($page->firstRow.','.$page->listRows)->select();
        foreach($arr as $k => $v){
            $arr[$k]['lirun'] = $arr[$k]['lirun']*100;
        }
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    public function add(){
        $m = M('school');
        $data['type'] = I('request.type');
        $data['name'] = I('request.name');
        $data['expense'] = I('request.expense');
        $data['state'] = I('request.state');
        $data['happy'] = I('request.happy');
        $data['isnow'] = I('request.isnow');
        $data['is_delivery'] = I('request.is_delivery');
        $data['man'] = I('request.man');
        $data['jian'] = I('request.jian');
        $data['deli_menu'] = I('request.deli_menu');
        $data['lirun'] = I('request.lirun');
        $data['tactics'] = I('request.tactics');

        if($data['happy'] == 0){
            $data['show_count'] = 0;
        }else{
            $data['show_count'] = I('request.show_count');
        }

        $data['delete_flag'] = 0;
        $m->data($data)->add();
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = I('request.id');
        $this->assign('id',$id);
        $m = M('school');
        $array = $m->where("id = ".$id)->select();
        $this->assign('obj',$array[0]);
        $this->display();
    }

    public function edit(){
        $m = M('school');
        $data['type'] = I('request.type');
        $data['name'] = I('request.name');
        $data['expense'] = I('request.expense');
        $data['state'] = I('request.state');
        $data['id'] = I('request.id');
        $data['happy'] = I('request.happy');
        $data['isnow'] = I('request.isnow');
        $data['is_delivery'] = I('request.is_delivery');
        $data['man'] = I('request.man');
        $data['jian'] = I('request.jian');
        $data['deli_menu'] = I('request.deli_menu');
        $data['lirun'] = I('request.lirun');
        $data['tactics'] = I('request.tactics');

        if($data['happy'] == 0){
            $data['show_count'] = 0;
        }else{
            $data['show_count'] = I('request.show_count');
        }

        $m->save($data);
        $this->success('修改成功','index');
    }

    public function del(){
        $m = M('school');
        $data['delete_flag'] = 1;
        $m->where('id in ('.I('request.id').')')->save($data);
        $this->success('删除成功','index');
    }

}
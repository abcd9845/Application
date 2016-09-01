<?php
namespace Admin\Controller;
use Think\Controller;

class GoodsController extends Controller {

    public function __initialize()
    {
        echo 'hello IndexAction';
        return;
    }

    public function index(){
        $goods_type = M('goods_type');
        $filter = array();
        if($_SESSION['current_user']['gloab_role'] == true){
            $goods_type_result = $goods_type->where(' delete_flag = 0 and up_type_id = 0')->select();
            $this->assign('goods_type',$goods_type_result);
        }else{
            $filter['filter_school'] = $_SESSION['current_user']['admin_school'];
            $type = M('goods_type');
            $this->assign('goods_type',$type->where('up_type_id = 0 and delete_flag = 0 and school_id = ' . $_SESSION['current_user']['admin_school'])->select());
        }





        $where = ' goods.delete_flag = 0 and goods.ishappy = 0';
        wrapper_sql_where_lk($where,'basic_goods.name','filter_name',$_REQUEST,$filter);
        if($_SESSION['current_user']['gloab_role'] == true){
            wrapper_sql_where($where,'school.type','filter_school_type',$_REQUEST,'string',$filter);
            wrapper_sql_where($where,'goods.school_id','filter_school',$_REQUEST,'string',$filter);
        }else{
            $where.=" and school.id = ".$_SESSION['current_user']['admin_school'];
        }
        wrapper_sql_where($where,'goods.goods_parent_type','filter_goods_type',$_REQUEST,'string',$filter);
        wrapper_sql_where($where,'goods.online','filter_online',$_REQUEST,'string',$filter);

        $this->assign('filter',$filter);
        $m = M();
        $m->table('goods')->field('school.type as school_type,school.name as school_name,goods.online,goods.id,basic_goods.title_pic,basic_goods.name,goods_type.type_name,goods.price,goods.vip_price,goodsother.inventory ')
        ->join('left join basic_goods on basic_goods.id= goods.basic_id')
        ->join('left join goods_type on goods.goods_parent_type = goods_type.id')
        ->join('left join goodsother on goods.id = goodsother.goods_id')
        ->join('left join school_address on school_address.id = goods.school_id')
        ->join('left join school on school.id = goods.school_id')

        ->where($where)
        ->order('goods.oper_time desc');
        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,5);
        $page->parameter['goods.delete_flag'] = 0;
        $page->parameter['goods.ishappy'] = 0;
        set_page_param($page,'filter_name',$_REQUEST,$filter);
        if($_SESSION['current_user']['gloab_role'] == true){
            set_page_param($page,'filter_school_type',$_REQUEST,$filter);
            set_page_param($page,'filter_school',$_REQUEST,$filter);
        }else{
            $page->parameter['filter_school'] = $_SESSION['current_user']['admin_school'];
        }
        set_page_param($page,'filter_goods_type',$_REQUEST,$filter);
        set_page_param($page,'filter_online',$_REQUEST,$filter);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        if($_SESSION['current_user']['gloab_role'] == false) {
            $type = M('goods_type');
            $this->assign('goods_type',$type->where('up_type_id = 0 and delete_flag = 0 and school_id = ' . $_SESSION['current_user']['admin_school'])->select());
        }

        $type = M('basic_goods');
        $this->assign('goods_name',$type->where(' delete_flag=0')->select());


        $this->display();
    }

    public function add(){
        $data['basic_id'] = I('request.goods_name');
        $data['goods_parent_type'] = I('request.goods_type');
        if($_SESSION['current_user']['gloab_role'] == true) {
            $data['school_id'] = I('request.school');
        }else{
            $data['school_id'] = $_SESSION['current_user']['admin_school'];
        }

        $data['price'] = I('request.price');
        $data['vip_price'] = I('request.vip_price');
        $data['unit'] = I('request.unit');
        $data['online'] = I('request.online');
        $data['show_icon'] = (I('request.show_icon')==''||I('request.show_icon')==NULL)?0:1;
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
//        $data['title_pic'] = './'.$this::getPublicFilePath(I('request.title_pic'));
        $data['description'] = I('request.description');
        $data['buynum'] = I('request.buynum');

        $m = M('goods');
        $other = M('goodsother');
        $m->startTrans();
        $result1 = $m->data($data)->add();

        $otherData['goods_id'] = $result1;
        $otherData['inventory'] = I('request.inventory');
        $otherData['buy_number'] = 0;

        $result2 = $other->data($otherData)->add();

//        $m_img = M('img_mapping');
//        $img_key = array();
//        $img_result = $m_img->where('user = '.$_SESSION['current_user']['id'])->select();
//        for($i = 0;$i<count($img_result);$i++){
//            if($data['title_pic'] == $img_result[$i]['img']){
//
//            }else{
//                unlink($img_result[$i]['img']);
//            }
//            array_push($img_key,$img_result[$i]['id']);
//        }

//        $nm_img = clone(M('img_mapping'));
//        $nm_img->delete(implode(',', $img_key));

        if($result1 && $result2){
            $m->commit();
        }else{
            $m->rollback();
        }
        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = I('request.id');
        $where['goods.id'] = $id;
        $this->assign('id',$id);
        $m = M();
        $m->table('goods')->field('school.type,goods.school_id,goods.buynum,goods.description,basic_goods.id as goods_name,goods.weight,goods.unit,goods.show_hp,basic_goods.show_name,goods.spec,goods.online,goods.show_icon,goods.goods_parent_type,goodsother.inventory,goods.price,goods.vip_price,basic_goods.title_pic')
            ->join('left join basic_goods on basic_goods.id = goods.basic_id')
            ->join('left join goods_type as parent_type on goods.goods_parent_type = parent_type.id')
            ->join('left join goodsother on goods.id = goodsother.goods_id')
            ->join('left join supplier on goods.supplier_id = supplier.id')
            ->join('left join school on school.id = goods.school_id')
            ->where($where);
        $array = $m->find();
        $this->assign('obj',$array);

        if($_SESSION['current_user']['gloab_role'] == false) {
            $type = M('goods_type');
            $this->assign('goods_type',$type->where('up_type_id = 0 and delete_flag = 0 and school_id = ' . $_SESSION['current_user']['admin_school'])->select());
        }

        $type = M('basic_goods');
        $this->assign('goods_name',$type->where(' delete_flag=0')->select());

        $this->display();
    }

    public function toView(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $m = M();
        $m->table('goods')->field('goods.buynum,goods.description,goods.name,goods.weight,goods.unit,goods.show_hp,goods.show_name,goods.spec,goods.goods_no,goods.end_date,goods.online,goods.show_icon,parent_type.type_name as parent_name,goods_type.type_name,supplier.supplier_name,goodsother.inventory,goods.price,goods.vip_price,goods.title_pic,goods.info_title_pic,goods.content')
            ->join('left join goods_type on goods.goods_type = goods_type.id')
            ->join('left join goods_type as parent_type on goods.goods_parent_type = parent_type.id')
            ->join('left join goodsother on goods.id = goodsother.goods_id')
            ->join('left join supplier on goods.supplier_id = supplier.id')
            ->where("goods.id = ".$id);
        $array = $m->select();
        $this->assign('t',$array[0]);
        $this->display();
    }

    public function edit(){
        $data['id'] = $_REQUEST['id'];
        $data['goods_parent_type'] = I('request.goods_type');
        $data['price'] = I('request.price');
        $data['vip_price'] = I('request.vip_price');
        $data['unit'] = I('request.unit');
        $data['online'] = I('request.online');
        $data['show_icon'] = (I('request.show_icon')==''||I('request.show_icon')==NULL)?0:1;
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
        $data['description'] = I('request.description');
        $data['buynum'] = I('request.buynum');


        $m = M('goods');
        $m->startTrans();
        $result1 = $m->data($data)->save();

        $goodsOther=M('goodsother');
        $obj = $goodsOther->where('goods_id = '.$_REQUEST['id'])->find();
        $otherData = array();
        $otherData['id'] = $obj['id'];
        $otherData['inventory'] = $_REQUEST['inventory'];
        $otherData['goods_id'] = $_REQUEST['id'];
        $result2 = $goodsOther->data($otherData)->save();

//        if($_REQUEST['title_pic']!=''){
//            $m_img = M('img_mapping');
//            $img_key = array();
//            $img_result = $m_img->where('user = '.$_SESSION['current_user']['id'])->select();
//            for($i = 0;$i<count($img_result);$i++){
//                if($data['title_pic'] == $img_result[$i]['img']){
//
//                }else{
//                    unlink($img_result[$i]['img']);
//                }
//                array_push($img_key,$img_result[$i]['id']);
//            }
//
//            $nm_img = clone(M('img_mapping'));
//            $nm_img->delete(implode(',', $img_key));
//        }

        if($result1>=0 && $result2>=0){
            $m->commit();
//            if($_REQUEST['title_pic'] != ''){
//                unlink($_REQUEST['old_title_pic']);
//            }

            $this->success('修改成功','index');
        }else{
            $m->rollback();
            $this->error('修改失败','index');
        }

    }

    public function del(){
        $m = M('goods');
        $data['delete_flag'] = 1;
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);
        $this->success('删除成功','index');
    }

    public function changeVal(){
        $m = M('goods_type');
        $array = $m->where('delete_flag = 0 and up_type_id = '.$_REQUEST['goods_sec_type'])->select();
        $this->ajaxReturn($array);
    }

    public function changeSchool(){
        $m = M('school');
        $array = $m->where('state = 1 and type = '.I('request.school_id'))->select();
        $this->ajaxReturn($array);
    }

    public function changeAddress(){
        $m = M('school_address');
        $array = $m->where('school = '.I('request.school_id'))->select();
        $this->ajaxReturn($array);
    }

    public function changeDeliveryRoot(){
        $m = M('address');
        $array = $m->where("school_id = ".I('request.school_id')." and level = 0")->select();
        $this->ajaxReturn($array);
    }

    public function changeDelivery(){
        $m = M('address');
        $array = $m->where("level = ".I('request.school_id'))->select();
        $this->ajaxReturn($array);
    }

    public function changeGoodsType(){
        $m = M('goods_type');
        $array = $m->where('school_id = '.I('request.school_id').' and delete_flag = 0')->select();
        $this->ajaxReturn($array);
    }

    function getPath($name){
        return substr($name,stripos($name, 'Public'),strrpos($name,'/')-stripos($name, 'Public')+1);
    }

    function getFileName($name){
        return substr($name,strrpos($name,'/')+1);
    }

    function getPublicFilePath($name){
        return substr($name,stripos($name, 'Public'));
    }

    public function getDesc(){
        $m = M('basic_goods');
        $po = $m->where('id = '.$_REQUEST['goods_id'])->find();
        $this->ajaxReturn($po['description']);
    }

    public function getBasicGoods(){
        $m = M('basic_goods');
        $po = $m->where('goods_basic_type = '.$_REQUEST['type_id'])->order('oper_time desc')->select ();
        $this->ajaxReturn($po);
    }

    //类型
    public function changeType(){
        $m = M('goods_type');
        $array = $m->where(array(school_id=>I('request.school_id'),delete_flag=>0))->select();

        $school = M('school')->where(array(id=>I('request.school_id')))->find();
        if($school['isnow'] == 1){
            array_unshift($array,array(id=>NOWMENU,type_name=>'现买现提'));
        }
        $this->ajaxReturn($array);
    }

}
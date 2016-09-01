<?php
namespace Admin\Controller;
use Think\Controller;

class HappyTimeController extends Controller {

    public function index(){
        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            $from = D('School')->getSchoolList();
            $this->assign('storage',$from);
        }else{
            $from = M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school'],'delete_flag' => 0))->select();
            $this->assign('storage',$from);
        }

        $filters = array(
            storage=>'',
            name=>'',
            online=>'',
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $where = array();
        add_where('storage.school_id',$filters['storage'],$where);
//        add_where('storage_item.show_type',HAPPYMENU,$where);
        add_where('basic_goods.name',$filters['name'],$where,'like');
        add_where('storage_item.online',$filters['online'],$where);
        add_where('basic_goods.goods_basic_type',HAPPYTYPE,$where);
        if($_SESSION[SESSION_USER]['admin_school'] != ZONGCANG){
            add_where('school.id',$_SESSION[SESSION_USER]['admin_school'],$where);
        }

        $m = M();
        $m->table('storage')->field('basic_goods.id as bbb ,storage_item.start_price,storage_item.end_price,storage_item.id,goods_type.type_name as show_type,storage_item.online,storage_item.isnew,storage_item.price,storage_item.vip,storage_item.unit,storage_item.isnew,storage_item.buynum,storage.count,basic_goods.name,school.name as school_name')
            ->join('left join school on storage.school_id = school.id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->join('left join storage_item on storage.id = storage_item.storage_id')
            ->join('left join goods_type on goods_type.id = storage_item.show_type')
            ->where($where);
        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,PAGECOUNT);
        add_param($page->parameter,$filters);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->order('school.id,storage_item.show_type')->select();

//        foreach($arr as $k => $v){
//            echo $v['bbb'].',';
//        }

        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $type = M('basic_goods');
        $this->assign('goods_name',$type->where(' delete_flag=0')->select());

        $type = M('basic_goods');
        $this->assign('goods_name',$type->where(' delete_flag=0')->select());

        $this->display();
    }

    public function add(){
        $data['basic_id'] = I('request.goods_name');
//        $data['goods_parent_type'] = I('request.goods_type');
        $data['school_id'] = I('request.school');
        $data['price'] = I('request.price');
        $data['vip_price'] = I('request.vip_price');
        $data['start_price'] = I('request.start_price');
        $data['end_price'] = I('request.end_price');
        $data['unit'] = I('request.unit');
        $data['online'] = I('request.online');
        $data['show_icon'] = (I('request.show_icon')==''||I('request.show_icon')==NULL)?0:1;
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
//        $data['title_pic'] = './'.$this::getPublicFilePath(I('request.title_pic'));
        $data['description'] = I('request.description');
        $data['ishappy'] = 1;
//        $data['buynum'] = I('request.buynum');

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
        $this->assign('goods',D('Storage')->getStorage(I('request.id')));

        $this->display();
    }

    public function edit(){
        try{
            $m = M('storage_item');
            $data['id'] = $_REQUEST['id'];
            $data['show_type'] = HAPPYMENU;
            $data['price'] = I('request.price');
            $data['vip'] = I('request.vip');
            $data['start_price'] = I('request.start_price');
            $data['end_price'] = I('request.end_price');
            $data['ishappy'] = 1;
            $data['unit'] = I('request.unit');
            $data['online'] = I('request.online');
            $data['isnew'] = (I('request.show_icon')==''||I('request.show_icon')==NULL)?0:1;
            $data['description'] = I('request.description');
            $data['buynum'] = 1;
            $data['oper_time'] = date('Y-m-d H:i:s',time());
            $m->save($data);
            $msg['state'] = true;
            $msg['msg'] = '修改成功';
            $this->ajaxReturn(json_encode($msg));
        }catch(Exception $e){
            $msg['state'] = false;
            $msg['msg'] = '修改失败';
            $this->ajaxReturn(json_encode($msg));
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

    public function changeGoodsType(){
        $m = M('goods_type');
        $array = $m->where('school_id = '.I('request.school_id'))->select();
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

}
<?php
namespace Admin\Controller;
use Think\Controller;
class TransfersController extends Controller {

    public function index(){

        $from = D('School')->getSchoolList();
        $to = D('School')->getSchoolList();
        $this->assign('from_storage',$from);
        $this->assign('to_storage',$to);

        $filters = array(
            from_storage=>'',
            to_storage=>'',
            create_time_start=>'',
            create_time_end=>'',
            storage=>'',
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $where = array();
        if($_SESSION[SESSION_USER]['role_id'] == 1){
            add_where('transfers.from_id',$filters['from_storage'],$where);
            add_where('transfers.to_id',$filters['to_storage'],$where);
        }else{
            if($filters['storage'] != 1){
                add_where('transfers.to_id',$_SESSION[SESSION_USER]['admin_school'],$where);
            }else if($filters['storage'] == 1){
                add_where('transfers.from_id',$_SESSION[SESSION_USER]['admin_school'],$where);
            }
        }

        if($filters['create_time_start']!=''&&$filters['create_time_end']!=''){
            add_where('transfers.oper_time',($filters['create_time_start'].'|'.$filters['create_time_end']),$where,'d2d');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('transfers.oper_time',$filters['create_time_start'],$where,'egt');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('transfers.oper_time',$filters['create_time_end'],$where,'elt');
        }

        $m = M();
        $m->table('transfers')->field('transfers.id,from_school.name as from_name,to_school.name as to_name,oper_user.username as oper_user,confirm_user.username as confirm_user,transfers.oper_time,transfers.confirm_time')
            ->join('left join school as from_school on transfers.from_id = from_school.id')
            ->join('left join school as to_school on transfers.to_id = to_school.id')
            ->join('left join user as oper_user on oper_user.id = transfers.oper_user')
            ->join('left join user as confirm_user on confirm_user.id = transfers.confirm_user')
            ->where($where);

        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,PAGECOUNT);
        add_param($page->parameter,$filters);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->order('transfers.oper_time desc')->select();

        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $type = M('basic_goods_type');
        $this->assign('basic_type',$type->where(array(delete_flag => 0))->select());

//        $school = M('school');
//        $this->assign('to_storage',$school->where(array(id=>array('neq',ZONGCANG)))->select());

        $to = array();
        if($_SESSION[SESSION_USER]['admin_school'] != ZONGCANG){
            $to = M('school')->where(array('school.id' => array('neq',$_SESSION[SESSION_USER]['admin_school']),'delete_flag'=>0,state=>1))->select();
            array_unshift($to,array('id'=>'','name'=>'请选择'));
        }else{
            $to = M('school')->where(array('school.id' => array('neq',$_SESSION[SESSION_USER]['admin_school']),'delete_flag'=>0,state=>1))->select();
            array_unshift($to,array('id'=>'','name'=>'请选择'));
        }
        $this->assign('to_storage',$to);


        $where = array();
        $where['storage.school_id '] = $_SESSION[SESSION_USER]['admin_school'];

        $m = M();
        $list = $m->table('storage')->field('storage.id,storage.basic_id,storage.count,basic_goods.name,school.name as school_name,basic_goods_type.basic_type')
            ->join('left join school on storage.school_id = school.id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->join('left join basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
            ->where($where)
            ->select();

        foreach($list as $k => $v){
            $list[$k]['name'] =  $list[$k]['name'] .'('.$list[$k]['count'].')';
        }

        $this->assign('goods',$list);

        $this->display();
    }

    public function view(){
        $this->assign('array',M()->table('transfers_item')->field('basic_goods.name,transfers_item.count,basic_goods_type.basic_type')
            ->join('left join basic_goods on basic_goods.id = transfers_item.goods_id')
            ->join('left join basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
            ->where(array(transfers_id=>I('request.id')))->select());
        $this->display();
    }

    public function add(){
        $transfers['from_id'] = $_SESSION[SESSION_USER]['admin_school'];
        $transfers['to_id'] = I('request.to_storage');
        $transfers['oper_user'] = $_SESSION[SESSION_USER]['id'];
        $transfers['oper_time'] = date('Y-m-d H:i:s',time());
        $transfers['type'] = 0;

        $transfers_item['ids'] = I('request.l_id');
        $transfers_item['counts'] = I('request.l_count');


        D('Transfers')->diaobo($transfers,$transfers_item);

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

    public function getGoods(){
        $where = array();
        $where['storage.school_id '] = $_SESSION[SESSION_USER]['admin_school'];

        if(I('request.type') != '')
            $where['basic_goods.goods_basic_type'] = I('request.type');


        $m = M();
        $list = $m->table('storage')->field('storage.id,storage.basic_id,storage.count,basic_goods.name,school.name as school_name,basic_goods_type.basic_type')
            ->join('left join storage_item on storage_item.storage_id = storage.id')
            ->join('left join school on storage.school_id = school.id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->join('left join basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
            ->where($where)
            ->order('storage_item.oper_time')
            ->select();

        $newList = array();
        foreach($list as $k => $v){
            if($list[$k]['count'] > 0){
                $list[$k]['name'] =  $list[$k]['name'] .'('.$list[$k]['count'].')';
                array_push($newList,$list[$k]);
            }
        }

        $this->ajaxReturn($newList);
    }

}
<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;

class OnlineNowController extends Controller {

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
            type=>'',
            name=>'',
            online=>'',
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $where = array();
        add_where('storage.school_id',$filters['storage'],$where);
//        add_where('storage_item.show_type',NOWMENU,$where);
        add_where('basic_goods.name',$filters['name'],$where,'like');
        add_where('storage_item.online',$filters['online'],$where);
        add_where('basic_goods.goods_basic_type',NOWTYPE,$where);
        if($_SESSION[SESSION_USER]['admin_school'] != ZONGCANG){
            add_where('school.id',$_SESSION[SESSION_USER]['admin_school'],$where);
        }

        $m = M();
        $m->table('storage')->field('storage_item.show_type as show_id,basic_goods.goods_basic_type,storage_item.id,goods_type.type_name as show_type,storage_item.online,storage_item.isnew,storage_item.price,storage_item.vip,storage_item.unit,storage_item.isnew,storage_item.buynum,storage.count,basic_goods.name,school.name as school_name')
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

        foreach($arr as $k => $v){
            if($v['show_id'] == NOWMENU && $v['show_type'] == ''){
                $arr[$k]['show_type'] = '现买现提';
            }
        }

        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toEdit(){
        $storage = D('Storage')->getStorage(I('request.id'));
        $this->assign('goods',$storage);

        $type_list = array();
//        $school = M('School')->where(array(id=>$storage['school_id']))->find();
//        if($school['isnow'] == 1){
            array_unshift($type_list,array('id'=>NOWMENU,'type_name'=>'现买现提'));
//        }

        $this->assign('goods_type',$type_list);



        $this->display();
    }

    public function edit(){
        try{
            $m = M('storage_item');
            $data['id'] = $_REQUEST['id'];
            $data['show_type'] = I('request.goods_type');
            $data['price'] = I('request.price');
            $data['vip'] = I('request.vip');
            $data['unit'] = I('request.unit');
            $data['online'] = I('request.online');
            $data['isnew'] = (I('request.show_icon')==''||I('request.show_icon')==NULL)?0:1;
            if(I('request.goods_type') == NOWMENU){
                $data['isnow'] = 1;
            }else{
                $data['isnow'] = 0;
            }
            $data['description'] = I('request.description');
            $data['buynum'] = I('request.buynum');
            $data['oper_time'] = date('Y-m-d H:i:s',time());
            $m->save($data);
//        $this->success('修改成功','index');
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
        $m = M('delivery');
        $m->where('id in ('.$_REQUEST['id'].')')->delete();
        $this->success('删除成功','index');
    }

}
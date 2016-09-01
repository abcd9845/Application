<?php
namespace Admin\Controller;
use Think\Controller;

class PrintedGoodsController extends Controller {

    public function index(){
        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            $school_type = M('school');
            $school_type_result = $school_type->where(array(delete_flag=>0,state=>1))->select();
            $this->assign('school',$school_type_result);
        }else{
            $school_type = M('school');
            $school_type_result = $school_type->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select();
            $this->assign('school',$school_type_result);
        }

        $filters = array(
            create_time_start=>date('Y-m-d 00:00:00'),
            create_time_end=>date('Y-m-d 23:59:59'),
            school=>'',
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $where = array();
        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            add_where('order.school_id',$filters['school'],$where);
        }else{
            add_where('order.school_id',$_SESSION[SESSION_USER]['admin_school'],$where);
        }

        if($filters['create_time_start']!=''&&$filters['create_time_end']!=''){
            add_where('order.create_time',($filters['create_time_start'].'|'.$filters['create_time_end']),$where,'d2d');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('order.create_time',$filters['create_time_start'],$where,'egt');
        }else if($filters['create_time_start']!=''&&$filters['create_time_end']==''){
            add_where('order.create_time',$filters['create_time_end'],$where,'elt');
        }

        add_where('order.delete_flag',0,$where);
        add_where('order.print_state',1,$where);
        add_where('order.isnow',0,$where);

        $orderList = M('order')
            ->where($where)->select();

        $goodsArray = array();
        foreach($orderList as $k => $v){
            $goods = json_decode($v['goods_items'],true);
            foreach($goods as $k1 => $v1){
                if($goodsArray[$v1['id']] == null){
                    if($v1['ishappy'] == true)
                        $v1['name'] = $v1['name'].'[幸运价]';

                    $goodsArray[$v1['id']] = $v1;
                }else{
                    $goodsArray[$v1['id']]['count'] = $goodsArray[$v1['id']]['count']+$v1['count'];
                }
            }
        }

        foreach($goodsArray as $k => $v){
            $po = M('storage')
                ->field('storage.id,storage_item.ishappy,storage_item.unit,storage.count as inventory,storage.buy as buy_number')
                ->join('storage_item on storage_item.storage_id = storage.id')
                ->where("storage.id = ".$v['id'])->find();

            if($po){
                $goodsArray[$k]['unit'] = $po['unit'];
                $goodsArray[$k]['inventory'] = $po['inventory'];
                $goodsArray[$k]['buy_number'] = $po['buy_number'];
            }
        }

        usort($goodsArray,'sortByNumber');

        $this->assign('array',$goodsArray);
        $this->display();
    }

}
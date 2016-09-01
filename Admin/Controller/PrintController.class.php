<?php

namespace Admin\Controller;

use Think\Controller;

class PrintController extends Controller {

    public $common_selet = 'delete_flag = 0';

    public function index() {
        $filters = array('print_state' => '0', 'create_time' => date('Y-m-d'), 'address_id' => '', 'order_no' => '', 'mobile' => '', 'pick_no' => '','state_id' => '', 'pick_time' => date('Y-m-d', time() + 3600 * 24), 'time_span' => '17:00' ,'create_time_start' =>  date('Y-m-d 00:00:00') ,'create_time_end' => date('Y-m-d 23:59:59'),'school_type'=> '','school' => '','address' => '');
        $filters = array_merge($filters, I('get.filters', array()));
        $this->assign('filters', $filters);
        $where = array(
            "order.delete_flag" => 0,
            "order.re_type" => 0,
            "order.state_id" => array('egt', 2),
            "order.print_state" => $filters['print_state'],
            "order.order_no" => array('like', "%{$filters['order_no']}%"),
            "order.id" => array('like', "%{$filters['pick_no']}%"),
            "user.mobile" => array('like', "%{$filters['mobile']}%"),
            "order.create_time" => array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end'])),
            "order.isnow" => 0,
            "order.hdfk" => 0,
        );

        if ($filters['create_time_start'] != "" && $filters['create_time_end'] != "" ) {
            $where["order.create_time"] = array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end']));
        }

        if ($filters['pick_time_start'] != "" && $filters['pick_time_end'] != "" ) {
            $where["order.pick_time"] = array(array('egt', $filters['pick_time_start']), array('elt', $filters['pick_time_end']));
        }

        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if ($filters['school_type'] != "") {
                $where["school.type"] = $filters['school_type'];
            }

            if ($filters['school'] != "") {
                $where["order.school_id"] = $filters['school'];
            }


            if ($filters['address'] != "") {
                $where["order.address_id"] = $filters['address'];
            }

        }else{
            $this->assign('school',M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select());
            $where["order.school_id"] = $_SESSION[SESSION_USER]['admin_school'];

            if ($filters['address'] != "") {
                $where["order.address_id"] = $filters['address'];
            }

        }

        if ($filters['state_id'] != "") {
            $where['order.state_id'] = array('like', "%{$filters['state_id']}%");
        }

        $timeSpanList = $this->calcPickTimeSpan();
        $this->assign('timeSpanList', $timeSpanList);

        $m = M();
        $m->table('order')->field('order.pick_time_txt,order.pick_time,order.state_id,order.id,user.real_name,order.id,order.address_id,order.create_time,school_address.address,order.goods_items,user.mobile,order.order_no,order.pick_time,order.pick_no ')
                ->join('left join school_address on school_address.id = order.address_id')
                ->join('left join school on school.id = school_address.school')
                ->join('left join user on user.username = order.purchaser')
                ->where($where);
        $arr = $m->order('order.oper_time desc')->select();


        $result = array();


        foreach ($arr as $a) {
            $total = 0;
            $spList = array();
            $print_str = '';
            $export_str = '';
            if (!isset($result[$a['pick_time_txt']])) {
                $result[$a['address_id'].$a['pick_time_txt']]['address'] = $a['address'];
                $result[$a['address_id'].$a['pick_time_txt']]['create_time'] = explode(' ',$a['create_time'])[0];
                $result[$a['address_id'].$a['pick_time_txt']]['pick_time'] = $a['pick_time_txt'];
            }

            $goods_list = json_decode($a['goods_items'],true);

            $index = 0;
            foreach($goods_list as $k=>$v){
                $po = M('storage')
                    ->field('storage.id,storage_item.ishappy,storage_item.unit,storage.count,storage_item.vip')
                    ->join('storage_item on storage_item.storage_id = storage.id')
                    ->where("storage.id = ".$v['id'])->find();

                if($index++ != 0){
                    $export_str.='|';
                    $print_str.='[]';
                }

                //$print_str = $v['name'];
                $export_str.=$v['name'];


                $print_str .= $v['name'].'|'.$po['vip'].'|'.$po['unit'].'|'.$v['count'].'|'.($po['vip'] * $v['count']);

                $total += $po['vip'] * $v['count'];

                array_push($spList,array(
                    name=>$v['name'],
                    unit=>$po['unit'],
                    price=>$po['vip'],
                    count=>$v['count'],
                    total=>$po['vip'] * $v['count']
                ));

                $export_str.=$po['unit'];
                $export_str.='*';
                $export_str.=$v['count'];

            }

            $result[$a['address_id'].$a['pick_time_txt']]['data'][] = array('pick_time'=> $a['pick_time'],'real_name' => $a['real_name'], 'order_no' => $a['order_no'], 'pick_no' => $a['id'], 'mobile' => $a['mobile'], 'good_items' => $print_str,'good_items_print' => $export_str ,'state_id' =>$a['state_id'], 'create_time' =>$a['create_time'],'spList'=>$spList,'total'=>$total,'print_str'=>$print_str);
        }

        $this->assign('result', $result);
        $this->display();
    }

    public function door() {

        $filters = array('print_state' => '0', 'create_time' => date('Y-m-d'), 'state_id' => '', 'address_id' => '', 'order_no' => '', 'mobile' => '', 'pick_no' => '', 'pick_time' => date('Y-m-d', time() + 3600 * 24), 'time_span' => '17:00','create_time_start' =>  date('Y-m-d 00:00:00') ,'create_time_end' => date('Y-m-d 23:59:59'),'school_type'=> '','school' => '','address' => '');
        $filters = array_merge($filters, I('get.filters', array()));
        $this->assign('filters', $filters);
        $where = array(
            "order.delete_flag" => 0,
            "order.re_type" => 1,
            "order.state_id" => array('egt', 2),
            "order.print_state" => array('like', "%{$filters['print_state']}%"),
            "order.order_no" => array('like', "%{$filters['order_no']}%"),
            "user.mobile" => array('like', "%{$filters['mobile']}%"),
            "order.create_time" => array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end'])),
            "order.hdfk" => 0,
        );

        if ($filters['create_time_start'] != "" && $filters['create_time_end'] != "" ) {
            $where["order.create_time"] = array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end']));
        }

        if ($filters['pick_time_start'] != "" && $filters['pick_time_end'] != "" ) {
            $where["order.pick_time"] = array(array('egt', $filters['pick_time_start']), array('elt', $filters['pick_time_end']));
        }


        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if ($filters['school_type'] != "") {
                $where["school.type"] = $filters['school_type'];
            }

            if ($filters['school'] != "") {
                $where["order.school_id"] = $filters['school'];
            }


            if ($filters['address'] != "") {
                $where["address.level"] = $filters['address'];
            }

            if ($filters['address_child'] != "") {
                $where["address.id"] = $filters['address_child'];
            }

        }else{
            $this->assign('school',M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select());
            $where["order.school_id"] = $_SESSION[SESSION_USER]['admin_school'];

            if ($filters['address'] != "") {
                $where["address.level"] = $filters['address'];
            }

            if ($filters['address_child'] != "") {
                $where["address.id"] = $filters['address_child'];
            }

        }

        if ($filters['state_id'] != "") {
            $where['order.state_id'] = array('like', "%{$filters['state_id']}%");
        }

        $m = M();
        $m->table('order')->field('school.name as address,order.delivery_address,order.delivery_time_txt,order.pick_time,order.state_id,order.create_time,user.real_name,school.name,order.id,order.delivery_address,order.school_id,order.create_time,order.goods_items,user.mobile,order.order_no,order.pick_time,order.pick_no')
                ->join('left join school on school.id = order.school_id')
                ->join('left join address on address.id = order.delivery_address_id')
                ->join('left join user on user.username = order.purchaser')
                ->where($where);

        $arr = $m->order('order.oper_time desc')->select();




        $result = array();
        foreach ($arr as $a) {
            $total = 0;
            $spList = array();
            $print_str = '';
            $export_str = '';

            if (!isset($result[$a['address_id']])) {
                $result[$a['delivery_time_txt']]['address'] = $a['delivery_address'];
                $result[$a['delivery_time_txt']]['create_time'] = explode(' ',$a['create_time'])[0];
                $result[$a['delivery_time_txt']]['pick_time'] = $a['delivery_time_txt'];
            }

            $goods_list = json_decode($a['goods_items'],true);

            $index = 0;
            foreach($goods_list as $k=>$v){
                $po = M('storage')
                    ->field('storage.id,storage_item.ishappy,storage_item.unit,storage.count,storage_item.vip')
                    ->join('storage_item on storage_item.storage_id = storage.id')
                    ->where("storage.id = ".$v['id'])->find();

                if($index++ != 0){
                    $export_str.='|';
                    $print_str.='[]';
                }

                $print_str .= $v['name'].'|'.$po['vip'].'|'.$po['unit'].'|'.$v['count'].'|'.($po['vip'] * $v['count']);

                $total += $po['vip'] * $v['count'];
                array_push($spList,array(
                    name=>$v['name'],
                    unit=>$po['unit'],
                    price=>$po['vip'],
                    count=>$v['count'],
                    total=>$po['vip'] * $v['count']
                ));


                if($v['name'] != '爱心苹果'){
                    $export_str.=$po['unit'];
                    $export_str.='*';
                    $export_str.=$v['count'];
                    $index++;
                }


            }


//      $result[$a['address_id']]['data'][] = array('real_name' => $a['real_name'], 'order_no' => $a['order_no'], 'pick_no' => $a['pick_no'], 'mobile' => $a['mobile'], 'good_items' => json_decode($a['goods_items'], TRUE));
            $result[$a['delivery_time_txt']]['data'][] = array('pick_no' => $a['id'],'address'=>$a['address'],'delivery_time_txt'=>$a['delivery_time_txt'],'pick_time'=> $a['pick_time'],'real_name' => $a['real_name'],'state_id'=>$a['state_id'],'create_time'=>$a['create_time'], 'order_no' => $a['order_no'], 'delivery_address' => $a['delivery_address'], 'mobile' => $a['mobile'], 'good_items' => $print_str,'good_items_print' => $export_str,'spList'=>$spList,'total'=>$total,'print_str'=>$print_str, TRUE);
        }

//        dump($spList);
        $this->assign('result', $result);
        $this->display();
    }

    public function hdfkZiti() {
        $filters = array('print_state' => '0', 'create_time' => date('Y-m-d'), 'address_id' => '', 'order_no' => '', 'mobile' => '', 'pick_no' => '','state_id' => '', 'pick_time' => date('Y-m-d', time() + 3600 * 24), 'time_span' => '17:00' ,'create_time_start' =>  date('Y-m-d 00:00:00') ,'create_time_end' => date('Y-m-d 23:59:59'),'school_type'=> '','school' => '','address' => '');
        $filters = array_merge($filters, I('get.filters', array()));
        $this->assign('filters', $filters);
        $where = array(
            "order.delete_flag" => 0,
            "order.re_type" => 0,
            "order.state_id" => array('egt', 2),
            "order.print_state" => $filters['print_state'],
            "order.order_no" => array('like', "%{$filters['order_no']}%"),
            "order.id" => array('like', "%{$filters['pick_no']}%"),
            "user.mobile" => array('like', "%{$filters['mobile']}%"),
            "order.create_time" => array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end'])),
            "order.isnow" => 0,
            "order.hdfk" => 1,
        );
//
//
        if ($filters['create_time_start'] != "" && $filters['create_time_end'] != "" ) {
            $where["order.create_time"] = array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end']));
        }

        if ($filters['pick_time_start'] != "" && $filters['pick_time_end'] != "" ) {
            $where["order.pick_time"] = array(array('egt', $filters['pick_time_start']), array('elt', $filters['pick_time_end']));
        }

        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if ($filters['school_type'] != "") {
                $where["school.type"] = $filters['school_type'];
            }

            if ($filters['school'] != "") {
                $where["order.school_id"] = $filters['school'];
            }


            if ($filters['address'] != "") {
                $where["order.address_id"] = $filters['address'];
            }

        }else{
            $this->assign('school',M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select());
            $where["order.school_id"] = $_SESSION[SESSION_USER]['admin_school'];

            if ($filters['address'] != "") {
                $where["order.address_id"] = $filters['address'];
            }

        }

        if ($filters['state_id'] != "") {
            $where['order.state_id'] = array('like', "%{$filters['state_id']}%");
        }

        $timeSpanList = $this->calcPickTimeSpan();
        $this->assign('timeSpanList', $timeSpanList);

        $m = M();
        $m->table('order')->field('order.pick_time_txt,order.pick_time,order.state_id,order.id,user.real_name,order.id,order.address_id,order.create_time,school_address.address,order.goods_items,user.mobile,order.order_no,order.pick_time,order.pick_no ')
            ->join('left join school_address on school_address.id = order.address_id')
            ->join('left join school on school.id = school_address.school')
            ->join('left join user on user.username = order.purchaser')
            ->where($where);
        $arr = $m->order('order.oper_time desc')->select();


        $result = array();

        $index = 0;
        foreach ($arr as $a) {
            $total = 0;
            $spList = array();

            $print_str = '';
            $export_str = '';
            if (!isset($result[$a['pick_time_txt']])) {
                $result[$a['address_id'].$a['pick_time_txt']]['address'] = $a['address'];
                $result[$a['address_id'].$a['pick_time_txt']]['create_time'] = explode(' ',$a['create_time'])[0];
                $result[$a['address_id'].$a['pick_time_txt']]['pick_time'] = $a['pick_time_txt'];
            }

            $goods_list = json_decode($a['goods_items'],true);

            foreach($goods_list as $k=>$v){
                $po = M('storage')
                    ->field('storage.id,storage_item.ishappy,storage_item.unit,storage.count,storage_item.vip')
                    ->join('storage_item on storage_item.storage_id = storage.id')
                    ->where("storage.id = ".$v['id'])->find();

                if($index++ != 0){
                    $export_str.='|';
                    $print_str.='[]';
                }

//                $print_str.=$v['name'];
                $export_str.=$v['name'];
                $print_str .= $v['name'].'|'.$po['vip'].'|'.$po['unit'].'|'.$v['count'].'|'.($po['vip'] * $v['count']);

                $total += $po['vip'] * $v['count'];
                array_push($spList,array(
                    name=>$v['name'],
                    unit=>$po['unit'],
                    price=>$po['vip'],
                    count=>$v['count'],
                    total=>$po['vip'] * $v['count']
                ));



                $export_str.=$po['unit'];
                $export_str.='*';
                $export_str.=$v['count'];


            }

            $result[$a['address_id'].$a['pick_time_txt']]['data'][] = array('pick_time'=> $a['pick_time'],'real_name' => $a['real_name'], 'order_no' => $a['order_no'], 'pick_no' => $a['id'], 'mobile' => $a['mobile'], 'good_items' => $print_str,'good_items_print' => $export_str ,'state_id' =>$a['state_id'], 'create_time' =>$a['create_time'],'spList'=>$spList,'total'=>$total,'print_str'=>$print_str);
        }

//        dump($arr);
//        return;

//        $this->assign('count',count($arr));
        $this->assign('result', $result);
        $this->display();
    }

    public function hdfkShangmen() {

        $filters = array('print_state' => '0', 'create_time' => date('Y-m-d'), 'state_id' => '', 'address_id' => '', 'order_no' => '', 'mobile' => '', 'pick_no' => '', 'pick_time' => date('Y-m-d', time() + 3600 * 24), 'time_span' => '17:00','create_time_start' =>  date('Y-m-d 00:00:00') ,'create_time_end' => date('Y-m-d 23:59:59'),'school_type'=> '','school' => '','address' => '');
        $filters = array_merge($filters, I('get.filters', array()));
        $this->assign('filters', $filters);
        $where = array(
            "order.delete_flag" => 0,
            "order.re_type" => 1,
            "order.state_id" => array('egt', 2),
            "order.print_state" => array('like', "%{$filters['print_state']}%"),
            "order.order_no" => array('like', "%{$filters['order_no']}%"),
            "user.mobile" => array('like', "%{$filters['mobile']}%"),
            "order.create_time" => array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end'])),
            "order.hdfk" => 1,
        );

        if ($filters['create_time_start'] != "" && $filters['create_time_end'] != "" ) {
            $where["order.create_time"] = array(array('egt', $filters['create_time_start']), array('elt', $filters['create_time_end']));
        }

        if ($filters['pick_time_start'] != "" && $filters['pick_time_end'] != "" ) {
            $where["order.pick_time"] = array(array('egt', $filters['pick_time_start']), array('elt', $filters['pick_time_end']));
        }


        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if ($filters['school_type'] != "") {
                $where["school.type"] = $filters['school_type'];
            }

            if ($filters['school'] != "") {
                $where["order.school_id"] = $filters['school'];
            }


            if ($filters['address'] != "") {
                $where["address.level"] = $filters['address'];
            }

            if ($filters['address_child'] != "") {
                $where["address.id"] = $filters['address_child'];
            }

        }else{
            $this->assign('school',M('school')->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select());
            $where["order.school_id"] = $_SESSION[SESSION_USER]['admin_school'];

            if ($filters['address'] != "") {
                $where["address.level"] = $filters['address'];
            }

            if ($filters['address_child'] != "") {
                $where["address.id"] = $filters['address_child'];
            }

        }

        if ($filters['state_id'] != "") {
            $where['order.state_id'] = array('like', "%{$filters['state_id']}%");
        }

        $m = M();
        $m->table('order')->field('school.name as address,order.delivery_address,order.delivery_time_txt,order.pick_time,order.state_id,order.create_time,user.real_name,school.name,order.id,order.delivery_address,order.school_id,order.create_time,order.goods_items,user.mobile,order.order_no,order.pick_time,order.pick_no')
            ->join('left join school on school.id = order.school_id')
            ->join('left join address on address.id = order.delivery_address_id')
            ->join('left join user on user.username = order.purchaser')
            ->where($where);

        $arr = $m->order('order.oper_time desc')->select();

        $result = array();
        foreach ($arr as $a) {
            $total = 0;
            $spList = array();

            $print_str = '';
            $export_str = '';

            if (!isset($result[$a['address_id']])) {
                $result[$a['delivery_time_txt']]['address'] = $a['delivery_address'];
                $result[$a['delivery_time_txt']]['create_time'] = explode(' ',$a['create_time'])[0];
                $result[$a['delivery_time_txt']]['pick_time'] = $a['delivery_time_txt'];
            }

            $goods_list = json_decode($a['goods_items'],true);

            $index = 0;

            foreach($goods_list as $k=>$v){
                $po = M('storage')
                    ->field('storage.id,storage_item.ishappy,storage_item.unit,storage.count,storage_item.vip')
                    ->join('storage_item on storage_item.storage_id = storage.id')
                    ->where("storage.id = ".$v['id'])->find();

                if($index++ != 0){
                    $export_str.='|';
                    $print_str.='[]';
                }

//                $print_str = $v['name'];

                $total += $po['vip'] * $v['count'];


                $print_str .= $v['name'].'|'.$po['vip'].'|'.$po['unit'].'|'.$v['count'].'|'.($po['vip'] * $v['count']);
                array_push($spList,array(
                    name=>$v['name'],
                    unit=>$po['unit'],
                    price=>$po['vip'],
                    count=>$v['count'],
                    total=>$po['vip'] * $v['count']
                ));


                if($v['name'] != '爱心苹果'){
                    $export_str.=$po['unit'];
                    $export_str.='*';
                    $export_str.=$v['count'];
                    $index++;
                }


            }

//      $result[$a['address_id']]['data'][] = array('real_name' => $a['real_name'], 'order_no' => $a['order_no'], 'pick_no' => $a['pick_no'], 'mobile' => $a['mobile'], 'good_items' => json_decode($a['goods_items'], TRUE));
            $result[$a['delivery_time_txt']]['data'][] = array('pick_no' => $a['id'],'address'=>$a['address'],'delivery_time_txt'=>$a['delivery_time_txt'],'pick_time'=> $a['pick_time'],'real_name' => $a['real_name'],'state_id'=>$a['state_id'],'create_time'=>$a['create_time'], 'order_no' => $a['order_no'], 'delivery_address' => $a['delivery_address'], 'mobile' => $a['mobile'], 'good_items' => $print_str,'good_items_print' => $export_str,'spList'=>$spList,'total'=>$total,'print_str'=>$print_str, TRUE);
        }

        $this->assign('result', $result);
        $this->display();
    }

    public function changePrintState() {
        $orderNos = explode(',', I('post.nos', ''));
        if (!is_null($orderNos) && count($orderNos) > 0) {
            M('Order')->where(array('order_no' => array('in', $orderNos)))->save(array('print_state' => 1));
        }
        $this->success();
    }

    public function createTable($section,$obj,$i,$fontStyle,$pageBreak){
            //              $s .= "\n[送货上门]{$post['delivery_address'][$i]},{$post['create_time']},{$post['pick_time']},{$post['pick_no'][$i]},{$post['order_no'][$i]},{$post['mobile'][$i]},{$post['goods'][$i]}";

            $width = 2500;
            $height = 400;
            $cellFontStyle = array('align'=>'center','size'=>12);
            $cellStyleCell = array('valign'=>'center');
            $contentStyleCell = array('valign'=>'center');
            $restartStyleCell = array('cellMerge' => 'restart','valign'=>'center','align'=>'left');
            $continuedStyleCell = array('cellMerge' => 'continue','valign'=>'center','align'=>'left');
            $normalWidth = 1300;
            $smalllWidth = 1100;
            $largeWidth = 1800;
            $picTdWidth = 1400;
            $rowHeight = 2500;
            $table = $section->addTable('myOwnTableStyle');
            $table->addRow($height);
            $table->addCell($width,$cellStyleCell)->addText('订单号', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('提货单号', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('收货人', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('收货人电话', $fontStyle,$cellFontStyle);

            $table->addRow($height);
            $table->addCell($width,$contentStyleCell)->addText($obj['order_no'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['pick_no'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['real_name'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['mobile'][$i], $fontStyle,$cellFontStyle);

            $table->addRow($height);
            $table->addCell($width,$cellStyleCell)->addText('收货地址', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('订单状态', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('下单时间', $fontStyle,$cellFontStyle);
            $table->addCell($width,$cellStyleCell)->addText('送货时间', $fontStyle,$cellFontStyle);

            $table->addRow($height);
            $table->addCell($width,$contentStyleCell)->addText($obj['delivery_address'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['state_id'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['create_time'][$i], $fontStyle,$cellFontStyle);
            $table->addCell($width,$contentStyleCell)->addText($obj['delivery_time_txt'][$i], $fontStyle,$cellFontStyle);



            $table->addRow($height);
            $temp = $table->addCell($width, $restartStyleCell);
            $print_str = $obj['print_str'][$i];
            $print_array = explode('[]',$print_str);
            $all_str = '';
            for($j=0;$j<count($print_array);$j++){
                $items_array = explode('|',$print_array[$j]);
                $new_str = $items_array[0].'    ￥'.$items_array[1].'元／'.$items_array[2].' x '.$items_array[3].'            '.'￥'.$items_array[4].'元';
                $temp->addText($new_str,$fontStyle,array('align'=>'right'));
            }

            $table->addCell($width, $continuedStyleCell);
            $table->addCell($width, $continuedStyleCell);
            $table->addCell($width, $continuedStyleCell);

            $table->addRow($height);
            $table->addCell($width, $restartStyleCell)->addText('合计:  ￥'.$obj['total'][$i].'元', $fontStyle,array('align'=>'right'));
            $table->addCell($width, $continuedStyleCell);
            $table->addCell($width, $continuedStyleCell);
            $table->addCell($width, $continuedStyleCell);


            $section->addTextBreak();
            $section->addTextBreak();
            $section->addTextBreak();
    }

    public function export(){
        Vendor('PHPWord.PHPWord');
        // New Word Document
        $PHPWord = new \PHPWord();

        $section = $PHPWord->createSection();

        $styleTable = array('borderSize'=>6, 'borderColor'=>'000000', 'cellMargin'=>80);
        $styleFirstRow = array('borderSize'=>6);

        $fontStyle = array('align'=>'center','size'=>12);

        $PHPWord->addTableStyle('myOwnTableStyle', $styleTable, $styleFirstRow);

        $post = I('post.');
        $key = 0;
        $count = count($post['real_name']);
        for ($i = 0; $i < count($post['real_name']); $i++) {
            if($post['goods'][$i] != ''){
                $this->createTable($section,$post,$i,$fontStyle);
            }
        }

        $fileName = $post['title'].'.docx';
        $objWriter = \PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $objWriter->save('./Public/Word/'.$fileName);


        $file_dir = './Public/Word/';
        $file_name = $fileName;

        $file = fopen($file_dir . $file_name,"r");

        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: ".filesize($file_dir . $file_name));

        Header("Content-Disposition: attachment; filename=" . $file_name);

        echo fread($file,filesize($file_dir . $file_name));
        fclose($file);

        exit;
        return;
    }

    public function export1() {
        $post = I('post.');

        if (count($post['delivery_address'])) {
            $s = '配送地址,订单日期,送货时间,取货单号,订单编号,联系人电话,订单信息';
            for ($i = 0; $i < count($post['real_name']); $i++) {
                if($post['goods'][$i] != ''){
                    $s .= "\n[送货上门]{$post['delivery_address'][$i]},{$post['create_time']},{$post['pick_time']},{$post['pick_no'][$i]},{$post['order_no'][$i]},{$post['mobile'][$i]},{$post['goods'][$i]}";
                }
            }

            C('SHOW_PAGE_TRACE', FALSE);
            header('Content-Disposition: attachment;filename="'. '上门订单' . '|' . $post['address'] . '|' . $post['create_time'] . '.csv"');
            header('Cache-Control: max-age=0');
            echo $s;
        } else if (count($post['pick_no'])) {
            $s = '配送地址,订单日期,送货时间,取货单号,订单编号,联系人电话,订单信息';
            for ($i = 0; $i < count($post['pick_no']); $i++) {
                $s .= "\n{$post['address']},{$post['create_time']},{$post['pick_time']},{$post['pick_no'][$i]},{$post['order_no'][$i]},{$post['mobile'][$i]},{$post['goods'][$i]}";
            }

            C('SHOW_PAGE_TRACE', FALSE);
            header('Content-Disposition: attachment;filename="' . $post['address'] . '_' . $post['pick_time'] . '.csv"');
            header('Cache-Control: max-age=0');
            echo $s;
        }else{
            $this->redirect($post['postadd']);
        }
    }

    private function calcPickTimeSpan() {
        $startTime = strtotime(date('Y-m-d') . ' ' . C('RECIVEEVERYDAY'));
        $endTime = $startTime + 3 * 3600;
        $list = array();
        while ($startTime < $endTime) {
            $list[] = date('H:i', $startTime);
            $startTime += C('RECIVETIMESTAMP');
        }
        return $list;
    }

}

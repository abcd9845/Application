<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Model;

use Think\Model;

class ProductModel extends Model {

  protected $autoCheckFields = false;

  public function _initialize() {
    
  }

  public function typeList() {
    $list = M('goods_type')->where(array('delete_flag' => 0,'up_type_id' => 0,'school_id' => $_SESSION['school']['id']))->order('srt')->select();
    return $list;
  }

  public function goodsList($type = '',$happy = 0, $isnow = 0) {
//      $where['goods.delete_flag'] = 0;
      $where['storage_item.online'] = 0;

      $where['storage.school_id'] = $_SESSION['school']['id'];

      if($happy == 1){
//        $where['goods.ishappy'] = $happy;

          $m = M('happy_time');
          $w = array(
              "create_date" => array(array('egt', date('Y-m-d') . ' 00:00:00'), array('elt', date('Y-m-d') . ' 23:59:59'))
             ,"user_id" => $_SESSION['openid']
             ,'school_type'=>$_SESSION['school']['type']
             ,'school_id'=>$_SESSION['school']['id']
          );
          $happy_list = $m->where($w)->select();
          $idArray = array();
          $id = array();
          foreach($happy_list  as $k=>$v){
            array_push($idArray , $v['goods_id']);
            array_push($id , $v['id']);
          }

          if(count($happy_list) == 0)
            return 'happy_none';



        $list = M('storage')
            ->field('
                basic_goods.title_pic,
                storage.count as inventory,
                basic_goods.name,
                storage.id,
                storage_item.vip as vip_price,
                storage_item.price,
                storage_item.unit,
                storage.basic_id,
                storage_item.show_type,
                storage.school_id,
                storage_item.ishappy,
                storage_item.start_price,
                storage_item.end_price,
                storage_item.buynum,
                storage_item.unit,
                storage_item.online,
                storage_item.isnew as show_icon,
                storage_item.description as de,
                happy_time.price as happy_price,
                happy_time.is_buy,
                basic_goods_type.id as basic_type_id
            ')
            ->join('storage_item on storage.id=storage_item.storage_id')
            ->join('basic_goods on basic_goods.id=storage.basic_id')
            ->join('happy_time on happy_time.goods_id=storage.id')
            ->join('basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
            ->where(array(
                'storage.id'=>array('in',$idArray)
                ,'happy_time.id'=>array('in',$id)
                ,'happy_time.school_type'=> $_SESSION['school']['type']
                ,'happy_time.school_id'=> $_SESSION['school']['id']
            ))
            ->select();

      }else{
        $where['storage_item.show_type'] = $type;
        $where['storage_item.ishappy'] = $happy;
        $where['storage_item.isnow'] = $isnow;
        $list = M('storage')
            ->field('
                basic_goods.title_pic,
                storage.count as inventory,
                basic_goods.name,
                storage.id,
                storage_item.vip as vip_price,
                storage_item.price,
                storage_item.unit,
                storage.basic_id,
                storage_item.show_type,
                storage.school_id,
                storage_item.ishappy,
                storage_item.isnow,
                storage_item.start_price,
                storage_item.end_price,
                storage_item.buynum,
                storage_item.unit,
                storage_item.online,
                storage_item.isnew as show_icon,
                storage_item.description as de,
                basic_goods_type.id as basic_type_id
            ')
          ->join('storage_item on storage.id=storage_item.storage_id')
          ->join('basic_goods on basic_goods.id=storage.basic_id')
          ->join('basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
          ->where($where)
          ->order('storage_item.isnew desc,storage_item.oper_time desc')
          ->select();

      }

    return $list;
  }

  public function getGoods($id = '') {
    $sql = "select a.*,b.buy_number,b.inventory from goods a,goodsother b where a.id=b.goods_id and a.id=$id and a.delete_flag=0";
    $goods = M()->query($sql);
    return $goods[0];
  }

  public function IsEnough($gid = '', $consum_num = 0) {
    $goods = M('GoodsOther')->where(array('goods_id' => $gid))->find();
    if ($consum_num > $goods['inventory']) {
      return FALSE;
    } else {
      return TRUE;
    }
  }

  public function IsActive($gid) {
    
  }

}

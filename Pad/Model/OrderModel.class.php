<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Model;

use Think\Model;

class OrderModel extends Model {

  protected $autoCheckFields = false;

  public function getBuyAdss(){
    return M('buy_address')->where(array(username=>$_SESSION['openid'],school_id=>$_SESSION['school']['id'],deli_menu=>$_SESSION['school']['deli_menu']))->find();
  }

  public function add($post = '') {
    $order['goods_items'] = json_encode($post['goods']);
    $orderNo = $this::build_order_no();
    $order['order_no'] = $orderNo;
    $order['pay_type'] =  $post['pay_type'];
    $order['total'] = $post['total'];
//    $order['total'] = 0.01;
    $order['discount'] = $post['discount'];
    $order['discount_type'] = 1;
    $order['purchaser'] = $_SESSION['openid'];
    if($post['pay_type'] == 3){
      $order['state_id'] = 5;
      $order['hdfk'] = 1;
    }else{
      $order['state_id'] = 1;
      $order['hdfk'] = 0;
    }

    $order['re_type'] = $post['re_type'];
    $order['happy_order'] = $post['happy_order'];

    $order['oper_user'] = $post['oper_user'];
    $order['oper_time'] = date('Y-m-d H:i:s');
    $order['create_time'] = date('Y-m-d H:i:s');
    $order['school_id'] = $_SESSION['school']['id'];
    $order['delete_flag'] = 0;
    $order['print_state'] = 0;
    $order['isnow'] = $post['isnow'];
    if($post['re_type'] == '0'){
      $order['address_id'] = $post['address_id'];
      $order['pick_no'] = $post['pick_no'];
      $order['pick_time_txt'] = $post['pick_time_txt'];
    }else{
      $order['expense'] = $post['expense'];
      $order['delivery_address'] = htmlspecialchars($post['delivery_address']);
      $order['delivery_address_id'] = $post['delivery_address_id'];
      $order['delivery_time_txt'] = $post['delivery_time_txt'];
    }

    $order['pick_time'] = $post['pick_time'];

    $openid = $_SESSION['openid'];
    $m = M('Order');
    $m1 = M('user');
    $m->startTrans();
    $id = $m->add($order);
    $order['id'] = $id;
    for($i=0;$i<count($post['goods']);$i++){
      $other = M('storage')->where('id = '.$post['goods'][$i]->id)->find();
      if($post['goods'][$i]->count <= $other['count']){
        $other['count'] = $other['count'] - $post['goods'][$i]->count;
        $other['buy'] = $other['buy'] + $post['goods'][$i]->count;
        M('storage')->save($other);
      }else{
        $m->rollback();
        return '-1';
      }
    }

    $user = $m1->where("username = '".$openid."'")->select();

    if($_SESSION['school']['type'] == 0)
      $userdata['last_login_address'] = $_SESSION['school']['id'];
    else
      $userdata['last_login_delivery'] = $_SESSION['school']['id'];

    $userdata['last_school'] = $_SESSION['school']['id'];

    $userdata['id'] = $user[0]['id'];
    $result = $m1->data($userdata)->save();


    $adList = $this::getBuyAdss();
    if($adList == null){
      $adData = array(
          username=>$_SESSION['openid']
         ,school_id=>$_SESSION['school']['id']
         ,deli_menu=>$_SESSION['school']['deli_menu']
      );
      if($post['re_type'] == 0){
        $adData['ziti'] = $post['address_id'];
      }else{
        $adData['songhuo'] = $post['delivery_address'];
        $adData['songhuo_id'] = $post['delivery_address_id'];
      }
      M('buy_address')->data($adData)->add();
    }else{
      $adData = $adList;
      if($post['re_type'] == 0){
        $adData['ziti'] = $post['address_id'];
      }else{
        $adData['songhuo'] = $post['delivery_address'];
        $adData['songhuo_id'] = $post['delivery_address_id'];
      }
      M('buy_address')->save($adData);
    }



    if($id >= 0 && $result >= 0){
      $m->commit();
      return $order;
    }else{
      $m->rollback();
      return null;
    }


  }

  public function generateOrder($post) {
    $checked = $post['goods'];
    foreach ($checked as $key => $c) {
      $suppMap[$c['supplier_id']][] = $c;
    }
    $orderArr = [];
    foreach ($suppMap as $key => $s) {
      $total = 0;
      $orderNo = $this->generateNO($key);
      $no[] = $orderNo;
      $item = json_encode($s, FALSE);
      M('OrderItem')->add(array('order_no' => $orderNo, 'goods_items' => $item));
      foreach ($s as $ss) {
        $this->changeNUM($ss['id'], $ss['num'], 'order');
        $total+=$ss['price'] * $ss['num'];
      }
      $add['order_no'] = $orderNo;
      $add['pay_type'] = $post['pay_type'];
      $add['total'] = $total + $post['postage'];
      $add['postage'] = $post['postage'];

      $add['state_id'] = 1;
      $add['remark'] = $post['remark'];
      $add['address_id'] = $post['address_id'];
      $add['oper_user'] = $post['oper_user'];
      $add['oper_time'] = date('Y-m-d H:i:s');
      $add['delete_flag'] = 0;
      M('Order')->add($add);
      $this->OrderLog($orderNo, 1);
      $order['order_no'] = $orderNo;
      $order['pay_type'] = $post['pay_type'];
      $order['total'] = $total + $post['postage'];
      $order['postage'] = $post['postage'];
      $order['state_id'] = 1;
      $order['remark'] = $post['remark'];
      $order['address_id'] = $post['address_id'];
      $order['oper_user'] = $post['oper_user'];
      $order['oper_time'] = date('Y-m-d H:i:s');
      $order['delete_flag'] = 0;
      $order['supplier_name'] = $this->getSupplierName($key);
      $order['item'] = $this->getOrderGoodsList($s);

      $orderArr[] = $order;
    }
//    dump($orderArr);
//    $car['id'] = $post['carId'];
//    $car['delete_flag'] = 1;
//    M('ShoppingCart')->save($car); //清空购物车

    D('Car')->clearGoodsCart($checked);
    return $orderArr;
  }

  public function generateNO($supplierId) {
    $supp = M('Supplier')->where(array('id' => $supplierId))->find();
    $short = $supp['short_name']; //substr($supp['short_name'], 0, 2);
    $NO = $this->getMaxNO($short);
    M('TestOrders')->add(array('orderNo' => $NO, 'orderName' => 'testNo'));
    return $NO;
  }

  function build_order_no()
  {
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    /* PHPALLY + 年月日 + 6位随机数 + uid */
    return 'GS' . date('ymd') . str_pad(mt_rand(1, 999999), 4, '0', STR_PAD_LEFT).strtoupper(pickNo($_SESSION['current_user']['id']));
  }

  function build_pick_no()
  {
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    /* PHPALLY + 年月日 + 6位随机数 + uid */
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
  }

  public function changeNUM($gid, $buyNum, $type = 'order') {
    $result = M('Goodsother')->where(array('goods_id' => $gid))->find();
    if ($result) {
      if ($type == 'cancel') {
        $result['inventory'] = $result['inventory'] + $buyNum;
        $result['buy_number'] = $result['buy_number'] - $buyNum;
        $return = M('Goodsother')->save($result);
      } else {
        $result['inventory'] = $result['inventory'] - $buyNum;
        $result['buy_number'] = $result['buy_number'] + $buyNum;
        $return = M('Goodsother')->save($result);
      }
    } else {
      $result['goods_id'] = $gid;
      $result['inventory'] = 9999;
      $result['buy_number'] = $buyNum;
      $result['free_number'] = 0;
      $return = M('Goodsother')->add($result);
    }
    return $return;
  }

  public function changeState($orderNO, $state) {
    $result = M('Order')->where(array('order_no'=>$orderNO))->save(array('state_id'=>$state));
    $result = $this->OrderLog($orderNO, $state);
    return $result;
  }

  public function getMaxNO($short = '') {
    $where = $short . date('Ymd');
    $date = date('His');
    $sql = "select cast(max(substring(orderNo,-4)) as signed) as maxno from test_orders where orderNo like '%$where%'";
    $data = M()->query($sql);
    $num = $data[0][maxno] + 1;
    $No = substr('0000' . $num, -4);
    return $where . $date . $No;
  }

  public function OrderLog($orderNO, $state) {
    $log['state_id'] = $state;
    $log['order_no'] = $orderNO;
    $log['oper_user'] = $_SESSION['current_user']['id'];
    $log['oper_time'] = date('Y-m-d H:i:s');
    $result = M('OrderLog')->add($log);
    return $result;
  }

  public function getGoodsList($checked = '') {
    $goodsList = [];
    $uid = $_SESSION['current_user']['id'];
    foreach ($checked as $c) {
        $model = M('Goods')->join('goodsother on goods.id=goodsother.goods_id')->where(array('goods.id' => $c['id']))->find();
        $model['specArr'] = json_decode($model['spec'], true);
        $model['num'] = $c['num'];
        $model['id'] = $c['id'];
        $model['price'] = D('User')->isVIP($uid) ? $model['vip_price'] : $model['price'];
        $model['goodSums'] = $model['price'] * $model['num'];
        $total+=$model['price'] * $model['num'];
        $goodsList[] = $model;
    }
    $goods['list'] = $goodsList;
    $goods['total'] = $total;
    return $goods;
  }

    public function getOrderGoodsList($checked = '') {
        $goodsList = [];
        foreach ($checked as $c) {
            $model = M('Goods')->join('goodsother on goods.id=goodsother.goods_id')->where(array('goods.id' => $c['id']))->find();
            $model['num'] = $c['num'];
            $model['price'] = $c['price'];
            $model['supplier_name'] = $this->getSupplierName($model['supplier_id']);
            $total+=$model['price'] * $model['num'];
            $goodsList[] = $model;
        }
        $goods['list'] = $goodsList;
        $goods['total'] = $total;
        return $goods;
    }

    public function getOrdersGoodsList($checked = '') {
        $ordersGoodsList = [];
        $uid = $_SESSION['current_user']['id'];
        foreach ($checked as $c) {
            $where['a.order_no'] = $c['order_no'];
            $order = D('Order')->lists($where);
            $arr = json_decode($order[0]['goods_items'], TRUE);
            $goods = D('Order')->getGoodsList($arr);
            $pcaArr = json_decode($order[0]['address'], true);
            $order[0]['strAddress'] = $pcaArr['provinece_name'] . ' ' . $pcaArr['city_name'] . ' ' . $pcaArr['area_name'] . ' ' . $pcaArr['address'];
            $order[0]['goodslist'] = $goods['list'];
            $order_state = M('Order_state')->where(array('id' => $order[0]['state_id']))->find();
            $order[0]['order_state'] = $order_state['state'];
            $ordersGoodsList[] = $order[0];
        }
        return $ordersGoodsList;
    }

    public function lists($where = '', $page = '') {

        $where["a.delete_flag"] = 0;
        $list = M('Order')->table(array('order' => 'a'))->join(["address b on a.address_id=b.id", "order_state c on a.state_id=c.id", "order_item d on a.order_no=d.order_no", "pay_type e on a.pay_type=e.id"])->where($where)->field("*,a.oper_time as order_date,c.state,d.goods_items,e.pay_typename")->order('a.oper_time desc')->select();
    //    echo M('Order')->getLastSql();
        return $list;
    }

  public function delete($orderNO = '') {
    $order = M('Order')->table(array('order' => 'a'))->join('order_item b on a.order_no=b.order_no')->where(array('a.order_no' => $orderNO, 'a.delete_flag=0'))->find();
    $arr = json_decode($order['goods_items'], true);
    $goods = $this->getOrderGoodsList($arr);
    foreach ($goods['list'] as $l) {
      $num = $l['num'];
      $gid = $l['id'];
      $this->changeNUM($gid, $num, 'cancel');
    }
    $sql = "update `order` set delete_flag=1 , oper_time=CURRENT_TIMESTAMP where order_no ='$orderNO'";
    $result = M()->execute($sql);
    return TRUE;
  }

  public function remark($no = '', $remark = '') {
    $sql = "update `order` set remark='$remark' , oper_time=CURRENT_TIMESTAMP where order_no='$no'";
    $result = M()->execute($sql);
    return $result;
  }

  public function getDeliveryList($did = '', $where = '') {
    $where['delivery_user'] = $did;
    $list = $this->lists($where);
//    dump($list);
    $order = [];
    foreach ($list as $l) {
      $goods = json_decode($l['goods_items'], true);
      $l['goods'] = $this->getOrderGoodsList($goods);
      $l['supplier'] = $this->getSupplierName($l['goods']['list'][0]['supplier_id']);
      $order[] = $l;
    }
    return $order;
  }

  public function getSupplierName($sid = '') {
    $supplier = M('Supplier')->where(array('id' => $sid))->find();
    return $supplier['supplier_name'];
  }

  public function getAddress() {
    $sql = "select a.province,a.provinceid,b.city,b.cityid,c.areaid,c.area "
      . "from c_provinces a,c_cities b,c_areas c "
      . "where a.provinceid=b.provinceid and b.cityid=c.cityid";
    $address = M()->query($sql);
    $addressArr = [];
    $proArr = [];
    $cityArr = [];
    $areaArr = [];
    foreach ($address as $p) {
      $addressArr[$p['provinceid']]['name'] = $p['province'];
      $addressArr[$p['provinceid']]['children'][$p['cityid']]['name'] = $p['city'];
      $addressArr[$p['provinceid']]['children'][$p['cityid']]['children'][$p['areaid']]['name'] = $p['area'];
    }
    return $addressArr;
  }

  public function spellAddress($pcaArr = '') {
    $address['pname'] = M('CProvinces')->where(array('provinceid' => $pcaArr['provinceid']))->find();
    $address['cname'] = M('CCities')->where(array('cityid' => $pcaArr['cityid']))->find();
    $address['aname'] = M('CAreas')->where(array('areaid' => $pcaArr['areaid']))->find();
    $address['detail'] = $pcaArr['address'];
//    $address['strAddress'] = $address['pname']['province'] . ' ' . $address['cname']['city'] . ' ' . $address['aname']['address'] . ' ' . $address['detail'];
    return $address;
  }

  public function orderPay($order_no) {
    $where['a.order_no'] = $order_no;
    $order = $this->lists($where);
//    dump($order);
    $arr = json_decode($order[0]['goods_items'], TRUE);
    $goods = $this->getOrderGoodsList($arr);
//    dump($goods);
    $order[0]['item'] = $goods;
    $order[0]['supplier_name'] = $goods['list'][0]['supplier_name'];
//    dump($order[0]);
    return $order;
  }

  public function orderListPay($orderArr) {
    $orderList = [];
    foreach ($orderArr as $o) {
      $order = $this->orderPay($o['order_no']);
      $orderList[] = $order[0];
    }
    return $orderList;
  }

}

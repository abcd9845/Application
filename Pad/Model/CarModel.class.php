<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Model;

use Think\Model;

class CarModel extends Model {

    protected $autoCheckFields = false;

    public function getCar($uid = '') {

        $car = M('ShoppingCart')->where(array('oper_user' => $uid, 'delete_flag' => 0))->find();
        if ($car) {
            $goods = json_decode($car['goods_items'], true);
            $ids = list_column_data($goods, 'id');
            $result = array();
            if (count($ids) > 0) {
                $list = M('Goods')->where(array('id' => array('in', $ids)))->select();
                for ($i = 0; $i < count($list); $i++) {
                    $map[$list[$i]['id']] = $list[$i];
                }
                foreach ($goods as $good) {
                    if (array_key_exists($good['id'], $map)) {
                        $good['name'] = $map[$good['id']]['name'];
                        $good['title_pic'] = $map[$good['id']]['title_pic'];
                        $good['price'] = $map[$good['id']]['price'];
                        $good['vip_price'] = $map[$good['id']]['vip_price'];
                        $good['specArr'] = json_decode($map[$good['id']][spec], true);
                        $result[] = $good;
                    }
                }
            }
            $car['goods'] = $result;
        }
        return $car;
    }

    public function add($car) {
        $gid = $car['gid'];
        $oldCar = $this->getCar($car['oper_user']);
        $supplier = $this->getGoodsSupplier($gid);
        $goods = M('Goods')->where(array('id' => $gid, 'delete_flag=0'))->find();
        $goods_arr = array();
        $goods_arr['id'] = $gid;
        $goods_arr['num'] = 1;
        $goods_arr['price'] = D('User')->isVIP($car['oper_user']) ? $goods['vip_price'] : $goods['price'];
        
        $goods_arr['supplier_id'] = $supplier['sid'];
        $goods_arr['short_name'] = $supplier['short_name'];
        $total_price = 0;
        $goods_list = [];
        if ($oldCar) {
            $list = [];
            $goods_list = json_decode($oldCar['goods_items'], true);
            $exist = FALSE;
            foreach ($goods_list as $g) {
                if ($g['id'] == $goods['id']) {
                    $g['num'] = $g['num'] + 1;
                    $list[] = $g;
                    $exist = true;
                } else {
                    $list[] = $g;
                }
            }
            if (!$exist) {
                $list[] = $goods_arr;
            }
            foreach ($list as $l) {
                $total_price+=$l['num'] * $l['price'];
            }
            $item['goods_items'] = json_encode($list);
            $item['total_price'] = $total_price;
            $item['oper_time'] = date('Y-m-d h:s:i');
            $item['id'] = $oldCar['id'];
            $result = M('ShoppingCart')->save($item);
        } else {
            $goods_list[] = $goods_arr;
            $item['goods_items'] = json_encode($goods_list);
            $item['total_price'] = $goods_arr['price'];
            $item['oper_time'] = date('y-m-d h:s:i');
            $item['delete_flag'] = 0;
            $item['oper_user'] = $car['oper_user'];
//            dump($goods_arr);
            $result = M('ShoppingCart')->add($item);
        }
        return $result;
    }

    public function deleteGoods($gid = '', $id = '') {
        $car = M('ShoppingCart')->where(array('id' => $id))->find();
        $goods = json_decode($car['goods_items'], true);
        $goodslist = [];
        foreach ($goods as $g) {
            if ($g['id'] != $gid) {
                $goodslist[] = $g;
            }
        }

        foreach ($goodslist as $l) {
            $total_price+=$l['num'] * $l['price'];
        }
        $car['goods_items'] = json_encode($goodslist, TRUE);
        $car['total_price'] = $total_price;
        $result = M('ShoppingCart')->save($car);
        return $result;
    }

    public function clearGoodsCart($goodsArr=''){
        $uid = $_SESSION['current_user']['id'];
        $cart = M('ShoppingCart')->where(array('oper_user' => $uid, 'delete_flag' => 0))->find();
        $goods_items = [];
        $total_price = 0;
        $cart['oper_time'] = date('y-m-d h:s:i');
        if ($cart) {
            $goods = json_decode($cart['goods_items'], true);
            $ids = list_column_data($goodsArr, 'id');
            if (count($ids) > 0) {
                for ($i = 0; $i < count($goodsArr); $i++) {
                    $map[$goodsArr[$i]['id']] = $goodsArr[$i];
                }
                foreach ($goods as $good) {
                    if (array_key_exists($good['id'], $map)) {
                        $goodNum = $good['num'] - $map[$good['id']]['num'];
                        if ($goodNum>0) {
                            $good['num'] = $goodNum;
                            $total_price+=$good['num'] * $good['price'];
                            $goods_items[] = $good;
                        }
                    }  else {
                        $goods_items[] = $good;
                        $total_price+=$good['num'] * $good['price'];
                    }
                }
            }
            if (count($goods_items)>0){
                $cart['goods_items'] = json_encode($goods_items, TRUE);
                $cart['total_price'] = $total_price;
            }  else {
                $cart['delete_flag'] = 1;
            }
            M('ShoppingCart')->save($cart);
        }
        return $goods_items;
    }
    
    public function changeNum($gid = '', $id = '', $num = 0) {
        $car = M('ShoppingCart')->where(array('id' => $id, 'delete_flag' => 0))->find();
        $goods = json_decode($car['goods_items'], true);
        $goodslist = [];
        foreach ($goods as $g) {
            if ($g['id'] == $gid) {
                $g['num'] = $num;
                $goodslist[] = $g;
            } else {
                $goodslist[] = $g;
            }
        }
        foreach ($goodslist as $l) {
            $total_price+=$l['num'] * $l['price'];
        }

//    dump($goodslist);

        $car['goods_items'] = json_encode($goodslist, TRUE);
        $car['total_price'] = $total_price;
        $result = M('ShoppingCart')->save($car);
        return $result;
    }

    public function getGoodsList($id = '') {
        $car = M('ShoppingCart')->where(array('id' => $id))->find();
        $goods = json_decode($car['goods_items'], TRUE);
        return $goods;
    }

    public function getGoodsSupplier($gid = '') {
        $sql = "select a.id as gid,b.id as sid,b.short_name from goods a,supplier b where a.supplier_id=b.id and a.delete_flag=0 and a.id=$gid";
        $model = M()->query($sql);
        return $model[0];
    }

}

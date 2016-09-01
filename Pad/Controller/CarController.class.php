<?php

namespace Pad\Controller;

use Common\Controller\BaseController;

class CarController extends BaseController {

    public function index() {
        $uid = $_SESSION['current_user']['id'];
//    $uid = 1;
        $goods_list = D('Car')->getCar($uid);
        $goods_inventory = M('Goodsother')->select();

        foreach ($goods_inventory as $key => $gi) {
            $goodsother[$gi['goods_id']] = $gi;
        }
        $goods = [];
        foreach ($goods_list['goods'] as $g) {
            $g['inventory'] = $goodsother[$g['id']]['inventory'];
            $goods[] = $g;
        }
        $goods_list['goods'] = $goods;
        $this->assign('goodsList', $goods_list);
        $this->display();
    }

    public function carSuccess() {
        $post = I('post.id');
        $this->display();
    }

    public function goodsDelete() {
        $id = I('post.id');
        $gid = I('post.gid');
        $result = D('Car')->deleteGoods($gid, $id);
        if ($result) {
            $this->ajaxReturn('success');
        } else {
            $this->ajaxReturn('error');
        }
    }

    public function goodsChangeNum() {
        $id = I('post.id');
        $gid = I('post.gid');
        $num = I('post.num');
        $result = D('Car')->changeNum($gid, $id, $num);
        if ($result) {
            $this->ajaxReturn('success');
        } else {
            $this->ajaxReturn('error');
        }
    }

}

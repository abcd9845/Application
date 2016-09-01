<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Controller;

use Common\Controller\BaseController;

class FavouriteController extends BaseController {

    public function index() {
        $uid = $_SESSION['current_user']['id'];
        $list = D('Favourite')->lists($uid);
//    dump($list);
        $this->assign('goods_list', $list);
        $this->display();
    }

    public function add() {
        $fav['goods_id'] = I('post.gid');
        $fav['user_id'] = $_SESSION['current_user']['id'];
        $f = M('Favourite')->where($fav)->select();
        if (count($f) > 0) {
            if ($f[0]['delete_flag'] == 1) {
                $sql = "update favourite set delete_flag=0 where goods_id=" . $fav['goods_id'] . ' and user_id=' . $fav['user_id'];
                M()->execute($sql);
                $this->ajaxReturn('success');
            } else {
                $this->ajaxReturn('exist');
            }
        } else {
            $result = M('Favourite')->add($fav);
            if ($result) {
                $this->ajaxReturn('success');
            } else {
                $this->ajaxReturn('error');
            }
        }
    }

    public function delete() {
        $gid = I('post.gid');
        $uid = $_SESSION['current_user']['id'];
        $result = D('Favourite')->delete($uid, $gid);
        if ($result) {
            $this->ajaxReturn('success');
        } else {
            $this->ajaxReturn('error');
        }
    }

}

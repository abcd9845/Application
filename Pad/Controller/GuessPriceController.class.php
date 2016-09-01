<?php

namespace Pad\Controller;

use Think\Controller;

class GuessPriceController extends Controller {

  public function index() {
    $invitor = I('get.invitor', '');
    if (!$invitor) {
      $_SESSION['invitorUserId'] = 1;
    }
    $item = M('GuessPrice')->where("status = 'available' AND start_time < NOW() and end_time > NOW()")->find();
    $stat = M("GuessPriceUser")->field("COUNT(*) count, MAX(price) max_price, MIN(price) min_price")->where("guess_id='{$item['id']}'")->find();
    $item = array_merge($item, $stat);
    $this->assign('item', $item);
    $prevItem = M('GuessPrice')->where("status = 'finish' AND end_time < NOW()")->order('end_time DESC')->find();
    $this->assign('prevItem', $prevItem);
    $nextItem = M('GuessPrice')->where("status = 'available' AND start_time > NOW()")->order('start_time ASC')->find();
    $this->assign('nextItem', $nextItem);

    $user = $_SESSION['current_user'];
    $user['sumbit_count'] = M("GuessPriceUser")->where("user_id='{$user['id']}' AND guess_id='{$item['id']}'")->count();
    $user['chance_count'] = M("GuessPriceInvite")->where("from_user_id='{$user['id']}' AND guess_id='{$item['id']}'")->count() + 1;
    $user['price'] = M("GuessPriceUser")->where("user_id='{$user['id']}' AND guess_id='{$item['id']}'")->order('create_time DESC')->getField('price');
    $this->assign('user', $user);
    $this->display();
  }

  public function submitPrice() {
    $post = I("post.");
    $user = $_SESSION['current_user'];
    $submitCount = M("GuessPriceUser")->where("user_id='{$user['id']}' AND guess_id='{$item['id']}'")->count();
    $chanceCount = M("GuessPriceInvite")->where("from_user_id='{$user['id']}' AND guess_id='{$item['id']}'")->count() + 1;
    if ($submitCount < $chanceCount) {
      $data = array("user_id" => $user['id'], "guess_id" => $post['guess_id'], "price" => $post['submit_price'], 'create_time' => date('Y-m-d H:i:s'));
      M('GuessPriceUser')->add($data);

      if ($_SESSION['invitorUserId'] != 1) {
        $count = M("GuessPriceInvite")->where(array('from_user_id' => $_SESSION['invitorUserId'], 'to_user_id' => $user['id'], 'guess_id' => $post['guess_id']))->count();
        if ($count == 0) {
          M("GuessPriceInvite")->add(array(
              'from_user_id' => $_SESSION['invitorUserId'],
              'to_user_id' => $user['id'],
              'guess_id' => $post['guess_id'],
              'ratio' => 1,
              'create_time' => date('Y-m-d H:i:s')
          ));
        }
      }

      $this->success('请等待开奖结果。');
    } else {
      $this->error("您已经没有猜水果的机会啦，您可以分享此页面增加您的机会~~");
    }
  }

  public function result() {
    $id = I('get.id');
    $item = M('GuessPrice')->where(array("id" => $id))->find();
    $this->assign('item', $item);
    if ($item == null) {
      $err = '您访问的地址有误';
    } else {
      $prevItem = M('GuessPrice')->where(array("no" => array("lt", $item['no'])))->order("no desc")->find();
      $nextItem = M('GuessPrice')->where(array("no" => array("gt", $item['no'])))->order("no asc")->find();
      $this->assign('prevItem', $prevItem);
      $this->assign('nextItem', $nextItem);
      if ($item['status'] != "finish") {
        $err = '本期还没有结果';
      } else {
        $users = M("User")->field("real_name")->join("guess_price_user gpu ON gpu.user_id = user.id")->where(array("gpu.guess_id" => $item['id'], "gpu.result" => 1))->select();
        $this->assign('users', $users);
      }
      $allusers = M("User")->field("real_name, gpu.create_time")->join("guess_price_user gpu ON gpu.user_id = user.id")
                      ->where(array("gpu.guess_id" => $item['id']))->order("create_time ASC")->select();
      $this->assign('allusers', $allusers);
    }


    $this->assign("err", $err);

    $this->display();
  }

}

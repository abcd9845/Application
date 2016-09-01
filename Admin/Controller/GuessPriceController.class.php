<?php

namespace Admin\Controller;

use Think\Controller;

class GuessPriceController extends Controller {

  public function index() {
    $filters = array('query'=>'', 'start_time'=>'', 'end_time'=>'', 'status'=>'');        
    $filters = array_merge($filters, I('post.filters', array()));
    if($filters['query'] != "") {
      $where['no|title'] = array('like', '%' . $filters['query'] . '%');
    }
    if($filters['start_time'] != '') {
      $where['start_time'] = array('egt', $filters['start_time']);
    }
    if($filters['end_time'] != '') {
      $where['end_time'] = array('elt', $filters['end_time']);
    }
    if($filters['status'] != '') {
      $where['status'] = $filters['status'];
    } else {
      $where['status'] = array('in', array('available', 'draft'));
    }
    $count = M("GuessPrice")->where($where)->count();
    $page = new \Think\Page($count, 10);
    $list = M("GuessPrice")->where($where)->limit($page->firstRow, $page->listRows)->order("no DESC")->select();
    $this->assign('filters', $filters);
    $this->assign('list', $list);
    $this->assign('page', $page->show());
    $this->display();
  }

  public function toEdit() {
    $id = $_REQUEST['id'];
    
    $item = M('GuessPrice')->where(array("id" => $id))->find();
    $this->assign('obj', $item);
    
    $goods = M('Goods')->field('id, name')->where("online = 1")->order('name ASC')->select();
    $this->assign('goods', $goods);
    $this->display();
  }

  public  function publish() {
    $id = I('get.id');
    M('GuessPrice')->where(array('id'=>$id))->setField('status', 'available');
    $this->success('保存成功');
  }


  public function edit() {
    $data = I('post.');
    $data['create_time'] = date('Y-m-d H:i:s', time());
    unset($data['old_title_pic']);
    $m = M('guess_price');
    if ($data['id']) {
      $result1 = $m->data($data)->save();
    } else {
      $data['status'] = 'draft';
      $result1 = $m->data($data)->add();
    }



    if ($result1 >= 0) {
      if(isset($_REQUEST['old_title_pic']) && $_REQUEST['old_title_pic'] != $_REQUEST['img']){
        unlink(img_fullpath($_REQUEST['old_title_pic']));
      }
      $this->success('提交成功', 'index');
    } else {
      $this->error('提交失败', 'index');
    }
  }

  public function del() {
    $m = M('guess_price');
    $data['status'] = 'delete';
    $m->where('id in (' . $_REQUEST['id'] . ')')->save($data);
    $this->success('删除成功', U('index'));
  }

  public function toView() {
    $id = I('get.id', '');
    $filters = array('scope'=>'0');
    $filters = array_merge($filters, I('get.filters',array()));
    $item = M('GuessPrice')->where(array('id'=>$id))->find();
    if($item == null){
      $this->error('没有找到此条目，请选择其他');
    }
    $sql = "select gpu.*, u.real_name from guess_price_user gpu "
            . "JOIN user u ON u.id = gpu.user_id "
            . "where gpu.guess_id=$id";
    if($filters['scope'] == 1) {
      $sql .= " AND gpu.price = {$item['price']}";
    }
    $list = M()->query($sql);
    $this->assign('item', $item);
    $this->assign('list', $list);
    $this->assign('filters', $filters);
    $this->display();
  }

  public function ImgSaveToFile() {
    $config = array(
      'maxSize' => 100 * 1024 * 1024, // 单位是b
      'rootPath' => './Public/images/product/',
      'autoSub' => false,
      'exts' => array(
        'jpg',
        'gif',
        'png',
        'jpeg'
      )
    );
    $upload = new \Think\Upload($config);
    $info = $upload->upload();
    if (!$info) {// 上传错误提示错误信息
      $response = array(
        "status" => 'error',
        "message" => 'something went wrong',
      );
    } else {// 上传成功 获取上传文件信息
      $image = new \Think\Image();
      $image->open('./Public/images/product/' . $info['img']['savepath'] . $info['img']['savename']);
      $response = array(
        "status" => 'success',
        "url" => __ROOT__ . '/Public/images/product/' . $info['img']['savepath'] . $info['img']['savename'],
        "width" => $image->width(),
        "height" => $image->height()
      );
    }
    print json_encode($response);
  }

  public function ImgCropToFile() {
    /*
     * 	!!! THIS IS JUST AN EXAMPLE !!!, PLEASE USE ImageMagick or some other quality image processing libraries
     */
    $imgUrl = $_POST['imgUrl'];
// original sizes
    $imgInitW = $_POST['imgInitW'];
    $imgInitH = $_POST['imgInitH'];
// resized sizes
    $imgW = $_POST['imgW'];
    $imgH = $_POST['imgH'];
// offsets
    $imgY1 = $_POST['imgY1'];
    $imgX1 = $_POST['imgX1'];
// crop box
    $cropW = $_POST['cropW'];
    $cropH = $_POST['cropH'];
// rotation angle
    $angle = $_POST['rotation'];

    $jpeg_quality = 100;

    $what = new \Think\Image();
    $name = './' . $this::getPath($imgUrl) . $this::getFileName($imgUrl);
    $c_name = './' . $this::getPath($imgUrl) . 'c_' . $this::getFileName($imgUrl);
    $p_name = './' . $this::getPath($imgUrl) . 'p_' . $this::getFileName($imgUrl);
    $what->open($name);
    $what->thumb($imgW, $imgH)->save($c_name);
    $what->open($c_name);
    $what->crop(($cropW), ($cropH), $imgX1, $imgY1)->save($p_name);
    unlink($c_name);
    unlink($name);

    $m = M('img_mapping');
    $data = array();
    $data['user'] = $_SESSION['current_user']['id'];
    $data['img'] = './' . $this::getPath($p_name) . $this::getFileName($p_name);
    $m->data($data)->add();

    $response = Array(
      "status" => 'success',
      "url" => __ROOT__ . '/' . $this::getPath($p_name) . $this::getFileName($p_name)
    );
    print json_encode($response);
  }

  function getPath($name) {
    return substr($name, stripos($name, 'Public'), strrpos($name, '/') - stripos($name, 'Public') + 1);
  }

  function getFileName($name) {
    return substr($name, strrpos($name, '/') + 1);
  }

  function getPublicFilePath($name) {
    return substr($name, stripos($name, 'Public'));
  }

}

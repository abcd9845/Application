<?php

namespace Pad\Controller;

use Common\Controller\BaseController;

class UserController extends BaseController {

    public function info() {
        $uid = $_SESSION['current_user']['id'];
        $m = M('user');
        $obj = $m->where('id = '. $uid)->find();
        $this->assign('obj',$obj);
        $this->display();
    }

    public function edit(){
        $m = M('user');

        $data['password'] = $_REQUEST['password'];

        $data['real_name'] = $_REQUEST['real_name'];

        $data['mail'] = $_REQUEST['mail'];

        $data['mobile'] = $_REQUEST['mobile'];

        $data['oper_user'] = $_SESSION['current_user']['id'];

        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $data['id'] = $_REQUEST['id'];

        $m->save($data);
        $this->success('修改成功','info');
    }


}

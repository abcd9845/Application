<?php

namespace Pad\Controller;

use Common\Controller\BaseController;

class AddressController extends BaseController {

    public function lists() {
        $uid = $_SESSION['current_user']['id'];
        $address = M('Address')->where(array('user_id' => $uid, 'delete_flag' => 0))->select();
        $this->assign('array',$address);
        $this->display();
    }

    public function toAdd(){
        $this->display();
    }

    function add(){
        $m = M('address');
        $data = array();
        $data['provinceid'] = $_REQUEST['province'];
        $data['cityid'] = $_REQUEST['city'];
        $data['areaid'] = $_REQUEST['area'];
        $data['province'] = $_REQUEST['province_name'];
        $data['city'] = $_REQUEST['city_name'];
        $data['area'] = $_REQUEST['area_name'];
        $data['address'] = $_REQUEST['detailAddress'];
        $data['user_id'] = $_SESSION['current_user']['id'];
        $data['use_def'] = 0;
        $data['recipient'] = $_REQUEST['recipient'];
        $data['phone'] = $_REQUEST['phone'];
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());

        $m->add($data);
        $this->success('添加成功','lists');
    }

    function toEdit(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $address = M('Address')->where(array('id' => $id, 'delete_flag' => 0))->select();
        $this->assign('obj',$address[0]);
        $this->display();
    }

    public function edit(){
        $m = M('address');
        $data = array();
        $data['id'] = $_REQUEST['id'];
        $data['provinceid'] = $_REQUEST['province'];
        $data['cityid'] = $_REQUEST['city'];
        $data['areaid'] = $_REQUEST['area'];
        $data['province'] = $_REQUEST['province_name'];
        $data['city'] = $_REQUEST['city_name'];
        $data['area'] = $_REQUEST['area_name'];
        $data['address'] = $_REQUEST['detailAddress'];
        $data['user_id'] = $_SESSION['current_user']['id'];
        $data['recipient'] = $_REQUEST['recipient'];
        $data['phone'] = $_REQUEST['phone'];
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
        $m->save($data);
        $this->success('修改成功','lists');
    }

    public function del(){
        $m = M('address');
        $data['delete_flag'] = 1;
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);
        $this->success('删除成功','lists');
    }

    public function toDef(){
        $def = M('address');
        $all = M('address');
        $all_data = array();
        $def_data = array();

        $def->startTrans();

        $orig_def = M('address')->where('use_def = 1 and user_id = '.$_SESSION['current_user']['id'].' and delete_flag = 0')->find();

        $all_data['use_def'] = 0;
        $all_data['id'] = $orig_def['id'];
        $all_data['user_id'] = $_SESSION['current_user']['id'];
        $all_data['oper_user'] = $_SESSION['current_user']['id'];
        $all_data['oper_time'] = date('Y-m-d H:i:s',time());

        $def_data['id'] = $_REQUEST['id'];
        $def_data['use_def'] = 1;
        $def_data['oper_user'] = $_SESSION['current_user']['id'];
        $def_data['oper_time'] = date('Y-m-d H:i:s',time());

        $result2 = $all->save($all_data);
        $result1 = $def->save($def_data);

        if($result1>=0 && $result2>=0){
            $def->commit();
            $this->success('设置默认地址成功',__CONTROLLER__.'/lists');
        }else{
            $m->rollback();
            $this->error('设置默认地址失败',__CONTROLLER__.'/lists');
        }


    }




}

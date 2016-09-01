<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {


    public function index(){
//        $m = M('Supplier');
//        $count = $m->count();
//        $page = new \Think\Page($count,25);
//        $page->show();
//        $show = $page->show();
//        $arr = $m->limit($page->firstRow.','.$page->listRows)->select();
//        $this->assign('array',$arr);
//        $this->assign('show',$show);
//        $this->display();

        if($_SESSION['current_user']){
            $this->display();
        }  else {
            redirect(__APP__);
        }
    }

    public function login(){
        $this->display();
    }

    public function verifyCode() {
        $config = array(
            'fontSize' => 14, // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'imageW'=>0,
            'imageH'=>0
        );
        $verify = new \Think\Verify($config);
        $verify->entry();
    }

    public function logon() {

        $username = I('post.username');
        $password = I('post.password');
        $verifyCode = I('post.verify');
        $verify = new \Think\Verify();
        if(!$verify->check($verifyCode)) {
            $this->error("验证码错误", U('Index/login'));
        }

        $login['user.username'] = $username;
        $login['user.password'] = $password;
        $login['user.status&user.delete_flag'] = array('0', '0', '_multi'=>true);
//        $result = D('User')->logon($login);
        $user = M();
        $result = $user
            ->table('user')
            ->field('user.id,user.admin_school,user.username,user.last_login_address,user.last_login_delivery,user.password,user.real_name,user.role_id,user.mobile,user.status,user.front_admin,user.admin_school,school.name')
            ->join('left join school on school.id = user.admin_school')->where($login)->find();

        $result['gloab_role'] = 1;
        if($result!=null){
            if($result['admin_school'] == -1){
                $result['gloab_role'] = true;
            }else{
                $result['gloab_role'] = false;
            }
        }

        if ($result) {
            $_SESSION['current_user'] = $result;
            if($result['role_id'] == 1 ||$result['role_id'] == 3 ){
//                if($result['role_id'] == 5){
//                    $supplierObj = M('supplier')->where('id='.$result['supplier_id'])->find();
//                    $_SESSION['supplier'] = $supplierObj;
//                }
                $this->success('登录成功',__APP__.'/Admin');
//            }else if($result['role_id'] == 4 ){
//                $this->success('登录成功',__APP__.'/Pad/Delivery');
            }else{
                $this->error('登录失败,该用户名不能登录');
            }
        } else {
            $this->error('登录失败,用户名密码不存在');
        }

    }

    public function logoff() {
        $_SESSION['current_user'] = NULL;
        $this->success('注销成功',U('Index/login'));
    }
//
//    public function toAdd(){
//        $this->display();
//    }
//
//    public function add(){
//        $m = M('Supplier');
//        $data['supplier_name'] = $_REQUEST['supplier_name'];
//        $data['short_name'] = $_REQUEST['short_name'];
//        $m->data($data)->add();
//        $this->success('添加成功','index');
//    }
//
//    public function toEdit(){
//        $id = $_REQUEST['id'];
//        $this->assign('id',$id);
//        $m = M('Supplier');
//        $array = $m->where("id = ".$id)->select();
//        $this->assign('obj',$array[0]);
//        $this->display();
//    }
//
//    public function edit(){
//        $m = M('Supplier');
//        $data['supplier_name'] = $_REQUEST['supplier_name'];
//        $data['short_name'] = $_REQUEST['short_name'];
//        $data['id'] = $_REQUEST['id'];
//        $m->save($data);
//        $this->success('修改成功','index');
//    }
//
//    public function del(){
//        xx
//        $this->success('删除成功','index');
//    }

}
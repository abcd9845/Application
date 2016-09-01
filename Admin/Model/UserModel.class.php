<?php
namespace Admin\Model;
use Think\Model;
class UserModel extends Model {
    public function logon($login) {
        $user = M();
        $obj = $user
            ->table('user')
            ->field('user.id,user.username,user.last_login_address,user.last_login_delivery,user.password,user.real_name,user.role_id,user.mobile,user.status,user.front_admin,user.admin_school,school.name')
            ->join('left join school on school.id = user.admin_school')->where($login)->find();
        if($obj!=null){
            if($obj['admin_school'] == -1){
                $user['gloab_role'] = true;
            }else{
                $user['gloab_role'] = false;
            }
        }
        return $obj;
    }
}
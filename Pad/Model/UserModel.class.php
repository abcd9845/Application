<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UserModel
 *
 * @author Administrator
 */

namespace Pad\Model;

use Think\Model;

class UserModel extends Model {

    protected $autoCheckFields = false;

    public function _initialize() {
        
    }

    public function logon($login) {
        $user = M('User')->where($login)->find();
        if ($user){
            $user['isVIP'] = $this->isVIP($user['id']);
        }
        return $user;
    }

    public function isVIP($uid) {
        $user = M('User')->where(array('id' => $uid))->find();
        if ($user['role_id'] == 3) {
            return true;
        } else {
            return FALSE;
        }
    }
    
    

}

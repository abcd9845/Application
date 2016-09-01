<?php

namespace Common\Behavior;
use Think\Behavior;
defined('THINK_PATH') or exit();

// 初始化钩子信息
class InitPageVariablesBehavior extends Behavior {

    // 行为扩展的执行入口必须是run
    public function run(&$controller){    
    dump($controller);  
      $controller->assign('options',$controller->options);
      //$controller->assign('session',$controller->session);
    }
}
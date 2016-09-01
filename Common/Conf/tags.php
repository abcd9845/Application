<?php
//  return array(
////    'view_begin'=>array('Common\Behavior\InitPageVariables')
//      'app_end'=>array('CronRun'), // 定时任务，thinkphp固定名称，不可以改变
//  );

return array(
//    'view_filter' => array('Behavior\TokenBuildBehavior'),
    "action_end"=>array("Behavior\CronRunBehavior"),
 );
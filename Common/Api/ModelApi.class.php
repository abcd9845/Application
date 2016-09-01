<?php
namespace Common\Api;

class ModelApi {
  public static function getModelMeta($id = null, $field = null){
    static $list;
    /* 非法分类ID */
    if(!(is_numeric($id) || is_null($id))){
        return '';
    }

    /* 读取缓存数据 */
    if(empty($list)){
        $list = S('MODEL_META_LIST');
    }

    /* 获取模型名称 */
    if(empty($list)){
        $map   = array('status' => 1);
        $model = M('Model')->where($map)->field(true)->select();
        foreach ($model as $value) {
          $list[$value['id']] = $value;
        }
        S('DOCUMENT_MODEL_LIST', $list); //更新缓存
    }
    
    /* 根据条件返回数据 */
    if(is_null($id)){
        return $list;
    } elseif(is_null($field)){
        return $list[$id];
    } else {
        return $list[$id][$field];
    }
  }
  
  public static function parseValueSpace($string, $noValue = FALSE) {
    if(0 === strpos($string,':')){
      // 采用函数定义
      return eval(substr($string,1).';');
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
      $value = array();
      foreach ($array as $val) {
        $val = explode(':', $val);
        if(count($val) == 2 || $noValue)
          $value[$val[0]] = $val[1];
        else {
          $value[$val[0]] = array('code'=>$val[0], 'title'=>$val[1], 'value'=>$val[2]);
        }
      }
    }else{
      $value  =   $array;
    }
    return $value;
  }
}
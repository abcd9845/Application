<?php
namespace Admin\Model;
use Think\Model;
class BasicGoodsTypeModel extends Model {
//    protected $tableName = 'basic_goods_type';
    public function getBasicType(){
        return M('basic_goods_type')->where(array(delete_flag=>0))->select();
    }
}
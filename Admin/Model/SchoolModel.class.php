<?php
namespace Admin\Model;
use Think\Model;
class SchoolModel extends Model {
    public function getGoodsType($school_id){
        return M("goods_type")->where(array(school_id=>$school_id,delete_flag=>0))->select();
    }

    public function getSchoolList(){
        return M('school')->where(array('delete_flag'=>0,'state'=>1))->select();
    }



}
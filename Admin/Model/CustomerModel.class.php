<?php
namespace Admin\Model;
use Think\Model;
class CustomerModel extends Model {
    public function getGoodsType($school_id){
        return M("goods_type")->where(array(school_id=>$school_id,delete_flag=>0))->select();
    }

    public function getSchoolList(){
        return M('school')->where(array('delete_flag'=>0,'state'=>1))->select();
    }

    public function getCustomerCount(){
        return M('customer')->count();
    }

    public function getCustomerPaging(){
        $page = new \Think\Page($this::getCustomerCount(),PAGECOUNT);
        return array('list'=>M('customer')->limit($page->firstRow.','.$page->listRows)->order('oper_time desc')->select(),'page'=>$page);

    }



}
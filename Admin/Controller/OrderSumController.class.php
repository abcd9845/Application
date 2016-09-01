<?php
namespace Admin\Controller;
use Think\Controller;

class OrderSumController extends Controller {

    public function index(){
        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            $school_type = M('school');
            $school_type_result = $school_type->where(array(delete_flag=>0,state=>1))->select();
            $this->assign('school',$school_type_result);
        }else{
            $school_type = M('school');
            $school_type_result = $school_type->where(array(id=>$_SESSION[SESSION_USER]['admin_school']))->select();
            $this->assign('school',$school_type_result);
        }

        $filters = array(
            re_type=>'',
            school_type=>'',
            school=>'',
            address=>'',
            address_child=>'',
            create_time_start=>date('Y-m-d 00:00:00'),
            create_time_end=>date('Y-m-d 23:59:59'),
        );
        merge_filter($filters);
        $this->assign('filters', $filters);

        $sql = "select "
            ."school.name"
            .",`order`.re_type "
            .",count(`order`.id) as num "
            .",FORMAT(sum(`order`.total - `order`.expense),2) as total "
            .",FORMAT(sum(`order`.expense),2) as expense "
            .",FORMAT(sum(`order`.total - `order`.expense)*school.lirun,2) as lirun "
            .",FORMAT(avg(`order`.total - `order`.expense),2) as pjz "
            ."from "
            ."`order` "
            ."inner join school "
            ."on school.id = `order`.school_id "
            ."where 1=1 "
            ."and `order`.hdfk = 0";

        $sql .= " and `order`.create_time >= '".$filters['create_time_start']."'"
            ." and `order`.create_time <= '".$filters['create_time_end']."'";

        if(I('request.school')!=''){
            $sql .= " and `order`.school_id = ".I('request.school');
        }
        if(I('request.address')!=''){
            $sql .= " and `order`.address_id = ".I('request.address');
        }
        if(I('request.re_type')!=''){
            $sql .= " and `order`.re_type = ".I('request.re_type');
        }


        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if(I('request.re_type') == 0) {
                if(I('request.school_type')!='')
                    $sql .= " and school.type = ".I('request.school_type');

                if(I('request.school')!='')
                    $sql .= " and `order`.school_id = ".I('request.school');

                if(I('request.address')!='')
                    $sql .= " and `order`.address_id = ".I('request.address');

            }else if(I('request.re_type') == 1){
                if(I('request.school_type')!='')
                    $sql .= " and school.type = ".I('request.school_type');

                if(I('request.school')!='')
                    $sql .= " and `order`.school_id = ".I('request.school');

                if(I('request.address')!='')
                    $sql .= " and address.level = ".I('request.address');

                if(I('request.address_child')!='')
                    $sql .= " and address.id = ".I('request.address_child');
            }
        }else{
            if($filters['re_type'] == 0) {
                $sql .= " and school.id = ".$_SESSION[SESSION_USER]['admin_school'];

                if(I('request.address')!='')
                    $sql .= " and `order`.address_id = ".I('request.address');

            }else if($filters['re_type'] == 1){
                $sql .= " and school.id = ".$_SESSION[SESSION_USER]['admin_school'];

                if(I('request.address')!='')
                    $sql .= " and address.level = ".I('request.address');

                if(I('request.address_child')!='')
                    $sql .= " and address.id = ".I('request.address_child');
            }
        }




        $sql.=" and `order`.state_id in(2,3,4) "
            ."group by "
            ."name ";
        $schoolList = M()->query($sql);

        $sql1 = "select "
            ."date_format(`order`.create_time,'%Y-%m-%d') as create_time"
            .",school.name"
//            .",`order`.re_type "
            .",count(`order`.id) as num "
            .",FORMAT(sum(`order`.total - `order`.expense),2) as total "
            .",FORMAT(sum(`order`.expense),2) as expense "
            .",FORMAT(sum(`order`.total - `order`.expense)*school.lirun,2) as lirun "
            .",FORMAT(avg(`order`.total - `order`.expense),2) as pjz "
            ."from "
            ."`order` "
            ."inner join school "
            ."on school.id = `order`.school_id "
            ."where 1=1 "
            ."and `order`.hdfk = 0";

        $sql1 .= " and `order`.create_time >= '".$filters['create_time_start']."'"
            ." and `order`.create_time <= '".$filters['create_time_end']."'";

        if(I('request.school')!=''){
            $sql1 .= " and `order`.school_id = ".I('request.school');
        }
        if(I('request.address')!=''){
            $sql1 .= " and `order`.address_id = ".I('request.address');
        }
        if(I('request.re_type')!=''){
            $sql1 .= " and `order`.re_type = ".I('request.re_type');
        }


        if($_SESSION[SESSION_USER]['admin_school'] == ZONGCANG){
            if(I('request.re_type') == 0) {
                if(I('request.school_type')!='')
                    $sql1 .= " and school.type = ".I('request.school_type');

                if(I('request.school')!='')
                    $sql1 .= " and `order`.school_id = ".I('request.school');

                if(I('request.address')!='')
                    $sql1 .= " and `order`.address_id = ".I('request.address');

            }else if(I('request.re_type') == 1){
                if(I('request.school_type')!='')
                    $sql1 .= " and school.type = ".I('request.school_type');

                if(I('request.school')!='')
                    $sql1 .= " and `order`.school_id = ".I('request.school');

                if(I('request.address')!='')
                    $sql1 .= " and address.level = ".I('request.address');

                if(I('request.address_child')!='')
                    $sql1 .= " and address.id = ".I('request.address_child');
            }
        }else{
            if($filters['re_type'] == 0) {
                $sql1 .= " and school.id = ".$_SESSION[SESSION_USER]['admin_school'];

                if(I('request.address')!='')
                    $sql1 .= " and `order`.address_id = ".I('request.address');

            }else if($filters['re_type'] == 1){
                $sql1 .= " and school.id = ".$_SESSION[SESSION_USER]['admin_school'];

                if(I('request.address')!='')
                    $sql1 .= " and address.level = ".I('request.address');

                if(I('request.address_child')!='')
                    $sql1 .= " and address.id = ".I('request.address_child');
            }
        }




        $sql1.=" and `order`.state_id in(2,3,4) "
            ."group by "
            ."`order`.create_time,name";


        $schoolList1 = M()->query($sql1);


        $this->assign('array',$schoolList);
        $this->assign('array1',$schoolList1);
        $this->display();

    }


}
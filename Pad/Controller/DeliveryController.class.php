<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Controller;

use Common\Controller\BaseController;

class DeliveryController extends BaseController {

    public function index() {
        $uid = $_SESSION['current_user']['id'];
        $where['express'] = $_SESSION['current_user']['express_id'];
        if (I('post.')) {
            $querydate = I('post.querydate');
            $where['state_id'] = I('post.querystate');
            switch ($querydate) {
                case 'today':
                    $where['a.oper_time'] = array('egt', date('Y-m-d'));
                    break;
                case '1 week':
                    $where['a.oper_time'] = array('egt', date('Y-m-d', time() - 7 * 24 * 3600));
                    break;
                case '1 month':
                    $where['a.oper_time'] = array('egt', date('Y-m-d', time() - 30 * 24 * 3600));
                    break;
                case '3 month':
                    $where['a.oper_time'] = array('egt', date('Y-m-d', time() - 3 * 30 * 24 * 3600));
                    break;
                case '3 month ago':
                    $where['a.oper_time'] = array('lt', date('Y-m-d', time() - 3 * 30 * 24 * 3600));
                    break;
                default:
                    break;
            }
            $this->assign('querydate', $querydate);
            $this->assign('querystate', $where['state_id']);
        }else{
            $where['state_id'] =3;
        }

        $order = D('Order')->getDeliveryList($uid, $where);
        $state = D('OrderState')->where('id in (3,4)')->select();


//        for($i=0;$i<count($order);$i++){
//            $json = json_decode($order[$i]['address']);
//            $order[$i]['provinece_name'] = $json->provinece_name;
//            $order[$i]['city_name'] = $json->city_name;
//            $order[$i]['area_name'] = $json->area_name;
//            $order[$i]['address_name'] = $json->address;
//        }

        $this->assign('orders', $order);
        $this->assign('states', $state);

        $sn = getExpress_no($order[0]['express_no']);
        $this->assign('orderURL', $sn);

        $this->display();
    }

}

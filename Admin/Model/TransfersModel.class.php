<?php
namespace Admin\Model;
use Think\Model;
class TransfersModel extends Model {
    public function diaobo($transfersData,$transfersItem){
        $mTransfers = M('transfers');
        $mitem = M('storage_item');
        $mTransfers_item = M('transfers_item');

        $mStorage = M('storage');
        $mRuku = M('ruku');

        $model = M();
        try{

            $model->execute('lock tables storage write');
            $mTransfers->startTrans();

            //生成调拨数据
            $r_tranfers = $mTransfers->data($transfersData)->add();


            $itemData = array();
            foreach($transfersItem['counts'] as $k=>$v){
                $temp['transfers_id'] = $r_tranfers;
                $temp['count'] = $transfersItem['counts'][$k];
                $temp['goods_id'] = $transfersItem['ids'][$k];
                array_push($itemData,$temp);
            }


            $r_item = $mTransfers_item->addAll($itemData);

            foreach($transfersItem['counts'] as $k=>$v){

                //二级仓做入库
                $storagePo = $mStorage->where(array(school_id=>$transfersData['to_id'],basic_id=>$transfersItem['ids'][$k]))->find();

                $storageData['basic_id'] = $transfersItem['ids'][$k];
                $storageData['count'] = $transfersItem['counts'][$k];
                $storageData['school_id'] = $transfersData['to_id'];

                if($storagePo == null){
                    $pk = $mStorage->data($storageData)->add();
                    $item['storage_id'] = $pk;
                    $item['online'] = DOWN;
                    $item['oper_time'] = date('Y-m-d H:i:s',time());
                    $mitem->data($item)->add();
                }else{
                    $storageData['id'] = $storagePo['id'];
                    $storageData['count'] += $storagePo['count'];
                    $mStorage->save($storageData);
                }

                //发货仓减库存
                $storagePo_ZC = $mStorage->where(array(school_id=>$_SESSION[SESSION_USER]['admin_school'],basic_id=>$transfersItem['ids'][$k]))->find();

                $storagePo_ZC['count'] -= $transfersItem['counts'][$k];

                $mStorage->save($storagePo_ZC);


                //入库记录
                $ruku['type'] = RUKU;
                $ruku['school_id'] = $transfersData['to_id'];
                $ruku['goods_id'] = $transfersItem['ids'][$k];
                $ruku['count'] =  $transfersItem['counts'][$k];
                $ruku['oper_user'] = $_SESSION[SESSION_USER]['id'];
                $ruku['oper_time'] = date('Y-m-d H:i:s',time());

                $mRuku->data($ruku)->add();
            }

            $mTransfers->commit();
            $model->execute('unlock tables');
        }catch(Exception $e){
            $model->execute('unlock tables');
            $mTransfers->rollback();
        }



//        $mTransfers->commit();






//        $mStorage->startTrans();
//
//        //库里面由此产品更新数量记Log,没由此产品添加一条记Log
//        $storagePo = $mStorage->where(array(school_id=>$storageData['school_id'],basic_id=>$storageData['basic_id']))->find();
//
//        if($storagePo == null){
//            $result1 = $mStorage->data($storageData)->add();
//        }else{
//            $storageData['id'] = $storagePo['id'];
//            $storageData['count'] = $storagePo['count']+$storageData['count'] ;
//            $result1 = $mStorage->save($storageData);
//        }
//
//        $result2 = $mRuku->data($storageLog)->add();
//
//        if($result1 && $result2){
//            $mStorage->commit();
//        }else{
//            $mStorage->rollback();
//        }
//
//        $mStorage->commit();
    }



}
<?php
namespace Admin\Model;
use Think\Model;
class StorageModel extends Model {

    public function getStorage($id){
        $m = M();
        return $m->table('storage_item')
            ->field('basic_goods.name as goodsname,storage.school_id,storage_item.ishappy,storage_item.start_price,storage_item.end_price,storage_item.id,storage_item.show_type,storage_item.online,storage_item.isnew,storage_item.buynum,storage_item.price,storage_item.vip,storage_item.unit,storage_item.isnew,basic_goods.name as goods_name,basic_goods.description,storage_item.description as basic_description')
            ->join('left join storage on storage.id = storage_item.storage_id')
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->where(array('storage_item.id' => $id))
            ->find();

    }

    public function ruku($storageData,$ruku){
        $mStorage = M('storage');
        $mitem = M('storage_item');
        $mRuku = M('ruku');
        $model = M();
        try{
            $model->execute('lock tables storage write');
            $mStorage->startTrans();
            //库里面由此产品更新数量记Log,没由此产品添加一条记Log
            $storagePo = $mStorage->where(array(school_id=>$storageData['school_id'],basic_id=>$storageData['basic_id']))->find();

            if($storagePo == null){
                $pk = $mStorage->data($storageData)->add();
                $item['storage_id'] = $pk;
                $item['online'] = DOWN;
                $item['oper_time'] = date('Y-m-d H:i:s',time());
                $mitem->data($item)->add();
            }else{
                $storageData['id'] = $storagePo['id'];
                $storageData['count'] = $storagePo['count']+$storageData['count'];
                $mStorage->save($storageData);
            }

            $mRuku->data($ruku)->add();
            $mStorage->commit();
            $model->execute('unlock tables');
        }catch(Exception $e){
            $model->execute('unlock tables');
            $mStorage->rollback();
        }

    }



}
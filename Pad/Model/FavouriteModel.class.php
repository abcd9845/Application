<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Pad\Model;

use Think\Model;

class FavouriteModel extends Model {

    public function lists($uid = '') {
//        $gids = M('Favourite')->where(array('user_id' => $uid, 'delete_flag' => 0))->field('goods_id')->select();
        $where['a.user_id'] = $uid;
        $where['a.delete_flag'] = 0;
        $list =  M('favourite')->table('favourite a')
                    ->field('*,c.id as id')
                    ->join('left join goodsother b on a.goods_id = b.goods_id')
                    ->join('right join goods c on c.id = a.goods_id')
                    ->where($where)
                    ->select();
//        $ids = [];
//        foreach ($gids as $g) {
//            $ids[] = $g['goods_id'];
//        }
//        $list = M('Goods')->where(array('id' => array('in', $ids)))->select();
        $returnList = [];
        foreach ($list as $key) {
            $key['specArr'] = json_decode($key['spec']);
            $returnList[] = $key;
        }
        return $returnList;
    }

    public function delete($uid = '', $gid = '') {
        $sql = "update favourite set delete_flag=1 where goods_id=" . $gid . " and user_id=" . $uid;
//        echo $sql;
        $result = M()->execute($sql);
        return true;
    }

}

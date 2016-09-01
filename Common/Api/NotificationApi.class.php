<?php
namespace Common\Api;

class NotificationApi {
  public function create($aid, $action, $from_mid, $title, $url, $createTime, $topTime, $description, $to_mids) {
    $app = api('App/info', "id=$aid");
    $hash = md5($app['name'] . $from_mid . $url);
    $notice = array(
        'from_mid'=>$from_mid,
        'aid'=>$aid,
        'hash'=>$hash,
        'title'=>$title,
        'url'=>$url,
        'create_time'=>strtotime($createTime),
        'top_time'=>strtotime($topTime),
        'description'=>$description,
        'status'=>1
      );
    $id = M('Notice')->add($notice);
    $to_mids = is_array($to_mids) ? $to_mids : array($to_mids);
    $nu = array('notice_id'=>$id, 'mid'=>0, 'status'=>1, 'read_time'=>0);
    for($i = 0; $i < count($to_mids); $i++) {
      $nu['mid'] = $to_mids[$i];
      M('NoticeUser')->add($nu);
    }
    return TRUE;
  }
  
  public static function getList($mid = 0, $type = null, $offset = 0){
    $where['nu.mid'] = $mid;
    if($type !== NULL)
      $where['nu.status_code'] = $type;
    $list = M('Notice')
            ->field('m.truename, m.avatar, notice.*, nu.read_time')
            ->join('notice_user nu ON nu.notice_id = notice.id')
            ->join('member m ON m.mid = nu.mid')
            ->where($where)->order('notice.top_time DESC, notice.create_time DESC')->limit($offset, 10)->select();
    return $list;
  }
}
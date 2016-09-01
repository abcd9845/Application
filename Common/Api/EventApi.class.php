<?php
namespace Common\Api;

class EventApi {
  public function create($aid, $from_mid, $title, $url, $startTime, $endTime, $description, $place, $period, $to_mids) {
    $app = api('App/info', "id=$aid");
    $hash = md5($app['name'] . $from_mid . $url);
    $event = array(
        'from_mid'=>$from_mid,
        'aid'=>$aid,
        'hash'=>$hash,
        'title'=>$title,
        'url'=>$url,
        'start_time'=>strtotime($startTime),
        'end_time'=>strtotime($endTime),
        'description'=>$description,
        'place'=>$place,
        'period'=>$period,
        'status'=>1,
        'create_time'=>time()
      );
    $id = M('Event')->add($event);
    $to_mids = is_array($to_mids) ? $to_mids : array($to_mids);
    $eu = array('event_id'=>$id, 'mid'=>0, 'status'=>1);
    for($i = 0; $i < count($to_mids); $i++) {
      $eu['mid'] = $to_mids[$i];
      M('EventUser')->add($eu);
    }
    return TRUE;
  }


  public static function getList($mid = 0, $type = null, $startTime = '', $endTime = ''){
    $where['eu.mid'] = $mid;
    if($type !== NULL)
      $where['eu.status'] = $type;
    if($startTime != '')
      $where['event.start_time'] = array('egt', $startTime);
    if($endTime != '')
      $where['event.end_time'] = array('egt', $endTime);
      
    $list = M('Event')
            ->field('m.truename, m.avatar, event.*, eu.read_time')
            ->join('event_user eu ON eu.event_id = event.id')
            ->join('member m ON m.mid = eu.mid')
            ->where($where)->order('event.create_time DESC')->select();
    return $list;
  }
}
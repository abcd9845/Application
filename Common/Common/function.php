<?php
define('SENDTEMPLATE','sendTemplate');
define('GETIP','getIP');
define('USERINFO','userInfo');

function sortByNumber($a, $b) {
  if ((int)$a['count'] == (int)$b['count']) {
    return 0;
  } else {
    return ((int)$a['count'] > (int)$b['count']) ? -1 : 1;
  }
}

function getToken(){
  $url_get ='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='
      . C('APPID') . '&secret=' . C('SECRET');
  $accesstxt = weixin_curl_get($url_get);
  $access = json_decode ( $accesstxt, true );
  S('GSFARM_TOKEN',$access['access_token'],3600);
  return $access;
}

function getUserInfo($access_token,$openid){

  $url_get ='https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
  $accesstxt = weixin_curl_get($url_get);
  $access = json_decode ( $accesstxt, true );
  return $access;
}


function weixin_getAuthInfo($timeout = 3){
  if (!isset($_GET['code'])){
    $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING']);
    $url = __CreateOauthUrlForCode($baseUrl,true);
    Header("Location: $url");
    exit();
  } else {
    $post_data['appid'] = C('APPID');
    $post_data['secret'] = C('SECRET');
    $post_data['code'] = I('request.code');
    $post_data['grant_type'] = 'authorization_code';
    $url='https://api.weixin.qq.com/sns/oauth2/access_token';
    $result = weixin_curl_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.C('APPID').'&secret='.C('SECRET').'&code='.I('get.code').'&grant_type=authorization_code');
    $obj = json_decode($result);
    if($timeout != 0){
      if($obj->{'errcode'} == null || $obj->{'errcode'} != '0'){
        weixin_openid(--$timeout);
      }
    }
    return $obj;
  }
}


//wechat Code and OpenID


function weixin_openid($timeout = 3){
  if (!isset($_GET['code'])){
    $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING']);
    $url = __CreateOauthUrlForCode($baseUrl,false);
    Header("Location: $url");
    exit();
  } else {
    $post_data['appid'] = C('APPID');
    $post_data['secret'] = C('SECRET');
    $post_data['code'] = I('request.code');
    $post_data['grant_type'] = 'authorization_code';
    $url='https://api.weixin.qq.com/sns/oauth2/access_token';
    $result = weixin_curl_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.C('APPID').'&secret='.C('SECRET').'&code='.I('get.code').'&grant_type=authorization_code');
    $obj = json_decode($result);
    if($timeout != 0){
      if($obj->{'errcode'} == null || $obj->{'errcode'} != '0'){
        weixin_openid(--$timeout);
      }
    }
    return $obj->{'openid'};
  }
}

function init_openid(){
  $_SESSION['openid'] = weixin_openid();
}

function ToUrlParams($urlObj)
{
  $buff = "";
  foreach ($urlObj as $k => $v)
  {
    if($k != "sign"){
      $buff .= $k . "=" . $v . "&";
    }
  }

  $buff = trim($buff, "&");
  return $buff;
}

function __CreateOauthUrlForCode($redirectUrl,$auth)
{
  $urlObj["appid"] = C('APPID');
  $urlObj["redirect_uri"] = "$redirectUrl";
  $urlObj["response_type"] = "code";
  if($auth)
    $urlObj["scope"] = "snsapi_userinfo";
  else
    $urlObj["scope"] = "snsapi_base";
  $urlObj["state"] = "STATE"."#wechat_redirect";
  $bizString = ToUrlParams($urlObj);
  return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
}


//执行微信命令
function weixin_exec($type,$param = null,$timeout = 1){
  if($type == SENDTEMPLATE){
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.S('GSFARM_TOKEN');
    $method = 'post';
  }else if($type == GETIP){
    $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.S('GSFARM_TOKEN');
    $method = 'get';
  }else if($type == USERINFO){
    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.S('GSFARM_TOKEN').'&openid='.$_SESSION['openid'].'&lang=zh_CN';
    $method = 'get';
  }
  if($method == 'post'){
    $accesstxt = weixin_curl_post($url,$param);
  }else{
    $accesstxt = weixin_curl_get($url);
  }


  $access = json_decode ( $accesstxt, true );
  if($timeout != 0){
    if($access['errcode'] == '40001' ||  $access['errcode'] == '40014' || $access['errcode'] == '42001' || $access['errcode'] == '41001'){
        getToken();
        return weixin_exec($type,$param,--$timeout);
    }
  }

  return $access;
}

//预定通知
function sendSuccessMsg($param){
  //$param['openid']
  $template = array('touser' => 'oW5r-v8t46GxtfGrsIpMuJxsouyE',
      'template_id' => 'lLJuK4aK0v30ymqTncdu0MfgyxJdew2VVTaD4O2DRhI',
//      'url' => $param['url'],
      'topcolor' => '#7B68EE',
      'data' => array('first' => array(
          'value' => $param['title'],
          'color' => '#7E3A3A'
      ),
          'keyword1' => array(
              'value' => $param['name'],
              'color' => '#7E3A3A'
          ),
          'keyword2' => array(
              'value' => $param['leader'],
              'color' => '#7E3A3A'
          ),
          'remark' => array(
              'value' => urlencode($param['remark']),
              'color' => '#E84C3D'
          ),
      )
  );
  return urldecode(json_encode($template));
}

//预定通知
function sendFailedMsg($param){
  //$param['openid']
  $template = array('touser' => 'oW5r-v8t46GxtfGrsIpMuJxsouyE',
      'template_id' => 'lLJuK4aK0v30ymqTncdu0MfgyxJdew2VVTaD4O2DRhI',
//      'url' => $param['url'],
      'topcolor' => '#7B68EE',
      'data' => array('first' => array(
          'value' => $param['title'],
          'color' => '#7E3A3A'
      ),
          'keyword1' => array(
              'value' => $param['name'],
              'color' => '#7E3A3A'
          ),
          'keyword2' => array(
              'value' => $param['leader'],
              'color' => '#7E3A3A'
          ),
          'remark' => array(
              'value' => urlencode($param['remark']),
              'color' => '#E84C3D'
          ),
      )
  );
  return urldecode(json_encode($template));
}



//预定通知
function sendYDMsg($param,$openid){
  //$param['openid']
  $template = array('touser' => $openid,
      'template_id' => 'vPKHisBxXG-SxHXJzYNH7JbCFu5CP3qSKezDsh0RStQ',
//      'url' => $param['url'],
      'topcolor' => '#7B68EE',
      'data' => array('first' => array(
          'value' => urlencode('您预订的团购已经成功,等待开团'),
          'color' => '#7E3A3A'
      ),
          'productType' => array(
              'value' => '商品名称',
              'color' => '#7E3A3A'
          ),
          'name' => array(
              'value' => $param['name'],
              'color' => '#7E3A3A'
          ),
          'number' => array(
              'value' => $param['number'],
              'color' => '#7E3A3A'
          ),
          'expDate' => array(
              'value' => $param['expDate'],
              'color' => '#7E3A3A'
          ),
          'remark' => array(
              'value' => urlencode($param['remark']),
              'color' => '#E84C3D'
          ),
      )
  );
  return urldecode(json_encode($template));
}


//发送模版消息
function sendTemplateData($param){
    $template = array('touser' => $param['openid'],
        'template_id' => 'LlD_uzdnju1pqGByLfG1lakHJElOdVD9gu74HjVlTnA',
        'url' => $param['url'],
        'topcolor' => '#7B68EE',
        'data' => array('first' => array(
            'value' => urlencode('您的订单已创建成功'),
            'color' => '#7E3A3A'
            ),
            'orderno' => array(
                'value' => urlencode($param['orderno']),
                'color' => '#7E3A3A'
            ),
            'refundno' => array(
                'value' => urlencode(1),
                'color' => '#7E3A3A'
            ),
            'refundproduct' => array(
                'value' => urlencode($param['refundproduct']),
                'color' => '#7E3A3A'
            ),
            'remark' => array(
                'value' => urlencode($param['remark']),
                'color' => '#E84C3D'
            ),
        )
    );
  return urldecode(json_encode($template));
}


function img_fullpath($img){
  $v_arr = explode('/',$img);
  return './Public/images/product/'.$v_arr[count($v_arr)-1];
}

function getHappyOrderCount(){
  $m = M('order');

  $where = array(
      "create_time" => array(array('egt', date('Y-m-d') . ' 00:00:00'), array('elt', date('Y-m-d') . ' 23:59:59'))
    , "purchaser" => $_SESSION['openid']
    , "delete_flag" => 0
    , 'state_id'=>array('in',array(2,3,4))
  );

  $count = $m->where($where)->count();

  return $count;
}


/**
 * 相加，供模板使用
 * @param <type> $a
 * @param <type> $b
 * @author:liufangfang.net@gmail.com
 */
function  template_add( $a , $b ){
  echo ( intval ( $a )+ intval ( $b ));
}

/**
 * 相减，供模板使用
 * @param <type> $a
 * @param <type> $b
 * @author:liufangfang.net@gmail.com
 */
function Wtemplate_substract( $a , $b ){
  if(($a - $b) <= 0)
    echo 0.01;
  else
    echo $a - $b;
}

//后台使用
function Wtemplate_substract_back( $a , $b ){
  if(($a - $b) <= 0)
    return 0.01;
  else
    return round($a-$b,2);
}


//送货上门时间
function developerTime(){
  $delivery_time = M('delivery_time')->select();
  $now=strtotime(date('Y-m-d H:i:00'));
  foreach($delivery_time as $k=>$v){

    $start = strtotime(date('Y-m-d '.explode('-',$v['time'])[0].':00'));
    $end = strtotime(date('Y-m-d '.explode('-',$v['time'])[1].':59'));

    if($now>=$start&&$now<=$end)
    {
      return array(time=>date('Y-m-d '.$v['pick_time'].':00',strtotime('+'.$v['day'].' day')),id=>$v[id]);
    }

  }
  return array();
}

function developerTimeFilter($type = 0){
  $delivery_time = M('delivery_time')->where(array(school_id=>$_SESSION['school']['id'],type => $type))->select();
  $now=strtotime(date('Y-m-d H:i:00'));
  $array = array();
  foreach($delivery_time as $k=>$v){

    $start = strtotime(date('Y-m-d '.explode('-',$v['time'])[0].':00'));
    $end = strtotime(date('Y-m-d '.explode('-',$v['time'])[1].':59'));

    if(!($now>$start&&$now>$end))
    {
      if($delivery_time[$k]['pick_time'] == 'now'){
        $delivery_time[$k]['pick_time'] = date('Y-m-d').' '.date('H:i').'-'.date('H:i ',strtotime('+1 hour'));
      }else{
        $delivery_time[$k]['pick_time'] = date('Y-m-d',strtotime('+'.$v['day'].' day')).' '.$delivery_time[$k]['pick_time'];
      }
      array_push($array,$delivery_time[$k]);
    }
  }
  return $array;
}

function getPickTime($time_txt){
  $date = explode(' ',$time_txt);
  $time = explode('-',$date[1]);
  return $date[0].' '.$time[0].':00';
}

function _curl($url,$post_data){
  $o='';
  foreach ($post_data as $k=>$v)
  {
    $o.="$k=".$v.'&';
  }
  $post_data=substr($o,0,-1);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}


function weixin_curl_post($url,$param){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function weixin_curl_get($url){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);

  $result = curl_exec($ch);
  curl_close($ch);
  return $result;

}

function generate_code($length = 6) {
  $random = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
  return $random;
}

function sendSMS($phone){
    if($phone == null)
      return false;

    $code = generate_code();

    $post_data = array();
    $post_data['userid'] = C('SMS_ID');
    $post_data['account'] = C('SMS_ACCOUNT');
    $post_data['password'] = C('SMS_PWD');
    $post_data['content'] = '您在的手机验证码为：'.$code.'，如非本人操作，请及时反馈在线客服。';
    $post_data['mobile'] = $phone;
    $post_data['sendtime'] = '';
    $url=C('SMS_URL');
    $o='';
    foreach ($post_data as $k=>$v)
    {
      $o.="$k=".urlencode($v).'&';
    }
    $post_data=substr($o,0,-1);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    $xml = simplexml_load_string($result);
    if((string)$xml->returnstatus == 'Success'){
      $_SESSION[''] = $code;
      return true;
    }else{
      return false;
    }
}

function getOpenID(){
  $post_data = array();
  $post_data['grant_type'] = 'client_credential';
  $post_data['appid'] = '';
  $post_data['secret'] = '';
  $url='https://api.weixin.qq.com/cgi-bin/token';

  foreach ($post_data as $k=>$v)
  {
    $o.="$k=".urlencode($v).'&';
  }
  $post_data=substr($o,0,-1);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  $xml = simplexml_load_string($result);
  if((string)$xml->returnstatus == 'Success'){
    return true;
  }else{
    return false;
  }
}


function pickNo($address){
  $curtime=time()/$address;
  $rand=rand(100,999);
  $id = $curtime/$rand;
  $key = base_convert(ceil($id), 10, 36);
  return $key;
}

function getExpress_no($sn,$express = 'shunfeng'){
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, 'http://www.kuaidi100.com/applyurl?key=&com='.$express.'&nu='.$sn);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $data = curl_exec($curl);
  curl_close($curl);
  return $data;
}


function getPostageConf($provinceid = null){
  $postage = C('postage');
  if($postage[$provinceid] == null){
    return $postage['def'];
  }else{
    return $postage[$provinceid];
  }
}

function postageCompute($goods,$postageData){
  $total = 0;
  foreach($goods as $key => $data){
    $total += ($data['num'] * $data['weight']);
  }
  $weight = ceil($total / 1000);
  if($weight < 1)
    return 0;
  else if($weight == 1){
      return $postageData['first'] * $weight;
  }else{
    return $postageData['first']+(($weight-1) * $postageData['other']);
  }
}

function float_rand($Min, $Max, $round=0){
  //validate input
  if ($min>$Max) { $min=$Max; $max=$Min; }
  else { $min=$Min; $max=$Max; }
  $randomfloat = $min + mt_rand() / mt_getrandmax() * ($max - $min);
  if($round>0)
    $randomfloat = round($randomfloat,$round);

  return $randomfloat;
}



function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0) {
  $tree = array();
  if (is_array($list)) {
    $refer = array();
    foreach ($list as $key => $data) {
      $refer[$data[$pk]] = & $list[$key];
    }
    foreach ($list as $key => $data) {
      $parentId = $data[$pid];
      if ($root == $parentId) {
        $tree[] = & $list[$key];
      } else {
        if (isset($refer[$parentId])) {
          $parent = & $refer[$parentId];
          $parent[$child][] = & $list[$key];
        }
      }
    }
  }
  return $tree;
}

function tree_to_list($tree, $child = 'children', $order = 'id', $pid = 0, &$list = array()) {
  if (is_array($tree)) {
    $refer = array();
    foreach ($tree as $key => $value) {
      $reffer = $value;
      if (isset($reffer[$child])) {
        unset($reffer[$child]);
        tree_to_list($value[$child], $child, $order, $reffer['id'], $list);
      }
      $reffer['pid'] = $pid;
      $list[] = $reffer;
    }
  }
  return $list;
}

function list_sort_by($list, $field, $sortby = 'asc') {
  if (is_array($list)) {
    $refer = $resultSet = array();
    foreach ($list as $i => $data)
      $refer[$i] = &$data[$field];
    switch ($sortby) {
      case 'asc':
        asort($refer);
        break;
      case 'desc':
        arsort($refer);
        break;
      case 'nat':
        natcasesort($refer);
        break;
    }
    foreach ($refer as $key => $val)
      $resultSet[] = &$list[$key];
    return $resultSet;
  }
  return false;
}

function tree_to_view($tree, $ctpl = '<li data-id="[id]">[title][children]</li>', $ptpl = '<ol>[children]</ol>', $child = 'children', $search = NULL) {
  $result = "";
  if (!is_array($search) || count($search) == 0) {
    preg_match_all("/\[\w+\]/", $ctpl, $search);
    $search = $search[0];
  }
  $keys = str_replace(array('[', ']'), '', $search);
  for ($i = 0; $i < count($tree); $i++) {
    $ret = '';
    if (isset($tree[$i][$child]) && is_array($tree[$i][$child]) && count($tree[$i][$child])) {
      $ret = tree_to_view($tree[$i][$child], $ctpl, $ptpl, $child, $search);
    }
    foreach ($keys as $key) {
      $item[$key] = $key == $child ? $ret : $tree[$i][$key];
    }
    $childCode = str_replace($search, $item, $ctpl);
    $result .= $childCode;
  }
  return str_replace('[children]', $result, $ptpl);
}

function time_format($time = NULL, $format = 'Y-m-d H:i') {
  $time = $time === NULL ? NOW_TIME : intval($time);
  return date($format, $time);
}

function api($name, $vars = array()) {
  $array = explode('/', $name);
  $method = array_pop($array);
  $classname = array_pop($array);
  $module = $array ? array_pop($array) : 'Common';
  $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
  if (is_string($vars)) {
    parse_str($vars, $vars);
  }
  return call_user_func_array($callback, $vars);
}

function friend_time($time = NULL) {
  $text = '';
  $time = $time === NULL || $time > time() ? time() : intval($time);
  $t = time() - $time; //时间差 （秒）
  $y = date('Y', $time) - date('Y', time()); //是否跨年
  switch ($t) {
    case $t == 0:
      $text = '刚刚';
      break;
    case $t < 60:
      $text = $t . '秒前'; // 一分钟内
      break;
    case $t < 60 * 60:
      $text = floor($t / 60) . '分钟前'; //一小时内
      break;
    case $t < 60 * 60 * 24:
      $text = floor($t / (60 * 60)) . '小时前'; // 一天内
      break;
    case $t < 60 * 60 * 24 * 3:
      $text = floor($time / (60 * 60 * 24)) == 1 ? '昨天 ' . date('H:i', $time) : '前天 ' . date('H:i', $time); //昨天和前天
      break;
    case $t < 60 * 60 * 24 * 30:
      $text = date('m月d日 H:i', $time); //一个月内
      break;
    case $t < 60 * 60 * 24 * 365 && $y == 0:
      $text = date('m月d日', $time); //一年内
      break;
    default:
      $text = date('Y年m月d日', $time); //一年以前
      break;
  }

  return $text;
}

function list_column_data($array, $field) {
  if (is_array($array)) {
    $ret = [];
    foreach ($array as $v) {
      $ret[] = $v[$field];
    }
    return $ret;
  } else {
    return $array;
  }
}

function get_display($code, $name, $useValue = FALSE) {
  $valueSpace = D('ValueSpace');
  if ($useValue) {
    return $valueSpace->getValue($code, $name);
  } else {
    return $valueSpace->getTitle($code, $name);
  }
}

function trans_grade($year, $section) {
  $y = date('Y');
  $m = date('m');
  $s = get_display($section, 'study_section');
  $g = ['一', '二', '三', '四', '五', '六'];
  if ($m > 9)
    return $s . $g[$y - $year] . '年级';
  else
    return $s . $g[$y - $year - 1] . '年级';
}

function load_excel($fileName, $hasTitle, $columnNames, $sheet = 0) {
  if (!file_exists($fileName)) {
    return array("error" => 0, 'message' => '找不到文件！');
  }
  import("Vendor.PHPExcel.PHPExcel");
  import("Vendor.PHPExcel.PHPExcel.IOFactory");
  import("Vendor.PHPExcel.PHPExcel.Worksheet");

  try {
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    if (!$objReader->canRead($fileName)) {
      $objReader = PHPExcel_IOFactory::createReader('Excel5');
      if (!$objReader->canRead($fileName)) {
        return array("error" => 1, 'message' => '不是 Excel 文件！');
      }
    }
  } catch (Exception $e) {
    
  }
  $objReader = $objReader->load($fileName);
  $objReader->setActiveSheetIndex($sheet);         
  $sheet = $objReader->getActiveSheet();
  $i = 0;
  $array = [];
  $sheetname = $sheet->getTitle();
  $allRow = $sheet->getHighestRow();
  $highestColumn = $sheet->getHighestColumn();
  $allColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
  $array["Title"] = $sheetname;
  $array["Cols"] = $allColumn;
  $array["Rows"] = $allRow;
  $arr = array();

  for ($i = 0, $currentRow = $hasTitle ? 2 : 1; $currentRow <= $allRow; $i++, $currentRow++) {
    $row = array();
    for ($currentColumn = 0; $currentColumn < $allColumn; $currentColumn++) {
      $cell = $sheet->getCellByColumnAndRow($currentColumn, $currentRow);
      $col = PHPExcel_Cell::stringFromColumnIndex($currentColumn);
      $address = $col . $currentRow;
      $value = $sheet->getCell($address)->getValue();
      if (substr($value, 0, 1) == '=') {
        return array("error" => 1, 'message' => 'can not use the formula!');
        exit;
      }
      if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
        $cellstyleformat = $cell->getStyle($cell->getCoordinate())->getNumberFormat();
        $formatcode = $cellstyleformat->getFormatCode();
        if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
          $value = gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
        } else {
          $value = PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
        }
      }
      $row[$columnNames[$currentColumn]] = trim($value);
    }
    $arr[$i] = $row;
  }
  $array["Content"] = $arr;
  unset($sheet);
  unset($objReader);
  return array("error" => 0, "data" => $array);
}

function my_date_diff($date_1, $date_2, $differenceFormat = '%a') {
  $datetime1 = date_create($date_1);
  $datetime2 = date_create($date_2);

  $interval = date_diff($datetime1, $datetime2);

  return $interval->format($differenceFormat);
}

/**
 * 生成当前Action对应js地址
 */
function action_js() {
  $jsFile = '/Public/app/' . strtolower(MODULE_NAME) . '/js/' . strtolower(CONTROLLER_NAME) . '_' . ACTION_NAME . '.js';
  if(file_exists('.' . $jsFile))
    echo '<script type="text/javascript" src="' . __ROOT__ . $jsFile . '"></script>';
}

function merge_filter(&$filters){
  foreach($filters as $k => $v){
    if(I('request.'.$k) != ''){
      $filters[$k] = I('request.'.$k);
    }
  }
}
function add_where($w_name,$filter,&$where,$type=''){
  if($filter !== '') {
    if ($type === '') {
      $where[$w_name] = $filter;
    } else if ($type == 'd2d') {
      $where[$w_name] = array(array('egt', explode('|', $filter)[0]), array('elt', explode('|', $filter)[1]));
    } else if ($type == 'like') {
      $where[$w_name] = array($type, '%'.$filter.'%');
    } else {
      $where[$w_name] = array($type, $filter);
    }
  }
}
function add_param(&$page,$filters){
  foreach($filters as $k => $v){
    if($v != ''){
      $page[$k] = $v;
    }
  }
}


/**
 * 处理sql的where条件
 */
function wrapper_sql_where(&$where,$fieldName,$formName,$request,$type,&$filter){
  $val = trim($request[$formName]);
  if($val != ''){
    $where = $where . " and " . $fieldName . " = ";
    $filter[$formName] = $val;
    if($type == 'string'){
        $where = $where . "'" . $val . "'";
    }else{
        $where = $where . $val;
    }
  }
}


function wrapper_sql_where_lk(&$where,$fieldName,$formName,$request,&$filter){
  $val = trim($request[$formName]);
  if($val != ''){
    $where = $where . " and " . $fieldName . " like ";
    $filter[$formName] = $val;
    $where = $where . "'%" . $val . "%'";
  }
}

function wrapper_sql_where_qe_lt(&$where,$fieldName,$formName,$request,&$filter){
  $val = trim($request[$formName]);
  if($val != ''){
    $where = $where . " and " . $fieldName . " >= ";
    $where = $where . "'".$val." 00:00:00'";
    $where = $where . " and " . $fieldName . " <= ";
    $where = $where . "'".$val." 23:59:59'";
    $filter[$formName] = $val;
  }

}

/**
 * 向Page添加分页查询条件
 */
function set_page_param(&$page,$formName,&$request){
  $val = trim($request[$formName]);
  if($val != ''){
    $page->parameter[$formName] = urlencode($val);
  }
}

function id2Name($list){
  $goods_item = json_decode($list['goods_items']);
  for($i =0; $i<count($goods_item); $i++){
    $m = M();
    $arr = $m->table('goods')->field('goods.name,supplier.supplier_name')
        ->join('left join supplier on supplier.id = goods.supplier_id')
        ->where('goods.id = '.$goods_item[$i]->id . ' and goods.supplier_id = '.$goods_item[$i]->supplier_id)
        ->select();
    if(count($arr) == 1){
      $goods_item[$i]->id = $arr[0]['name'];
      $goods_item[$i]->supplier_id = $arr[0]['supplier_name'];
    }
  }
  $list['goods_items'] = $goods_item;
  return json_encode($list['goods_items']);
}

//* @param string $string 原文或者密文
//* @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
//* @param string $key 密钥
//* @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
//* @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
//*
//     * @example
//*
//     *  $a = authcode('abc', 'ENCODE', 'key');
//     *  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
//     *
//     *  $a = authcode('abc', 'ENCODE', 'key', 3600);
//     *  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
//     */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600) {

  $ckey_length = 4;
  // 随机密钥长度 取值 0-32;
  // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
  // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
  // 当此值为 0 时，则不产生随机密钥

  $key = md5($key ? $key : 'default_key'); //这里可以填写默认key值
  $keya = md5(substr($key, 0, 16));
  $keyb = md5(substr($key, 16, 16));
  $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

  $cryptkey = $keya.md5($keya.$keyc);
  $key_length = strlen($cryptkey);

  $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
  $string_length = strlen($string);

  $result = '';
  $box = range(0, 255);

  $rndkey = array();
  for($i = 0; $i <= 255; $i++) {
    $rndkey[$i] = ord($cryptkey[$i % $key_length]);
  }

  for($j = $i = 0; $i < 256; $i++) {
    $j = ($j + $box[$i] + $rndkey[$i]) % 256;
    $tmp = $box[$i];
    $box[$i] = $box[$j];
    $box[$j] = $tmp;
  }

  for($a = $j = $i = 0; $i < $string_length; $i++) {
    $a = ($a + 1) % 256;
    $j = ($j + $box[$a]) % 256;
    $tmp = $box[$a];
    $box[$a] = $box[$j];
    $box[$j] = $tmp;
    $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
  }

  if($operation == 'DECODE') {
    if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
      return substr($result, 26);
    } else {
      return '';
    }
  } else {
    return $keyc.str_replace('=', '', base64_encode($result));
  }

}

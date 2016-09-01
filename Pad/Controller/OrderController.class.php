<?php

namespace Pad\Controller;

use Common\Controller\BaseController;
use Alipay;
use Common\Common\WxPayUnifiedOrder;
use Common\Common\WxPayApi;
use Common\Common\JsApiPay;
use Common\Common\YouHui\YouHui;

//import('Wechat.example.lib.WxPay.Data');

class OrderController extends BaseController {

  public function getReciveTime(){
    //$order_count = M('order')->where(array('address_id'=>I('post.address_id'),'delete_flag'=>0,'create_time'=>array(array('egt',date('Y-m-d 00:00:00', time())),array('lt',date('Y-m-d 23:59:59', time())))))->count();
//    $_SESSION['order_count']
    $this->ajaxReturn(reciveTime($_SESSION['order_count']));
  }


  public function checkuser(){
    $user = D('User')->where(array('username' => $_SESSION['openid']))->select();
    if($user == null){
      $_SESSION['validateNum'] = null;
      $this->redirect('reg');
    }else {
      $login['username'] = $_SESSION['openid'];
      $login['status&delete_flag'] = array('0', '0', '_multi' => true);
      $result = D('User')->logon($login);
      if($result){
        $_SESSION['current_user'] = $result;
        $this->redirect('index');
      }else{
        $this->display('Index/errorPage');
      }
    }
  }

  public function reg(){
    $this->display('reg');
  }


  public function index(){
//    echo date('Y-m-d H:i:00');

//      $youhui = new YouHui();
//      $youhui->abc();
//      return;
      $count = M('huodong')->where(array(user_id=>$_SESSION['current_user']['username']))->count();
      if($count == 0){
        $this->assign('hashuodong',1);
      }else{
        $this->assign('hashuodong',0);
      }
//      $this->assign('hashuodong',0);

      $this->assign('adPo',D('Order')->getBuyAdss());

      $this->assign('nowtime',date('Y-m-d H:i:s',time()));

      $addressList = M('school_address')->where(array('school'=>$_SESSION['school']['id']))->select();

//      $deliveryList = M('delivery_time')->where(array('school_id'=>$_SESSION['school']['id']))->select();
//
//      foreach ($deliveryList as $k=>$v) {
//        $deliveryList[$k]['pick_time'] = date('Y-m-d '.$v['pick_time'].':00',strtotime('+'.$v['day'].' day'));
//      }

      $deliveryList = developerTimeFilter(1);
      $recivetimeList = developerTimeFilter(0);

      $zitiList= M('address')->where(array('school_id'=>$_SESSION['school']['id']))->select();

      $parent = array();
      $child = array();
      foreach ($zitiList as $k=>$v) {
        if($v['level'] == 0){
          array_push($parent,array(id=>$v['id'],name=>$v['address']));
        }
      }
      foreach($parent as $k=>$v){
        $tmp = array();
        foreach($zitiList as $k1=>$v1){
          if($v1['level'] == $v['id']){
            array_push($tmp,array(id=>$v1['id'],name=>$v1['address']));
          }
        }
        $child[$v['id']] = $tmp;
      }

      $this->assign('child',json_encode($child));
      $this->assign('parent',json_encode($parent));



//      $order_count = M('order')->where(array('address_id'=>$addressList[0]['id'],'delete_flag'=>0,'create_time'=>array(array('egt',date('Y-m-d 00:00:00', time())),array('lt',date('Y-m-d 23:59:59', time())))))->count();
//      $_SESSION['order_count'] = $order_count;

//      if(count($addressList) == 1){
//        $this->assign('recivetime',reciveTime());
//      }

      $this->assign('address',$addressList);
      $this->assign('addressCount',count($addressList));
      $this->assign('delivery',$deliveryList);
      $this->assign('recivetime',$recivetimeList);

      $pay = M('PayType')->where(array('state' => 0))->order('id')->select();
      $this->assign('pay', $pay);

      $count= D('Order')->where(array('purchaser' => $_SESSION['openid'],'delete_flag' => 0))->count();

      if($count == 0)
        $_SESSION['discount'] = 0;
//        $_SESSION['discount'] = 3;
      else
        $_SESSION['discount'] = 0;


      $this->display();

  }

  public function register(){
    $this->display();
  }

  public function postage(){
    $result = $this::postageJS(I('post.id'));
    if($result == null){
      $this->ajaxReturn(["status"=>false]);
    }else{
      $this->ajaxReturn(["status"=>true,"obj"=>$result]);
    }

  }

  private function postageJS($id){
    $address = M('Address')->where('id='.$id)->order('oper_time desc')->select();
    if(count($address) == 0){
      return null;
    }else {
      $postageData = getPostageConf($address[0]['provinceid']);
      $goods = $_SESSION['pay_goods'];
      if ($goods['total'] >= 100) {
        $postage = 0;
        $all_total = $goods['total'];
      } else {
        $postage = postageCompute($goods['list'], $postageData);
        $postage = $postage;
        $all_total = $goods['total'] + $postage;
      }
    }
    return ["total"=>$goods['total'],"postage"=>$postage,"all_total"=>$all_total];
  }



  public function getAddress() {
    $id = I('request.id');
    $address = M('Address')->where(array('id' => $id, 'delete_flag' => 0))->order('oper_time desc')->select();
    $this->ajaxReturn($address);
  }

  public function saveAddress() {
    $post['address'] = I('post.address');
    $post['provinceid'] = I('post.provinceid');
    $post['province'] = I('post.province');
    $post['cityid'] = I('post.cityid');
    $post['city'] = I('post.city');
    $post['areaid'] = I('post.areaid');
    $post['area'] = I('post.area');
    $post['recipient'] = I('post.recipient');
    $post['phone'] = I('post.phone');
    $post['user_id'] = $_SESSION['current_user']['id'];
    $post['oper_user'] = $_SESSION['current_user']['id'];
    $post['oper_time'] = date('Y-m-d h:i:s');
    $post['delete_flag'] = 0;
    $post['id'] = I('post.newaddid');
    if ($post['id']) {
      $flag = 'update';
      $result = M('Address')->save($post);
    } else {
      $flag = 'add';
      $result = M('Address')->add($post);
    }

    if ($result) {
      $return['id'] = $result;
      $return['result'] = 'success';
      $return['flag'] = $flag;
    } else {
      $return['result'] = 'error';
    }
    $this->ajaxReturn($return);
  }

  public function deleteAddress() {
    $item['id'] = I('post.id');
    $item['delete_flag'] = 1;
    $result = M('Address')->save($item);
    if ($result) {
      $this->ajaxReturn('success');
    } else {
      $this->ajaxReturn('error');
    }
  }


  public function weixin_succ()
  {
    $orderParam = $_SESSION['orderParam'];

    $tools = new JsApiPay();
    $input = new WxPayUnifiedOrder();
    $input->SetBody($orderParam['order_no']);
    $input->SetOut_trade_no('1'.generate_code(9). date("YmdHis"));
    $input->SetTotal_fee(Wtemplate_substract_back($orderParam['total'], $orderParam['discount']) * 100); //微信单位为分
//    $input->SetTotal_fee(1); //微信单位为分
    $input->SetNotify_url("http://weixin.yflh.net/index.php/Pad/Order/notify/id/" . $orderParam['id'] . "|" . $_SESSION['openid']);
    $input->SetTrade_type("JSAPI");
    $input->SetOpenid($_SESSION['openid']);

    $order = WxPayApi::unifiedOrder($input);
    $jsApiParameters = $tools->GetJsApiParameters($order);

    $this->ajaxReturn($jsApiParameters . '|' . $orderParam['id']);
  }



  public function ali_succ(){

    $orderParam = $_SESSION['orderParam'];

    $alipay_config['partner']   = '2088811687101201';

//收款支付宝帐户
    $alipay_config['seller_email']	= 'hugerice@qq.com';

//安全检验码，以数字和字母组成的32位字符
//如果签名方式设置为“MD5”时，请设置该参数
    $alipay_config['key']	= 'kpuda6v01ek9o2rakjq2qhnq9n1ixo4j';


//商户的私钥（后缀是.pem）文件相对路径
//如果签名方式设置为“0001”时，请设置该参数
    $alipay_config['private_key_path']	= 'key/rsa_private_key.pem';

//支付宝公钥（后缀是.pem）文件相对路径
//如果签名方式设置为“0001”时，请设置该参数
    $alipay_config['ali_public_key_path']= 'key/alipay_public_key.pem';


//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


//签名方式 不需修改
    $alipay_config['sign_type']    = 'MD5';

//字符编码格式 目前支持 gbk 或 utf-8
    $alipay_config['input_charset']= 'utf-8';

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
    $alipay_config['cacert']    = getcwd().'\\cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    $alipay_config['transport']    = 'http';



//返回格式
    $format = "xml";
//必填，不需要修改

//返回格式
    $v = "2.0";
//必填，不需要修改

//请求号
    $req_id = date('Ymdhis').generate_code(9);
//必填，须保证每次请求都是唯一

//**req_data详细信息**

//服务器异步通知页面路径
    $notify_url = "http://weixin.gsfarm.com.cn/index.php/Pad/Order/notify?id=".$orderParam['id']."|".$_SESSION['openid'];
//需http://格式的完整路径，不允许加?id=123这类自定义参数

//页面跳转同步通知页面路径
    $call_back_url = "http://weixin.gsfarm.com.cn/index.php/Pad/Order/callback?id=".$orderParam['id'];
//需http://格式的完整路径，不允许加?id=123这类自定义参数

//操作中断返回地址
    $merchant_url = "http://weixin.gsfarm.com.cn/WS_WAP_PAYWAP-PHP-UTF-8/xxxx.php";
//用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数

//商户订单号
    $out_trade_no = $orderParam['order_no'];
//商户网站订单系统中唯一订单号，必填

//订单名称
    $subject = $orderParam['order_no'];
//必填

//付款金额
    $total_fee = Wtemplate_substract_back($orderParam['total'],$orderParam['discount']);
//必填

//请求业务参数详细
//    echo '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . trim($alipay_config['seller_email']) . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
//    return;
    $req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . trim($alipay_config['seller_email']) . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
//必填
    /************************************************************/


//构造要请求的参数数组，无需改动
    $para_token = array(
        "service" => "alipay.wap.trade.create.direct",
        "partner" => trim($alipay_config['partner']),
        "sec_id" => trim($alipay_config['sign_type']),
        "format" => $format,
        "v"	=> $v,
        "req_id"	=> $req_id,
        "req_data"	=> $req_data,
        "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
    );

//    dump($para_token);
//    return;

//建立请求
    $alipaySubmit = new \Alipay\lib\AlipaySubmit($alipay_config);
    $html_text = $alipaySubmit->buildRequestHttp($para_token);


//URLDECODE返回的信息
    $html_text = urldecode($html_text);


//解析远程模拟提交后返回的信息
    $para_html_text = $alipaySubmit->parseResponse($html_text);


    //dump($para_html_text);
//获取request_token
    $request_token = $para_html_text['request_token'];

    //$url['call_back_url'] = urlencode($call_back_url);
//    $url['partner']  = $alipay_config['partner'];
//    $url['sec_id']  = $alipay_config['sign_type'];
//    $url['req_id']  = $req_id;
//    $url['format'] = $format;
//    $url['v'] = $v;
//    $url['req_data'] = urlencode($para_html_text['res_data']);
//    $url['sign'] = $para_html_text['sign'];
//    $url['service'] = 'alipay.wap.auth.authAndExecute';
    //$url['_input_charset'] = 'utf-8';

//    $urlstr = 'https://mclient.alipay.com/service/rest.htm?';
//    foreach ($url as $k=>$v)
//    {
//      $urlstr.="$k=".$v.'&';
//    }
//    $urlstr=substr($urlstr,0,-1);
//
////    dump($urlstr);
//    $this->ajaxReturn($urlstr);


    /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

//业务详细
    $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
//必填


//构造要请求的参数数组，无需改动
    $parameter = array(
        "service" => "alipay.wap.auth.authAndExecute",
        "partner" => trim($alipay_config['partner']),
        "sec_id" => trim($alipay_config['sign_type']),
        "format"	=> $format,
        "v"	=> $v,
        "req_id"	=> $req_id,
        "req_data"	=> $req_data,
        "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
    );



//    call_back_url=http%3A%2F%2Fm.taobao.com&partner=2088201962473581&
//    sec_id=MD5&v=2.0&format=xml&req_data=<%3Fxml+version%3D"1.0"+encoding%3D"UTF-8"%3F><auth_and_execute_req><request_token>201502045307e4beed899e2d08ee2d5b24745c80<%2Frequest_token><%2Fauth_and_execute_req>&
//    sign=1eee4b7f34c3ea185c084d6b723b7843&service=alipay.wap.auth.authAndExecute



    $url['sign'] = urlencode($para_html_text['sign']);
    $url['sec_id']  = urlencode(trim($alipay_config['sign_type']));
    $url['v'] = urlencode($v);
    $url['_input_charset']  = urlencode($alipay_config['input_charset']);
    $url['req_data'] = urlencode($req_data);
    $url['service']  = urlencode("alipay.wap.auth.authAndExecute");
    $url['partner']  = urlencode(trim($alipay_config['partner']));
    $url['format'] = urlencode($format);


//    $url['call_back_url'] = urlencode($call_back_url);








//    $url['partner']  = trim($alipay_config['partner']);
//
//    $url['req_id']  = $req_id;
//
//
//

    $urlstr = 'http://wappaygw.alipay.com/service/rest.htm?';
//    ksort($url);
//    reset($url);

     foreach ($url as $k=>$v)
    {
      $urlstr.="$k=".$v.'&';
    }
    $urlstr=substr($urlstr,0,-1);
//    $url['req_id'] = urlencode($para_html_text['res_data']);

//    $url['_input_charset'] = trim(strtolower($alipay_config['input_charset']));


//    $urlstr = 'http://wappaygw.alipay.com/service/rest.htm?';
//    $urlstr.='call_back_url='.urlencode($call_back_url).'&';
//    $urlstr.='partner='.trim($alipay_config['partner']).'&';
//    $urlstr.='sec_id='.trim($alipay_config['sign_type']).'&';
//    $urlstr.='v='.$v.'&';
//    $urlstr.='format='.$format.'&';
//    $urlstr.='req_data='.urlencode($para_html_text['res_data']).'&';
//    $urlstr.='sign='.$para_html_text['sign'].'&';
//    $urlstr.='service=alipay.wap.auth.authAndExecute&';

//    $urlstr.='_input_charset=utf-8';

//    foreach ($url as $k=>$v)
//    {
//      $urlstr.="$k=".$v.'&';
//    }
//    $urlstr=substr($urlstr,0,-1);


    //dump($urlstr);
//    $this->ajaxReturn($urlstr);

    //return;

//建立请求
    $alipaySubmit = new \Alipay\lib\AlipaySubmit($alipay_config);
    $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');

    echo $html_text;
    return;

//    $this->redirect('orderDetail',array('id'=>I('request.id')));
  }

  public function OrderCheck(){
    $orderParam = $_SESSION['orderParam'];
    $order = M('order')->where('id=' . $orderParam['id'])->find();
    if($order['state_id'] == 1){
      $this->redirect('payError',array('id'=>I('request.id')));
    }else{
      $this->redirect('Product/index');
    }
  }

  public function callback(){
      $this->redirect('paySuccess',array('id'=>I('request.id')));
  }

  public function notify(){
    $id = explode('|',I('request.id'))[0];
    $openid = explode('|',I('request.id'))[1];
    error_log("ID:".$id.'OPENID:'.$openid, 3, "1.log");
    $m = M('order');
    $m->startTrans();
      $order = $m->where('id = '.$id)->find();
      if($order['state_id'] == 1){
        $goods_param['state_id'] = 2;
        $goods_param['id'] = $id;

        //处理happy_time数据
        $array = json_decode($order['goods_items']);

        $happy_id = array();
        foreach($array as $k => $v){
          if($v->ishappy == 1){
            $h = M('happy_time')->where(array(
                "create_date" => array(array('egt', date('Y-m-d') . ' 00:00:00'), array('elt', date('Y-m-d') . ' 23:59:59'))
            ,"goods_id" => $v->id
            ,"user_id" => $openid
            ))->find();
            if($h){
              array_push($happy_id,$h['id']);
            }
          }

        }

        if(count($happy_id) > 0){
          M('happy_time')->data(array(is_buy=>1))->where(array(
              'id'=>array('in',$happy_id)
          ))->save();
        }

        $m->data($goods_param)->save();

        $param['openid'] = $openid;
        $param['url'] = 'http://weixin.yflh.net/index.php/Pad/Order/view?id='.$id.'&openid='.$openid;
        $param['orderno'] = $order['order_no'];
        if($order['re_type'] == 0){
          $param['refundproduct'] = Wtemplate_substract_back($order['total'],$order['discount']).'元';
        }else{
          $param['refundproduct'] = Wtemplate_substract_back($order['total'],$order['discount']).'元['.$order['expense'].'元快递费]';
        }

        if($order['re_type'] == 0)
          $param['remark'] = '\n取货时间：'.$order['pick_time'].'\n取货单号：'.$order['id'];
        else{
          $param['remark'] = '\n送货地点：'.htmlspecialchars_decode($order['delivery_address']);
          $param['remark'] .= '\n送货时间：'.$order['delivery_time_txt'];
        }

        $count = M('huodong')->where(array(user_id=>$openid))->count();
        if($count == 0){
          $addData = array();
          $addData['user_id'] = $openid;
          $addData['description'] = '金秋10月8折扣优惠';
          M('huodong')->data($addData)->add();
        }

        weixin_exec(SENDTEMPLATE,sendTemplateData($param));
      }
    $m->commit();

  }

  public function hdfkNotify($order){
    $param['openid'] = $_SESSION['openid'];
    $param['url'] = 'http://weixin.yflh.net/index.php/Pad/Order/view?id='.$order['id'].'&openid='.$_SESSION['openid'];
    $param['orderno'] = $order['order_no'];
    if($order['re_type'] == 0){
      $param['refundproduct'] = Wtemplate_substract_back($order['total'],$order['discount']).'元';
    }else{
      $param['refundproduct'] = Wtemplate_substract_back($order['total'],$order['discount']).'元['.$order['expense'].'元快递费]';
    }

    if($order['re_type'] == 0)
      $param['remark'] = '\n取货时间：'.$order['pick_time'].'\n取货单号：'.$order['id'];
    else{
      $param['remark'] = '\n送货地点：'.htmlspecialchars_decode($order['delivery_address']);
      $param['remark'] .= '\n送货时间：'.$order['delivery_time_txt'];
    }

    weixin_exec(SENDTEMPLATE,sendTemplateData($param));
  }

  public function save() {
    //$_SESSION['school']['company_discount'] = 0.8;
    $dazhe = true;
    $count = M('huodong')->where(array(user_id=>$_SESSION['current_user']['username']))->count();
    if($count == 0){
      $dazhe = true;
    }else{
      $dazhe = false;
    }

    $post['goods'] = I('post.goods');

    $d_time = M('delivery_time')->where(array(id=>I('post.pick_time')))->find();
    $d1_time = M('delivery_time')->where(array(id=>I('post.delivery_time')))->find();

    if(I('request.re_type') == 0){
      if(I('post.pick_time_txt') == ''){
        $post['pick_time_txt'] = developerTimeFilter(0)[0]['pick_time'];
      }else{
        $post['pick_time_txt'] = I('post.pick_time_txt');
      }
      $post['pick_time'] = getPickTime($post['pick_time_txt']);
    }

    if(I('request.re_type') == 1){
      if(I('post.delivery_time_txt') == ''){
        $post['delivery_time_txt'] = developerTimeFilter(1)[0]['pick_time'];
      }else{
        $post['delivery_time_txt'] = I('post.delivery_time_txt');
      }
      $post['pick_time'] = getPickTime($post['delivery_time_txt']);
    }

    $post['pay_type'] = I('post.pay_type');
    $post['oper_user'] = $_SESSION['current_user']['id'];
    $total = 0;

    $fruieTotal = 0;
    $otherTotal = 0;

    //0 自提 1 送货save上门
    $post['re_type'] = I('request.re_type');


    if(I('request.re_type') == 0){
      $post['address_id'] = I('post.address_id');
      $post['pick_no'] = pickNo($post['address_id']);
    }else{
      $post['delivery_address'] = I('request.address_txt');
      $post['delivery_address_id'] = I('request.address_txt_id');
    }

    $post['isnow'] = I('request.isnow');



    $post['happy_order'] = 0;

    $arr = json_decode($_POST['goods']);

    $happy_id = array();
    for($i=0;$i<count($arr);$i++){
      $goods = M('storage')
          ->field('
                basic_goods.title_pic,
                storage.count as inventory,
                basic_goods.name,
                storage.id,
                storage_item.vip as vip_price,
                storage_item.price,
                storage_item.unit,
                storage.basic_id,
                storage_item.show_type,
                storage.school_id,
                storage_item.ishappy,
                storage_item.start_price,
                storage_item.end_price,
                storage_item.buynum,
                storage_item.unit,
                storage_item.online,
                storage_item.isnew as show_icon,
                storage_item.description as de,
                basic_goods_type.id as basic_type_id
            ')
          ->join('storage_item on storage.id=storage_item.storage_id')
          ->join('basic_goods on basic_goods.id=storage.basic_id')
          ->join('basic_goods_type on basic_goods_type.id = basic_goods.goods_basic_type')
          ->where(array('storage.id'=>$arr[$i]->id,'storage_item.online'=>0,'basic_goods.delete_flag'=>0))->select();
      if($goods){
        $arr[$i]->price = $goods[0]['vip_price'];
        if($goods[0]['ishappy'] == 1){
          $obj = M('happy_time')->where(array(
              "create_date" => array(array('egt', date('Y-m-d') . ' 00:00:00'), array('elt', date('Y-m-d') . ' 23:59:59'))
             ,"user_id" => $_SESSION['current_user']['username']
             ,"school_type" => $_SESSION['school']['type']
             ,"school_id" => $_SESSION['school']['id']
              ,"goods_id"=>$arr[$i]->id
          ))->find();

          if($obj == null){
            $this->ajaxReturn(json_encode(array(status=>'error',msg=>$goods[0]['name'].'[幸运价] 不存在')));
            return;
          }

          if($obj['is_buy'] == 1){
            $this->ajaxReturn(json_encode(array(status=>'error',msg=>$goods[0]['name'].'[幸运价] 已经购买过')));
            return;
          }

          array_push($happy_id,$obj['id']);

          $arr[$i]->price = $obj['price'];
          $post['happy_order'] = $goods[0]['ishappy'];
        }
        $arr[$i]->name = $goods[0]['name'];


        if($_SESSION['school']['tactics'] == 0){
          if($arr[$i]->basic_type_id == 1 || $arr[$i]->basic_type_id == 8){
            $total += ($arr[$i]->price * $arr[$i]->count);
          }else{
            if($_SESSION['school']['company_discount_flag'] == 1){
              $total += ($arr[$i]->price * $arr[$i]->count) * $_SESSION['school']['company_discount'];
            }else{
              $total += ($arr[$i]->price * $arr[$i]->count);
            }
          }
        }else if($_SESSION['school']['tactics'] == 1){
          if($arr[$i]->basic_type_id == 8){
            $fruieTotal += ($arr[$i]->price * $arr[$i]->count);
          }else{
            if($_SESSION['school']['company_discount_flag'] == 1 && $arr[$i]->basic_type_id != 1){
              $otherTotal += ($arr[$i]->price * $arr[$i]->count) * $_SESSION['school']['company_discount'];
            }else{
              $otherTotal += ($arr[$i]->price * $arr[$i]->count);
            }
          }
        }else if($_SESSION['school']['tactics'] == 2){
          if($d_time['day'] == 1 || $d1_time['day'] == 1){//预定打折
            //if($arr[$i]->basic_type_id == 2 || $arr[$i]->basic_type_id == 3){
              if($dazhe == true)
                $total += ($arr[$i]->price * C('TACTICS_ROLE_ZHE_KOU') * $arr[$i]->count);
              else
                $total += ($arr[$i]->price * $arr[$i]->count);
//            }else{
//              if($_SESSION['school']['company_discount_flag'] == 1 && $arr[$i]->basic_type_id != 1){
//                $total += ($arr[$i]->price * $arr[$i]->count) * $_SESSION['school']['company_discount'];
//              }else{
//                $total += ($arr[$i]->price * $arr[$i]->count);
//              }
//            }
          }else{
            if($_SESSION['school']['company_discount_flag'] == 1 && $arr[$i]->basic_type_id != 1){
              $total += ($arr[$i]->price * $arr[$i]->count) * $_SESSION['school']['company_discount'];
            }else{
              $total += ($arr[$i]->price * $arr[$i]->count);
            }
          }
        }
      }else{
        $this->ajaxReturn('{"status":"error","msg":"'.$arr[$i]->name.'已下架"}');
        return;
      }
    };


    if($_SESSION['school']['tactics'] == 1) {
      $total = $fruieTotal - floor($fruieTotal / C('TACTICS_ROLE_ZHE_MAN')) * C('TACTICS_ROLE_ZHE_JIAN') + $otherTotal;
    }

    if($post['re_type'] == 1){
      if($total == 0.01){
        $post['expense'] = 0;
        $total = 0.01;
      }else if($total >= $_SESSION['school']['man']){
        $post['expense'] = $_SESSION['school']['expense']-$_SESSION['school']['jian'];
        $total += $_SESSION['school']['expense'] - $_SESSION['school']['jian'];
      }else{
        $post['expense'] = $_SESSION['school']['expense'];
        $total += $_SESSION['school']['expense'];
      }
    }

    $post['happy_id'] = $happy_id;

    $post['goods'] = $arr;
    $post['total'] = $total;
    $post['discount'] = $_SESSION['discount'];

    $orderParam = D('Order')->add($post);

    if($orderParam == null){
      $this->ajaxReturn('{"status":"error","msg":"内部错误"}');
    }else if($orderParam == -1) {
      $this->ajaxReturn('{"status":"error","msg":"部分商品已售罄,请重新选购!"}');
    }else{
      $_SESSION['orderParam'] = $orderParam;
      if($post['pay_type'] == 1){//微信
        $this::weixin_succ();
      }else if($post['pay_type'] == 2){//支付宝
        $this::ali_succ();
      }else{
        $this->hdfkNotify($orderParam);
        echo json_encode(array(
            id=>$orderParam['id']
        ));
      }
    }

  }

  public function pay($orderList) {
//    dump($orderList);
    $this->assign('orderList', $orderList);
    $this->display('pay');
  }

  public function orderPay($orderList = '') {
    if ($orderList) {
      $orderList = D('Order')->orderListPay($orderList);
    } else {
      $order_no = I('get.orderno');
      $orderList = D('Order')->orderPay($order_no);
    }
//    dump($orderList);
    $this->assign('orderList', $orderList);
    $this->display('pay');
  }

  public function lists() {
    init_openid();
    $this->redirect('Order/listsShow?scope=today');
  }

  public function listsShow() {
    $filter = array();
    $this->assign('filter',$filter);

    $m = M();
    $m->table('order')->field('order.delivery_time_txt,order.id,order.expense,order.re_type,order.delivery_address,user.real_name,order.pick_no,order.state_id,order.pick_time,order.total,order.discount,order.id,order.create_time,school_address.address,user.mobile,order.order_no,order_state.state,order.remark ')
        ->join('left join order_state on order_state.id = order.state_id')
        ->join('left join school_address on school_address.id = order.address_id')
        ->join('left join user on user.username = order.purchaser');
    if(I('request.scope') == 'today')
      $m->where(array('order.state_id'=>array('in','0,2,3,4,5'),'order.delete_flag'=>0,'order.purchaser'=>$_SESSION['openid'],'create_time'=>array(array('egt',date('Y-m-d 00:00:00', time())),array('lt',date('Y-m-d 23:59:59', time())))));
    else
      $m->where(array('order.state_id'=>array('in','0,2,3,4,5'),'order.delete_flag'=>0,'order.purchaser'=>$_SESSION['openid']));

    $arr = $m->order('order.create_time desc')->select();
    $this->assign('array',$arr);
    if(I('request.scope') == 'today')
      $this->display();
    else
      $this->display('Order/listsHistory');
  }



  public function paySuccess(){
    $id = I('request.id');
    $m = M();
    $arr = $m->table('order')->field('order.re_type,order.delivery_address,order.pay_type,order.pick_no,order.pick_time,order.id,school_address.address,order.express_no,user.mobile,order.order_no,order_state.state,order.remark,order.total,order.goods_items,order.discount')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join order_state on order_state.id = order.state_id')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where('order.id = '.$id)
        ->find();
    $this->assign('t',$arr);
    $this->display();

  }
  public function orderDetail() {
    $id = I('request.id');
    $m = M();
    $arr = $m->table('order')->field('user.real_name,order.pick_no,order.pick_time,order.id,school_address.address,order.express_no,user.mobile,order.order_no,order_state.state,order.remark,order.total,order.goods_items,order.discount')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join order_state on order_state.id = order.state_id')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where('order.id = '.$id)
        ->select();
    $this->assign('t',$arr[0]);
    $this->display();
  }

  public function cancelOrder(){
    $id = I('request.id');
    $order = M('order')->where(array(id=>$id))->find();
    if($order['state_id'] == 5){
      M('order')->save(array(id=>$id,state_id=>0));
    }
    $this->redirect('cancelSuccess');
  }

  public function cancelSuccess(){
    $this->display();
  }

  public function view() {
    $id = I('request.id');
    $m = M();
    $arr = $m->table('order')->field('order.expense,order.delivery_time_txt,order.expense,order.re_type,order.delivery_address,user.real_name,order.pick_no,order.pick_time,order.id,school_address.address,order.express_no,user.mobile,order.order_no,order_state.state,order_state.id as state_id,order.remark,order.total,order.goods_items,order.discount')
        ->join('left join user on user.username = order.purchaser')
        ->join('left join order_state on order_state.id = order.state_id')
        ->join('left join school_address on school_address.id = order.address_id')
        ->where('order.id = '.$id)
        ->select();
    $scope = I('request.scope');

    $this->assign('t',$arr[0]);
    $this->assign('scope',$scope);
    $this->display();
  }

  public function delete() {
    $orderNO = I('post.orderno');
    $result = D('Order')->delete($orderNO);
    if ($result) {
      $this->ajaxReturn('success');
//      $this->success('撤单成功', U('Order/lists'), 3);
    } else {
      $this->ajaxReturn('error');
//      $this->error('无法撤单', 3);
    }
  }

  public function signOff() {
    $uid = $_SESSION['current_user']['id'];
    $oid = I('post.oid');
    $no = I('post.no');
    $order['id'] = $oid;
    $order['state_id'] = 4;
    $sql = "update `order` set state_id=4 , oper_time=CURRENT_TIMESTAMP where order_no='$no'";
    M()->execute($sql);
//        echo $sql;
    D('Order')->OrderLog($no, 4);
    $this->ajaxReturn('success');
  }

  public function comment() {
    $order['order_no'] = I('post.no');
    $order['remark'] = I('post.comment');
    $result = D('Order')->remark($order['order_no'], $order['remark']);
    $this->ajaxReturn('success');
  }

  public function testGetjson() {
    $address = D('Order')->getAddress();
    $this->ajaxReturn($address);
  }

}

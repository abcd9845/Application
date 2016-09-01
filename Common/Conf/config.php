<?php

//用户Session名
define('SESSION_USER','current_user');
//总仓
define('ZONGCANG','1');

//入库类型
define('RUKU','0');

//出库类型
define('CHUKU','1');

//上架／下架
define('UP','0');
define('DOWN','1');

//new icon
define('ISNEW','1');
define('UNNEW','0');

//基础商品类型
define('HAPPYTYPE','1');
define('NOWTYPE','2');


//欢乐时光、现提
define('HAPPYMENU','-1');
define('NOWMENU','-2');

define('PAGECOUNT','50');

return array(
  'MODULE_DENY_LIST' => array('Common', 'User'),
  'DEFAULT_MODULE' => 'Pad',
  /* 调试配置 */
  'SHOW_PAGE_TRACE' => false,
  /* 数据库配置 */
  'DB_TYPE' => 'MySql', // 数据库类型
  'DB_HOST' => 'localhost', // 服务器地址
  'DB_NAME' => 'moxibustion', // 数据库名
  'DB_USER' => 'root', // 用户名
  'DB_PWD' => '',
  'DB_PORT' => '3306', // 端口
  'DB_PREFIX' => '', // 数据库表前缀
  'DB_CASE_LOWER' => true,
  'DB_TRIGGER_PREFIX' => 'tr_',
  'DB_SEQUENCE_PREFIX' => 'ts_',
  /* URL配置 */
  'URL_CASE_INSENSITIVE' => true,
  'URL_MODEL' => 1, //URL模式
  'VAR_URL_PARAMS' => '', // PATHINFO URL参数变量
  'URL_PATHINFO_DEPR' => '/', //PATHINFO URL分割符

  /* 模板配置 */
  'URL_HTML_SUFFIX' => '',
  'TMPL_PARSE_STRING' => [
    '__UPLOAD__' => __ROOT__ . '/Upload'
  ],
  'UPLOAD_CONFIG' => [
    'maxSize' => 67108864,
    'rootPath' => './Upload/',
    'savePath' => '',
    'saveName' => array('uniqid', ''),
    'autoSub' => true,
    'subName' => array('date', 'Ym')
  ],
  '__UPLOAD__' => __ROOT__ . '/Upload',

  FIRST_DISCOUNT_MSG => '首次下单，立减',
  FIRST_DISCOUNT => 3,
  RECIVEEVERYSTEPDAY => '1',
  RECIVEEVERYDAY => '17:00:00',
  RECIVETIMESTAMP => 1200,
  RECIVECOUNT => 500,

);

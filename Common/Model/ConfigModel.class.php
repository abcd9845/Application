<?php
namespace Common\Model;
use Think\Model;
class ConfigModel extends Model {
  public function load() {
    $config = S('DB_CONFIG_DATA');
    if (!$config) {
      $config = api('Config/lists');
      S('DB_CONFIG_DATA', $config);
    }
    C($config);
  }
}
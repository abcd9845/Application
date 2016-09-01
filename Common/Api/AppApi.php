<?php

namespace Common\Api;
class AppApi {
  public static function info($aid) {
    $app = M('App')->where("aid=$aid")->find();
    return $app;
  }
}
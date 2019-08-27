<?php

namespace AllBoatsRise\WebhookHandler;

use Symfony\Component\Yaml\Yaml;

class Config {
  private static $config = null;

  static function get() {
    $file = dirname(__DIR__) . '/config.yml';

    if (self::$config === null) {
      self::$config = Yaml::parseFile($file);
    }

    return self::$config;
  }

  static function getHandlers() {
    return self::get()['handlers'];
  }
}

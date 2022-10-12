<?php
namespace ChristopherBolt\BoltShopTools\Helpers;

use SilverStripe\Control\Controller;

class Session {
    private static function object() {
        return Controller::curr()->getRequest()->getSession();
    }
    public static function get($name) {
        return static::object()->get($name);
    }
    public static function set($name, $val) {
        return static::object()->set($name, $val);
    }
    public static function clear($name) {
        return static::object()->clear($name);
    }
}
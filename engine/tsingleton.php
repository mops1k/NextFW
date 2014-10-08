<?php
namespace NextFW\Engine;


trait TSingleton
{
    private static $instance = array();
    private static $cursor;
    public function getInstance($cursor = 0) {
        if ($cursor !== null) {
            self::$cursor = $cursor;
        }
        if (!array_key_exists(self::$cursor, self::$instance)) {
            self::$instance[self::$cursor] = new self();
        }
        return self::$instance[self::$cursor];
    }
    public function gI($cursor = 0) {
        $this->getInstance($cursor);
    }
    public static function get($class,$cursor = 0) {
        if ($cursor !== null) {
            self::$cursor = $cursor;
        }
        if (!array_key_exists(self::$cursor, self::$instance)) {
            self::$instance[self::$cursor] = new $class();
        }
        return self::$instance[self::$cursor];
    }
}
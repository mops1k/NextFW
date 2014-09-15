<?php
namespace NextFW\Engine;


trait TSingleton
{
    /**
     * @var self
     */
    private static
        $instance = NULL;

    /**
     * @return TSingleton
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function gI()
    {
        return self::getInstance();
    }

    /**
     * @return self
     */
    public static function get($class)
    {
        try {
            self::$instance = new $class;

            return self::$instance;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    public function test()
    {
        var_dump($this);
    }
}
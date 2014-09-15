<?php
namespace NextFW\Config;

class Main {
    static public $debug = true; // change to false in production version
    static public $template = 'default'; // default template name folder: view/default/
    static public $dbEnabled = false; // database status in framework
    static public $initPage = "index:test"; // string like controller:method
}
<?php
namespace NextFW;

/* const section */
define("PATH",dirname(__FILE__).DIRECTORY_SEPARATOR);
define("LOG",PATH."logs".DIRECTORY_SEPARATOR);
/* end const section */

require_once PATH."engine/autoload.php";

/* use section */
use NextFW\Engine as Engine;
use NextFW\Config as Config;
/* end section */

/* enable autoload */
new Engine\Autoload();

/* glabal set */
class Vars
{
    static public $get, $post;
    static function init()
    {
        if (count($_POST) > 0) {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $val) {
                        self::$post[ $k ] = strip_tags($val);
                    }
                } else {
                    self::$post[ $key ] = strip_tags($value);
                }
            }
        }
        if (count($_GET) > 0) {
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $val) {
                        self::$get[ $k ] = strip_tags($val);
                    }
                } else {
                    self::$get[ $key ] = strip_tags($value);
                }
            }
        }
        self::$get = (object)self::$get;
        self::$post = (object)self::$post;
    }
}

Vars::init();
/* end global set */

/* init section */
header("Content-type: text/html; Charset=utf-8");

if(Config\Main::$debug)
{
    ini_set('display_errors','On');
    ini_set('html_errors', 'on');

    error_reporting(-1);
} else {
    ini_set('display_errors','On');
    ini_set('html_errors', 'on');

    error_reporting(E_ALL);
}
ob_start();
register_shutdown_function(function() { $error = new Engine\Error(); $error->fatal_error_handler(); });
set_error_handler(["NextFW\\Engine\\Error","error_handler"]);

/* init section */

/* start application */
Engine\Route::init(Config\Main::$initPage);
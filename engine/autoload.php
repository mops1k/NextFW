<?php
namespace NextFW\Engine;

class Autoload
{
    public static $loader;

    public static function init()
    {
        if (self::$loader == NULL) {
            self::$loader = new self();
        }

        return self::$loader;
    }

    public function __construct()
    {

        spl_autoload_register([$this, 'autoload']);

    }

    function autoload($className)
    {
        global $loads;
        $classNameOld = $className;
        $className = str_replace("nextfw\\","",strtolower(ltrim($className, '\\')));
        $fileName = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if(file_exists(PATH.$fileName))
        {
            require_once(PATH.$fileName);
            $loads[] = PATH.$fileName;
        }

        $type = null;
        if(!class_exists($classNameOld)) $type = "Class";
        elseif(!trait_exists($classNameOld) AND class_exists($classNameOld)) $type = "Trait";
        elseif(!interface_exists($classNameOld) AND (trait_exists($classNameOld) AND class_exists($classNameOld))) $type = "Interface";

        if($type != null) {
            header('HTTP/1.0 404 Not Found');
        }
    }
}
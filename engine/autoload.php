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
        $classNameOld = $className;
        $className = str_replace("nextfw","",strtolower(ltrim($className, '\\')));
        $fileName = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if(file_exists(PATH."/".$fileName))
            require(PATH."/".$fileName);

        $exists = true;
        $type = "Class";
        if(!class_exists($classNameOld)) $exists = false;

        if((!trait_exists($classNameOld) AND $exists == false) AND class_exists($classNameOld))
        {
            $type = "Trait";
            $exists = false;
        }
        else $exists = true;

        if(!interface_exists($classNameOld) AND $exists == false AND (trait_exists($classNameOld) OR class_exists($classNameOld)))
        {
            $type = "Interface";
            $exists = false;
        }
        else $exists = true;

        if(!$exists)
        {
            Error::$errors = true;
            $error = new Error();
            $error->render('{type} {class} not found!',[
                    'type' => $type,
                    'class' => $classNameOld
                ]);
        };
    }
}
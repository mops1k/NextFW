<?php
namespace NextFW\Engine;

class Route
{
    static private $controllersPath;
    static private $requestUrl = [];
    static private $requestType;
    static public $request = [];

    static public function construct()
    {
        $requestUrl = urldecode($_SERVER['REQUEST_URI']);
        $host = $_SERVER['HTTP_HOST'];
        $address = "http://" . $host . "/" . $requestUrl;

        if (preg_match("/http:\/\/[a-zA-Z0-9а-яА-Я\-\.]+\/([a-zA-Z0-9а-яА-Я\-\._\/]*)(.*)/", $address, $output_array)) {
            $requestUrl = $output_array[1];
        }

        $requestUrl = str_replace(".html", "", $requestUrl);
        $requestUrl = str_replace("index.php/", "", $requestUrl);
        $requestUrl = str_replace("index.php", "", $requestUrl);
        $requestUrl = substr($requestUrl, 1);
        $requestUrl = htmlspecialchars($requestUrl);
        $requestUrl = ($requestUrl == "") ? [] : explode("/", $requestUrl);

        for($i = 2; $i < count($requestUrl); $i++)
        {
            self::$request[] = $requestUrl[$i];
        }

        self::$controllersPath = PATH . "/controllers/";
        self::$requestType = $_SERVER['REQUEST_METHOD'];
        self::$requestUrl = $requestUrl;
    }

    public static function is_ajax()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && @empty($_SERVER['HTTP_X_REQUESTED_WITH']) && @strtolower(
                        $_SERVER['HTTP_X_REQUESTED_WITH']
                    ) != 'xmlhttprequest'
        ) {
            return false;
        } else {
            return true;
        }
    }

    static public function init($startApp = "index:start")
    {
        $count = count(self::$requestUrl);
        $startApp = explode(":", $startApp);
        self::$requestUrl = ($count == 0) ? $startApp : self::$requestUrl;
        self::$requestUrl = (($count < 2 AND $count != 0) OR (self::$requestUrl[1] == "")) ? [
            self::$requestUrl[0],
            "start"
        ] : self::$requestUrl;
        $controller = "NextFW\\Controller\\" . self::$requestUrl[0];
        $object = new $controller;
        $method = (self::$requestType == 'POST' AND !self::is_ajax() AND method_exists($controller,self::$requestUrl[1]."Post"))? self::$requestUrl[1]."Post" : self::$requestUrl[1];
        $method = (self::is_ajax())? self::$requestUrl[1]."Ajax" : $method;
        $error = method_exists($controller,$method) ? false : true;
        if(!$error)
            $object->$method();
        else
        {
            Error::$errors = true;
            $error = new Error();
            $error->render('Method {method} not found',[
                    'method' => $controller."->".$method
                ]);
            die();
        }
    }
}

Route::construct();
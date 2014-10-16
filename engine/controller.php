<?php
namespace NextFW\Engine;

use NextFW;
use NextFW\Config;
use NextFW\Module;

abstract class Controller
{
    use TSingleton;
    public $tpl;
    /* @var object */
    public $mod;
    public $username;
    public $get, $post;

    function __construct()
    {
        if(Config\Main::$dbEnabled) DB::init();
        $vars = new NextFW\Vars();

        // Get and Post as array
        $this->get = $vars['get'];
        $this->post = $vars['post'];

        $this->tpl = new View();
        $this->tpl['path'] = PATH . "view" . DIRECTORY_SEPARATOR . Config\Main::$template . DIRECTORY_SEPARATOR;
        $this->tpl->set(
            "THEME",
            DIRECTORY_SEPARATOR . "view" . DIRECTORY_SEPARATOR . Config\Main::$template . DIRECTORY_SEPARATOR
        );
        $username = (isset($_SESSION['username'])) ? $_SESSION['username'] : 'guest';
        if (!Route::is_ajax()) {
            $loadTpl = 'index.tpl';
            $this->tpl->loadTpl($loadTpl);
            $this->tpl->getBlocks([
                    'content',
                    'second_title'
                ]);
            $this->tpl->set('username',$username);
        }

        // module init
        $controller = "NextFW\\Module\\".Route::getUrl()[0];
        $className = str_replace("nextfw","",strtolower(ltrim($controller, '\\')));
        $fileName = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if(file_exists(PATH."/".$fileName))
        {
            /* @var object $controller */
            $this->mod = new $controller;
        }
        // end module init
    }

    function __call($m, $a) {
        Error::$errors = true;
        $error = new Error();
        $error->render('Страницы {m} не существует.',[ "m" => $m ]);
    }

    function __destruct()
    {
        if(Error::$errors) $this->tpl->clear();
        echo $this->tpl;
    }
}
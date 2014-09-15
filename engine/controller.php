<?php
namespace NextFW\Engine;

use NextFW;
use NextFW\Config;

abstract class Controller
{
    use TSingleton;
    public $tpl;
    /* @var NextFW\Engine\DB */

    function __construct()
    {
        if(Config\Main::$dbEnabled) DB::init();
        $this->tpl = new View();
        $this->tpl->path = PATH . DIRECTORY_SEPARATOR . "view" . DIRECTORY_SEPARATOR . Config\Main::$template . DIRECTORY_SEPARATOR;
        $this->tpl->set(
            "THEME",
            DIRECTORY_SEPARATOR . "view" . DIRECTORY_SEPARATOR . Config\Main::$template . DIRECTORY_SEPARATOR
        );
        if (!Route::is_ajax()) {
            $loadTpl = 'index.tpl';
            $this->tpl->loadTpl($loadTpl);
        }
    }

    function __destruct()
    {
        if(Error::$errors) $this->tpl->clear();
        $this->tpl->view();
    }
}
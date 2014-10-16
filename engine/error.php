<?php
namespace NextFW\Engine;

use NextFW\Config;

class Error extends \Exception {
    private $tpl;
    public static $errors = false;
    public function render($message, $param = [])
    {
        header('HTTP/1.0 404 Not Found');
        $this->tpl = new View();
        $this->tpl->clear();
        $this->tpl->path = PATH.DIRECTORY_SEPARATOR."view".DIRECTORY_SEPARATOR."errors".DIRECTORY_SEPARATOR;
        $this->tpl->set("THEME",DIRECTORY_SEPARATOR."view".DIRECTORY_SEPARATOR."errors".DIRECTORY_SEPARATOR);
        foreach ($param as $key => $value) {
            $message = str_replace("{".$key."}",$value,$message);
        }
        $this->tpl->set('message',$message);
        $loadTpl = 'main.tpl';
        $this->tpl->loadTpl($loadTpl);
        $this->tpl->view();
        die();
    }
    static function error_handler($errno, $errstr, $errfile, $errline)
    {
        // если ошибка попадает в отчет (при использовании оператора "@" error_reporting() вернет 0)
        if (error_reporting() & $errno)
        {
            $errors = array(
                E_ERROR => 'E_ERROR',
                E_WARNING => 'E_WARNING',
                E_PARSE => 'E_PARSE',
                E_NOTICE => 'E_NOTICE',
                E_CORE_ERROR => 'E_CORE_ERROR',
                E_CORE_WARNING => 'E_CORE_WARNING',
                E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                E_USER_ERROR => 'E_USER_ERROR',
                E_USER_WARNING => 'E_USER_WARNING',
                E_USER_NOTICE => 'E_USER_NOTICE',
                E_STRICT => 'E_STRICT',
                E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                E_DEPRECATED => 'E_DEPRECATED',
                E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            );

            // выводим свое сообщение об ошибке
            $tpl = new View();
            $tpl->clear();
            $tpl->path = PATH.DIRECTORY_SEPARATOR."view".DIRECTORY_SEPARATOR."errors".DIRECTORY_SEPARATOR;
            $tpl->set("THEME",DIRECTORY_SEPARATOR."view".DIRECTORY_SEPARATOR."errors".DIRECTORY_SEPARATOR);
            $message = "<b>{$errors[$errno]}:</b> $errstr <i>(<b>$errfile</b> на $errline строке)</i><br />\n";
            $tpl->set('message',$message);
            $loadTpl = 'php.tpl';
            $tpl->loadTpl($loadTpl);
            $tpl->view();
            $tpl->clear();
        }

        // не запускаем внутренний обработчик ошибок PHP
        return TRUE;
    }

    /**
     * Функция перехвата фатальных ошибок
     */
    function fatal_error_handler()
    {
        // если была ошибка и она фатальна
        if ($error = error_get_last() AND $error['type'] & ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR))
        {
            // очищаем буффер (не выводим стандартное сообщение об ошибке)
            ob_end_clean();
            // запускаем обработчик ошибок
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line']);
            die();
        }
        else
        {
            // отправка (вывод) буфера и его отключение
            ob_end_flush();
        }
    }
} 
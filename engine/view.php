<?php
namespace NextFW\Engine;

use NextFW\Config;
/**
 * Класс шаблонизатора системы
 */
class View implements \ArrayAccess
{
    use TArrayAccess;
    /**
     * Путь к папке с шаблонами
     *
     * @var string
     */
    public $path = './';
    /**
     * Название шаблона
     *
     * @var null
     */
    public $name = NULL;
    public $_data = [];
    public $_array = [];
    public $_blocks = [];
    private $str = NULL;
    private $time = 0;

    function __construct()
    {
        $this->time = microtime();
    }

    /**
     * Задает переменную шалона и ее значение
     *
     * @param string $name
     * @param string $string
     */
    public function set($name, $string)
    {
        $exists = false;
        if (is_array($this->_data)) {
            foreach ($this->_data as $key => $val) {
                if (isset($val[ $name ])) {
                    $this->_data[ $key ] = [$name => $string];
                    $exists = true;
                    break;
                }
            }
        }
        if (!$exists) {
            $this->_data[] = [$name => $string];
        }
    }

    /**
     * Этот хак дает возможность задавать массивы и переменные шаблона как переменные класса
     * @param string $name
     * @param string|array $value
     */
    function __set($name, $value)
    {
        if(is_array($value)) $this->setArray($name, $value);
        else $this->set($name, $value);
    }

    /**
     * Подменяет значения массива. Тег в стиле {name.key}
     *
     * @param $name
     * @param $array
     */
    public function setArray($name, $array)
    {
        $exists = false;
        foreach ($this->_array as $key => $val) {
            if (isset($val[ $name ])) {
                $this->_array[ $key ] = [ $name => $array ];
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $this->_array[] = [$name => $array];
        }
    }

    /**
     * Устанавливает значения массово [ "name" => "string" ]
     *
     * @param $array
     */
    public function setAll($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $exists = false;
                if (is_array($this->_data)) {
                    foreach ($this->_data as $k => $val) {
                        if (isset($val[ $key ])) {
                            $this->_data[ $k ] = [$key => $value];
                            $exists = true;
                            break;
                        }
                    }
                }
                if (!$exists) {
                    $this->_data[] = [$key => $value];
                }
            }
        }
    }

    /**
     * Загружает шаблон
     *
     * @param string $file
     */
    public function loadTpl($file)
    {
        if (!file_exists($this->path . $this->name . DIRECTORY_SEPARATOR . $file)) {
            die("Невозможно загрузить файл шаблона: {$file}!");
        }
        $string = file_get_contents($this->path . $this->name . DIRECTORY_SEPARATOR . $file);
        $string = preg_replace_callback(
            "#\\{include file=['\"](.+?)['\"]\\}#is",
            function ($m) {
                return $this->subLoad($m[1]);
            },
            $string
        );
        $this->str = $string;
    }

    /**
     * Получает блок в шаблоне
     *
     * @param string $var
     */
    public function getBlock($var)
    {
        $string = $this->str;
        if (preg_match("/\[" . $var . "\]([\w\W\s]*)\[\/" . $var . "\]/", $string, $output_array)) {
            $this->_blocks[ $var ] = $output_array[1];
        }
    }

    /**
     * Получить все блоки в шаблоне
     *
     * @param array $array
     */
    public function getBlocks($array)
    {
        for ($i = 0; $i < count($array); $i++) {
            $string = $this->str;
            if (preg_match(
                "/\[" . $array[ $i ] . "\]([\w\W\s]*)\[\/" . $array[ $i ] . "\]/",
                $string,
                $output_array
            )) {
                $this->_blocks[ $array[ $i ] ] = $output_array[1];
            }
        }
    }

    /**
     * Изменяет блок
     *
     * @param string $var
     * @param string $value
     */
    public function setBlock($var, $value)
    {
        $this->str = preg_replace_callback(
            "/\[" . $var . "\]([\w\W\s]*)\[\/" . $var . "\]/",
            function () use ($value) {
                return $value;
            },
            $this->str
        );
    }

    /**
     * Удаляет блок и его содержимое
     *
     * @param $var
     */
    public function removeBlock($var)
    {
        $this->str = preg_replace_callback(
            "/\[" . $var . "\]([\w\W\s]*)\[\/" . $var . "\]/",
            function () {
                return "";
            },
            $this->str
        );
    }

    /**
     * Загружает шаблон и отдает в видет текста скомпоновав его или вернув результат скрипта PHP
     *
     * @param string $file
     *
     * @return mixed|string
     */
    public function subLoad($file)
    {
        $ext = function ($file) {
            $i = explode(".", $file);

            return $i[ count($i) - 1 ];
        };
        if ($ext($file) != "php") {
            if (!file_exists($this->path . $this->name . "/" . $file)) {
                die("Невозможно загрузить файл шаблона: {$file}!");
            }

            return $this->parseVal(file_get_contents($this->path . $this->name . "/" . $file));
        } else {
            if (!file_exists($file)) {
                die("Невозможно загрузить файл php: {$file}!");
            }
            ob_start();
            include($file);
            $php = ob_get_clean();

            return $php;
        }
    }

    /**
     * Парсим шаблон
     *
     * @param $string
     *
     * @return mixed
     */
    private function parseVal($string)
    {
        $count = count($this->_data);
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->_data[ $i ] as $key => $value) {
                $string = str_replace("{" . $key . "}", $value, $string);
            }
        }
        $count = count($this->_array);
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->_array[ $i ] as $key => $value) {
                foreach ($value as $k => $val) {
                    $string = str_replace("{" . $key . "." . $k . "}", $val, $string);
                }
            }
        }
        foreach ($this->_blocks as $key => $value) {
            $string = preg_replace_callback(
                "/\[" . $key . "\]([\w\W\s]*)\[\/" . $key . "\]/",
                function ($m) {
                    return $m[1];
                },
                $string
            );
        }
        $string = preg_replace_callback(
            "#\\{post\.(.+?)\\}#is",
            function ($m) {
                return isset($_POST[ $m[1] ]) ? $_POST[ $m[1] ] : "";
            },
            $string
        );
        $string = preg_replace_callback(
            "#\\{get\.(.+?)\\}#is",
            function ($m) {
                return isset($_GET[ $m[1] ]) ? $_GET[ $m[1] ] : "";
            },
            $string
        );
        $string = preg_replace_callback(
            "#\\{\*(.+?)\*}#is",
            function ($m) {
                return "<!-- {$m[1]} -->";
            },
            $string
        );

        $string = preg_replace_callback(
            "#\\{(@|run):([0-9a-zA-Z\_]+?)\((.*)\)\\}#is",
            function ($m) {
                ob_start();
                eval("return ".$m[2]."(".$m[3].");");
                $func = ob_get_clean();
                return $func;
            },
            $string
        );

        # \{\% if[\ ]{0,1}(.+?) \%\}((.+?)\{\% else \%\}{0,1}(.+?))\{\% endif \%\}
        $string = preg_replace_callback(
            "#\\{\% if[\ ]{0,1}(.+?) \%\}((.+?)\{\% else \%\}?(.+?){0,1}|.*)\{\% endif \%\}#is",
            function ($m) {
                $count = count($m);
                if($count > 3) {
                    $str = "if{$m[1]} { return \"{$m[3]}\"; } else { return \"{$m[4]}\"; }";
                } else {
                    $str = "if{$m[1]} { return \"{$m[2]}\"; } }";
                }
                return eval($str);
            },
            $string
        );

        return $string;
    }

    /**
     * Полная очистка памяти шаблона
     */
    public function clear()
    {
        $this->str = "";
    }

    /**
     * Компонует и выводит на экран
     */
    public function view()
    {
        $this->set('jQuery','<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>');
        $this->set('bootstrap','<link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>');

        $this->time = round(microtime() - $this->time, 4);
        $this->set("time", $this->time);

        $text = $this->parseVal($this->str);

        if(Config\Main::$tidyEnabled)
        {
            $tidy = new \Tidy;
            $tidy->parseString($text);
            $tidy->cleanRepair();
            $text = $tidy;
        }

        echo $text;
    }
    function __toString()
    {
        $this->set('jQuery','<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>');
        $this->set('bootstrap','<link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>');

        $this->time = round(microtime() - $this->time, 4);
        $this->set("time", $this->time);

        $text = $this->parseVal($this->str);

        if(Config\Main::$tidyEnabled)
        {
            $tidy = new \Tidy;
            $tidy->parseString($text);
            $tidy->cleanRepair();
            $text = $tidy;
        }

        return (string)$text;
    }
}
<?php
namespace NextFW\Engine;

use NextFW\Config as Config;

class DB {
    private static $connections = [];
    private static $instance = [];
    private static $name = 'default';
    private static $_nq = 0;
    private static $_statement = NULL;
    private static $_statements = [];

    private static $_return_result = \PDO::FETCH_OBJ; // PDO::FETCH_OBJ, PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_NUM

    // Запрещаем создавать объект
    final private function __construct()
    {
    }

    // Запрещаем клонировать объект
    final private function __clone()
    {
    }

    /**
     * Вызов всех статических методов для PDO (PHP ver. >= 5.3)
     *
     * @param $method - метод
     * @param $args - аргументы
     * @return mixed
     */
    final public static function __callStatic($method, $args)
    {
        $_instance = self::init(self::$name);

        return call_user_func_array([$_instance, $method], $args);
    }

    /**
     * Инициализация подключения к базе данных или переключение по уже подключенным базам
     * @param string $name Название соединения
     * @param array  $array Массив со всем возможными конфигурациями БД
     *
     * @return \PDO
     */
    public static function init($name = 'default',$array = [])
    {
        if(!Config\Main::$dbEnabled)
        {
            throw new \PDOException("Подключение к базе данных отключено в конфигурации!");
        }

        if(count(self::$connections) > 0) $array = self::$connections;

        if(!is_array($array) OR count($array) == 0)
            throw new \PDOException("Неверная конфигурация подключения к базе данных!");

        $arrayKeys = [
            "driver",
            "host",
            "user",
            "pass",
            "db",
            "charset"
        ];

        $error = false;
        $errorLines = [];
        foreach ( $array as $base => $config )
        {
            foreach ( $arrayKeys as $key )
            {
                if(!isset($config[$key]))
                {
                    $error = true;
                    $errorLines[$base][] = $key;
                }
            }
        }

        if($error)
        {
            $errorText = "Ошибки в конфигурации базы данных:\n";
            foreach ( $errorLines as $key => $value )
            {
                $errorText .= "\n  ".$key." =>";
                foreach ( $value as $line )
                {
                    $errorText .= "\n    ".$line.",";
                }
                $errorText = substr($errorText,0,-1);
            }
            $errorText .= "\n";
            throw new \PDOException($errorText);
        }

        if(count(self::$connections) == 0) self::$connections = $array;
        self::$name = $name;
        if(!isset(self::$instance[$name]))
        {
            $params = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];
            if (isset(self::$connections[$name]['persistent']) && self::$connections[$name]['persistent']) {
                $params[ \PDO::ATTR_PERSISTENT ] = true;
            }
            if (self::$connections[$name]['driver'] == 'mysql') {
                $params[ \PDO::MYSQL_ATTR_INIT_COMMAND ] = "SET NAMES ".self::$connections[$name]['charset'];
            }
            try
            {
                $db = new \PDO(self::$connections[$name]['driver'].':host='.self::$connections[$name]['host'].';dbname='.self::$connections[$name]['db'],
                    self::$connections[$name]['user'],
                    self::$connections[$name]['pass'],
                    $params);
                self::$instance[$name] = $db;
            }
            catch(\PDOException $e)
            {
                throw $e;
            }
        }

        return self::$instance[self::$name];
    }

    /**
     * Выполняет запрос с подготовленным выражением (или без) для результата (SELECT)
     *
     * @param string|array $sql - запрос
     * @return bool
     */
    private static function pquery($sql)
    {
        $args = func_get_args();
        if (is_array($sql)) {
            // если передан как массив - вытаскиваем данные
            $args = $args[0];
            if (!isset($args[0])) {
                return false;
            }
            $sql = strval(array_shift($args));
        } else {
            array_shift($args);
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        // если пустой запрос
        if (empty($sql)) {
            return false;
        }

        if (empty($args)) {
            // выполняем запрос без подготовленного выражения
            try {
                self::$_statement = self::init(self::$name)->query($sql);
                self::$_nq++;
            } catch (\PDOException $e) {
                throw $e;
            }
            $state = true;
        } else {
            // выполняем запрос с подготовленным выражением

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                try {
                    // подготавливаем выражение
                    self::$_statement = self::init(self::$name)->prepare($sql);
                } catch (\PDOException $e) {
                    throw $e;
                }
                // запоминаем
                self::$_statements[ $hash ] = self::$_statement;
            } else {
                self::$_statement = self::$_statements[ $hash ];
            }
            if (self::$_statement) {
                try {
                    // выполняем запрос
                    $state = self::$_statement->execute($args);
                    self::$_nq++;
                } catch (\PDOException $e) {
                    throw $e;
                }
            }
        }

        return $state;
    }

    /**
     * Выполняет запрос с подготовленным выражением (или без) без результата (INSERT/UPDATE)
     * Возвращает кол-во затронутых строк (измененных/удаленных)
     *
     * @param string/array $sql - запрос
     * @return bool
     */
    private static function exec($sql)
    {
        $count = 0;
        // подготовка аргументов
        $args = func_get_args();
        if (is_array($sql)) {
            // если передан как массив - вытаскиваем данные
            $args = $args[0];
            if (!isset($args[0])) {
                return false;
            }
            $sql = strval(array_shift($args));
        } else {
            array_shift($args);
        }
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }
        // если пустой запрос
        if (empty($sql)) {
            return false;
        }

        if (empty($args)) {
            // выполняем запрос без подготовленного выражения
            return self::exec_noprepare($sql);
        } else {

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                try {
                    // подготавливаем выражение
                    self::$_statement = self::init(self::$name)->prepare($sql);
                } catch (\PDOException $e) {
                    throw $e;
                }
                // запоминаем
                self::$_statements[ $hash ] = self::$_statement;
            } else {
                self::$_statement = self::$_statements[ $hash ];
            }
            if (self::$_statement) {
                try {
                    // выполняем запрос
                    $count = self::$_statement->execute($args);
                    self::$_nq++;
                } catch (\PDOException $e) {
                    throw $e;
                }
            }
        }

        return $count;
    }

    /**
     * Выполняет запрос и возвращает кол-во затронутых строк (измененных/удаленных)
     *
     * @param string $sql - запрос
     * @return int - кол-во затронутых строк
     */
    private static function exec_noprepare($sql)
    {
        $count = 0;
        try {
            // выполняем запрос
            $count = self::init(self::$name)->exec($sql);
            self::$_nq++;
        } catch (\PDOException $e) {
            throw $e;
        }

        return $count;
    }

    /**
     * Выполняет запрос и возвращает результат (SELECT)
     *
     * @param string $sql - запрос
     * @return object - \PDOStatement
     */
    private static function query($sql)
    {
        try {
            // выполняем запрос
            self::$_statement = self::init(self::$name)->query($sql);
            self::$_nq++;
        } catch (\PDOException $e) {
            throw $e;
        }

        return self::$_statement;
    }

    /**
     * Возвращает строку
     *
     * @param string $sql - запрос
     *
     * @return mixed|boolean
     */
    public static function getOne($sql)
    {
        return self::getRow(func_get_args());
    }

    /**
     * Возвращает строку
     *
     * @param string $sql - запрос
     *
     * @return mixed|boolean
     */
    public static function getRow($sql)
    {
        self::pquery(func_get_args());

        return self::$_statement ? self::$_statement->fetch(self::$_return_result) : false;
    }

    /**
     * Возвращает ряд строк
     *
     * @param string/array $sql - запрос
     *
     * @return mixed|boolean
     */
    public static function getAll($sql)
    {
        self::pquery(func_get_args());

        return self::$_statement ? self::$_statement->fetchAll(self::$_return_result) : false;
    }

    /**
     * Установка стиля возвращаемого результата (объект, массив)
     *
     * @param int $res
     */
    public static function setReturnResult($res)
    {
        $res = intval($res);
        if (in_array($res, [\PDO::FETCH_OBJ, \PDO::FETCH_ASSOC, \PDO::FETCH_BOTH, \PDO::FETCH_NUM])) {
            self::$_return_result = (int)$res;
        }
    }

    /**
     * Выбор всех записей из таблицы по условиям и столбцам
     *
     * @param       $table Таблица
     * @param array $cols Столбцы для выборки
     * @param array $where Условия выборки (массив), могут содержать вид:
     *                     [
     *                         "colName" => [
     *                                 "val" => 'значение для условия',
     *                                 "equals" => 'BETWEEN|LIKE - необязательный параметр',
     *                                 "xor" => 'AND|OR - оператор перед поиском',
     *                                 "From" => 'от значение, если equals = BETWEEN',
     *                                 "To" => 'до значение, если equals = BETWEEN'
     *                             ]
     *                     ]
     * @param null  $order Условие упорядочивания (пример: col DESC)
     * @param null  $group Условие группирования (пример: col)
     *
     * @return bool|mixed
     */
    public static function selectAll($table, $cols = [], $where = [], $order = null, $group = null)
    {
        $params = [];
        // столбцы выборки
        $colsLine = null;
        if(count($cols) > 0)
        {
            foreach ( $cols as $col ) {
                $colsLine .= '`'.$col.'`, ';
            }
            $colsLine = substr($colsLine,0,-2);
        } else $colsLine = '*';

        // условия выборки
        $whereLine = null;
        if(count($where) > 0)
        {
            $whereLine = " WHERE ";
            foreach ( $where as $col => $val ) {
                $Line = null;
                if(isset($val['equal']) && $val['equal'] == 'BETWEEN')
                {
                    $Line = "(`".$col."` BETWEEN :".$col."From AND :".$col."To) ";
                    $params[":".$col."From"] = $val['from'];
                    $params[":".$col."To"] = $val['to'];
                }
                if(isset($val['equal']) && $val['equal'] == 'LIKE')
                {
                    $Line = "`{$col}` LIKE :{$col} ";
                    $params[":".$col] = $val['val'];
                }
                if(!isset($val['equal']))
                {
                    $Line = "`".$col."` = :$col ";
                    $params[":$col"] = $val['val'];
                }

                if(isset($val['xor']))
                {
                    $whereLine .= " ".$val['xor']." ";
                    $whereLine .= $Line;
                }
                else
                {
                    $whereLine .= $Line;
                }
            }
            $whereLine = substr($whereLine,0,-1);
        }
        $orderLine = $order != null ? " ORDER by ".$order : null;
        $groupLine = $group != null ? " GROUP by ".$group : null;

        $fullLine = "SELECT ".$colsLine." FROM `".$table."`".$whereLine.$groupLine.$orderLine.";";

        if(count($params) > 0)
            return self::getAll($fullLine,$params);
        else
            return self::getAll($fullLine);
    }

    /**
     * Выбор одной записи из таблицы по условиям выбокри и столбцам
     *
     * @param       $table Таблица
     * @param array $cols Столбцы для выборки
     * @param array $where Условия выборки (массив), могут содержать вид:
     *                     [
     *                         "colName" => [
     *                                 "val" => 'значение для условия',
     *                                 "equals" => 'BETWEEN|LIKE - необязательный параметр',
     *                                 "xor" => 'AND|OR - оператор перед поиском',
     *                                 "From" => 'от значение, если equals = BETWEEN',
     *                                 "To" => 'до значение, если equals = BETWEEN'
     *                             ]
     *                     ]
     * @param null  $order Условие упорядочивания (пример: col DESC)
     * @param null  $group Условие группирования (пример: col)
     *
     * @return bool|mixed
     */
    public static function selectOne($table, $cols = [], $where = [], $order = null, $group = null)
    {
        $params = [];
        $colsLine = null;
        if(count($cols) > 0)
        {
            foreach ( $cols as $col ) {
                $colsLine .= '`'.$col.'`, ';
            }
            $colsLine = substr($colsLine,0,-2);
        } else $colsLine = '*';

        $whereLine = null;
        if(count($where) > 0)
        {
            $whereLine = " WHERE ";
            foreach ( $where as $col => $val ) {
                $Line = null;
                if(isset($val['equal']) && $val['equal'] == 'BETWEEN')
                {
                    $Line = "(`".$col."` BETWEEN :".$col."From AND :".$col."To) ";
                    $params[":".$col."From"] = $val['from'];
                    $params[":".$col."To"] = $val['to'];
                }
                if(isset($val['equal']) && $val['equal'] == 'LIKE')
                {
                    $Line = "`{$col}` LIKE :{$col} ";
                    $params[":".$col] = $val['val'];
                }
                if(!isset($val['equal']))
                {
                    $Line = "`".$col."` = :$col ";
                    $params[":$col"] = $val['val'];
                }

                if(isset($val['xor']))
                {
                    $whereLine .= " ".$val['xor']." ";
                    $whereLine .= $Line;
                }
                else
                {
                    $whereLine .= $Line;
                }
            }
            $whereLine = substr($whereLine,0,-1);
        }
        $orderLine = $order != null ? " ORDER by ".$order : null;
        $groupLine = $group != null ? " GROUP by ".$group : null;

        $fullLine = "SELECT ".$colsLine." FROM `".$table."`".$whereLine.$groupLine.$orderLine.";";

        if(count($params) > 0)
            return self::getRow($fullLine,$params);
        else
            return self::getRow($fullLine);
    }

    /**
     * Обновляет запись в таблице
     * @param       $table Таблица
     * @param array $vals Массив ключ => значение, аналогично столбец = значение
     * @param array $condition Условия выборки (массив), могут содержать вид:
     *                     [
     *                         "colName" => [
     *                                 "val" => 'значение для условия',
     *                                 "equals" => 'BETWEEN|LIKE - необязательный параметр',
     *                                 "xor" => 'AND|OR - оператор перед поиском',
     *                                 "From" => 'от значение, если equals = BETWEEN',
     *                                 "To" => 'до значение, если equals = BETWEEN'
     *                             ]
     *                     ]
     * @return bool
     */
    public static function update($table,$vals = [],$condition = [])
    {
        $params = [];
        if(count($vals) == 0) throw new \PDOException('Не указаны параметры для обновления записи(ей)');
        $valsLine = null;
        foreach ( $vals as $key => $value ) {
            $valsLine .= '`'.$key.'` = :'.$key.'Vals, ';
            $params[":{$key}Vals"] = $value;
        }
        $valsLine = substr($valsLine,0,-2);

        // where
        $whereLine = null;
        if(count($condition) > 0)
        {
            $whereLine = " WHERE ";
            foreach ( $condition as $col => $val ) {
                $Line = null;
                if(isset($val['equal']) && $val['equal'] == 'BETWEEN')
                {
                    $Line = "(`".$col."` BETWEEN :".$col."From AND :".$col."To) ";
                    $params[":".$col."From"] = $val['from'];
                    $params[":".$col."To"] = $val['to'];
                }
                if(isset($val['equal']) && $val['equal'] == 'LIKE')
                {
                    $Line = "`{$col}` LIKE :{$col} ";
                    $params[":".$col] = $val['val'];
                }
                if(!isset($val['equal']))
                {
                    $Line = "`".$col."` = :$col ";
                    $params[":$col"] = $val['val'];
                }

                if(isset($val['xor']))
                {
                    $whereLine .= " ".$val['xor']." ";
                    $whereLine .= $Line;
                }
                else
                {
                    $whereLine .= $Line;
                }
            }
            $whereLine = substr($whereLine,0,-1);
        }
        $fullLine = "UPDATE `{$table}` SET {$valsLine}".$whereLine;

        return count($params) > 0 ? self::exec($fullLine,$params) : self::exec($fullLine);
    }

    /**
     * Вставить значение в таблицу
     * @param $table Таблица
     * @param $values Массив ключ => значение, аналогично столбец = значение
     *
     * @return bool
     */
    public static function insert($table,$values)
    {
        if(!is_array($values) || count($values) == 0) throw new \PDOException('Неверный формат значений для вставки');
        $valuesLine1 = "(";
        $valuesLine2 = "(";
        $params = [];
        foreach ( $values as $key => $value ) {
            $valuesLine1 .= "`{$key}`, ";
            $valuesLine2 .= " :{$key},";
            $params[":{$key}"] = $value;
        }
        $valuesLine1 = substr($valuesLine1,0,-2).")";
        $valuesLine2 = substr($valuesLine2,0,-1).")";

        $fullLine = "INSERT INTO `{$table}` {$valuesLine1} VALUES {$valuesLine2}";

        return count($params) > 0 ? self::exec($fullLine,$params) : self::exec($fullLine);
    }

    /**
     * Удалить значения из таблицы
     * @param       $table Таблица
     * @param array $conditions Условия для удаления (массив), могут содержать вид:
     *                     [
     *                         "colName" => [
     *                                 "val" => 'значение для условия',
     *                                 "equals" => 'BETWEEN|LIKE - необязательный параметр',
     *                                 "xor" => 'AND|OR - оператор перед поиском',
     *                                 "From" => 'от значение, если equals = BETWEEN',
     *                                 "To" => 'до значение, если equals = BETWEEN'
     *                             ]
     *                     ]
     *
     * @return bool
     */
    public static function delete($table,$conditions = [])
    {
        $params = [];
        // where
        $whereLine = null;
        if(count($conditions) > 0)
        {
            $whereLine = " WHERE ";
            foreach ( $conditions as $col => $val ) {
                $Line = null;
                if(isset($val['equal']) && $val['equal'] == 'BETWEEN')
                {
                    $Line = "(`".$col."` BETWEEN :".$col."From AND :".$col."To) ";
                    $params[":".$col."From"] = $val['from'];
                    $params[":".$col."To"] = $val['to'];
                }
                if(isset($val['equal']) && $val['equal'] == 'LIKE')
                {
                    $Line = "`{$col}` LIKE :{$col} ";
                    $params[":".$col] = $val['val'];
                }
                if(!isset($val['equal']))
                {
                    $Line = "`".$col."` = :$col ";
                    $params[":$col"] = $val['val'];
                }

                if(isset($val['xor']))
                {
                    $whereLine .= " ".$val['xor']." ";
                    $whereLine .= $Line;
                }
                else
                {
                    $whereLine .= $Line;
                }
            }
            $whereLine = substr($whereLine,0,-1);
        }
        $fullLine = "DELETE FROM `{$table}`".$whereLine;

        return count($params) > 0 ? self::exec($fullLine,$params) : self::exec($fullLine);
    }
}

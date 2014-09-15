<?php
namespace NextFW\Engine;

use NextFW\Config;

class DB
{
    /**
     * Экземпляр класса
     *
     * @var object
     */
    private static $_instance;

    /**
     * Соединения
     *
     * @var array
     */
    private static $_connections = [];

    /**
     * Текущая БД (в конфиге)
     *
     * @var string
     */
    private static $_name;

    /**
     * Режим отладки
     *
     * @var int
     */
    private static $_debug = 0;

    /**
     * Режим записи ошибок в лог
     *
     * @var int
     */
    private static $_log_error = 0;

    /**
     * Время выполнения запроса в секундах - для записи запросов в лог (если 0 - отключено)
     *
     * @var int
     */
    private static $_log_sql = 0;

    /**
     * Вывод времени выполнения запросов
     *
     * @var int
     */
    private static $_print_time = 0;

    /**
     * Счетчик количества запросов
     *
     * @var int
     */
    private static $_nq = 0;

    /**
     * Текущее подготовленное выражение
     *
     * @var object
     */
    private static $_statement = NULL;

    /**
     * Подготовленные выражения
     *
     * @var array
     */
    private static $_statements = [];

    /**
     * Возвращаемый результат - объект, массив
     *
     * @var int
     */
    private static $_return_result = \PDO::FETCH_OBJ; // PDO::FETCH_OBJ, PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_NUM

    // Запрещаем создавать объект
    final private function __construct()
    {
    }

    // Запрещаем клонировать объект
    final private function __clone()
    {
    }

    /*
     * Вызов всех статических методов для PDO (PHP ver. >= 5.3)
     *
     * @param $method - метод
     * @param $args - аргументы
     * @return mixed
     */
    final public static function __callStatic($method, $args)
    {
        $_instance = self::init();

        return call_user_func_array([$_instance, $method], $args);
    }

    /*
     * Возвращает текущий экзмепляр объекта PDO или создает новое соединение
     *
     * @param string $name - имя конфига
     * @return object
     */
    public static function init($name = '')
    {
        if(Config\Main::$dbEnabled) {
        // по умолчанию - default
        if (empty($name)) {
            $name = !empty(self::$_name) ? self::$_name : 'default';
        }
        // если нет соединения, или новое
        if (!self::$_instance || self::$_name != $name) {
            // проверка, было ли ранее
            if (isset(self::$_connections[ $name ])) {
                self::$_instance = self::$_connections[ $name ];
                self::$_name = $name;

                return self::$_instance;
            }
            // проверка, установленно ли расширение
            if (!class_exists('\PDO')) {
                self::error('Extension PDO not installed');
            }
            // загрузка конфига БД
            $config = Config\Database::$data;
            $db_config = $config[ $name ];
            self::$_debug = (int)$db_config['debug'];
            self::$_log_error = (int)$db_config['log_error'];
            self::$_log_sql = isset($db_config['log_sql']) ? floatval($db_config['log_sql']) : 0;
            $params = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];
            if ($db_config['driver'] == 'mysql') {
                $params[ \PDO::MYSQL_ATTR_INIT_COMMAND ] = "SET NAMES '{$db_config['char']}'";
            }
            // постоянное подключение
            if (isset($db_config['persistent']) && $db_config['persistent']) {
                $params[ \PDO::ATTR_PERSISTENT ] = true;
            }
            // создание нового соединения
            try {
                @self::$_instance = new \PDO(
                    "{$db_config['driver']}:host={$db_config['host']};dbname={$db_config['name']}",
                    $db_config['user'],
                    $db_config['pass'],
                    $params
                );
            } catch (\PDOException $e) {
                self::error('Connection failed: ' . $e->getMessage());
            }
            // запоминаем соединение
            self::$_name = $name;
            self::$_connections[ $name ] = self::$_instance;
        }

        return self::$_instance;
        } else {
            $error = new Error();
            $error->render("Database not enabled");
        }
    }

    /*
     * Выполняет запрос с подготовленным выражением (или без) для результата (SELECT)
     *
     * @param string/array $sql - запрос
     * @return bool
     */
    public static function pquery($sql)
    {
        $state = false;
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
            try {
                self::$_statement = self::init()->query($sql);
                self::$_nq++;
            } catch (\PDOException $e) {
                self::error_sql($e, $sql);
            }
            $state = true;
        } else {
            // выполняем запрос с подготовленным выражением

            // старт времени
            if (self::$_print_time == 1 || self::$_log_sql > 0) {
                timer::start('db_query');
            }

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                try {
                    // подготавливаем выражение
                    self::$_statement = self::init()->prepare($sql);
                } catch (\PDOException $e) {
                    self::error_sql($e, $sql);
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
                    self::error_sql($e, $sql);
                }
            }
            // логирование по времени
            self::logEnd($sql);
        }

        return $state;
    }

    /*
     * Выполняет запрос с подготовленным выражением (или без) без результата (INSERT/UPDATE)
     * Возвращает кол-во затронутых строк (измененных/удаленных)
     *
     * @param string/array $sql - запрос
     * @return bool
     */
    public static function exec($sql)
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
            // выполняем запрос с подготовленным выражением

            // старт времени
            if (self::$_print_time == 1 || self::$_log_sql > 0) {
                timer::start('db_query');
            }

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                try {
                    // подготавливаем выражение
                    self::$_statement = self::init()->prepare($sql);
                } catch (\PDOException $e) {
                    self::error_sql($e, $sql);
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
                    self::error_sql($e, $sql);
                }
            }
            // логирование по времени
            self::logEnd($sql);
        }

        return $count;
    }

    /*
     * Выполняет запрос и возвращает кол-во затронутых строк (измененных/удаленных)
     *
     * @param string $sql - запрос
     * @return int - кол-во затронутых строк
     */
    private static function exec_noprepare($sql)
    {
        $count = 0;
        // старт времени
        if (self::$_print_time == 1 || self::$_log_sql > 0) {
            timer::start('db_query');
        }
        try {
            // выполняем запрос
            $count = self::init()->exec($sql);
            self::$_nq++;
        } catch (\PDOException $e) {
            self::error_sql($e, $sql);
        }
        // логирование по времени
        self::logEnd($sql);

        return $count;
    }

    /*
     * Выполняет запрос и возвращает результат (SELECT)
     *
     * @param string $sql - запрос
     * @return object - \PDOStatement
     */
    public static function query($sql)
    {
        // старт времени
        if (self::$_print_time == 1 || self::$_log_sql > 0) {
            timer::start('db_query');
        }
        try {
            // выполняем запрос
            self::$_statement = self::init()->query($sql);
            self::$_nq++;
        } catch (\PDOException $e) {
            self::error_sql($e, $sql);
        }
        // логирование по времени
        self::logEnd($sql);

        return self::$_statement;
    }

    /**
     * Завершает логирование по времени
     *
     * @param string $sql - запрос
     */
    private static function logEnd($sql)
    {
        if (self::$_print_time == 1 || self::$_log_sql > 0) {
            $time = timer::get('db_query');
            if (self::$_print_time == 1) {
                // на экран
                echo $time, '<br />', $sql, '<br /><br />';
            } elseif ($time >= self::$_log_sql) {
                // в файл
                $file = LOG . date('Y-m-d') . '_db_query.log';
                $e_file = is_file($file);
                if ($e_file && !is_writable($file)) {
                    @chmod($file, 0777);
                }
                @file_put_contents(
                    $file,
                    date('Y-m-d H:i:s') . ' ' . self::$_name . ' *** ' . $time . ' - ' . $sql . "\n\n",
                    FILE_APPEND
                );
                if (!$e_file) {
                    @chmod($file, 0777);
                }
            }
        }
    }

    /**
     * Возвращает одно значение
     *
     * @param string $sql - запрос
     *
     * @return mixed
     */
    public static function getOne($sql)
    {
        self::pquery(func_get_args());

        return self::$_statement ? self::$_statement->fetchColumn() : false;
    }

    /**
     * Возвращает строку
     *
     * @param string $sql - запрос
     *
     * @return mixed
     */
    public static function getRow($sql)
    {
        self::pquery(func_get_args());

        return self::$_statement ? self::$_statement->fetch(self::$_return_result) : false;
    }

    /*
     * Возвращает ряд строк
     *
     * @param string/array $sql - запрос
     * @return mixed
     */
    public static function getAll($sql)
    {
        self::pquery(func_get_args());

        return self::$_statement ? self::$_statement->fetchAll(self::$_return_result) : false;
    }

    /*
     * Обработка исключений для SQL-запроса
     *
     * @param object $e - ошибка
     * @param string $sql - запрос
     */
    private static function error_sql($e, $sql)
    {
        self::error(
            '[' . $e->errorInfo[1] . '] ' . (isset($e->errorInfo[2]) ? $e->errorInfo[2] : '') . "\n\nQuery: <b>" . $sql . "</b>",
            $e->errorInfo[0]
        );
    }

    /*
     * Обработка исключений
     *
     * @param string $message - сообщение
     * @param int $code - код ошибки
     */
    private static function error($message, $code = 0)
    {
        if (self::$_log_error == 1) {
            $file = LOG . date('Y-m-d') . '_db_error.log';
            $e_file = is_file($file);
            if ($e_file && !is_writable($file)) {
                @chmod($file, 0777);
            }
            @file_put_contents($file, date('Y-m-d H:i:s') . ' *** ' . $code . ' - ' . $message . "\n\n", FILE_APPEND);
            if (!$e_file) {
                @chmod($file, 0777);
            }
        }
        if (self::$_debug == 0) {
            throw @new Error('error in ' . __CLASS__, -1);
        } elseif (self::$_debug == 1) {
            throw @new Error($message, $code);
        }
    }

    /**
     * Установка кодировки
     *
     * @param string $char - кодировка
     */
    public static function setNames($char)
    {
        db::init();
        self::query("SET NAMES ?", $char);
    }

    /**
     * Установка режима отладки
     *
     * @param int $mode - режим
     */
    public static function setDebug($mode)
    {
        db::init();
        self::$_debug = (int)$mode;
    }

    /**
     * Установка режима вывода времени выполнения запросов на экран
     *
     * @param int $mode - режим
     */
    public static function setPrintTime($mode)
    {
        self::$_print_time = (int)$mode;
    }

    /**
     * Установка режима логирования запросов в файл
     *
     * @param int $mode - режим
     */
    public static function setLogError($mode)
    {
        db::init();
        self::$_log_error = (int)$mode;
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
     * Возвращает общее количество запросов за сессию
     *
     * @return int
     */
    public static function getQueries()
    {
        return self::$_nq;
    }

    /*
     * Возвращает кол-во строк затронутых при запросах DELETE, INSERT или UPDATE
     *
     * @return int
     */
    public static function rowCount()
    {
        return self::$_statement ? intval(self::$_statement->rowCount()) : 0;
    }

    /*
     * Выполняет вставку записи в БД и возвращает ее ID
     *
     * @param string $sql - запрос, должен содержать %sql%
     * @param array $data - данные для вставки (массив: поле - значение)
     * @param array $types - типы данных (массив: поле - тип) (необязательное)
     *   тип может быть:
     *      str, \PDO::PARAM_STR - строка (по умолчанию)
     *      int, \PDO::PARAM_INT - число
     *      bool, \PDO::PARAM_BOOLEAN - 1/0
     *      null, \PDO::PARAM_NULL - null
     *      expr - выражение SQL (не экранируется!)
     *
     * @param array $fields - поля, которые необходимо использовать (необязательное)
     * @return int
     */
    public static function insert($sql, array $data, array $types = [], array $fields = [])
    {
        try {
            $prep_k = $prep_v = [];
            $fz = empty($fields);
            foreach ($data as $field => $value) {
                if ($fz || in_array($field, $fields)) {
                    if (!preg_match('|^[a-z0-9_-]+$|i', $field)) {
                        self::error("Insert failed: field {$field} contains invalid characters");
                    }
                    $prep_k[] = "`{$field}`";
                    if (!empty($types[ $field ]) && $types[ $field ] === 'expr') {
                        // выражение
                        $prep_v[] = $value;
                    } else {
                        $prep_v[] = ":{$field}";
                    }
                }
            }
            if (empty($prep_k)) {
                self::error('Insert failed: empty data');
            }
            // подготавливаем
            $prep = '(' . implode(',', $prep_k) . ') VALUES (' . implode(',', $prep_v) . ')';
            $sql = str_replace('%sql%', $prep, $sql);

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                // подготавливаем выражение
                self::$_statement = self::init()->prepare($sql);
                // запоминаем
                self::$_statements[ $hash ] = self::$_statement;
            } else {
                self::$_statement = self::$_statements[ $hash ];
            }

            // установка данных
            foreach ($data as $field => $value) {
                if ($fz || in_array($field, $fields)) {
                    if (empty($types[ $field ])) {
                        $type = \PDO::PARAM_STR;
                    } else {
                        $t = $types[ $field ];
                        // если выражение - пропускаем
                        if ($t === 'expr') {
                            continue;
                        }
                        if ($t === 'str' || $t === \PDO::PARAM_STR) {
                            $type = \PDO::PARAM_STR;
                        } elseif ($t === 'int' || $t === \PDO::PARAM_INT) {
                            $type = \PDO::PARAM_INT;
                        } elseif ($t === 'bool' || $t === PDO::PARAM_BOOL) {
                            $type = \PDO::PARAM_BOOL;
                        } elseif ($t === 'null' || $t === \PDO::PARAM_NULL) {
                            $type = \PDO::PARAM_NULL;
                        } else {
                            self::error('Insert failed: Unknown type for column: ' . $field . "\n\nQuery:\n" . $sql);
                        }
                    }
                    self::$_statement->bindValue($field, $value, $type);
                }
            }
            // выполняем
            self::$_statement->execute();
        } catch (\PDOException $e) {
            self::error($e->getMessage() . "\n\nQuery:\n" . $sql);
        }

        return (int)self::init()->lastInsertId();
    }

    /*
     * Выполняет обновление записи в БД
     *
     * @param string $sql - запрос, должен содержать %sql%
     * @param array $data - данные для обновления (массив: 0 - значение, 1 - тип)
     * @param array $types - типы данных (массив: поле - тип) (необязательное)
     *   тип может быть:
     *      str, \PDO::PARAM_STR - строка (по умолчанию)
     *      int, \PDO::PARAM_INT - число
     *      bool, \PDO::PARAM_BOOLEAN - 1/0
     *      null, \PDO::PARAM_NULL - null
     *      expr - выражение SQL (не экранируется!)
     *
     * @param array $fields - поля, которые необходимо использовать (необязательное)
     * @param array $data_dop - дополнительные данные
     * @return bool
     */
    public static function update($sql, array $data, array $types = [], array $fields = [], array $data_dop = [])
    {
        $res = false;
        try {
            $prep = '';
            $fz = empty($fields);
            foreach ($data as $field => $value) {
                if ($fz || in_array($field, $fields)) {
                    if (!preg_match('|^[a-z0-9_-]+$|i', $field)) {
                        self::error("Update failed: field {$field} contains invalid characters");
                    }
                    $prep .= ",`{$field}` = ";
                    if (!empty($types[ $field ]) && $types[ $field ] === 'expr') {
                        // выражение
                        $prep .= $value;
                    } else {
                        $prep .= ":{$field}";
                    }
                }
            }
            if (empty($prep)) {
                self::error('Update failed: empty data');
            }
            // подготавливаем
            $prep = substr($prep, 1);
            $sql = str_replace('%sql%', ' SET ' . $prep, $sql);

            // хэш запроса
            $hash = md5($sql);
            // проверяем, был ли ранее
            if (!isset(self::$_statements[ $hash ])) {
                // подготавливаем выражение
                self::$_statement = self::init()->prepare($sql);
                // запоминаем
                self::$_statements[ $hash ] = self::$_statement;
            } else {
                self::$_statement = self::$_statements[ $hash ];
            }

            // установка данных
            foreach ($data as $field => $value) {
                if ($fz || in_array($field, $fields)) {
                    if (empty($types[ $field ])) {
                        $type = \PDO::PARAM_STR;
                    } else {
                        $t = $types[ $field ];
                        // если выражение - пропускаем
                        if ($t === 'expr') {
                            continue;
                        }
                        if ($t === 'str' || $t === \PDO::PARAM_STR) {
                            $type = \PDO::PARAM_STR;
                        } elseif ($t === 'int' || $t === \PDO::PARAM_INT) {
                            $type = \PDO::PARAM_INT;
                        } elseif ($t === 'bool' || $t === \PDO::PARAM_BOOL) {
                            $type = \PDO::PARAM_BOOL;
                        } elseif ($t === 'null' || $t === \PDO::PARAM_NULL) {
                            $type = \PDO::PARAM_NULL;
                        } else {
                            self::error('Update failed: Unknown type for column: ' . $field . "\n\nQuery:\n" . $sql);
                        }
                    }
                    self::$_statement->bindValue($field, $value, $type);
                }
            }
            foreach ($data_dop as $field => $value) {
                self::$_statement->bindValue($field, $value);
            }
            // выполняем
            $res = self::$_statement->execute();
        } catch (\PDOException $e) {
            self::error($e->getMessage() . "\n\nQuery:\n" . $sql);
        }

        return $res;
    }

    /*
     * Выполнение стандартных операций на список записей
     *
     * @param string $table - таблица
     * @param string $table_alias - алиас таблицы
     * @param strign $sort - поля сортировки
     * @param int $limit - кол-во записей
     * @param array $sql_add - условия выборки
     * @param array $sql_data - массив данных
     * @param array $sql_select - массив выборок
     * @param array $sql_join - массив присоединенных таблиц
     * @return array/false - результат запроса
     */
    public static function exec_list(
        $table,
        $table_alias = '',
        $sort = '',
        $limit = 0,
        array $sql_add = [],
        array $sql_data = [],
        array $sql_select = [],
        array $sql_join = []
    ) {
        $sql_add = array_unique($sql_add);
        $sql_join = array_unique($sql_join);
        $sql_select = array_unique($sql_select);
        $add_sql = (!empty($sql_add)) ? ' WHERE ' . implode(' AND ', $sql_add) : '';
        $add_join = implode(" \n", $sql_join);
        $select_fields = !empty($sql_select) ? implode(', ', $sql_select) : "{$table_alias}.*";

        if (empty($table_alias)) {
            $table_alias = substr($table, 0, 1);
        }

        $slimit = '';
        $limit = abs(intval($limit));
        if ($limit > 0) {
            $ENV = Environment::getInstance();
            $ENV['db_count_res'] = self::getOne(
                "SELECT COUNT(*) FROM {$table} {$table_alias} {$add_join} {$add_sql}",
                $sql_data
            );
            $offset = dynamic::pager($ENV['db_count_res'], $limit);
            $slimit = "LIMIT {$offset},{$limit}";
        }

        $order = !empty($sort) ? "ORDER BY {$table_alias}.{$sort}" : '';

        return db::getAll(
            "SELECT
         {$select_fields}
      FROM {$table} {$table_alias}
         {$add_join}
            {$add_sql}
         {$order}
         {$slimit}",
            $sql_data
        );
    }

    /*
     * Выполнение стандартных операций на получение записи
     *
     * @param string $table - таблица
     * @param string $table_alias - алиас таблицы
     * @param array $sql_add - условия выборки
     * @param array $sql_data - массив данных
     * @param array $sql_select - массив выборок
     * @param array $sql_join - массив присоединенных таблиц
     * @return array/false - результат запроса
     */
    public static function exec_one(
        $table,
        $table_alias = '',
        array $sql_add = [],
        array $sql_data = [],
        array $sql_select = [],
        array $sql_join = []
    ) {
        $sql_add = array_unique($sql_add);
        $sql_join = array_unique($sql_join);
        $sql_select = array_unique($sql_select);
        $add_sql = (!empty($sql_add)) ? ' WHERE ' . implode(' AND ', $sql_add) : '';
        $add_join = implode(" \n", $sql_join);
        $add_select = !empty($sql_select) ? ', ' . implode(', ', $sql_select) : '';
        $select_fields = !empty($sql_select) ? implode(', ', $sql_select) : "{$table_alias}.*";
        if (empty($table_alias)) {
            $table_alias = substr($table, 0, 1);
        }

        return db::getRow(
            "SELECT
         {$select_fields}
      FROM {$table} {$table_alias}
         {$add_join}
            {$add_sql}",
            $sql_data
        );
    }
} // конец класса db
<?php
namespace NextFW\Config;


class Database {
    public static $data = [
        'default' => [
            'driver' => 'mysql',
            'host' => 'mysql.hostinger.ru',
            'user' => 'u602610923_next',
            'pass' => '',
            'name' => 'u602610923_next',
            'char' => 'utf8',
            'debug' => 1,
            'persistent' => false,
            'log_error' => true,
            'log_sql' => 0.5
        ],
    ];
}
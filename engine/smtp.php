<?php
namespace NextFW\Engine;

class Smtp {

    private $config;

    function __construct( $username, $password, $host, $port = 25, $ssl = '', $from = '', $debug = false, $charset = 'UTF-8' )
    {
        if ( $from == '' ) {
            $from = $username;
        }
        $sslUrl = $ssl == '' ? '' : ($ssl == 'ssl') ? 'ssl://' : 'tls://';
        $this->config = [
            'smtp_username' => $username,
            'smtp_port'     => $port,
            'smtp_host'     => $sslUrl.$host,
            'smtp_password' => $password,
            'smtp_debug'    => $debug,
            'smtp_charset'  => $charset,
            'smtp_from'     => $from
        ];
    }

    function send($mail_to, $subject, $message, $headers='') {
        $config = $this->config;
        $SEND =   "Date: ".date("D, d M Y H:i:s") . " UT\r\n";
        $SEND .=   'Subject: =?'.$config['smtp_charset'].'?B?'.base64_encode($subject)."=?=\r\n";
        if ($headers) $SEND .= $headers."\r\n\r\n";
        else
        {
            $SEND .= "Reply-To: ".$config['smtp_username']."\r\n";
            $SEND .= "MIME-Version: 1.0\r\n";
            $SEND .= "Content-Type: text/html; charset=\"".$config['smtp_charset']."\"\r\n";
            $SEND .= "Content-Transfer-Encoding: 8bit\r\n";
            $SEND .= "From: \"".$config['smtp_from']."\" <".$config['smtp_username'].">\r\n";
            $SEND .= "To: $mail_to <$mail_to>\r\n";
            $SEND .= "X-Priority: 3\r\n\r\n";
        }
        $SEND .=  $message."\r\n";
        if( !$socket = fsockopen($config['smtp_host'], $config['smtp_port'], $errno, $errstr, 30) ) {
            if ($config['smtp_debug']) throw new \Exception($errno."&lt;br&gt;".$errstr);
            return false;
        }

        if (!$this->server_parse($socket, "220", __LINE__)) return false;

        fputs($socket, "HELO " . $config['smtp_host'] . "\r\n");
        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не могу отправить HELO!');
            fclose($socket);
            return false;
        }
        fputs($socket, "AUTH LOGIN\r\n");
        if (!$this->server_parse($socket, "334", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не могу найти ответ на запрос авторизаци.');
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
        if (!$this->server_parse($socket, "334", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Логин авторизации не был принят сервером!');
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
        if (!$this->server_parse($socket, "235", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Пароль не был принят сервером как верный! Ошибка авторизации!');
            fclose($socket);
            return false;
        }
        fputs($socket, "MAIL FROM: <".$config['smtp_username'].">\r\n");
        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не могу отправить комманду MAIL FROM');
            fclose($socket);
            return false;
        }
        fputs($socket, "RCPT TO: <" . $mail_to . ">\r\n");

        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не могу отправить комманду RCPT TO');
            fclose($socket);
            return false;
        }
        fputs($socket, "DATA\r\n");

        if (!$this->server_parse($socket, "354", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не могу отправить комманду DATA');
            fclose($socket);
            return false;
        }
        fputs($socket, $SEND."\r\n.\r\n");

        if (!$this->server_parse($socket, "250", __LINE__)) {
            if ($config['smtp_debug']) throw new \Exception('Не смог отправить тело письма. Письмо не было отправленно!');
            fclose($socket);
            return false;
        }
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        return TRUE;
    }
    private function server_parse($socket, $response, $line = __LINE__) {
        $config = $this->config;
        do {
            if (!($server_response = fgets($socket, 256))) {
                if ($config['smtp_debug']) throw new \Exception("<p>Проблемы с отправкой почты!</p>$response<br>$line<br>");
                return false;
            }
        }
        while (substr($server_response, 3, 1) != ' ');
        if (!(substr($server_response, 0, 3) == $response)) {
            if ($config['smtp_debug']) throw new \Exception("<p>Проблемы с отправкой почты!</p>$response<br>$line<br>");
            return false;
        }
        return true;
    }
}
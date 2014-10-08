<?php
namespace NextFW\Engine;


class Logger {
    public $customFile = null;
    public $customFolder = null;
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    function write($logStr,$logArray = [], $errorType = self::WARNING)
    {
        if(empty($logStr)) return false;
        if(count($logArray) > 0) {
            foreach ($logArray as $key => $value) {
                $logStr = str_replace("{".$key."}",$value,$logStr);
            }
        }
        $logStr .= "\n";
        $folder = $this->customFolder != null ? PATH.DIRECTORY_SEPARATOR.$this->customFolder : LOG;
        $file = $this->customFile != null ? $this->customFile : Route::getUrl()[0];
        $file .= "_".date("d-m-Y").".log";
        $full_path = $folder.DIRECTORY_SEPARATOR.$file;
        if(!file_exists($file) AND !is_writable($folder)) return false;

        $handler = fopen($full_path, "a");
        if ($handler) {
            fwrite($handler, "[".date("H:i:s")."] [".$errorType."] ".$logStr);
        } else return false;
        fclose($handler);
        return true;
    }
}
<?php

namespace PressToJamCore\Services;

class Logger {

    private $log_file;
    private $echo_log = false;
    private $format = "d/m/Y H:i:s";

    function __construct($log_file, $echo = false) {
        $this->log_file = $log_file;
        $this->echo_log = $echo;
    }

    function addLog($msg) {
        $fp = fopen($this->log_file, 'a');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, "\n" . date($this->format) . " " . $msg);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        if ($this->echo_log) echo "\n" . $msg;
    }
}
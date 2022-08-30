<?php

/**
 * add a new log inside JC log files 
 * 
 * generic description for the 4 functions
 * 
 * @param string $message message you want to be logged in the file
 * @param string $suffix if you need to create a dedicated log file '_trace' will add log in file 'JeedomConnect_trace'
 */
class JCLog {

    public static function trace($message, $suffix = '') {
        if (config::byKey('traceLog', 'JeedomConnect', 0)) {
            log::add('JeedomConnect' . $suffix, 'debug', '[TRACE] ' . $message);
        }
    }

    public static function debug($message, $suffix = '') {
        log::add('JeedomConnect' . $suffix, 'debug', $message);
    }

    public static function info($message, $suffix = '') {
        log::add('JeedomConnect' . $suffix, 'info', $message);
    }

    public static function warning($message, $suffix = '') {
        log::add('JeedomConnect' . $suffix, 'warning', $message);
    }

    public static function error($message, $suffix = '') {
        log::add('JeedomConnect' . $suffix, 'error', $message);
    }
}

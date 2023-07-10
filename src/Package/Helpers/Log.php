<?php

namespace SayHello\GitInstaller\Package\Helpers;

class Log
{
    public static function getFolder(): string
    {
        $folder = 'git-installer-log';
        $uploadDir = wp_get_upload_dir();
        $baseDir = trailingslashit($uploadDir['basedir']);
        $dir = $baseDir . $folder . '/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        if (!file_exists($dir . '.htaccess')) {
            file_put_contents($dir . '.htaccess', "order deny,allow\ndeny from all");
        }

        return $dir;
    }

    public static function getLogFile(): string
    {
        $folder = self::getFolder();
        $file = $folder . date("Y-m-d") . '.log';
        if (!file_exists($file)) {
            file_put_contents($file, '');
        }
        return $file;
    }

    public static function addLog($message): void
    {
        if (WP_DEBUG !== true) return;

        $logfile = self::getLogFile();
        $currentDate = date("[d-M-Y H:i:s T]");
        $e = new \Exception;
        $entry = "--------------------\n";
        $entry .= $currentDate . "\n\n";
        $entry .= $message . "\n\n";
        $entry .= "Stack trace:\n";
        $entry .= $e->getTraceAsString() . "\n";
        $entry .= "--------------------\n\n\n";

        file_put_contents($logfile, $entry, FILE_APPEND);
    }
}
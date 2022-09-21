<?php

namespace SayHello\GitInstaller\Package\Provider;

class Provider
{
    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        self::rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function getPackageDir($package)
    {
        if ($package['theme']) {
            return trailingslashit(get_theme_root()) . $package['name'];
        }

        return trailingslashit(WP_PLUGIN_DIR) . $package['name'];
    }

    public static function getLastUrlParam($url)
    {
        $params = array_filter(explode('/', $url), 'strlen');

        return end($params);
    }

    public static function trimString($string)
    {
        return preg_replace('/[^a-zA-Z_\-0-9]/', '', $string);
    }
}

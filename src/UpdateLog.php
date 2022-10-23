<?php

namespace SayHello\GitInstaller;

class UpdateLog
{
    private static $refOptions = [
        'push-to-deploy',
        'update-trigger',
    ];
    private static $optionKey = 'shgi-updatelog';

    public static function addLog($key, $ref, $prevVersion, $nextVersion)
    {
        $logs = self::getLogs($key);
        $newEntry = self::mapEntry([
            'ref' => $ref,
            'time' => time(),
            'prevVersion' => $prevVersion,
            'newVersion' => $nextVersion,
        ]);

        $logs[] = $newEntry;

        update_option(self::$optionKey . '-' . $key, $logs);
        return $newEntry;
    }

    public static function getLogs($key)
    {
        $option = get_option(self::$optionKey . '-' . $key, []);
        return array_map(function ($o) {
            return self::mapEntry($o);
        }, $option);
    }

    private static function mapEntry($option)
    {
        return [
            'ref' => array_key_exists('ref', $option) ? $option['ref'] : null,
            'time' => array_key_exists('time', $option) ? $option['time'] : null,
            'prevVersion' => array_key_exists('prevVersion', $option) ? $option['prevVersion'] : null,
            'newVersion' => array_key_exists('newVersion', $option) ? $option['newVersion'] : null,
        ];
    }

    public static function getRefOptions()
    {
        return apply_filters('', self::$refOptions);
    }
}

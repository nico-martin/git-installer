<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;

class UpdateLog
{
    private static $optionKey = 'shgi-updatelog';

    public function run()
    {
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'packages-update-log/(?P<slug>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'getLogsApi'],
            'args' => [
                'slug',
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);
    }

    public function getLogsApi($data)
    {
        $key = $data['slug'];
        return self::getLogs($key);
    }

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
            $e = self::mapEntry($o);
            $e['date'] = wp_date(get_option('date_format') . ' ' . get_option('time_format'), $e['time']);
            $e['refName'] = array_key_exists($e['ref'], self::getRefOptions()) ? self::getRefOptions()[$e['ref']] : '-';
            return $e;
        }, $option);
    }

    private static function mapEntry($option)
    {
        return [
            'ref' => array_key_exists('ref', $option) && array_key_exists($option['ref'], self::getRefOptions()) ? $option['ref'] : null,
            'time' => array_key_exists('time', $option) ? $option['time'] : null,
            'prevVersion' => array_key_exists('prevVersion', $option) ? $option['prevVersion'] : null,
            'newVersion' => array_key_exists('newVersion', $option) ? $option['newVersion'] : null,
        ];
    }

    public static function getRefOptions()
    {
        return apply_filters('shgi/UpdateLog/refOptions', [
            'webhook-update' => __('Webhook', 'shgi'),
            'update-trigger' => __('update button', 'shgi')
        ]);
    }
}

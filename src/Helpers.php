<?php

namespace SayHello\GitInstaller;

class Helpers
{
    public static string $authAdmin = 'administrator';
    private static array $activeNotifications = [];

    public static function checkAuth(): bool
    {
        return current_user_can(self::$authAdmin);
    }

    public static function getPages(): array
    {
        $pages = [];
        foreach (get_pages() as $post) {
            $pages[$post->ID] = get_the_title($post);
        }

        return $pages;
    }

    public static function isEmail($string)
    {
        return filter_var($string, FILTER_VALIDATE_EMAIL);
    }

    public static function isUrl($string)
    {
        return filter_var($string, FILTER_VALIDATE_URL);
    }

    public static function getRestJson($url, $args = [])
    {
        $json = json_decode(self::fetchPlainText($url, $args), true);

        if (!$json) {
            return new \WP_Error('json_parse_error', sprintf(
                    __('Request to %s could not be processed', 'shgi'),
                    '<code>' . $url . '</code>')
            );
        }

        return $json;
    }

    public static function fetchPlainText($url, $args = [])
    {
        $request = wp_remote_get($url, $args);
        if (is_wp_error($request)) return $request;

        $code = wp_remote_retrieve_response_code($request);
        if ($code >= 300) new \WP_Error('remote_get_error', sprintf(
                __('Invalid request to %s', 'shgi'),
                '<code>' . $url . '</code>')
        );

        $body = wp_remote_retrieve_body($request);

        return str_replace('<?php', '', $body);
    }

    public static function getContentFolder($url = false): string
    {
        $folder = 'shgi';
        $uploadDir = wp_get_upload_dir();
        $baseUrl = trailingslashit($uploadDir['baseurl']);
        $baseDir = trailingslashit($uploadDir['basedir']);
        if (!is_dir($baseDir . $folder . '/')) {
            mkdir($baseDir . $folder . '/');
        }

        if ($url) {
            return $baseUrl . $folder . '/';
        }

        return $baseDir . $folder . '/';
    }

    public static function checkForFunction($func, $notification = true): bool
    {
        if (!function_exists($func)) {
            $message = 'The function <code>' . $func . '()</code> is not available. Some Parts of <b>' . sayhelloGitInstaller()->name . '</b> won\'t work as expected.';
            if ($notification) {
                self::showAdminNotification($message);
            }

            return false;
        }

        return true;
    }

    public static function showAdminNotification($message, $type = 'error')
    {
        $key = md5("{$type}: {$message}");
        if (!in_array($key, self::$activeNotifications)) {
            add_action('admin_notices', function () use ($message, $type) {
                $class = "notice notice-{$type}";
                printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
            });
            self::$activeNotifications[] = $key;
        }
    }

    public static function print($e)
    {
        echo '<pre style="margin-left: 200px">';
        print_r($e);
        echo '</pre>';
    }

    public static function useMustUsePlugins()
    {
        return apply_filters('shgi/Repositories/MustUsePlugins', false);
    }

    public static function sanitizeDir($dir): string
    {
        if (!$dir) return '';
        return trailingslashit($dir);
    }
}

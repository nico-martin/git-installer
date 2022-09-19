<?php

namespace SayHello\GitUpdater\Package\Provider;

use SayHello\GitUpdater\Helpers;

class Github extends Provider
{
    public static $provider = 'github';

    public static function validateUrl($url)
    {
        return strpos($url, 'https://github.com/') === 0;
    }

    public static function getInfos($url, $theme = false)
    {
        $base = 'https://github.com/';
        if (strpos($url, $base) !== 0) {
            return new \WP_Error(
                'invalid_github_url',
                sprintf(__('"%s" ist kein GitHub URL', 'shgu'), $base)
            );
        }

        $apiUrl = str_replace($base, 'https://api.github.com/repos/', untrailingslashit($url));
        $auth = self::authenticateRequest($apiUrl);
        $response = Helpers::getRestJson($auth[0], $auth[1]);

        if (!$response) {
            return new \WP_Error(
                'invalid_json',
                sprintf(
                    __('Anfrage an %s konnte nicht verarbeitet werden', 'shgu'),
                    '<code>' . $apiUrl . '</code>'
                )
            );
        }

        if (array_key_exists('message', $response)) {
            return new \WP_Error(
                'invalid_response',
                sprintf(
                    __('API Informationen konnten nicht abgerufen werden: %s', 'shgu'),
                    '<code>' . $apiUrl . '</code>'
                )
            );
        }

        $dir = self::getLastUrlParam($url);

        return [
            'key' => $dir,
            'name' => $response['name'],
            'theme' => $theme,
            'baseUrl' => $url,
            'hoster' => self::$provider,
            'url' => [
                'repository' => $response['html_url'],
                'zip' => $apiUrl . '/zipball/',
                'api' => $apiUrl,
            ],
        ];
    }

    public static function authenticateRequest($url, $args = [])
    {
        $github_auth_header = sayhelloGitUpdater()->Settings->getSingleSettingValue('git-packages-github-token');
        if ($github_auth_header) {
            $args = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $github_auth_header,
                ]
            ];
        }

        return [$url, $args];
    }
}

<?php

namespace SayHello\GitUpdater\Package\Provider;

use SayHello\GitUpdater\Helpers;

class Gitlab extends Provider
{
    public static $provider = 'gitlab';

    public static function validateUrl($url)
    {
        return strpos($url, 'https://gitlab.com/') === 0;
    }

    public static function getInfos($url, $theme = false)
    {
        $base = 'https://gitlab.com/';
        if (strpos($url, $base) !== 0) {
            return new \WP_Error(
                'invalid_gitlab_url',
                sprintf(__('"%s" ist kein GitLab URL', 'shgu'), $base)
            );
        }

        $gitlabID = urlencode(str_replace($base, '', $url));
        $apiUrl = 'https://gitlab.com/api/v4/projects/' . $gitlabID;
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
                'repository' => $response['web_url'],
                'zip' => trailingslashit($apiUrl) . 'repository/archive.zip',
                'api' => $apiUrl,

            ],
        ];
    }

    public static function authenticateRequest($url, $args = [])
    {
        $gitlabToken = sayhelloGitUpdater()->Settings->getSingleSettingValue('git-packages-gitlab-token');
        if (strpos($url, 'private_token=') === false) {
            if (strpos($url, '?') === false) {
                $url = $url . '?private_token=' . $gitlabToken;
            } else {
                $url = $url . '&private_token=' . $gitlabToken;
            }
        }

        return [$url, $args];
    }
}

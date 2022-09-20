<?php

namespace SayHello\GitInstaller\Package\Provider;

use SayHello\GitInstaller\Helpers;

class Gitlab extends Provider
{
    public static $provider = 'gitlab';

    public static function validateUrl($url)
    {
        $parsed = self::parseGitlabUrl($url);
        return $parsed['host'] === 'gitlab.com' && isset($parsed['id']);
    }

    private static function parseGitlabUrl($url)
    {
        $parsed = parse_url($url);
        $parsed['params'] = array_values(
            array_filter(
                explode('/', $parsed['path']),
                function ($e) {
                    return $e !== '';
                }
            )
        );


        return [
            'host' => $parsed['host'],
            'id' => urlencode(implode('/', $parsed['params'])),
            'repo' => end($parsed['params']),
        ];
    }

    public static function getInfos($url)
    {
        if (!self::validateUrl($url)) {
            return new \WP_Error(
                'invalid_url',
                sprintf(__('"%s" ist kein gültiges Gitlab Repository', 'shgi'), $url)
            );
        }

        $parsedUrl = self::parseGitlabUrl($url);
        // https://gitlab.com/api/v4/projects/say-hello%2Fplugins%2Fhello-cookies
        $apiUrl = 'https://gitlab.com/api/v4/projects/' . $parsedUrl['id'];
        $auth = self::authenticateRequest($apiUrl);

        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = self::getBranches($parsedUrl['id']);

        if (is_wp_error($branches)) return $branches;

        return [
            'key' => $parsedUrl['repo'],
            'name' => $response['name'],
            'private' => $response['visibility'] === 'private',
            'provider' => self::$provider,
            'branches' => $branches,
            'baseUrl' => $response['web_url'],
            'apiUrl' => $apiUrl,
        ];
    }

    private static function getBranches($id)
    {
        $apiUrl = 'https://gitlab.com/api/v4/projects/' . $id;
        $apiBranchesUrl = "{$apiUrl}/repository/branches";
        $auth = self::authenticateRequest($apiBranchesUrl);
        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = [];
        foreach ($response as $branch) {
            $branches[$branch['name']] = [
                'name' => $branch['name'],
                'url' => $branch['web_url'],
                'zip' => trailingslashit($apiUrl) . 'repository/archive.zip?sha=' . $branch['name'],
                'default' => $branch['default'],
            ];
        }
        return $branches;
    }

    public static function authenticateRequest($url, $args = [])
    {
        $gitlabToken = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-gitlab-token');
        if (strpos($url, 'private_token=') === false) {
            if (strpos($url, '?') === false) {
                $url = $url . '?private_token=' . $gitlabToken;
            } else {
                $url = $url . '&private_token=' . $gitlabToken;
            }
        }

        return [$url, $args];
    }

    public static function export()
    {
        return new class {
            public function validateUrl($url)
            {
                return Gitlab::validateUrl($url);
            }

            public function getInfos($url)
            {
                return Gitlab::getInfos($url);
            }

            public function authenticateRequest($url, $args = [])
            {
                return Gitlab::authenticateRequest($url, $args);
            }
        };
    }
}

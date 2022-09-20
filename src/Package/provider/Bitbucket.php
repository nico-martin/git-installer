<?php

namespace SayHello\GitInstaller\Package\Provider;

use SayHello\GitInstaller\Helpers;

class Bitbucket extends Provider
{
    public static $provider = 'bitbucket';

    public static function validateUrl($url)
    {
        $parsed = self::parseBitbucketUrl($url);
        return $parsed['host'] === 'bitbucket.org' && isset($parsed['workspace']) && isset($parsed['repo']);
    }

    private static function parseBitbucketUrl($url)
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
            'workspace' => $parsed['params'][0],
            'repo' => $parsed['params'][1]
        ];
    }

    public static function getInfos($url)
    {
        if (!self::validateUrl($url)) {
            return new \WP_Error(
                'invalid_url',
                sprintf(__('"%s" ist kein gültiges Bitbucket Repository', 'shgi'), $url)
            );
        }

        $parsedUrl = self::parseBitbucketUrl($url);
        // https://api.bitbucket.org/2.0/repositories/sayhellogmbh/shp-widget-medienjobs
        $apiUrl = "https://api.bitbucket.org/2.0/repositories/{$parsedUrl['workspace']}/{$parsedUrl['repo']}";
        $auth = self::authenticateRequest($apiUrl);

        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = self::getBranches($parsedUrl['workspace'], $parsedUrl['repo'], $response['mainbranch']['name']);

        if (is_wp_error($branches)) return $branches;

        return [
            'key' => $parsedUrl['repo'],
            'name' => $response['name'],
            'private' => $response['is_private'],
            'provider' => self::$provider,
            'branches' => $branches,
            'baseUrl' => $response['links']['html']['href'],
            'apiUrl' => $apiUrl,
        ];
    }

    private static function getBranches($workspace, $repo, $defaultBranch)
    {
        $apiUrl = "https://api.bitbucket.org/2.0/repositories/{$workspace}/{$repo}";
        $apiBranchesUrl = "{$apiUrl}/refs/branches?pagelen=100";
        $auth = self::authenticateRequest($apiBranchesUrl);
        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = [];
        foreach ($response['values'] as $branch) {
            $branches[$branch['name']] = [
                'name' => $branch['name'],
                'url' => $branch['links']['html']['href'],
                'zip' => "https://bitbucket.org/{$workspace}/{$repo}/get/{$branch['name']}.zip",
                'default' => $branch['name'] === $defaultBranch,
            ];
        }
        return $branches;
    }

    public static function authenticateRequest($url, $args = [])
    {
        $token = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-bitbucket-token');
        $user = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-bitbucket-user');
        if ($token && $user) {
            $args = [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("{$user}:{$token}"),
                ]
            ];
        }

        return [$url, $args];
    }

    public static function export()
    {
        return new class {
            public function validateUrl($url)
            {
                return Bitbucket::validateUrl($url);
            }

            public function getInfos($url)
            {
                return Bitbucket::getInfos($url);
            }

            public function authenticateRequest($url, $args = [])
            {
                return Bitbucket::authenticateRequest($url, $args);
            }
        };
    }
}

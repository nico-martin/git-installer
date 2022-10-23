<?php

namespace SayHello\GitInstaller\Package\Provider;

use SayHello\GitInstaller\Helpers;

class Github extends Provider
{
    public static $provider = 'github';

    public static function validateUrl($url)
    {
        $parsed = self::parseGithubUrl($url);
        return $parsed['host'] === 'github.com' && isset($parsed['owner']) && isset($parsed['repo']);
    }

    private static function parseGithubUrl($url)
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
            'owner' => $parsed['params'][0],
            'repo' => $parsed['params'][1]
        ];
    }

    public static function getInfos($url)
    {
        if (!self::validateUrl($url)) {
            return new \WP_Error(
                'invalid_url',
                sprintf(__('"%s" is not a valid GitHub repository', 'shgi'), $url)
            );
        }

        $parsedUrl = self::parseGithubUrl($url);
        // https://api.github.com/repos/SayHelloGmbH/progressive-wordpress
        $apiUrl = "https://api.github.com/repos/{$parsedUrl['owner']}/{$parsedUrl['repo']}";
        $auth = self::authenticateRequest($apiUrl);

        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = self::getBranches($parsedUrl['owner'], $parsedUrl['repo'], $response['default_branch']);

        if (is_wp_error($branches)) return $branches;

        return [
            'key' => $parsedUrl['repo'],
            'name' => $response['name'],
            'private' => $response['private'],
            'provider' => self::$provider,
            'branches' => $branches,
            'baseUrl' => "https://github.com/{$parsedUrl['owner']}/{$parsedUrl['repo']}",
            'apiUrl' => $apiUrl,
        ];
    }

    private static function getBranches($owner, $repo, $defaultBranch)
    {
        $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}";
        $apiBranchesUrl = "{$apiUrl}/branches";
        $auth = self::authenticateRequest($apiBranchesUrl);
        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = [];
        foreach ($response as $branch) {
            $branches[$branch['name']] = [
                'name' => $branch['name'],
                'url' => "https://github.com/{$owner}/{$repo}/tree/{$branch['name']}",
                'zip' => "{$apiUrl}/zipball/{$branch['name']}",
                'default' => $branch['name'] === $defaultBranch,
            ];
        }
        return $branches;
    }

    private static function getRepoFolderFiles($owner, $repo, $branch, $folder = '')
    {
        $auth = self::authenticateRequest("https://api.github.com/repos/{$owner}/{$repo}/git/trees/{$branch}?recursive=1");
        $response = Helpers::getRestJson($auth[0], $auth[1]);
        $files = array_values(
            array_filter(
                $response['tree'],
                function ($element) use ($folder) {
                    if ($element['type'] !== 'blob') return false;
                    if (!str_starts_with($element['path'], $folder)) return false;
                    if ($element['path'] === 'style.css') return true;
                    $relativePath = substr($element['path'], strlen($folder));
                    if (str_contains($relativePath, '/')) return false;
                    return str_ends_with($relativePath, '.php');
                }
            )
        );

        return array_map(function ($element) use ($folder) {
            $auth = self::authenticateRequest($element['url']);
            $response = Helpers::getRestJson($auth[0], $auth[1]);
            return [
                'file' => substr($element['path'], strlen($folder)),
                'content' => base64_decode($response['content']),
            ];
        }, $files);
    }

    public static function validateDir($url, $branch, $dir)
    {
        $parsed = self::parseGithubUrl($url);
        return self::getRepoFolderFiles($parsed['owner'], $parsed['repo'], $branch, $dir);
    }

    public static function authenticateRequest($url, $args = [])
    {
        $github_auth_header = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-github-token');
        if ($github_auth_header) {
            $github_auth_header = Provider::trimString($github_auth_header);
            $args = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $github_auth_header,
                ]
            ];
        }

        return [$url, $args];
    }

    public static function export()
    {
        return new class {
            public function name()
            {
                return 'GitHub';
            }

            public function hasToken()
            {
                return boolval(sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-github-token'));
            }

            public function validateUrl($url)
            {
                return Github::validateUrl($url);
            }

            public function getInfos($url)
            {
                return Github::getInfos($url);
            }

            public function authenticateRequest($url, $args = [])
            {
                return Github::authenticateRequest($url, $args);
            }

            public function validateDir($url, $branch, $dir = '')
            {
                return Github::validateDir($url, $branch, $dir);
            }
        };
    }
}

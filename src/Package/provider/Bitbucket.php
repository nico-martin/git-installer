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
                sprintf(__('"%s" is not a valid Bitbucket repository', 'shgi'), $url)
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

    private static function getBranches($workspace, $repo, $defaultBranch = '')
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
                'hash' => $branch['target']['hash'],
            ];
        }
        return $branches;
    }

    private static function getRepoFolderFiles($workspace, $repo, $branch, $folder = '')
    {
        $branchHash = self::getBranches($workspace, $repo)[$branch]['hash'];
        //return "https://api.bitbucket.org/2.0/repositories/{$workspace}/{$repo}/src/{$branchHash}";
        $auth = self::authenticateRequest("https://api.bitbucket.org/2.0/repositories/{$workspace}/{$repo}/src/{$branchHash}/$folder?pagelen=99");
        $response = Helpers::getRestJson($auth[0], $auth[1]);
        $files = array_values(
            array_filter(
                $response['values'],
                function ($element) use ($folder) {
                    if ($element['type'] !== 'commit_file') return false;
                    if (!str_starts_with($element['path'], $folder)) return false;
                    if ($element['path'] === 'style.css') return true;
                    $relativePath = substr($element['path'], strlen($folder));
                    if (str_contains($relativePath, '/')) return false;
                    return str_ends_with($relativePath, '.php');
                }
            )
        );

        return array_map(function ($element) use ($folder) {
            $url = $element['links']['self']['href'];
            $content = self::fetchFileContent($url);
            return [
                'file' => substr($element['path'], strlen($folder)),
                'fileUrl' => $url,
                'content' => $content['content'],
            ];
        }, $files);
    }

    public static function fetchFileContent($url): array
    {
        $auth = self::authenticateRequest($url);
        $response = Helpers::fetchPlainText($auth[0], $auth[1]);

        return [
            'name' => null,
            'content' => is_wp_error($response) ? null : $response,
        ];
    }

    public static function validateDir($url, $branch, $dir)
    {
        $parsed = self::parseBitbucketUrl($url);
        return self::getRepoFolderFiles($parsed['workspace'], $parsed['repo'], $branch, $dir);
    }

    public static function authenticateRequest($url, $args = [])
    {
        $token = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-bitbucket-token');
        $user = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-bitbucket-user');
        if ($token && $user) {
            $token = Provider::trimString($token);
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
            public function name()
            {
                return 'Bitbucket';
            }

            public function hasToken()
            {
                return boolval(sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-bitbucket-token'));
            }

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

            public function validateDir($url, $branch, $dir = '')
            {
                return Bitbucket::validateDir($url, $branch, $dir);
            }
        };
    }
}

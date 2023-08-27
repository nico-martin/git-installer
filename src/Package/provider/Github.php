<?php

namespace SayHello\GitInstaller\Package\Provider;

use SayHello\GitInstaller\Helpers;

class Github extends Provider
{
    public static string $provider = 'github';

    public static function validateUrl($url): bool
    {
        if (!$url) return false;
        $parsed = self::parseGithubUrl($url);
        return $parsed['host'] === 'github.com' && isset($parsed['owner']) && isset($parsed['repo']);
    }

    private static function parseGithubUrl($url)
    {
        $regex = '/^(?:https?:\/\/)?(?:ssh:\/\/)?(?:git@)?github.com(?::|\/)([^\/]+)\/([^\/\s]+)/';
        $match = preg_match($regex, $url, $matches);

        if ($match) {
            return [
                'host' => 'github.com',
                'owner' => $matches[1],
                'repo' => $matches[2]
            ];
        }

        return [
            'host' => 'invalid',
            'owner' => '',
            'repo' => ''
        ];
    }

    public static function getInfos($url, $dir)
    {
        if (!self::validateUrl($url)) {
            return new \WP_Error(
                'invalid_url',
                sprintf(__('"%s" is not a valid GitHub repository', 'shgi'), $url)
            );
        }

        $parsedUrl = self::parseGithubUrl($url);
        $apiUrl = "https://api.github.com/repos/{$parsedUrl['owner']}/{$parsedUrl['repo']}";
        $auth = self::authenticateRequest($apiUrl);

        $response = Helpers::getRestJson($auth[0], $auth[1]);
        if (is_wp_error($response)) return $response;

        $branches = self::getBranches($parsedUrl['owner'], $parsedUrl['repo'], $response['default_branch']);

        if (is_wp_error($branches)) return $branches;

        $key = $dir ? basename($dir) : $parsedUrl['repo'];

        return [
            'key' => Helpers::sanitizeRepositoryDir($key),
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
                'db' => $defaultBranch
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
                    $relativePath = substr($element['path'], strlen($folder));
                    if (str_contains($relativePath, '/')) return false;
                    return str_ends_with($relativePath, '.php') || str_ends_with($relativePath, 'style.css');
                }
            )
        );

        return array_map(function ($element) use ($folder, $owner, $repo, $branch) {
            $url = "https://raw.githubusercontent.com/{$owner}/{$repo}/{$branch}/{$element['path']}";
            $content = self::fetchFileContent($url);
            return [
                'file' => substr($element['path'], strlen($folder)),
                'fileUrl' => $url,
                'content' => $content,
            ];
        }, $files);
    }

    public static function fetchFileContent($url): ?string
    {
        $auth = self::authenticateRequest($url);
        $response = Helpers::fetchPlainText($auth[0], $auth[1]);

        return is_wp_error($response) ? null : $response;
    }

    public static function validateDir($url, $branch, $dir)
    {
        $parsed = self::parseGithubUrl($url);
        return self::getRepoFolderFiles($parsed['owner'], $parsed['repo'], $branch, $dir);
    }

    public static function authenticateRequest($url, $args = [])
    {
        $authHeader = self::authHeader();
        if ($authHeader) {
            $args = [
                'headers' => [
                    'Authorization' => $authHeader,
                ]
            ];
        }

        return [$url, $args];
    }

    public static function authHeader()
    {
        $githubAuthHeader = sayhelloGitInstaller()->Settings->getSingleSettingValue('git-packages-github-token');
        if (!$githubAuthHeader) return false;
        return 'Bearer ' . Provider::trimString($githubAuthHeader);
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

            public function getInfos($url, $dir)
            {
                return Github::getInfos($url, $dir);
            }

            public function authenticateRequest($url, $args = [])
            {
                return Github::authenticateRequest($url, $args);
            }

            public function validateDir($url, $branch, $dir = '')
            {
                return Github::validateDir($url, $branch, $dir);
            }

            public function fetchFileContent($url)
            {
                return Github::fetchFileContent($url);
            }

            public function getAuthHeader()
            {
                return Github::authHeader();
            }
        };
    }
}



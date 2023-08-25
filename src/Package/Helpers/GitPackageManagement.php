<?php

namespace SayHello\GitInstaller\Package\Helpers;

use SayHello\GitInstaller\Helpers;

/**
 * $gitPackage = [
 *   'key' => (string)
 *   'name' => (string)
 *   'private' => (bool)
 *   'provider' => (string) bitbucket, github or gitlab
 *   'branches' => Record<string, [
 *     'name' => (string)
 *     'url' => (string)
 *     'zip' => (string)
 *     'default' => (bool)
 *   ]>
 *   'activeBranch' => (string)
 *   'baseUrl' => (string)
 *   'apiUrl' => (string)
 *   'deployKey' => (string)
 *   'theme' => (bool)
 *   'headerFile' => (string)
 *   'saveAsMustUsePlugin' => (bool)
 *   'version' => (string)
 *   'dir' => (string)
 *   'postupdateHooks' => Array<string>
 * ]
 */
class GitPackageManagement
{
    private ?array $packages;
    public string $repo_option = 'sayhello-git-installer-git-repositories';
    public string $deploy_option = 'sayhello-git-installer-git-deploy';

    public function __construct()
    {
        $this->packages = null;
    }

    /**
     * @param $cache bool
     * @return array Array of key (string) => $gitPackage
     */

    public function getPackages(bool $cache = true, bool $flushCache = true): array
    {
        if ($this->packages !== null && $cache) {
            return $this->packages;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if ($flushCache) {
            wp_cache_flush();
            search_theme_directories(true); // flush theme cache
        }

        $plugins = get_plugins();
        $themes = wp_get_themes();

        $return_repos = [];
        $repos = get_option($this->repo_option, []);
        $deployKeys = get_option($this->deploy_option, []);

        foreach ($repos as $dir => $repo) {
            $version = null;
            $return_repos[$dir] = $repo;

            if ($repo['theme']) {
                if (array_key_exists($dir, $themes)) {
                    $version = $themes[$dir]['Version'];
                }
            } else {
                $filteredPlugins = array_values(
                    array_filter(
                        $plugins,
                        function ($key) use ($dir) {
                            return substr($key, 0, strlen($dir)) === $dir;
                        },
                        ARRAY_FILTER_USE_KEY
                    )
                );
                $plugin = count($filteredPlugins) >= 1 ? $filteredPlugins[0] : null;
                $version = $plugin['Version'];
            }

            $return_repos[$dir]['deployKey'] = $deployKeys[$dir];
            $return_repos[$dir]['version'] = $version;
        }

        $this->packages = $return_repos;
        return $return_repos;
    }

    /**
     * @param $cache bool
     * @return array Array of $gitPackage
     */

    public function getPackagesArray(bool $cache = true): array
    {
        return array_values($this->getPackages($cache));
    }

    /**
     * @param $key string
     * @return array $gitPackage
     */

    public function getPackage(string $key, bool $cache = false, bool $flushCache = true): ?array
    {
        $packages = $this->getPackages($cache, $flushCache);
        if (!array_key_exists($key, $packages)) {
            return null;
        }
        return $packages[$key];
    }

    /**
     * @param $key string
     * @return bool
     */

    public function deletePackage(string $key): bool
    {
        $packages = $this->getPackages();
        if (!array_key_exists($key, $packages)) {
            return false;
        }
        unset($packages[$key]);
        $this->packages = $packages;
        return update_option($this->repo_option, $packages);
    }

    /**
     * @param $key string
     * @param $data array Partial<$gitPackage>
     * @param bool $new
     * @return array $gitPackage
     */

    public function updatePackage(string $key, array $data, bool $new = false): ?array
    {
        $packageData = $new ? [] : $this->getPackage($key);
        Helpers::addLog($key);
        Helpers::addLog($packageData);
        if ($packageData === null) return null;

        $packages = $this->getPackages();
        $newData = array_merge($packageData, $data);
        $packages[$key] = $newData;

        update_option($this->repo_option, $packages);

        $this->packages = null;
        wp_cache_flush();
        search_theme_directories(true); // flush theme cache

        return $newData;
    }

    /**
     * @return string
     */

    private static function generateDeployKey(): string
    {
        return wp_generate_password(40, false);
    }

    /**
     * @param $key string
     * @return void
     */

    public function updateDeployKey(string $key)
    {
        $deployKeysOption = get_option($this->deploy_option, []);
        $deployKeysOption[$key] = self::generateDeployKey();
        update_option($this->deploy_option, $deployKeysOption);
    }

    /**
     * @param $key
     * @param bool $setIfNone
     * @return string|null
     */

    public function getDeployKey($key, bool $setIfNone = false): ?string
    {
        $deployKeysOption = get_option($this->deploy_option, []);
        $deployKey = array_key_exists($key, $deployKeysOption) ? $deployKeysOption[$key] : null;
        if (!$deployKey && $setIfNone) {
            $deployKey = self::generateDeployKey();
            $deployKeysOption[$key] = $deployKey;
            update_option($this->deploy_option, $deployKeysOption);
        }

        return $deployKey;
    }
}

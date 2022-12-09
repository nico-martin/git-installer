<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;
use SayHello\GitInstaller\Package\Helpers\GitPackageManagement;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class Updater
{
    public array $pluginFiles = [];
    public GitPackageManagement $packages;
    public static $packageHeadersTransient = 'shgi-git-packages-headers';

    public function run()
    {
        /*
        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'updatePlugins']);
        add_filter('site_transient_update_themes', [$this, 'updateThemes']);

        add_filter('plugin_row_meta', [$this, 'rowIcon'], 15, 2);
        add_filter('theme_row_meta', [$this, 'rowIcon'], 15, 2);

        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
*/

        add_action('admin_init', [$this, 'registerPackages']);
        add_action('rest_api_init', [$this, 'registerRoute']);

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->packages = new GitPackageManagement();
    }

    public function registerPackages()
    {
        foreach ($this->getPackagePluginFiles() as $key => $file) {
            PucFactory::buildUpdateChecker(
                get_rest_url(null, sayhelloGitInstaller()->api_namespace . '/' . "packages-updater-check/{$key}/"),
                trailingslashit(WP_PLUGIN_DIR) . $file,
                'shgi-' . $key
            );
        }
    }

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'packages-updater-check/(?P<slug>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'getUpdaterCheck'],
            'args' => [
                'slug',
            ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function getUpdaterCheck($data): array
    {
        $key = $data['slug'];

        $headers = $this->getNewPackageHeaders($key);
        $package = $this->packages->getPackage($key);

        $package = [
            "name" => $headers['plugin'],
            "version" => $headers['version'],
            "download_url" => $this->getPackageZipUrl($key),
            "homepage" => $package['baseUrl'],
            "requires" => $headers['requires-at-least'],
            "tested" => $headers['tested-up-to'],
            "author" => $headers['author'],
            "author_homepage" => $headers['author-uri'],
            "sections" => [
                "description" => $headers['description']
            ],
        ];
        Helpers::addLog($package);
        return $package;
    }

    private function getPackagePluginFiles(): array
    {
        if (count($this->pluginFiles) !== 0) {
            return $this->pluginFiles;
        }

        $keys = array_keys($this->packages->getPackages(false));
        $plugins = array_keys(get_plugins());

        $return = [];
        foreach ($keys as $key) {
            $file = null;
            foreach ($plugins as $plugin) {
                if (str_starts_with($plugin, $key)) {
                    $file = $plugin;
                }
            }
            if ($file) {
                $return[$key] = $file;
            }
        }
        $this->pluginFiles = $return;
        return $return;
    }

    public function info($res, $action, $args)
    {

        if ('plugin_information' !== $action) {
            return $res;
        }

        if (!in_array($args->slug, array_keys($this->getPackagePluginFiles()))) {
            return $res;
        }

        $headers = $this->getNewPackageHeaders($args->slug);
        $package = $this->packages->getPackage($args->slug);

        $res = new \stdClass();

        $res->plugin_name = $headers['plugin'];
        $res->name = $headers['plugin'];
        $res->slug = $args->slug;
        $res->homepage = $package['baseUrl'];
        //$res->donate_link = '';
        $res->version = $headers['version'];
        $res->tested = $headers['tested-up-to'];
        $res->author = $headers['author'];
        $res->author_profile = $headers['author-uri'];
        //$res->trunk = $remote->download_url;
        $res->requires = $headers['requires-at-least'];
        $res->requires_php = $headers['requires-php'];
        $res->short_description = substr(strip_tags(trim($headers['description'])), 0, 175) . '...';
        $res->sections = [
            // todo: could be readme.md
            'description' => $headers['description'],
            //'installation' => '$remote->sections->installation',
            //'changelog' => '$remote->sections->changelog'
        ];
        //$res->downloaded = 0;
        //$res->active_installs = 0;
        //$res->last_updated = null;
        $res->download_link = $this->getPackageZipUrl($args->slug);
        $res->update_supported = true;
        //$res->banners = [];
        //$res->icons = [];
        /*
         * $res->contributors = [
         *   'test' => [
         *     'display_name' => 'display_name',
         *     'profile' => 'profile url',
         *     'avatar' => 'avatar url',
         *   ]
         * ]
         */
        //$res->rating = 0;
        //$res->num_ratings = 0;

        return $res;
    }

    private static function getTransientKey($key): string
    {
        return self::$packageHeadersTransient . $key;
    }

    private function getNewPackageHeaders($key)
    {
        $transientKey = self::getTransientKey($key);
        $transient = get_site_transient($transientKey);

        if ($transient) {
            return $transient;
        }

        $headers = sayhelloGitInstaller()->GitPackages->loadNewPackageHeaders($key);
        set_site_transient($transientKey, $headers, 60 * 60 * 6);

        return $headers;
    }

    public function updatePlugins($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugins = get_plugins();

        foreach ($this->getPackagePluginFiles() as $key => $file) {

            $newHeaders = $this->getNewPackageHeaders($key);
            $oldHeaders = $plugins[$file];

            if ($newHeaders
                && version_compare($oldHeaders['Version'], $newHeaders['version'], '<')
                && version_compare($newHeaders['requires-at-least'], get_bloginfo('version'), '<=')
                && version_compare($newHeaders['requires-php'], PHP_VERSION, '<')
            ) {
                $res = new \stdClass();
                $res->slug = $key;
                $res->plugin = $file;
                $res->requires = $newHeaders['requires-at-least'];
                $res->requires_php = $newHeaders['requires-php'];
                $res->new_version = $newHeaders['version'];
                $res->package = $this->getPackageZipUrl($key);

                $transient->response[$file] = $res;

            }
        }

        return $transient;
    }

    public function updateThemes($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        foreach (
            array_keys(
                array_filter(
                    $this->packages->getPackages(false),
                    function ($p) {
                        return $p['theme'];
                    }
                )
            ) as $key
        ) {
            $newHeaders = $this->getNewPackageHeaders($key);
            $theme = wp_get_theme($key);
            if ($newHeaders
                && version_compare($theme->Version, $newHeaders['version'], '<')
                && version_compare($newHeaders['requires-at-least'], get_bloginfo('version'), '<=')
                && version_compare($newHeaders['requires-php'], PHP_VERSION, '<')
            ) {
                $res = [
                    'slug' => $key,
                    'requires' => $newHeaders['requires-at-least'],
                    'requires_php' => $newHeaders['requires-php'],
                    'new_version' => $newHeaders['version'],
                    'package' => $this->getPackageZipUrl($key),
                ];

                $transient->response[$key] = $res;
            }
        }

        return $transient;
    }

    public function rowIcon($links, $file)
    {
        if (in_array($file, array_values($this->getPackagePluginFiles()))) {
            $plugin = get_plugins()[$file];
            $menuKey = sayhelloGitInstaller()->AdminPage->settings_parent;
            $links[] = '<a href="' . admin_url("admin.php?page={$menuKey}") . '" title="' . htmlspecialchars(sprintf(__('"%s" is managed via %s'), $plugin['Name'], sayhelloGitInstaller()->name)) . '" class="shgi-git-icon">' . sayhelloGitInstaller()->iconSvg . '</a>';
        }

        return $links;
    }

    public function getPackageZipUrl($key)
    {
        $package = $this->packages->getPackage($key);
        $provider = sayhelloGitInstaller()->GitPackages::getProvider($package['provider']);
        $data = base64_encode(json_encode([
            'key' => $key,
            'zipUrl' => $package['branches'][$package['activeBranch']]['zip'],
            'authHeader' => $provider->getAuthHeader(),
            'dir' => $package['dir'],
        ]));
        return trailingslashit(sayhelloGitInstaller()->url) . "public/fetchPackageZip.php?data={$data}";
    }

    public function purge($upgrader, $options)
    {
        if (
            'update' === $options['action']
            && ('plugin' === $options['type'] || 'theme' === $options['type'])
        ) {
            $this->deleteCache();
        }
    }

    public function deleteCache()
    {
        foreach (
            array_keys(
                $this->packages->getPackages(false)
            ) as $key
        ) {
            delete_transient(self::getTransientKey($key));
        }
    }
}

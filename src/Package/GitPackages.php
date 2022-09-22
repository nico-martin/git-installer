<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;

class GitPackages
{
    public $repo_option = 'sayhello-git-installer-git-repositories';
    public $deploy_option = 'sayhello-git-installer-git-deploy';

    public function run()
    {
        if (!Helpers::checkForFunction('shell_exec')) {
            return;
        }

        add_filter('shgi/AdminPage/Menu', [$this, 'menu']);
        add_filter('shgi/Settings/register', [$this, 'settings']);
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerJsVars']);

        add_action('shgi/GitPackages/DoAfterUpdate', [$this, 'maybeDoComposerInstall']);

        /**
         * Rest
         */
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function menu($menu)
    {
        $menu['git-packages'] = [
            'title' => __('Git Packages', 'shgi'),
        ];

        return $menu;
    }

    public function settings($settings)
    {
        $settings['git-packages-gitlab-token'] = [
            'default' => '',
            'label' => __('Acces Token', 'shgi'),
            'validate' => null,
        ];

        $settings['git-packages-github-token'] = [
            'default' => '',
            'label' => __('Personal Acces Token', 'shgi'),
            'validate' => null,
        ];

        $settings['git-packages-bitbucket-token'] = [
            'default' => '',
            'label' => __('App-Password', 'shgi'),
            'validate' => null,
        ];

        $settings['git-packages-bitbucket-user'] = [
            'default' => '',
            'label' => __('User', 'shgi'),
            'validate' => null,
        ];

        return $settings;
    }

    public function footerJsVars($vars)
    {
        $vars['gitPackages'] = $this->getPackages();

        return $vars;
    }

    public function maybeDoComposerInstall($dir)
    {
        if (!file_exists(trailingslashit($dir) . 'composer.json')) {
            return;
        }

        $package = str_replace(ABSPATH, '', $dir);
        $logDir = Helpers::getContentFolder() . 'logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }
        $logFile = $logDir . 'composer.log';
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        $cd = getcwd();
        chdir($package);
        $composer = shell_exec('export HOME=~ && ~/bin/composer install 2>&1');
        $log = '[' . date('D Y-m-d H:i:s') . '] [client ' . $_SERVER['REMOTE_ADDR'] . ']' . PHP_EOL .
            'Package: ' . $package . PHP_EOL .
            'Response: ' . $composer . PHP_EOL;

        file_put_contents($logFile, $log, FILE_APPEND);
        chdir($cd);
    }

    /**
     * Rest
     */

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages', [
            'methods' => 'GET',
            'callback' => [$this, 'getRepos'],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages', [
            'methods' => 'PUT',
            'callback' => [$this, 'addRepo'],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages/(?P<slug>\S+)/', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteRepo'],
            'args' => [
                'slug',
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages-deploy/(?P<slug>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'pushToDeploy'],
            'args' => [
                'slug',
            ],
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages-check/(?P<url>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'checkGitUrl'],
            'args' => [
                'url',
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);
    }

    public function getRepos()
    {
        return $this->getPackages();
    }

    public function addRepo($data)
    {
        $params = $data->get_params();
        $repo_url = $params['url'];
        $theme = $params['theme'];
        $activeBranch = $params['activeBranch'];

        $repoData = $this->updateInfos($repo_url, $activeBranch, $theme);
        if (is_wp_error($repoData)) return $repoData;

        $update = $this->updatePackage($repoData['key']);
        if (is_wp_error($update)) return $update;

        return [
            'message' => sprintf(
                __('"%s" was installed successfully', 'shgi'),
                $repoData['key']
            ),
            'packages' => $this->getPackages(),
        ];
    }

    public function pushToDeploy($data)
    {
        if (!array_key_exists('key', $_GET)) {
            return new \WP_Error('wrong_request', __('Invalid request: no key found', 'shgi'), [
                'status' => 403,
            ]);
        }

        $key = $data['slug'];
        $deployKeys = get_option($this->deploy_option, []);
        if (!array_key_exists($key, $deployKeys) || $_GET['key'] != $deployKeys[$key]) {
            return new \WP_Error('wrong_request', __('Invalid request: invalid key', 'shgi'), [
                'status' => 403,
            ]);
        }

        $update = $this->updatePackage($key);
        if (is_wp_error($update)) {
            return new \WP_Error($update->get_error_code(), $update->get_error_message(), [
                'status' => 409,
            ]);
        }

        return $this->getPackages(false)[$key];
    }

    public function deleteRepo($data)
    {
        $repos = $this->getPackages(false);
        $key = $data['slug'];
        if (!array_key_exists($key, $repos)) {
            return new \WP_Error(
                'shgi_repo_not_found',
                sprintf(
                    __('Package %s could not be updated: The package does not exist', 'shgi'),
                    '<code>' . $key . '</code>'
                )
            );
        }
        $this->rrmdir($this->getPackageDir($key));

        unset($repos[$key]);
        update_option($this->repo_option, $repos);

        return [
            'message' => sprintf(__('"%s" was deleted successfully', 'shgi'), $key),
            'packages' => $this->getPackages(),
        ];
    }

    public function checkGitUrl($data)
    {
        $url = base64_decode($data['url']);
        $provider = self::getProvider('', $url);
        if (!$provider) return new \WP_Error(
            'repository_not_found',
            sprintf(
                __('Package %s could not be found', 'shgi'),
                '<code>' . $url . '</code>'
            ), [
            'status' => 404,
        ]);

        return $provider->getInfos($url);
    }

    /**
     * Helpers
     */

    public function updateInfos($url, $activeBranch, $theme = false)
    {
        $provider = self::getProvider('', $url);
        if (!$provider) {
            return new \WP_Error(
                'invalid_git_host',
                sprintf(__('"%s" is not a supported Git hoster', 'shgi'), $url)
            );
        }

        $repoData = $provider->getInfos($url);

        if (is_wp_error($repoData)) return $repoData;

        if (!array_key_exists($activeBranch, $repoData['branches'])) {
            $activeBranch = array_values(
                array_filter(
                    $repoData['branches'],
                    function ($branch) {
                        return $branch['default'];
                    }
                )
            )[0]['name'];
        }

        $repositories = get_option($this->repo_option, []);
        $deployKeysOption = get_option($this->deploy_option, []);
        if (!array_key_exists($repoData['key'], $deployKeysOption)) {
            $deployKeysOption[$repoData['key']] = wp_generate_password(40, false);
            update_option($this->deploy_option, $deployKeysOption);
        }
        $repositories[$repoData['key']] = $repoData;
        $repositories[$repoData['key']]['theme'] = $theme;
        $repositories[$repoData['key']]['activeBranch'] = $activeBranch;

        update_option($this->repo_option, $repositories);

        return $repoData;
    }

    private function getPackages($array = true)
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugins = get_plugins();
        $themes = wp_get_themes();
        $return_repos = [];
        $repos = get_option($this->repo_option, []);
        $deployKeys = get_option($this->deploy_option, []);

        foreach ($repos as $dir => $repo) {
            $version = null;

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
                $version = $plugin ? $plugin['Version'] : null;
            }

            $return_repos[$dir] = $repo;
            $return_repos[$dir]['deployKey'] = $deployKeys[$dir];
            $return_repos[$dir]['version'] = $version;
        }

        return $array ? array_values($return_repos) : $return_repos;
    }

    private function updatePackage($key)
    {
        $packages = $this->getPackages(false);

        if (!array_key_exists($key, $packages)) {
            return new \WP_Error(
                'shgi_repo_not_found',
                sprintf(
                    __('Package %s could not be updated: The package does not exist', 'shgi'),
                    '<code>' . $key . '</code>'
                ),
            );
        }

        $package = $packages[$key];
        $tempDir = Helpers::getContentFolder() . 'temp/';
        if (!is_dir($tempDir)) mkdir($tempDir);

        $zipUrl = $package['branches'][$package['activeBranch']]['zip'];
        $provider = self::getProvider($package['provider']);
        $request = $provider->authenticateRequest($zipUrl);

        $request = wp_remote_get($request[0], $request[1]);

        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) >= 300) {
            return new \WP_Error(
                'shgi_repo_not_fetched',
                sprintf(
                    __('Archive %s could not be copied', 'shgi'),
                    '<code>' . $zipUrl . '</code>'
                )
            );
        }

        file_put_contents($tempDir . $key . '.zip', wp_remote_retrieve_body($request));
        $zip = new \ZipArchive;
        $res = $zip->open($tempDir . $key . '.zip');
        if ($res !== true) {
            return new \WP_Error(
                'shgi_repo_unzip_failed',
                sprintf(
                    __('%s could not be unpacked', 'shgi'),
                    '<code>' . $zipUrl . '</code>'
                )
            );
        }
        $zip->extractTo($tempDir . $key . '/');
        $zip->close();
        unlink($tempDir . $key . '.zip');

        $subDirs = glob($tempDir . $key . '/*', GLOB_ONLYDIR);
        $packageDir = $subDirs[0];
        $oldDir = $this->getPackageDir($key);

        if (is_dir($oldDir)) {
            $this->rrmdir($oldDir);
        }

        $renamed = rename(trailingslashit($packageDir), $oldDir);
        $this->rrmdir($tempDir);

        if (!$renamed) {
            return new \WP_Error(
                'rename_repo_failed',
                __(
                    'The folder could not be copied. Possibly the old folder could not be emptied completely.',
                    'shgi'
                )
            );
        }

        do_action('shgi/GitPackages/DoAfterUpdate', $oldDir);

        return true;
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    private function getPackageDir($key)
    {
        $packages = $this->getPackages(false);
        if (!array_key_exists($key, $packages)) {
            return false;
        }
        $package = $packages[$key];

        if ($package['theme']) {
            return trailingslashit(get_theme_root()) . $key;
        }

        return trailingslashit(WP_PLUGIN_DIR) . $key;
    }

    private static function getProvider($provider = '', $url = '')
    {
        if ($provider === Provider\Github::$provider || Provider\Github::validateUrl($url)) {
            return Provider\Github::export();
        } elseif ($provider === Provider\Gitlab::$provider || Provider\Gitlab::validateUrl($url)) {
            return Provider\Gitlab::export();
        } elseif ($provider === Provider\Bitbucket::$provider || Provider\Bitbucket::validateUrl($url)) {
            return Provider\Bitbucket::export();
        }
        return null;
    }
}

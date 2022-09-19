<?php

namespace SayHello\GitUpdater\Package;

use SayHello\GitUpdater\Helpers;

class GitPackages
{
    /**
     * ToDo: add bitbucket workflow
     * todo: Add branch setting for Repo
     */
    public $repo_option = 'sayhello-git-updater-git-repositories';
    public $deploy_option = 'sayhello-git-updater-git-deploy';

    public function run()
    {
        if (!Helpers::checkForFunction('shell_exec')) {
            return;
        }

        add_filter('shgu/AdminPage/Menu', [$this, 'menu']);
        add_filter('shgu/Settings/register', [$this, 'settings']);
        add_filter('shgu/Assets/AdminFooterJS', [$this, 'footerJsVars']);

        add_action('shgu/GitPackages/DoAfterUpdate', [$this, 'maybeDoComposerInstall']);

        /**
         * Rest
         */
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function menu($menu)
    {
        $menu['git-packages'] = [
            'title' => __('Git Packages', 'shgu'),
        ];

        return $menu;
    }

    public function settings($settings)
    {
        $settings['git-packages-gitlab-token'] = [
            'default' => '',
            'label' => __('GitLab Acces Token', 'shgu'),
            'validate' => null,
        ];

        $settings['git-packages-github-token'] = [
            'default' => '',
            'label' => __('GitHub Personal Acces Token', 'shgu'),
            'validate' => null,
        ];

        return $settings;
    }

    public function footerJsVars($vars)
    {
        $vars['gitPackages'] = $this->getPackages();

        return $vars;
    }

    public function updateInfos($url, $theme = false)
    {
        $repo = null;
        if (Provider\Github::validateUrl($url)) {
            $repo = Provider\Github::getInfos($url, $theme);
        } elseif (Provider\Gitlab::validateUrl($url)) {
            $repo = Provider\Gitlab::getInfos($url, $theme);
        }

        if (is_wp_error($repo)) {
            return new \WP_Error(
                $repo->get_error_code(),
                $repo->get_error_message()
            );
        }

        if (!$repo) {
            return new \WP_Error(
                'invalid_git_host',
                sprintf(__('"%s" ist kein unterstützter Git Hoster', 'shgu'), $url)
            );
        }

        $repos = get_option($this->repo_option, []);
        $deployKeys = get_option($this->deploy_option, []);
        if (!array_key_exists($repo['key'], $deployKeys)) {
            $deployKeys[$repo['key']] = wp_generate_password(40, false);
            update_option($this->deploy_option, $deployKeys);
        }
        $repos[$repo['key']] = $repo;

        update_option($this->repo_option, $repos);

        return $repo;
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
        register_rest_route(sayhelloGitUpdater()->api_namespace, 'git-packages', [
            'methods' => 'GET',
            'callback' => [$this, 'getRepos'],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitUpdater()->api_namespace, 'git-packages', [
            'methods' => 'PUT',
            'callback' => [$this, 'addRepo'],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitUpdater()->api_namespace, 'git-packages/(?P<slug>\S+)/', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteRepo'],
            'args' => [
                'slug',
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitUpdater()->api_namespace, 'git-packages-deploy/(?P<slug>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'pushToDeploy'],
            'args' => [
                'slug',
            ],
        ]);
    }

    public function getRepos()
    {
        return $this->getPackages();
    }

    public function addRepo($data)
    {
        $params = $data->get_params();
        $error = false;
        $repo_url = $params['url'];
        $theme = $params['theme'];

        $repo = $this->updateInfos($repo_url, $theme);

        if (is_wp_error($repo)) {
            $error = $repo->get_error_message();
        }

        if (!$error) {
            $update = $this->updatePackage($repo['key']);
            if (is_wp_error($update)) {
                $error = $update->get_error_message();
            }
        }

        if ($error) {
            return new \WP_Error(
                'repo_install_failed',
                $error,
                [
                    'status' => 409,
                ]
            );
        }

        return [
            'message' => sprintf(
                __('"%s" wurde erfolgreich installiert', 'shgu'),
                $repo['key']
            ),
            'packages' => $this->getPackages(),
        ];
    }

    public function pushToDeploy($data)
    {
        if (!array_key_exists('key', $_GET)) {
            return new \WP_Error('wrong_request', __('Ungültige Anfrage: kein Key gefunden', 'shgu'), [
                'status' => 403,
            ]);
        }

        $key = $data['slug'];
        $deployKeys = get_option($this->deploy_option, []);
        if (!array_key_exists($key, $deployKeys) || $_GET['key'] != $deployKeys[$key]) {
            return new \WP_Error('wrong_request', __('Ungültige Anfrage: ungültiger Key', 'shgu'), [
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
        $repos = get_option($this->repo_option, []);
        $key = $data['slug'];
        if (!array_key_exists($key, $repos)) {
            return new \WP_Error(
                'shgu_repo_not_found',
                sprintf(
                    __('Paket %s konnte nicht geupdated werden: Das Packet existiert nicht', 'shgu'),
                    '<code>' . $key . '</code>'
                )
            );
        }

        unset($repos[$key]);
        update_option($this->repo_option, $repos);

        return [
            'message' => sprintf(__('"%s" wurde erfolgreich gelöscht', 'shgu'), $key),
            'packages' => $this->getPackages(),
        ];
    }

    /**
     * Helpers
     */

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
        $packages = get_option($this->repo_option, []);

        if (!array_key_exists($key, $packages)) {
            return new \WP_Error(
                'shgu_repo_not_found',
                sprintf(
                    __('Packet %s konnte nicht geupdated werden: Das Packet existiert nicht', 'shgu'),
                    '<code>' . $key . '</code>'
                )
            );
        }

        $package = $packages[$key];
        $tempDir = Helpers::getContentFolder() . 'temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir);
        }

        $zipUrl = $package['url']['zip'];
        $request = [$zipUrl, []];
        if (Provider\Github::validateUrl($package['baseUrl'])) {
            $request = Provider\Github::authenticateRequest($zipUrl);
        } elseif (Provider\Gitlab::validateUrl($package['baseUrl'])) {
            $request = Provider\Gitlab::authenticateRequest($zipUrl);
        }

        //Helpers::print($request);

        $request = wp_remote_get($request[0], $request[1]);

        if (is_wp_error($request)) {
            return new \WP_Error(
                'shgu_repo_not_fetched',
                sprintf(
                    __('Archiv %s konnte nicht kopiert werden', 'shgu'),
                    '<code>' . $zipUrl . '</code>'
                )
            );
        }

        file_put_contents($tempDir . $key . '.zip', wp_remote_retrieve_body($request));
        $zip = new \ZipArchive;
        $res = $zip->open($tempDir . $key . '.zip');
        if ($res !== true) {
            return new \WP_Error(
                'shgu_repo_unzip_failed',
                sprintf(
                    __('%s konnte nicht entpackt werden', 'shgu'),
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
                    'Der Ordner konnte nicht kopiert werden. Möglicherweise konnte der alte Ordner nicht ganz geleert werden.',
                    'shgu'
                )
            );
        }

        do_action('shgu/GitPackages/DoAfterUpdate', $oldDir);

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
        $packages = get_option($this->repo_option, []);
        if (!array_key_exists($key, $packages)) {
            return false;
        }
        $package = $packages[$key];

        if ($package['theme']) {
            return trailingslashit(get_theme_root()) . $key;
        }

        return trailingslashit(WP_PLUGIN_DIR) . $key;
    }
}

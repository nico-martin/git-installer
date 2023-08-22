<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;
use SayHello\GitInstaller\FsHelpers;
use SayHello\GitInstaller\Package\Helpers\GitPackageManagement;

class GitPackages
{

    public GitPackageManagement $packages;

    public function run()
    {
        $this->packages = new GitPackageManagement();
        add_filter('shgi/AdminPage/Menu', [$this, 'menu']);
        add_filter('shgi/Settings/register', [$this, 'settings']);
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerJsVars']);

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
        $vars['gitPackages'] = $this->packages->getPackagesArray();
        $vars['mustUsePlugins'] = Helpers::useMustUsePlugins();

        return $vars;
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
                'slug' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages-update/(?P<slug>\S+)/', [
            'methods' => ['GET', 'POST'],
            'callback' => [$this, 'updateRepo'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages-check/(?P<url>\S+)/', [
            'methods' => 'GET',
            'callback' => [$this, 'checkGitUrl'],
            'args' => [
                'url' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);

        register_rest_route(sayhelloGitInstaller()->api_namespace, 'git-packages-dir/', [
            'methods' => 'POST',
            'callback' => [$this, 'checkGitDir'],
            'args' => [
                'url' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
                'branch' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
                'dir' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_string($param);
                    },
                ],
            ],
            'permission_callback' => function () {
                return current_user_can(Helpers::$authAdmin);
            }
        ]);
    }

    public function getRepos(): array
    {
        return $this->packages->getPackagesArray();
    }

    public function addRepo($data)
    {
        $params = $data->get_params();
        $repo_url = $params['url'];
        $theme = $params['theme'];
        $activeBranch = $params['activeBranch'];
        $headersFile = $params['headersFile'];
        $saveAsMustUsePlugin = $params['saveAsMustUsePlugin'];
        $afterUpdateHooks = $params['afterUpdateHooks'];
        $dir = Helpers::sanitizeDir($params['dir']);

        $repoData = $this->updateInfos($repo_url, $activeBranch, $theme, $saveAsMustUsePlugin, $dir, $headersFile, true, $afterUpdateHooks);
        if (is_wp_error($repoData)) return $repoData;

        $update = $this->updatePackage($repoData['key'], 'install');
        if (is_wp_error($update)) return $update;

        return [
            'message' => sprintf(
                __('"%s" was installed successfully', 'shgi'),
                $repoData['key']
            ),
            'packages' => $this->packages->getPackagesArray(false),
            'dir' => $dir,
        ];
    }

    public function updateRepo($data)
    {
        if (!array_key_exists('key', $_GET)) return new \WP_Error('wrong_request', __('Invalid request: no key found', 'shgi'), [
            'status' => 403,
        ]);

        $key = $data['slug'];
        $deployKey = $this->packages->getDeployKey($key);
        if (!$deployKey || $_GET['key'] != $deployKey) return new \WP_Error('wrong_request', __('Invalid request: invalid key', 'shgi'), [
            'status' => 403,
        ]);

        $ref = array_key_exists('ref', $_GET) ? $_GET['ref'] : '';

        $update = $this->updatePackage($key, $ref);
        if (is_wp_error($update)) return new \WP_Error($update->get_error_code(), $update->get_error_message(), [
            'status' => 409,
        ]);

        $package = $this->packages->getPackage($key);

        do_action('shgi/GitPackages/DoAfterUpdate', $package);

        return $package;
    }

    public function deleteRepo($data)
    {
        $fullDelete = array_key_exists('fullDelete', $_GET) && $_GET['fullDelete'] === '1';
        $key = $data['slug'];
        $deleted = $this->packages->deletePackage($key);
        if (!$deleted) {
            return new \WP_Error(
                'shgi_repo_not_found',
                sprintf(
                    __('Package %s could not be updated: The package does not exist', 'shgi'),
                    '<code>' . $key . '</code>'
                ),
            );
        }

        $fullDelete && FsHelpers::removeDir($this->getPackageDir($key));
        UpdateLog::deleteLogs($key);

        return [
            'message' => sprintf(__('"%s" was deleted successfully', 'shgi'), $key),
            'packages' => $this->packages->getPackagesArray(false),
        ];
    }

    public function checkGitUrl($data)
    {
        $url = base64_decode($data['url']);
        $provider = self::getProvider('', $url);
        if (!$provider) return new \WP_Error(
            'repository_not_found',
            sprintf(
                __("Package %s could not be found. Please make sure it's a valid URL to a GitHub, Gitlab or Bitbucket repository", 'shgi'),
                '<code>' . $url . '</code>'
            ), [
            'status' => 404,
        ]);

        $infos = $provider->getInfos($url);
        if (is_wp_error($infos)) return new \WP_Error(
            'repo_validation_failed',
            sprintf(
                $provider->hasToken() ?
                    __('Either it is not a valid %s repository URL, or it is private and the deposited token does not have the required permissions.', 'shgi') :
                    __('Either it is not a valid %s repository URL, or it is Private. In this case, you would have to add a corresponding token under "Access control".', 'shgi'),
                $provider->name()
            ),
            [
                'status' => 404,
                'error' => $infos->get_error_message()
            ]
        );

        return $infos;
    }

    private function getPackageHeaders($url, $branch, $dir)
    {
        $provider = self::getProvider('', $url);

        $files = array_map(
            function ($element) {
                $element['parsed'] = self::parseHeader($element['content']);
                return $element;
            },
            $provider->validateDir($url, $branch, $dir)
        );

        $theme = array_values(array_filter($files, function ($file) {
            return $file['file'] === 'style.css' && boolval($file['parsed']['theme']);
        }));

        $plugin = array_values(array_filter($files, function ($file) {
            return boolval($file['parsed']['plugin']);
        }));

        if (count($theme) !== 0) {
            return [
                'type' => 'theme',
                'name' => $theme[0]['parsed']['theme'],
                'theme' => $theme,
                'headers' => $theme[0]['parsed'],
                'headersFile' => $theme[0]['fileUrl'],
            ];
        }

        if (count($plugin) !== 0) {
            return [
                'type' => 'plugin',
                'name' => $plugin[0]['parsed']['plugin'],
                'headers' => $plugin[0]['parsed'],
                'headersFile' => $plugin[0]['fileUrl'],
            ];
        }

        return null;
    }

    public function getPackageHeadersFile($packageKey): ?string
    {
        $package = $this->packages->getPackage($packageKey);
        if (array_key_exists('headersFile', $package) && boolval($package['headersFile'])) {
            return $package['headersFile'];
        }

        $headers = $this->getPackageHeaders($package['baseUrl'], $package['activeBranch'], $package['dir']);
        if (!$headers) {
            return null;
        }

        if ($headers['headersFile']) $this->packages->updatePackage(
            $packageKey,
            [
                'headersFile' => $headers['headersFile']
            ]
        );

        return $headers['headersFile'];
    }

    public function loadNewPackageHeaders($key): array
    {
        $headersFile = $this->getPackageHeadersFile($key);
        if (!$headersFile) return [];

        $package = $this->packages->getPackage($key);
        $provider = self::getProvider($package['provider']);
        $file = $provider->fetchFileContent($headersFile);
        if ($file === null) return [];

        return self::parseHeader($file);
    }

    public function checkGitDir($data)
    {
        $params = $data->get_params();
        $url = $params['url'];
        $dir = Helpers::sanitizeDir($params['dir']);
        $branch = $params['branch'];

        $headers = $this->getPackageHeaders($url, $branch, $dir);
        if (is_wp_error($headers)) return $headers;

        if (!$headers) {
            return new \WP_Error(
                'package_not_found',
                sprintf(__('No valid WordPress Theme (style.css with "Theme Name" header) or WordPress Plugin (Plugin PHP file with "Plugin Name" header) was found in the %s folder of the repository.', 'shgi'), ($dir) ? $dir : 'root'),
                [
                    'status' => 400,
                ]
            );
        }

        return $headers;
    }

    /**
     * Helpers
     */

    public function updateInfos($url, $activeBranch, $theme = false, $saveAsMustUsePlugin = false, $dir = '', $headersFile = '', $new = false, $afterUpdateHooks = [])
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

        $this->packages->getDeployKey($repoData['key'], true);

        $repoData['theme'] = $theme;
        $repoData['saveAsMustUsePlugin'] = $saveAsMustUsePlugin;
        $repoData['activeBranch'] = $activeBranch;
        $repoData['dir'] = $dir;
        $repoData['headersFile'] = $headersFile;
        $repoData['afterUpdateHooks'] = $afterUpdateHooks;

        return $this->packages->updatePackage($repoData['key'], $repoData, $new);
    }

    public function loadNewPackageFiles($key, $target)
    {
        $package = $this->packages->getPackage($key);
        if (!$package) return new \WP_Error(
            'shgi_repo_not_found',
            sprintf(
                __('Package %s could not be updated: The package does not exist', 'shgi'),
                '"' . $key . '"'
            ),
        );

        $tempDir = Helpers::getTempDir();

        $zipUrl = $package['branches'][$package['activeBranch']]['zip'];
        $provider = self::getProvider($package['provider']);
        $request = $provider->authenticateRequest($zipUrl);

        $request = wp_remote_get($request[0], $request[1]);

        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) >= 300) return new \WP_Error(
            'shgi_repo_not_fetched',
            sprintf(
                __('Archive %s could not be copied', 'shgi'),
                '<code>' . $zipUrl . '</code>'
            )
        );

        file_put_contents($tempDir . $key . '.zip', wp_remote_retrieve_body($request));

        $unzip = FsHelpers::unzip($tempDir . $key . '.zip', $tempDir . $key . '/');
        if (is_wp_error($unzip)) return $unzip;

        $subDirs = glob($tempDir . $key . '/*', GLOB_ONLYDIR);
        $packageDir = $subDirs[0];
        $renamed = FsHelpers::moveDir(
            trailingslashit($packageDir) . ($package['dir'] ? trailingslashit($package['dir']) : ''),
            $target
        );
        FsHelpers::removeDir($tempDir);

        return $renamed;
    }

    private function updatePackage($key, $ref = '')
    {
        $alreadyInMaintMode = FsHelpers::isInMaintenanceMode();
        if (!$alreadyInMaintMode) {
            FsHelpers::maintenanceMode(true);
        }
        $target = $this->getPackageDir($key);
        if (is_dir($target)) {
            FsHelpers::removeDir($target);
        }

        $package = $this->packages->getPackage($key, false, false);
        $moved = $this->loadNewPackageFiles($key, $target);

        if (is_wp_error($moved)) {
            do_action('shgi/GitPackages/updatePackage/error', $key, $ref, $moved);
            return $moved;
        }
        if (!$moved) {
            $error = new \WP_Error(
                'rename_repo_failed',
                __(
                    'The folder could not be copied. Possibly the old folder could not be emptied completely.',
                    'shgi',
                ),
            );
            do_action('shgi/GitPackages/updatePackage/error', $key, $ref, $error);
            return $error;
        }

        $newPackages = $this->packages->getPackages(false);
        do_action('shgi/GitPackages/updatePackage/success', $key, $ref, $package['version'], $newPackages[$key]['version']);
        if (!$alreadyInMaintMode) {
            FsHelpers::maintenanceMode(false);
        }

        return true;
    }

    public function getPackageDir($key)
    {
        $packages = $this->packages->getPackages();
        if (!array_key_exists($key, $packages)) {
            return false;
        }
        $package = $packages[$key];

        if ($package['theme']) {
            return trailingslashit(get_theme_root()) . $key;
        }

        if ($package['saveAsMustUsePlugin']) {
            if (!is_dir(trailingslashit(WPMU_PLUGIN_DIR))) mkdir(trailingslashit(WPMU_PLUGIN_DIR));
            return trailingslashit(WPMU_PLUGIN_DIR) . $key;
        }

        return trailingslashit(WP_PLUGIN_DIR) . $key;
    }

    public static function getProvider($provider = '', $url = '')
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

    private static function parseHeader($fileData)
    {
        $fileData = str_replace("\r", "\n", $fileData);
        $allHeaders = [];

        foreach (
            [
                'theme' => 'Theme Name',
                'theme-uri' => 'Theme URI',
                'plugin' => 'Plugin Name',
                'plugin-uri' => 'Plugin URI',
                'description' => 'Description',
                'tags' => 'Tags',
                'version' => 'Version',
                'requires-at-least' => 'Requires at least',
                'tested-up-to' => 'Tested up to',
                'requires-php' => 'Requires PHP',
                'author' => 'Author',
                'author-uri' => 'Author URI',
                'license' => 'License',
                'license-uri' => 'License URI',
                'text-domain' => 'Text Domain',
                'domain-path' => 'Domain Path',
                'network' => 'Network',
                'update-uri' => 'Update URI',
            ] as $field => $regex
        ) {
            if (preg_match('/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $fileData, $match) && $match[1]) {
                $allHeaders[$field] = _cleanup_header_comment($match[1]);
            } else {
                $allHeaders[$field] = '';
            }
        }

        return $allHeaders;
    }
}

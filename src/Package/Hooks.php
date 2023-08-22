<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;
use SayHello\GitInstaller\FsHelpers;
use SayHello\GitInstaller\Package\Helpers\GitPackageManagement;

/**
 * TODO:
 * - finish hooks
 * - delete full does not delete the files
 * - test!
 */
class Hooks
{
    public GitPackageManagement $packages;

    public function run()
    {
        $this->packages = new GitPackageManagement();

        add_filter('shgi/Hooks/AfterUpdateHooks', [$this, 'composerInstallHook']);
        add_filter('shgi/Hooks/AfterUpdateHooks', [$this, 'npmInstallHook']);
        add_action('shgi/GitPackages/DoAfterUpdate', [$this, 'runAfterUpdateHooks'], 10, 1);
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerJsVars']);
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function composerInstallHook($hooks)
    {
        $hooks['composer'] = [
            'title' => 'Composer Install',
            'description' => __('This Hook will execute "composer install" if a composer.json is found after the new files are added.', 'shgi'),
            'function' => function ($package) {
                /*
                    $dir = $package['dir'];
                    if (!file_exists(trailingslashit($dir) . 'composer.json')) {
                        Helpers::addLog('composer.json does not exist', "composer-{$package['key']}");
                        return;
                    }

                    if (!Helpers::checkForFunction('shell_exec', false)) {
                        Helpers::addLog('shell_exec does not exist', "composer-{$package['key']}");
                        return;
                    }

                    $packageDir = str_replace(ABSPATH, '', $dir);

                    $cd = getcwd();
                    chdir($packageDir);
                    $composer = shell_exec('export HOME=~ && ~/bin/composer install 2>&1');
                    $log =  '[' . date('D Y-m-d H:i:s') . '] [client ' . $_SERVER['REMOTE_ADDR'] . ']' . PHP_EOL .
                        'Package: ' . $packageDir . PHP_EOL .
                        'Response: ' . $composer . PHP_EOL;

                    Helpers::addLog($log, "composer-{$package['key']}");

                    chdir($cd);*/
            }
        ];

        return $hooks;
    }

    public function npmInstallHook($hooks)
    {
        $hooks['npm'] = [
            'title' => 'NPM Install',
            'function' => function ($package) {
                Helpers::addLog([
                    'hook' => 'npm',
                    'package' => $package,
                ]);
            }
        ];

        return $hooks;
    }

    public function runAfterUpdateHooks($package)
    {
        $hooks = self::getAfterUpdateHooks();

        foreach ($package['afterUpdateHooks'] as $key) {
            if (array_key_exists($key, $hooks)) {
                $hooks[$key]['function']($package);
            }
        }
    }

    public function footerJsVars($vars)
    {
        $vars['afterUpdateHooks'] = [];
        foreach (self::getAfterUpdateHooks() as $key => $hook) {
            $vars['afterUpdateHooks'][$key] = [
                'title' => $hook['title'],
                'description' => $hook['description'],
            ];
        }

        return $vars;
    }

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'hooks/after-update-hook/(?P<slug>\S+)/', [
            'methods' => ['POST'],
            'callback' => [$this, 'updateAfterUpdateHook'],
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
    }

    public function updateAfterUpdateHook($data)
    {
        $slug = $data['slug'];
        $package = $this->packages->getPackage($slug);
        $currentHooks = array_key_exists('afterUpdateHooks', $package) ? $package['afterUpdateHooks'] : [];
        $changed = $data['changedHooks'];
        foreach ($changed as $key => $checked) {
            if ($checked && !in_array($key, $currentHooks)) {
                $currentHooks[] = $key;
            } elseif (!$checked && in_array($key, $currentHooks)) {
                $currentHooks = array_diff($currentHooks, [$key]);
            }
        }

        return $this->packages->updatePackage($slug, [
            'afterUpdateHooks' => $currentHooks
        ]);
    }

    /**
     * Helpers
     */

    public static function getAfterUpdateHooks()
    {
        $hooks = apply_filters('shgi/Hooks/AfterUpdateHooks', []);

        $formatted = [];

        foreach ($hooks as $key => $hook) {
            $formatted[$key] = [
                'title' => array_key_exists('title', $hook)
                    ? $hook['title']
                    : 'Unnamed Hook',
                'description' => array_key_exists('description', $hook)
                    ? $hook['description']
                    : '',
                'function' => array_key_exists('function', $hook)
                    ? $hook['function']
                    : function ($package) {
                    },
            ];
        }

        return $formatted;
    }
}
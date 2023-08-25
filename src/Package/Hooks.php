<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;
use SayHello\GitInstaller\Package\Helpers\GitPackageManagement;

class Hooks
{
    public GitPackageManagement $packages;

    public function run()
    {
        $this->packages = new GitPackageManagement();

        add_filter('shgi/Hooks/PostupdateHooks', [$this, 'composerInstallHook']);
        add_filter('shgi/Hooks/PostupdateHooks', [$this, 'npmInstallHook']);
        add_action('shgi/GitPackages/DoAfterUpdate', [$this, 'runPostupdateHooks'], 10, 1);
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerJsVars']);
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function composerInstallHook($hooks)
    {
        $hooks['composer'] = [
            'title' => 'Composer Install',
            'description' => __('This Hook will execute "' . self::getComposerCommand() . ' install" if a composer.json is found after the new files are added.', 'shgi'),
            'function' => function ($package) {
                $dir = sayhelloGitInstaller()->GitPackages->getPackageDir($package['key']);

                if (!file_exists(trailingslashit($dir) . 'composer.json')) {
                    Helpers::addLog($package['key'] . PHP_EOL . 'composer.json does not exist' . PHP_EOL . trailingslashit($dir) . 'composer.json', "postupdateHooks");
                    return;
                }

                $packageDir = str_replace(ABSPATH, '', $dir);
                $command = self::getComposerCommand() . ' install';

                $output = shell_exec("cd $packageDir && $command 2>&1");
                $log = '[client ' . $_SERVER['REMOTE_ADDR'] . ']' . PHP_EOL .
                    $package['key'] . PHP_EOL .
                    'Package: ' . $packageDir . PHP_EOL .
                    'Response: ' . $output . PHP_EOL;

                Helpers::addLog($log, "postupdateHooks");
            },
            'check' => function () {
                return Helpers::checkForFunction('shell_exec', false) && strpos(shell_exec(self::getComposerCommand() . ' --version 2>&1'), 'Composer version') !== false;
            }
        ];

        return $hooks;
    }

    public function npmInstallHook($hooks)
    {
        $hooks['npm'] = [
            'title' => 'NPM Install',
            'description' => __('This Hook will execute "npm install" and "npm run build" if a package.json is found after the new files are added.', 'shgi'),
            'function' => function ($package) {
                $dir = sayhelloGitInstaller()->GitPackages->getPackageDir($package['key']);

                if (!file_exists(trailingslashit($dir) . 'package.json')) {
                    Helpers::addLog($package['key'] . PHP_EOL . 'package.json does not exist' . PHP_EOL . trailingslashit($dir) . 'package.json', "postupdateHooks");
                    return;
                }

                $packageDir = str_replace(ABSPATH, '', $dir);
                $command = 'npm install && npm run build';

                $output = shell_exec("cd $packageDir && $command 2>&1");
                $log = '[client ' . $_SERVER['REMOTE_ADDR'] . ']' . PHP_EOL .
                    $package['key'] . PHP_EOL .
                    'Package: ' . $packageDir . PHP_EOL .
                    'Response: ' . $output . PHP_EOL;

                Helpers::addLog($log, "postupdateHooks");
            },
            'check' => function () {
                return Helpers::checkForFunction('shell_exec', false) && shell_exec('npm --version') !== null;
            }
        ];

        return $hooks;
    }

    public function runPostupdateHooks($package)
    {
        $hooks = self::getPostupdateHooks();

        foreach ($package['postupdateHooks'] as $key) {
            if (array_key_exists($key, $hooks)) {
                $hooks[$key]['function']($package);
            }
        }
    }

    public function footerJsVars($vars)
    {
        $vars['postupdateHooks'] = [];
        foreach (self::getPostupdateHooks() as $key => $hook) {
            $vars['postupdateHooks'][$key] = [
                'title' => $hook['title'],
                'description' => $hook['description'],
            ];
        }

        return $vars;
    }

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'hooks/post-update-hook/(?P<slug>\S+)/', [
            'methods' => ['POST'],
            'callback' => [$this, 'updatePostupdateHook'],
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

    public function updatePostupdateHook($data)
    {
        $slug = $data['slug'];
        $package = $this->packages->getPackage($slug);
        $currentHooks = array_key_exists('postupdateHooks', $package) ? $package['postupdateHooks'] : [];
        $changed = $data['changedHooks'];
        foreach ($changed as $key => $checked) {
            if ($checked && !in_array($key, $currentHooks)) {
                $currentHooks[] = $key;
            } elseif (!$checked && in_array($key, $currentHooks)) {
                $currentHooks = array_diff($currentHooks, [$key]);
            }
        }

        return $this->packages->updatePackage($slug, [
            'postupdateHooks' => $currentHooks
        ]);
    }

    /**
     * Helpers
     */

    public static function getPostupdateHooks()
    {
        $hooks = apply_filters('shgi/Hooks/PostupdateHooks', []);

        $formatted = [];

        foreach ($hooks as $key => $hook) {
            $check = array_key_exists('check', $hook) ? $hook['check']() : true;

            if ($check) {
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
        }

        return $formatted;
    }

    public static function getComposerCommand()
    {
        return defined('SHGI_COMPOSER_COMMAND') ? SHGI_COMPOSER_COMMAND : 'composer';
    }
}
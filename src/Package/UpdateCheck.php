<?php

namespace SayHello\GitInstaller\Package;

use SayHello\GitInstaller\Helpers;

class UpdateCheck
{
    public bool $cacheAllowed;
    public string $cacheKey;
    public array $pluginFiles;

    public function run()
    {
        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);

        $this->cacheKey = 'shgi_custom_upd';
        $this->cacheAllowed = false;
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // https://github.com/rudrastyh/misha-update-checker/blob/main/misha-update-checker.php



        /*
        echo '<pre>';
        print_r(sayhelloGitInstaller()->GitPackages->loadNewPluginHeaders('wp-test-plugin'));
        //print_r($this->getPackagePluginFiles());
        //print_r($this->getPackageFilePath('wp-test-plugin'));
        //print_r(wp_get_themes());
        //print_r(get_plugins());
        //print_r($this->getPackageKeys());
        echo '</pre>';
        wp_die();*/
    }

    private function getPackageFilePath($key)
    {
        $package = sayhelloGitInstaller()->GitPackages->getPackages(false)[$key];
        if (!$package['theme']) {
            $plugins = array_keys(get_plugins());
            $file = null;
            foreach ($plugins as $plugin) {
                if (str_starts_with($plugin, $key)) {
                    $file = $plugin;
                }
            }
            return $file;
        }

        return null;
    }

    private function getPackagePluginFiles(): array
    {
        if ($this->pluginFiles) {
            return $this->pluginFiles;
        }

        $keys = array_keys(sayhelloGitInstaller()->GitPackages->getPackages(false));
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

        if ($this->getPackageFilePath('wp-test-plugin') !== $args->slug) {
            return $res;
        }


        if (!array_key_exists($args->slug, $this->getPackagePluginFiles())) {
            return $res;
        }

        $res = new \stdClass();

        $res->version = '1.0.0';


        return $res;
    }

    public function update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        /*
        $remote = $this->request();

        if (
            $remote
            && version_compare($this->version, $remote->version, '<')
            && version_compare($remote->requires, get_bloginfo('version'), '<=')
            && version_compare($remote->requires_php, PHP_VERSION, '<')
        ) {
            $res = new stdClass();
            $res->slug = $this->plugin_slug;
            $res->plugin = plugin_basename(__FILE__); // misha-update-plugin/misha-update-plugin.php
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;

            $transient->response[$res->plugin] = $res;

        }*/

        /**
         * get_plugins format
         * Array
         * (
         * [Name] => Deploy WP
         * [PluginURI] => https://github.com/SayHelloGmbH/deploy-wp/tree/main/apps/wp.wp-deploy.hello/wp-content/plugins/deploy-wp
         * [Version] => 0.0.1
         * [Description] => A deployment workflow for say hello
         * [Author] => Nico Martin - Say Hello GmbH
         * [AuthorURI] => https://sayhello.ch
         * [TextDomain] => deploy-wp
         * [DomainPath] => /languages
         * [Network] =>
         * [RequiresWP] =>
         * [RequiresPHP] =>
         * [UpdateURI] =>
         * [Title] => Deploy WP
         * [AuthorName] => Nico Martin - Say Hello GmbH
         * )
         */

        /**
         * todo: requires some refactoring:
         * 1. refactor GitPackages to decouple and cache some logic
         * 2. add the "headersUrl" to each package to call new Headers directly
         * 3. continue here
         */

        //foreach ($this->getPackagePluginFiles() as $key => $file) {
        foreach (['wp-test-plugin'=>'wp-test-plugin/wp-test-plugin.php'] as $key => $file) {

            //$oldHeaders = get_plugins()[$file];
            //$newHeaders = sayhelloGitInstaller()->GitPackages->getPackageHeadersByKey($key)['headers'];
            /*
            if ($newHeaders
                && version_compare($oldHeaders['Version'], $newHeaders['version'], '<')
                && version_compare($newHeaders['requires-at-least'], get_bloginfo('version'), '<=')
                && version_compare($newHeaders['requires-php'], PHP_VERSION, '<')
            ) {
                $res = new \stdClass();
                $res->new_version = $newHeaders['Version'];
                $res->package = 'test';
                $transient->response[$file] = $res;
            }*/
        }

        return $transient;
    }

    public function purge($upgrader, $options)
    {

        if (
            $this->cacheAllowed
            && 'update' === $options['action']
            && 'plugin' === $options['type']
        ) {
            // just clean the cache when new plugin version is installed
            delete_transient($this->cacheKey);
        }

    }
}

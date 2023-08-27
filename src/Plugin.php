<?php

namespace SayHello\GitInstaller;

use SayHello\GitInstaller\Package\GitPackages;

class Plugin
{

    private static ?Plugin $instance = null;
    public string $name = '';
    public string $version = '';
    public string $prefix = '';
    public string $api_namespace = '';
    public bool $debug = false;
    public string $file = '';
    public string $url = '';
    public string $iconSvg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M2.6,10.59L8.38,4.8L10.07,6.5C9.83,7.35 10.22,8.28 11,8.73V14.27C10.4,14.61 10,15.26 10,16A2,2 0 0,0 12,18A2,2 0 0,0 14,16C14,15.26 13.6,14.61 13,14.27V9.41L15.07,11.5C15,11.65 15,11.82 15,12A2,2 0 0,0 17,14A2,2 0 0,0 19,12A2,2 0 0,0 17,10C16.82,10 16.65,10 16.5,10.07L13.93,7.5C14.19,6.57 13.71,5.55 12.78,5.16C12.35,5 11.9,4.96 11.5,5.07L9.8,3.38L10.59,2.6C11.37,1.81 12.63,1.81 13.41,2.6L21.4,10.59C22.19,11.37 22.19,12.63 21.4,13.41L13.41,21.4C12.63,22.19 11.37,22.19 10.59,21.4L2.6,13.41C1.81,12.63 1.81,11.37 2.6,10.59Z" /></svg>';

    public string $upload_dir = '';

    public string $option_key = 'shgi_data';

    public AdminPage $AdminPage;
    public Assets $Assets;
    public Settings $Settings;
    public Package\GitPackages $GitPackages;
    public Package\UpdateLog $UpdateLog;
    public Package\Updater $Updater;
    public Package\Ecosystem $Ecosystem;
    public Package\Hooks $Hooks;

    public static function getInstance($file): Plugin
    {
        if (!isset(self::$instance)) {
            self::$instance = new Plugin();

            if (get_option(sayhelloGitInstaller()->option_key)) {
                $data = get_option(sayhelloGitInstaller()->option_key);
            } elseif (function_exists('get_plugin_data')) {
                $data = get_plugin_data($file);
            } else {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                $data = get_plugin_data($file);
            }

            self::$instance->name = $data['Name'];
            self::$instance->version = $data['Version'];

            self::$instance->prefix = 'shgi';
            self::$instance->api_namespace = 'git-installer/v1';
            self::$instance->debug = true;
            self::$instance->file = $file;
            self::$instance->url = plugin_dir_url($file);

            self::$instance->run();
        }

        return self::$instance;
    }

    public function run()
    {
        add_action('plugins_loaded', [$this, 'loadPluginTextdomain']);
        add_action('admin_init', [$this, 'updatePluginData']);
        register_deactivation_hook(sayhelloGitInstaller()->file, [$this, 'deactivate']);
        register_activation_hook(sayhelloGitInstaller()->file, [$this, 'activate']);

        add_filter('shgi/PluginStrings', [$this, 'pluginStrings']);
    }

    /**
     * Load translation files from the indicated directory.
     */
    public function loadPluginTextdomain()
    {
        load_plugin_textdomain(
            'progressive-wp',
            false,
            dirname(plugin_basename(sayhelloGitInstaller()->file)) . '/languages'
        );
    }

    /**
     * Update Assets Data
     */
    public function updatePluginData()
    {

        $db_data = get_option(sayhelloGitInstaller()->option_key);
        $file_data = get_plugin_data(sayhelloGitInstaller()->file);

        if (!$db_data || version_compare($file_data['Version'], $db_data['Version'], '>')) {

            sayhelloGitInstaller()->name = $file_data['Name'];
            sayhelloGitInstaller()->version = $file_data['Version'];

            update_option(sayhelloGitInstaller()->option_key, $file_data);
            if (!$db_data) {
                do_action('shgi_on_first_activate');
            } else {
                do_action('shgi_on_update', $db_data['Version'], $file_data['Version']);
            }
        }
    }

    public function activate()
    {
        do_action('shgi_on_activate');
    }

    public function deactivate()
    {
        do_action('shgi_on_deactivate');
        delete_option(sayhelloGitInstaller()->option_key);
    }

    public function pluginStrings($strings)
    {
        $strings['plugin.name'] = sayhelloGitInstaller()->name;

        return $strings;
    }
}

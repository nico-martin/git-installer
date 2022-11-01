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

    public string $upload_dir = '';

    public string $option_key = 'shgi_data';

    public AdminPage $AdminPage;
    public Assets $Assets;
    public Settings $Settings;
    public Package\GitPackages $GitPackages;
    public Package\UpdateLog $UpdateLog;
    public Package\UpdateCheck $UpdateCheck;
    public Package\Ecosystem $Ecosystem;

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
